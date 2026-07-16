<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'data_massal';
require __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../lib/XlsxLite.php';
if (empty($perpusAccess['allowed'])) return;

$message = '';
$error = '';
$importSummary = null;

function perpus_massal_excel_file(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Pilih file Excel .xlsx yang akan diimpor.');
    }
    $name = (string)($file['name'] ?? '');
    if (mb_strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'xlsx') {
        throw new RuntimeException('Format file harus Excel .xlsx.');
    }
    if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file Excel maksimal 15 MB.');
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) throw new RuntimeException('File upload tidak valid.');
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!in_array($mime, ['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true)) {
        throw new RuntimeException('Isi file bukan dokumen Excel .xlsx yang valid.');
    }
    return $tmp;
}

function perpus_massal_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '') return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }
    if (is_numeric($value)) {
        $serial = (float)$value;
        if ($serial > 1000 && $serial < 100000) return gmdate('Y-m-d', (int)(($serial - 25569) * 86400));
    }
    throw new RuntimeException('Format tanggal tidak valid: ' . $value);
}

function perpus_massal_money(string $value): float
{
    $value = trim($value);
    if ($value === '') return 0.0;
    $value = preg_replace('/[^0-9,.-]/', '', $value) ?? '';
    if (str_contains($value, ',') && str_contains($value, '.')) {
        if (strrpos($value, ',') > strrpos($value, '.')) $value = str_replace(['.', ','], ['', '.'], $value);
        else $value = str_replace(',', '', $value);
    } elseif (str_contains($value, ',')) {
        $value = str_replace(',', '.', $value);
    }
    return max(0, (float)$value);
}

function perpus_massal_master(mysqli $conn, string $entity, string $name): int
{
    $name = trim($name);
    if ($name === '') return 0;
    $map = [
        'kategori' => ['perpus_kategori_buku','nama',"INSERT INTO perpus_kategori_buku (nama,status_aktif) VALUES (?,1)"],
        'tipe' => ['perpus_tipe_koleksi','nama',"INSERT INTO perpus_tipe_koleksi (nama) VALUES (?)"],
        'pengarang' => ['perpus_pengarang','nama',"INSERT INTO perpus_pengarang (nama,tipe) VALUES (?,'p')"],
        'penerbit' => ['perpus_penerbit','nama',"INSERT INTO perpus_penerbit (nama) VALUES (?)"],
        'subyek' => ['perpus_subyek','nama',"INSERT INTO perpus_subyek (nama) VALUES (?)"],
        'gmd' => ['perpus_gmd','nama',"INSERT INTO perpus_gmd (nama) VALUES (?)"],
        'bahasa' => ['perpus_bahasa','nama',"INSERT INTO perpus_bahasa (nama) VALUES (?)"],
        'tempat' => ['perpus_tempat','nama',"INSERT INTO perpus_tempat (nama) VALUES (?)"],
    ];
    if (!isset($map[$entity])) throw new RuntimeException('Jenis data master tidak valid.');
    [$table,$column,$insertSql] = $map[$entity];
    $stmt = $conn->prepare("SELECT id FROM {$table} WHERE CONVERT(TRIM({$column}) USING utf8mb4) COLLATE utf8mb4_unicode_ci=CONVERT(TRIM(?) USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 1");
    $stmt->bind_param('s', $name); $stmt->execute();
    $id = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0); $stmt->close();
    if ($id > 0) return $id;
    $stmt = $conn->prepare($insertSql); $stmt->bind_param('s', $name); $stmt->execute();
    $id = (int)$stmt->insert_id; $stmt->close();
    return $id;
}

function perpus_massal_find_book(mysqli $conn, array $row): ?array
{
    $id = (int)($row['id_buku'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('SELECT * FROM perpus_buku WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($book) return $book;
    }

    $isbn = trim((string)($row['isbn'] ?? ''));
    if ($isbn !== '') {
        $stmt = $conn->prepare("SELECT * FROM perpus_buku WHERE REPLACE(REPLACE(isbn,'-',''),' ','')=REPLACE(REPLACE(?,'-',''),' ','') LIMIT 2");
        $stmt->bind_param('s', $isbn);
        $stmt->execute();
        $result = $stmt->get_result();
        $matches = [];
        while ($result && ($book = $result->fetch_assoc())) $matches[] = $book;
        $stmt->close();
        if (count($matches) === 1) return $matches[0];
        if (count($matches) > 1) throw new RuntimeException('ISBN digunakan oleh lebih dari satu bibliografi. Perbaiki data duplikat terlebih dahulu.');
    }

    $title = trim((string)($row['judul'] ?? ''));
    if ($title !== '') {
        $year = trim((string)($row['tahun_terbit'] ?? ''));
        $publisher = trim((string)($row['penerbit'] ?? ''));
        $stmt = $conn->prepare("SELECT b.*
            FROM perpus_buku b
            LEFT JOIN perpus_penerbit p ON p.id=b.penerbit_id
            WHERE CONVERT(TRIM(b.judul) USING utf8mb4) COLLATE utf8mb4_unicode_ci=CONVERT(TRIM(?) USING utf8mb4) COLLATE utf8mb4_unicode_ci
              AND (?='' OR COALESCE(b.tahun_terbit,'')=?)
              AND (?='' OR CONVERT(TRIM(COALESCE(NULLIF(p.nama,''),b.penerbit_teks,'')) USING utf8mb4) COLLATE utf8mb4_unicode_ci=CONVERT(TRIM(?) USING utf8mb4) COLLATE utf8mb4_unicode_ci)
            ORDER BY b.id LIMIT 2");
        $stmt->bind_param('sssss', $title, $year, $year, $publisher, $publisher);
        $stmt->execute();
        $result = $stmt->get_result();
        $matches = [];
        while ($result && ($book = $result->fetch_assoc())) $matches[] = $book;
        $stmt->close();
        if (count($matches) === 1) return $matches[0];
        if (count($matches) > 1) throw new RuntimeException('Judul cocok dengan lebih dari satu bibliografi. Lengkapi ISBN, tahun, atau penerbit.');
    }
    return null;
}

function perpus_massal_unique_barcode(mysqli $conn, string $preferred): string
{
    $preferred = trim($preferred);
    if ($preferred === '') $preferred = 'BK-' . date('Ymd-His');
    $candidate = $preferred;
    for ($i = 1; $i <= 9999; $i++) {
        $stmt = $conn->prepare('SELECT id FROM perpus_buku_eksemplar WHERE barcode=? LIMIT 1');
        $stmt->bind_param('s', $candidate); $stmt->execute(); $exists = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$exists) return $candidate;
        $candidate = $preferred . '-' . ($i + 1);
    }
    throw new RuntimeException('Barcode unik tidak dapat dibuat dari ' . $preferred . '.');
}

function perpus_massal_log(mysqli $conn, string $type, string $filename, array $summary, int $adminId): void
{
    $json = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $conn->prepare('INSERT INTO perpus_import_batch (jenis,nama_file,total_baris,berhasil,diperbarui,dilewati,gagal,ringkasan,admin_id) VALUES (?,?,?,?,?,?,?,?,?)');
    $total = (int)($summary['total'] ?? 0);
    $created = (int)($summary['created'] ?? 0);
    $updated = (int)($summary['updated'] ?? 0);
    $skipped = (int)($summary['skipped'] ?? 0);
    $failed = (int)($summary['failed'] ?? 0);
    $stmt->bind_param('ssiiiiisi', $type, $filename, $total, $created, $updated, $skipped, $failed, $json, $adminId);
    $stmt->execute(); $stmt->close();
}

function perpus_massal_import_collection(mysqli $conn, array $rows, string $filename, int $adminId): array
{
    $summary = [
        'total'=>count($rows),'created'=>0,'updated'=>0,'skipped'=>0,'failed'=>0,
        'copies_created'=>0,'copies_updated'=>0,'errors'=>[]
    ];
    foreach ($rows as $row) {
        $line = (int)($row['_row'] ?? 0);
        $title = trim((string)($row['judul'] ?? ''));
        if ($title === '' || str_starts_with(mb_strtoupper($title), 'CONTOH-')) {
            $summary['skipped']++;
            continue;
        }
        $transactionStarted = false;
        try {
            if (mb_strlen($title) > 255) throw new RuntimeException('Judul maksimal 255 karakter.');
            $conn->begin_transaction();
            $transactionStarted = true;

            $book = perpus_massal_find_book($conn, $row);
            $isbnInput = trim((string)($row['isbn'] ?? ''));
            $publisherInput = trim((string)($row['penerbit'] ?? ''));
            $categoryInput = trim((string)($row['kategori'] ?? ''));
            $collectionInput = trim((string)($row['tipe_koleksi'] ?? ''));
            $gmdInput = trim((string)($row['gmd'] ?? ''));
            $languageInput = trim((string)($row['bahasa'] ?? ''));
            $placeInput = trim((string)($row['tempat_terbit'] ?? ''));
            $authorInput = trim((string)($row['pengarang'] ?? ''));
            $subjectInput = trim((string)($row['subyek'] ?? ''));

            $publisherId = $publisherInput !== ''
                ? perpus_massal_master($conn, 'penerbit', $publisherInput)
                : (int)($book['penerbit_id'] ?? 0);
            $publisherName = $publisherInput !== ''
                ? $publisherInput
                : trim((string)($book['penerbit_teks'] ?? ''));
            $categoryId = $categoryInput !== ''
                ? perpus_massal_master($conn, 'kategori', $categoryInput)
                : (int)($book['kategori_id'] ?? 0);
            $collectionId = $collectionInput !== ''
                ? perpus_massal_master($conn, 'tipe', $collectionInput)
                : (int)($book['tipe_koleksi_id'] ?? 0);
            $gmdId = $gmdInput !== ''
                ? perpus_massal_master($conn, 'gmd', $gmdInput)
                : (int)($book['gmd_id'] ?? 0);
            if ($languageInput !== '') perpus_massal_master($conn, 'bahasa', $languageInput);
            if ($placeInput !== '') perpus_massal_master($conn, 'tempat', $placeInput);

            $authorNames = array_values(array_filter(array_map('trim', preg_split('/[;|]+/', $authorInput) ?: [])));
            $subjectNames = array_values(array_filter(array_map('trim', preg_split('/[;|]+/', $subjectInput) ?: [])));
            $primaryAuthor = $authorNames
                ? perpus_massal_master($conn, 'pengarang', $authorNames[0])
                : (int)($book['pengarang_id'] ?? 0);

            $yearInput = trim((string)($row['tahun_terbit'] ?? ''));
            if ($yearInput !== '' && !preg_match('/^\d{4}(?:\/\d{4})?$/', $yearInput)) {
                throw new RuntimeException('Tahun terbit harus 4 digit atau format 2025/2026.');
            }
            $year = $yearInput !== '' ? $yearInput : trim((string)($book['tahun_terbit'] ?? ''));
            $classificationInput = trim((string)($row['klasifikasi'] ?? ''));
            $classification = $classificationInput !== '' ? $classificationInput : trim((string)($book['klasifikasi'] ?? ''));
            $callInput = trim((string)($row['nomor_panggil'] ?? ''));
            $call = $callInput !== '' ? $callInput : trim((string)($book['nomor_panggil'] ?? ''));
            $physicalInput = trim((string)($row['deskripsi_fisik'] ?? ''));
            $physical = $physicalInput !== '' ? $physicalInput : trim((string)($book['deskripsi_fisik'] ?? ''));
            $language = $languageInput !== '' ? $languageInput : trim((string)($book['bahasa'] ?? ''));
            $place = $placeInput !== '' ? $placeInput : trim((string)($book['tempat_terbit'] ?? ''));
            $isbn = $isbnInput !== '' ? $isbnInput : trim((string)($book['isbn'] ?? ''));
            $opacRaw = trim((string)($row['status_opac'] ?? ''));
            $opac = $opacRaw !== '' ? ($opacRaw === '0' ? 0 : 1) : (int)($book['status_opac'] ?? 1);

            if ($book) {
                $bookId = (int)$book['id'];
                $stmt = $conn->prepare('UPDATE perpus_buku SET judul=?,isbn=?,kategori_id=?,tipe_koleksi_id=?,gmd_id=?,pengarang_id=?,penerbit_id=?,penerbit_teks=?,tahun_terbit=?,klasifikasi=?,nomor_panggil=?,bahasa=?,tempat_terbit=?,deskripsi_fisik=?,status_opac=? WHERE id=?');
                $stmt->bind_param('ssiiiiisssssssii', $title,$isbn,$categoryId,$collectionId,$gmdId,$primaryAuthor,$publisherId,$publisherName,$year,$classification,$call,$language,$place,$physical,$opac,$bookId);
                $stmt->execute();
                $stmt->close();
                $summary['updated']++;
            } else {
                $stmt = $conn->prepare('INSERT INTO perpus_buku (judul,isbn,kategori_id,tipe_koleksi_id,gmd_id,pengarang_id,penerbit_id,penerbit_teks,tahun_terbit,klasifikasi,nomor_panggil,bahasa,tempat_terbit,deskripsi_fisik,status_opac) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ssiiiiisssssssi', $title,$isbn,$categoryId,$collectionId,$gmdId,$primaryAuthor,$publisherId,$publisherName,$year,$classification,$call,$language,$place,$physical,$opac);
                $stmt->execute();
                $bookId = (int)$stmt->insert_id;
                $stmt->close();
                $summary['created']++;
            }

            // Kolom kosong tidak menghapus relasi pengarang/subyek yang sudah ada.
            if ($authorInput !== '') {
                $stmt = $conn->prepare('DELETE FROM perpus_buku_pengarang WHERE buku_id=?');
                $stmt->bind_param('i',$bookId);
                $stmt->execute();
                $stmt->close();
                foreach ($authorNames as $name) {
                    $authorId = perpus_massal_master($conn,'pengarang',$name);
                    $stmt = $conn->prepare("INSERT IGNORE INTO perpus_buku_pengarang (buku_id,pengarang_id,level_pengarang) VALUES (?,?,'utama')");
                    $stmt->bind_param('ii',$bookId,$authorId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            if ($subjectInput !== '') {
                $stmt = $conn->prepare('DELETE FROM perpus_buku_subyek WHERE buku_id=?');
                $stmt->bind_param('i',$bookId);
                $stmt->execute();
                $stmt->close();
                foreach ($subjectNames as $name) {
                    $subjectId = perpus_massal_master($conn,'subyek',$name);
                    $stmt = $conn->prepare('INSERT IGNORE INTO perpus_buku_subyek (buku_id,subyek_id) VALUES (?,?)');
                    $stmt->bind_param('ii',$bookId,$subjectId);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $count = max(0, min(500, (int)($row['jumlah_eksemplar'] ?? 0)));
            $barcodeBase = trim((string)($row['barcode_awal'] ?? ''));
            $shelf = trim((string)($row['lokasi_rak'] ?? ''));
            $source = trim((string)($row['sumber_pengadaan'] ?? ''));
            $priceRaw = trim((string)($row['harga'] ?? ''));
            $price = perpus_massal_money($priceRaw);
            $dateRaw = trim((string)($row['tanggal_pengadaan'] ?? ''));
            $date = perpus_massal_date($dateRaw);
            $note = trim((string)($row['catatan_eksemplar'] ?? ''));
            for ($i = 1; $i <= $count; $i++) {
                $barcode = $barcodeBase !== ''
                    ? ($count === 1 ? $barcodeBase : $barcodeBase . '-' . $i)
                    : ('BK-' . $bookId . '-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT));
                if (mb_strlen($barcode) > 64) throw new RuntimeException('Barcode maksimal 64 karakter: ' . $barcode);

                $stmt = $conn->prepare('SELECT * FROM perpus_buku_eksemplar WHERE barcode=? LIMIT 1 FOR UPDATE');
                $stmt->bind_param('s', $barcode);
                $stmt->execute();
                $existingCopy = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($existingCopy) {
                    if ((int)$existingCopy['buku_id'] !== $bookId) {
                        throw new RuntimeException('Barcode ' . $barcode . ' sudah digunakan oleh buku lain.');
                    }
                    $copyId = (int)$existingCopy['id'];
                    $copyType = $collectionId > 0 ? $collectionId : (int)($existingCopy['tipe_koleksi_id'] ?? 0);
                    $copyShelf = $shelf !== '' ? $shelf : (string)($existingCopy['lokasi_rak'] ?? '');
                    $copySource = $source !== '' ? $source : (string)($existingCopy['sumber_pengadaan'] ?? '');
                    $copyPrice = $priceRaw !== '' ? $price : (float)($existingCopy['harga'] ?? 0);
                    $copyDate = $dateRaw !== '' ? $date : ($existingCopy['tanggal_pengadaan'] ?? null);
                    $copyNote = $note !== '' ? $note : (string)($existingCopy['catatan'] ?? '');
                    $stmt = $conn->prepare('UPDATE perpus_buku_eksemplar SET tipe_koleksi_id=?,lokasi_rak=?,sumber_pengadaan=?,harga=?,tanggal_pengadaan=?,catatan=? WHERE id=?');
                    $stmt->bind_param('issdssi',$copyType,$copyShelf,$copySource,$copyPrice,$copyDate,$copyNote,$copyId);
                    $stmt->execute();
                    $stmt->close();
                    $summary['copies_updated']++;
                } else {
                    $stmt = $conn->prepare("INSERT INTO perpus_buku_eksemplar (buku_id,barcode,tipe_koleksi_id,lokasi_rak,kondisi_fisik,sumber_pengadaan,harga,tanggal_pengadaan,status,catatan) VALUES (?,?,?,?,'baik',?,?,?,'tersedia',?)");
                    $stmt->bind_param('isissdss', $bookId,$barcode,$collectionId,$shelf,$source,$price,$date,$note);
                    $stmt->execute();
                    $stmt->close();
                    $summary['copies_created']++;
                }
            }
            $conn->commit();
            $transactionStarted = false;
        } catch (Throwable $e) {
            if ($transactionStarted) {
                try { $conn->rollback(); } catch (Throwable) {}
            }
            $summary['failed']++;
            if (count($summary['errors']) < 50) $summary['errors'][] = 'Baris ' . $line . ': ' . $e->getMessage();
        }
    }
    perpus_massal_log($conn,'koleksi',$filename,$summary,$adminId);
    return $summary;
}

function perpus_massal_import_copies(mysqli $conn, array $rows, string $filename, int $adminId): array
{
    $summary = ['total'=>count($rows),'created'=>0,'updated'=>0,'skipped'=>0,'failed'=>0,'errors'=>[]];
    foreach ($rows as $row) {
        $line = (int)($row['_row'] ?? 0);
        $title = trim((string)($row['judul'] ?? ''));
        if (str_starts_with(mb_strtoupper($title), 'CONTOH-')) {
            $summary['skipped']++;
            continue;
        }
        $transactionStarted = false;
        try {
            $conn->begin_transaction();
            $transactionStarted = true;
            $book = perpus_massal_find_book($conn,$row);
            if (!$book) throw new RuntimeException('Buku tidak ditemukan melalui ID, ISBN, atau judul.');
            $barcode = trim((string)($row['barcode'] ?? ''));
            if ($barcode === '') throw new RuntimeException('Barcode wajib diisi.');
            if (mb_strlen($barcode) > 64) throw new RuntimeException('Barcode maksimal 64 karakter.');

            $stmt = $conn->prepare('SELECT * FROM perpus_buku_eksemplar WHERE barcode=? LIMIT 1 FOR UPDATE');
            $stmt->bind_param('s',$barcode);
            $stmt->execute();
            $copy = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $collectionInput = trim((string)($row['tipe_koleksi'] ?? ''));
            $collectionId = $collectionInput !== ''
                ? perpus_massal_master($conn,'tipe',$collectionInput)
                : (int)($copy['tipe_koleksi_id'] ?? $book['tipe_koleksi_id'] ?? 0);
            $inventoryInput = trim((string)($row['nomor_inventaris'] ?? ''));
            if (mb_strlen($inventoryInput) > 100) throw new RuntimeException('Nomor inventaris maksimal 100 karakter.');
            $inventory = $inventoryInput !== '' ? $inventoryInput : (string)($copy['nomor_inventaris'] ?? '');
            $shelfInput = trim((string)($row['lokasi_rak'] ?? ''));
            $shelf = $shelfInput !== '' ? $shelfInput : (string)($copy['lokasi_rak'] ?? '');
            $conditionInput = mb_strtolower(trim((string)($row['kondisi'] ?? '')));
            $condition = $conditionInput !== '' ? $conditionInput : (string)($copy['kondisi_fisik'] ?? 'baik');
            if (!in_array($condition,['baik','rusak','hilang'],true)) throw new RuntimeException('Kondisi tidak valid.');
            $statusInput = mb_strtolower(trim((string)($row['status'] ?? '')));
            $status = $statusInput !== ''
                ? $statusInput
                : (string)($copy['status'] ?? ($condition === 'baik' ? 'tersedia' : $condition));
            if (!in_array($status,['tersedia','rusak','hilang','nonaktif'],true)) throw new RuntimeException('Status tidak valid.');
            $sourceInput = trim((string)($row['sumber_pengadaan'] ?? ''));
            $source = $sourceInput !== '' ? $sourceInput : (string)($copy['sumber_pengadaan'] ?? '');
            $priceInput = trim((string)($row['harga'] ?? ''));
            $price = $priceInput !== '' ? perpus_massal_money($priceInput) : (float)($copy['harga'] ?? 0);
            $dateInput = trim((string)($row['tanggal_pengadaan'] ?? ''));
            $date = $dateInput !== '' ? perpus_massal_date($dateInput) : ($copy['tanggal_pengadaan'] ?? null);
            $noteInput = trim((string)($row['catatan'] ?? ''));
            $note = $noteInput !== '' ? $noteInput : (string)($copy['catatan'] ?? '');

            if ($copy) {
                if ((string)$copy['status']==='dipinjam') throw new RuntimeException('Eksemplar sedang dipinjam dan tidak dapat diperbarui massal.');
                $copyId=(int)$copy['id'];
                $bookId = (int)$book['id'];
                $stmt=$conn->prepare('UPDATE perpus_buku_eksemplar SET buku_id=?,nomor_inventaris=?,tipe_koleksi_id=?,lokasi_rak=?,kondisi_fisik=?,sumber_pengadaan=?,harga=?,tanggal_pengadaan=?,status=?,catatan=? WHERE id=?');
                $stmt->bind_param('isisssdsssi',$bookId,$inventory,$collectionId,$shelf,$condition,$source,$price,$date,$status,$note,$copyId);
                $stmt->execute();
                $stmt->close();
                $summary['updated']++;
            } else {
                $bookId = (int)$book['id'];
                $stmt=$conn->prepare('INSERT INTO perpus_buku_eksemplar (buku_id,barcode,nomor_inventaris,tipe_koleksi_id,lokasi_rak,kondisi_fisik,sumber_pengadaan,harga,tanggal_pengadaan,status,catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ississsdsss',$bookId,$barcode,$inventory,$collectionId,$shelf,$condition,$source,$price,$date,$status,$note);
                $stmt->execute();
                $stmt->close();
                $summary['created']++;
            }
            $conn->commit();
            $transactionStarted = false;
        } catch(Throwable $e) {
            if ($transactionStarted) {
                try { $conn->rollback(); } catch(Throwable) {}
            }
            $summary['failed']++;
            if(count($summary['errors'])<50)$summary['errors'][]='Baris '.$line.': '.$e->getMessage();
        }
    }
    perpus_massal_log($conn,'eksemplar',$filename,$summary,$adminId);
    return $summary;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        perpus_check_csrf();
        if (!$perpusCanManage) throw new RuntimeException('Hanya admin Perpustakaan yang dapat menjalankan proses data massal.');
        $action = (string)($_POST['action'] ?? '');
        if (in_array($action,['import_koleksi','import_eksemplar'],true)) {
            $tmp = perpus_massal_excel_file($_FILES['file_excel'] ?? []);
            $sheetName = $action === 'import_koleksi' ? 'IMPORT_KOLEKSI' : 'IMPORT_EKSEMPLAR';
            $data = PerpusXlsxLite::read($tmp,$sheetName,10050);
            $parsed = PerpusXlsxLite::rowsWithHeader($data['rows']);
            $required = $action === 'import_koleksi' ? ['judul'] : ['barcode'];
            foreach ($required as $field) if (!in_array($field,$parsed['headers'],true)) throw new RuntimeException('Kolom wajib tidak ditemukan: '.$field.'.');
            $filename = basename((string)($_FILES['file_excel']['name'] ?? 'import.xlsx'));
            $importSummary = $action === 'import_koleksi'
                ? perpus_massal_import_collection($conn,$parsed['data'],$filename,(int)$_SESSION['admin_id'])
                : perpus_massal_import_copies($conn,$parsed['data'],$filename,(int)$_SESSION['admin_id']);
            $message = 'Import selesai. ' . number_format($importSummary['created'],0,',','.') . ' dibuat, ' . number_format($importSummary['updated'],0,',','.') . ' diperbarui' . (isset($importSummary['copies_created']) ? ', ' . number_format($importSummary['copies_created'],0,',','.') . ' eksemplar dibuat dan ' . number_format($importSummary['copies_updated'],0,',','.') . ' eksemplar diperbarui' : '') . ', ' . number_format($importSummary['failed'],0,',','.') . ' gagal.';
        } elseif ($action === 'bulk_member') {
            $scope=(string)($_POST['scope']??'students');$typeId=(int)($_POST['tipe_member_id']??0);$end=perpus_massal_date((string)($_POST['tanggal_berakhir']??''));$classId=(int)($_POST['kelas_id']??0);
            if($typeId<=0)throw new RuntimeException('Tipe anggota wajib dipilih.');
            $ids=[];$owner='siswa';
            if($scope==='employees'){$owner='pegawai';$r=$conn->query("SELECT pegawai_id id FROM pegawai WHERE active='Y'");}
            elseif($scope==='class'){$owner='siswa';if($classId<=0)throw new RuntimeException('Pilih rombel.');$stmt=$conn->prepare("SELECT DISTINCT ps.id FROM pendaftaran_siswa ps JOIN siswa_kelas sk ON sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=ps.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1) WHERE ps.status_aktif=1 AND sk.kelas_id=?");$stmt->bind_param('i',$classId);$stmt->execute();$r=$stmt->get_result();}
            else{$owner='siswa';$r=$conn->query('SELECT id FROM pendaftaran_siswa WHERE status_aktif=1');}
            while($r&&($x=$r->fetch_assoc()))$ids[]=(int)$x['id'];if(isset($stmt)){$stmt->close();unset($stmt);}
            $count=0;foreach($ids as $ownerId){$m=sds_perpus_ensure_member($conn,$owner,$ownerId,true);$mid=(int)$m['id'];$stmt=$conn->prepare("UPDATE perpus_anggota SET tipe_member_id=?,status_keanggotaan='aktif',tanggal_berakhir=? WHERE id=?");$stmt->bind_param('isi',$typeId,$end,$mid);$stmt->execute();$stmt->close();$count++;}
            $message=number_format($count,0,',','.').' keanggotaan berhasil diaktifkan/diperbarui.';
        } elseif ($action === 'sync_inactive') {
            $conn->query("UPDATE perpus_anggota pa JOIN pendaftaran_siswa ps ON pa.pemilik_tipe='siswa' AND pa.pemilik_id=ps.id SET pa.status_keanggotaan='nonaktif' WHERE ps.status_aktif<>1 AND pa.status_keanggotaan='aktif'");$students=$conn->affected_rows;
            $conn->query("UPDATE perpus_anggota pa JOIN pegawai p ON pa.pemilik_tipe='pegawai' AND pa.pemilik_id=p.pegawai_id SET pa.status_keanggotaan='nonaktif' WHERE p.active<>'Y' AND pa.status_keanggotaan='aktif'");$employees=$conn->affected_rows;
            $message=number_format($students+$employees,0,',','.').' anggota nonaktif berhasil diselaraskan dari master SDS.';
        } else throw new RuntimeException('Aksi data massal tidak dikenali.');
    } catch(Throwable $e) { $error=$e->getMessage(); }
}

$types=[];$r=$conn->query('SELECT id,nama FROM perpus_tipe_member WHERE status_aktif=1 ORDER BY nama');while($r&&($x=$r->fetch_assoc()))$types[]=$x;
$classes=[];$r=$conn->query('SELECT id,nama_kelas,tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC,nama_kelas');while($r&&($x=$r->fetch_assoc()))$classes[]=$x;
$history=[];$r=$conn->query('SELECT * FROM perpus_import_batch ORDER BY id DESC LIMIT 10');while($r&&($x=$r->fetch_assoc()))$history[]=$x;
require __DIR__.'/../partials/master_page_style.php';
?>
<div class="sds-master-page">
 <div class="sds-hero"><div><h2>Data Massal & Excel</h2><p>Import koleksi dan eksemplar, aktivasi anggota massal, serta export laporan Excel.</p></div><div class="sds-hero-actions"><a class="btn btn-outline-primary" href="<?=perpus_h(sds_base_url('perpustakaan/opac/'))?>" target="_blank"><i data-feather="globe" class="me-1"></i>Buka OPAC</a></div></div>
 <?php if($message):?><div class="alert alert-success"><?=perpus_h($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=perpus_h($error)?></div><?php endif;?>
 <?php if($importSummary&&$importSummary['errors']):?><div class="alert alert-warning import-result"><strong>Baris yang gagal:</strong><ul class="mb-0 mt-2"><?php foreach($importSummary['errors'] as $x):?><li><?=perpus_h($x)?></li><?php endforeach;?></ul></div><?php endif;?>
 <div class="massal-grid mb-3">
  <div class="card card-outline card-primary"><div class="card-header"><h5>Import Koleksi</h5><a class="btn btn-sm btn-outline-primary" href="templates/template_import_koleksi.xlsx" download><i data-feather="download" class="me-1"></i>Template Excel</a></div><div class="card-body"><p class="text-muted">Tambah atau perbarui bibliografi sekaligus membuat eksemplar. Master pengarang, penerbit, kategori, dan referensi lain dibuat otomatis.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="import_koleksi"><input class="form-control mb-3" type="file" name="file_excel" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required><button class="btn btn-primary"><i data-feather="upload" class="me-1"></i>Import Koleksi</button></form></div></div>
  <div class="card card-outline card-success"><div class="card-header"><h5>Import Eksemplar / Barcode</h5><a class="btn btn-sm btn-outline-success" href="templates/template_import_eksemplar.xlsx" download><i data-feather="download" class="me-1"></i>Template Excel</a></div><div class="card-body"><p class="text-muted">Tambahkan atau perbarui barcode, nomor inventaris, lokasi rak, kondisi, dan data pengadaan untuk buku yang sudah tersedia.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="import_eksemplar"><input class="form-control mb-3" type="file" name="file_excel" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required><button class="btn btn-success"><i data-feather="upload" class="me-1"></i>Import Eksemplar</button></form></div></div>
 </div>
 <div class="card card-outline card-info mb-3"><div class="card-header"><h5>Keanggotaan Massal dari SDS</h5></div><div class="card-body"><form method="post" class="row g-3 align-items-end"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="bulk_member"><div class="col-lg-3"><label class="form-label">Cakupan</label><select class="form-select" name="scope" id="bulkScope"><option value="students">Seluruh siswa aktif</option><option value="class">Satu rombel</option><option value="employees">Seluruh pegawai aktif</option></select></div><div class="col-lg-3" id="bulkClassWrap" style="display:none"><label class="form-label">Rombel</label><select class="form-select" name="kelas_id"><option value="">Pilih rombel</option><?php foreach($classes as $c):?><option value="<?=(int)$c['id']?>"><?=perpus_h($c['nama_kelas'])?> · <?=perpus_h($c['tahun_ajaran'])?></option><?php endforeach;?></select></div><div class="col-lg-3"><label class="form-label">Tipe Anggota</label><select class="form-select" name="tipe_member_id" required><?php foreach($types as $t):?><option value="<?=(int)$t['id']?>"><?=perpus_h($t['nama'])?></option><?php endforeach;?></select></div><div class="col-lg-2"><label class="form-label">Berlaku Sampai</label><input type="date" class="form-control" name="tanggal_berakhir"></div><div class="col-lg-auto"><button class="btn btn-info text-white"><i data-feather="users" class="me-1"></i>Proses</button></div></form><hr><form method="post" onsubmit="return confirm('Nonaktifkan keanggotaan yang master siswa/pegawainya sudah nonaktif?')"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="sync_inactive"><button class="btn btn-outline-danger btn-sm"><i data-feather="user-x" class="me-1"></i>Selaraskan Siswa Lulus/Mutasi & Pegawai Nonaktif</button></form></div></div>
 <div class="card card-outline card-warning mb-3"><div class="card-header"><h5>Export Excel</h5></div><div class="card-body"><div class="massal-export-grid"><?php $exports=['anggota'=>'Anggota','koleksi'=>'Koleksi Bibliografi','eksemplar'=>'Eksemplar & Barcode','pinjaman_aktif'=>'Pinjaman Aktif','riwayat'=>'Riwayat Peminjaman','terlambat'=>'Keterlambatan','denda'=>'Denda','kunjungan'=>'Kunjungan','anggota_tanpa_rfid'=>'Anggota Belum Memiliki RFID'];foreach($exports as $key=>$label):?><a class="massal-export-item" href="export.php?type=<?=perpus_h($key)?>"><i data-feather="file-text"></i><span><strong><?=perpus_h($label)?></strong><small class="d-block text-muted">Download .xlsx</small></span></a><?php endforeach;?></div></div></div>
 <div class="card"><div class="card-header"><h5>Riwayat Import Terakhir</h5></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Waktu</th><th>Jenis</th><th>File</th><th>Total</th><th>Dibuat</th><th>Diperbarui</th><th>Gagal</th></tr></thead><tbody><?php if(!$history):?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat import.</td></tr><?php else:foreach($history as $h):?><tr><td><?=date('d/m/Y H:i',strtotime($h['created_at']))?></td><td><span class="badge bg-light text-dark border"><?=perpus_h(ucfirst($h['jenis']))?></span></td><td><?=perpus_h($h['nama_file'])?></td><td><?=number_format((int)$h['total_baris'],0,',','.')?></td><td><?=number_format((int)$h['berhasil'],0,',','.')?></td><td><?=number_format((int)$h['diperbarui'],0,',','.')?></td><td><?=number_format((int)$h['gagal'],0,',','.')?></td></tr><?php endforeach;endif;?></tbody></table></div></div>
</div>
<script>document.getElementById('bulkScope')?.addEventListener('change',function(){document.getElementById('bulkClassWrap').style.display=this.value==='class'?'block':'none'});</script>
