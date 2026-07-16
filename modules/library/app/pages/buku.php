<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'buku';
require __DIR__ . '/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;

$message = '';
$error = '';

function perpus_book_upload_cover(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload sampul buku gagal.');
    if ((int)($file['size'] ?? 0) > 3 * 1024 * 1024) throw new RuntimeException('Ukuran sampul maksimal 3 MB.');
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) throw new RuntimeException('File sampul tidak valid.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Sampul harus berupa JPG, PNG, atau WEBP.');
    $directory = sds_root_path('uploads/perpus/buku');
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload sampul tidak dapat dibuat.');
    }
    $filename = 'buku_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmp, $directory . '/' . $filename)) throw new RuntimeException('Sampul gagal disimpan ke server.');
    return $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $newCover = '';
    try {
        perpus_check_csrf();
        if (!$perpusCanManage) throw new RuntimeException('Hanya admin Perpustakaan yang dapat mengubah koleksi.');
        $action = (string)($_POST['action'] ?? 'save');
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'delete') {
            if ($id <= 0) throw new RuntimeException('Buku tidak valid.');
            $stmt = $conn->prepare('SELECT sampul FROM perpus_buku WHERE id=? LIMIT 1');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$book) throw new RuntimeException('Buku tidak ditemukan.');
            $stmt = $conn->prepare('SELECT COUNT(*) total FROM perpus_peminjaman_detail WHERE buku_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $used = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $stmt->close();
            if ($used > 0) throw new RuntimeException('Buku sudah memiliki riwayat transaksi dan tidak dapat dihapus. Sembunyikan dari OPAC atau nonaktifkan eksemplarnya.');

            $conn->begin_transaction();
            $stmt = $conn->prepare('DELETE FROM perpus_buku_pengarang WHERE buku_id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('DELETE FROM perpus_buku_subyek WHERE buku_id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('DELETE FROM perpus_buku_eksemplar WHERE buku_id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('DELETE FROM perpus_buku WHERE id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $conn->commit();
            $cover = basename((string)($book['sampul'] ?? ''));
            if ($cover !== '') @unlink(sds_root_path('uploads/perpus/buku/' . $cover));
            $message = 'Buku berhasil dihapus.';
        } elseif ($action === 'copy_status') {
            $copyId = (int)($_POST['copy_id'] ?? 0);
            $copyStatus = (string)($_POST['copy_status'] ?? 'tersedia');
            if ($copyId <= 0 || !in_array($copyStatus, ['tersedia','rusak','hilang','nonaktif'], true)) throw new RuntimeException('Status eksemplar tidak valid.');
            $stmt = $conn->prepare("SELECT COUNT(*) total FROM perpus_peminjaman_detail WHERE eksemplar_id=? AND status='dipinjam'");
            $stmt->bind_param('i', $copyId); $stmt->execute();
            $active = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0); $stmt->close();
            if ($active > 0) throw new RuntimeException('Eksemplar masih dipinjam dan statusnya tidak dapat diubah.');
            $stmt = $conn->prepare('UPDATE perpus_buku_eksemplar SET status=? WHERE id=?');
            $stmt->bind_param('si', $copyStatus, $copyId); $stmt->execute(); $stmt->close();
            $message = 'Status eksemplar berhasil diperbarui.';
        } elseif ($action === 'delete_copy') {
            $copyId = (int)($_POST['copy_id'] ?? 0);
            if ($copyId <= 0) throw new RuntimeException('Eksemplar tidak valid.');
            $stmt = $conn->prepare('SELECT COUNT(*) total FROM perpus_peminjaman_detail WHERE eksemplar_id=?');
            $stmt->bind_param('i', $copyId); $stmt->execute();
            $used = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0); $stmt->close();
            if ($used > 0) throw new RuntimeException('Eksemplar sudah memiliki riwayat transaksi dan tidak dapat dihapus. Ubah status menjadi nonaktif.');
            $stmt = $conn->prepare('DELETE FROM perpus_buku_eksemplar WHERE id=?');
            $stmt->bind_param('i', $copyId); $stmt->execute(); $stmt->close();
            $message = 'Eksemplar berhasil dihapus.';
        } else {
            $judul = trim((string)($_POST['judul'] ?? ''));
            $isbn = trim((string)($_POST['isbn'] ?? ''));
            $barcodeInduk = trim((string)($_POST['barcode_induk'] ?? ''));
            $kategoriId = (int)($_POST['kategori_id'] ?? 0);
            $collectionId = (int)($_POST['tipe_koleksi_id'] ?? 0);
            $gmdId = (int)($_POST['gmd_id'] ?? 0);
            $authorId = (int)($_POST['pengarang_id'] ?? 0);
            $publisherId = (int)($_POST['penerbit_id'] ?? 0);
            $publisher = trim((string)($_POST['penerbit_teks'] ?? ''));
            if ($publisherId > 0) {
                $stmt = $conn->prepare('SELECT nama FROM perpus_penerbit WHERE id=? LIMIT 1');
                $stmt->bind_param('i', $publisherId); $stmt->execute();
                $publisherRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if ($publisherRow) $publisher = (string)$publisherRow['nama'];
            }
            $tahun = trim((string)($_POST['tahun_terbit'] ?? ''));
            $edisi = trim((string)($_POST['edisi'] ?? ''));
            $klasifikasi = trim((string)($_POST['klasifikasi'] ?? ''));
            $call = trim((string)($_POST['nomor_panggil'] ?? ''));
            $bahasa = trim((string)($_POST['bahasa'] ?? ''));
            $tempat = trim((string)($_POST['tempat_terbit'] ?? ''));
            $desc = trim((string)($_POST['deskripsi_fisik'] ?? ''));
            $opac = !empty($_POST['status_opac']) ? 1 : 0;
            $copyCount = max(0, min(500, (int)($_POST['jumlah_eksemplar_baru'] ?? 0)));
            $copyPrefix = trim((string)($_POST['prefix_barcode'] ?? ''));
            $removeCover = !empty($_POST['hapus_sampul']);
            if ($judul === '') throw new RuntimeException('Judul buku wajib diisi.');
            if ($tahun !== '' && !preg_match('/^[0-9]{4}(?:\/[0-9]{4})?$/', $tahun)) throw new RuntimeException('Tahun terbit harus berupa tahun 4 digit.');

            $oldCover = '';
            if ($id > 0) {
                $stmt = $conn->prepare('SELECT sampul FROM perpus_buku WHERE id=? LIMIT 1');
                $stmt->bind_param('i', $id); $stmt->execute();
                $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$old) throw new RuntimeException('Buku tidak ditemukan.');
                $oldCover = basename((string)($old['sampul'] ?? ''));
            }
            $newCover = perpus_book_upload_cover($_FILES['sampul'] ?? []);
            $cover = $newCover !== '' ? $newCover : ($removeCover ? '' : $oldCover);

            $conn->begin_transaction();
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE perpus_buku SET judul=?,isbn=?,barcode_induk=?,kategori_id=?,tipe_koleksi_id=?,gmd_id=?,pengarang_id=?,penerbit_id=?,penerbit_teks=?,tahun_terbit=?,edisi=?,klasifikasi=?,nomor_panggil=?,bahasa=?,tempat_terbit=?,deskripsi_fisik=?,sampul=?,status_opac=? WHERE id=?');
                $stmt->bind_param('sssiiiiisssssssssii', $judul,$isbn,$barcodeInduk,$kategoriId,$collectionId,$gmdId,$authorId,$publisherId,$publisher,$tahun,$edisi,$klasifikasi,$call,$bahasa,$tempat,$desc,$cover,$opac,$id);
            } else {
                $stmt = $conn->prepare('INSERT INTO perpus_buku (judul,isbn,barcode_induk,kategori_id,tipe_koleksi_id,gmd_id,pengarang_id,penerbit_id,penerbit_teks,tahun_terbit,edisi,klasifikasi,nomor_panggil,bahasa,tempat_terbit,deskripsi_fisik,sampul,status_opac) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('sssiiiiisssssssssi', $judul,$isbn,$barcodeInduk,$kategoriId,$collectionId,$gmdId,$authorId,$publisherId,$publisher,$tahun,$edisi,$klasifikasi,$call,$bahasa,$tempat,$desc,$cover,$opac);
            }
            $stmt->execute();
            if ($id <= 0) $id = (int)$conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare('DELETE FROM perpus_buku_pengarang WHERE buku_id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            if ($authorId > 0) {
                $level = '1';
                $stmt = $conn->prepare('INSERT INTO perpus_buku_pengarang (buku_id,pengarang_id,level_pengarang) VALUES (?,?,?)');
                $stmt->bind_param('iis', $id, $authorId, $level); $stmt->execute(); $stmt->close();
            }

            if ($copyCount > 0) {
                if ($copyPrefix === '') $copyPrefix = $barcodeInduk !== '' ? $barcodeInduk : 'BK' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare('SELECT COUNT(*) total FROM perpus_buku_eksemplar WHERE buku_id=?');
                $stmt->bind_param('i', $id); $stmt->execute();
                $start = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0); $stmt->close();
                $stmt = $conn->prepare("INSERT INTO perpus_buku_eksemplar (buku_id,barcode,tipe_koleksi_id,status) VALUES (?,?,?,'tersedia')");
                for ($i = 1; $i <= $copyCount; $i++) {
                    $barcode = $copyPrefix . '-' . str_pad((string)($start + $i), 3, '0', STR_PAD_LEFT);
                    $stmt->bind_param('isi', $id, $barcode, $collectionId);
                    $stmt->execute();
                }
                $stmt->close();
            }
            $conn->commit();
            if (($newCover !== '' || $removeCover) && $oldCover !== '' && $oldCover !== $cover) {
                @unlink(sds_root_path('uploads/perpus/buku/' . $oldCover));
            }
            $newCover = '';
            $message = 'Data buku berhasil disimpan' . ($copyCount > 0 ? ' dan ' . $copyCount . ' eksemplar ditambahkan.' : '.');
        }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        if ($newCover !== '') @unlink(sds_root_path('uploads/perpus/buku/' . basename($newCover)));
        $raw = $e->getMessage();
        $error = stripos($raw, 'duplicate') !== false ? 'Barcode buku atau eksemplar sudah digunakan.' : $raw;
    }
}

function perpus_fetch_simple(mysqli $conn, string $table): array
{
    $rows = [];
    $result = $conn->query("SELECT * FROM `{$table}` ORDER BY nama");
    while ($result && ($row = $result->fetch_assoc())) $rows[] = $row;
    return $rows;
}

$categories = [];
$result = $conn->query('SELECT * FROM perpus_kategori_buku WHERE status_aktif=1 ORDER BY nama');
while ($result && ($row = $result->fetch_assoc())) $categories[] = $row;
$collections = perpus_fetch_simple($conn, 'perpus_tipe_koleksi');
$gmds = perpus_fetch_simple($conn, 'perpus_gmd');
$authors = perpus_fetch_simple($conn, 'perpus_pengarang');
$publishers = perpus_fetch_simple($conn, 'perpus_penerbit');

$q = trim((string)($_GET['q'] ?? ''));
$cat = (int)($_GET['kategori'] ?? 0);
$status = (string)($_GET['status'] ?? '');
$where = ['1=1']; $params = []; $types = '';
if ($q !== '') {
    $where[] = '(b.judul LIKE ? OR b.isbn LIKE ? OR b.barcode_induk LIKE ? OR b.nomor_panggil LIKE ? OR pa.nama LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like); $types .= 'sssss';
}
if ($cat > 0) { $where[] = 'b.kategori_id=?'; $params[] = $cat; $types .= 'i'; }
if ($status === 'aktif') $where[] = 'b.status_opac=1';
elseif ($status === 'nonaktif') $where[] = 'b.status_opac=0';
$sql = "SELECT b.*,k.nama kategori_nama,tk.nama tipe_koleksi_nama,g.nama gmd_nama,
    COALESCE(p.nama,b.penerbit_teks) penerbit_nama,GROUP_CONCAT(DISTINCT pa.nama ORDER BY pa.nama SEPARATOR ', ') pengarang_nama,
    COUNT(DISTINCT e.id) total_eksemplar,
    COUNT(DISTINCT CASE WHEN e.status='tersedia' THEN e.id END) tersedia,
    COUNT(DISTINCT CASE WHEN e.status='dipinjam' THEN e.id END) dipinjam,
    COUNT(DISTINCT CASE WHEN e.status IN ('rusak','hilang') THEN e.id END) bermasalah
    FROM perpus_buku b
    LEFT JOIN perpus_kategori_buku k ON k.id=b.kategori_id
    LEFT JOIN perpus_tipe_koleksi tk ON tk.id=b.tipe_koleksi_id
    LEFT JOIN perpus_gmd g ON g.id=b.gmd_id
    LEFT JOIN perpus_penerbit p ON p.id=b.penerbit_id
    LEFT JOIN perpus_buku_pengarang bpa ON bpa.buku_id=b.id
    LEFT JOIN perpus_pengarang pa ON pa.id=bpa.pengarang_id
    LEFT JOIN perpus_buku_eksemplar e ON e.buku_id=b.id
    WHERE " . implode(' AND ', $where) . " GROUP BY b.id ORDER BY b.judul";
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $refs = [$types]; foreach ($params as $index => &$value) $refs[] = &$value;
    call_user_func_array([$stmt, 'bind_param'], $refs); unset($value);
}
$stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$bookIds = array_map(static fn($row) => (int)$row['id'], $rows);
$copiesByBook = [];
if ($bookIds) {
    $idList = implode(',', $bookIds);
    $result = $conn->query("SELECT id,buku_id,barcode,status,tanggal_masuk,catatan FROM perpus_buku_eksemplar WHERE buku_id IN ({$idList}) ORDER BY buku_id,barcode");
    while ($result && ($copy = $result->fetch_assoc())) $copiesByBook[(int)$copy['buku_id']][] = $copy;
}
$totals = ['judul'=>count($rows),'eksemplar'=>0,'tersedia'=>0,'bermasalah'=>0];
foreach ($rows as $row) {
    $totals['eksemplar'] += (int)$row['total_eksemplar'];
    $totals['tersedia'] += (int)$row['tersedia'];
    $totals['bermasalah'] += (int)$row['bermasalah'];
}
require __DIR__ . '/../partials/master_page_style.php';
?>
<div class="sds-master-page perpus-books">
<div class="sds-hero"><div><h2>Koleksi Buku</h2><p>Judul, metadata bibliografi, dan eksemplar tersimpan pada tabel berprefix perpus_ di database SDS.</p></div><div class="sds-hero-actions"><a class="btn btn-outline-primary" href="master"><i data-feather="layers" class="me-1"></i>Data Master</a><a class="btn btn-outline-success" href="cetak"><i data-feather="printer" class="me-1"></i>Cetak Barcode</a><?php if($perpusCanManage):?><button class="btn btn-primary" type="button" onclick="openBookModal()"><i data-feather="plus" class="me-1"></i>Tambah Buku</button><?php endif;?></div></div>
<?php require __DIR__ . '/../partials/nav.php';?><?php if($message):?><div class="alert alert-success"><?=perpus_h($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=perpus_h($error)?></div><?php endif;?>
<div class="sds-stats"><div class="sds-stat-card"><small>Judul Ditampilkan</small><strong><?=number_format($totals['judul'],0,',','.')?></strong><span>Sesuai pencarian dan filter</span></div><div class="sds-stat-card"><small>Total Eksemplar</small><strong><?=number_format($totals['eksemplar'],0,',','.')?></strong><span>Termasuk yang sedang dipinjam</span></div><div class="sds-stat-card"><small>Tersedia</small><strong><?=number_format($totals['tersedia'],0,',','.')?></strong><span>Siap dipinjam</span></div><div class="sds-stat-card"><small>Rusak / Hilang</small><strong><?=number_format($totals['bermasalah'],0,',','.')?></strong><span>Perlu tindak lanjut</span></div></div>
<div class="card"><div class="card-header"><form class="book-filter"><input name="q" class="form-control form-control-sm search" value="<?=perpus_h($q)?>" placeholder="Cari judul, pengarang, ISBN, barcode, nomor panggil..."><select name="kategori" class="form-select form-select-sm"><option value="0">Semua Kategori</option><?php foreach($categories as $category):?><option value="<?=(int)$category['id']?>" <?=$cat===(int)$category['id']?'selected':''?>><?=perpus_h($category['nama'])?></option><?php endforeach;?></select><select name="status" class="form-select form-select-sm"><option value="">Semua Status</option><option value="aktif" <?=$status==='aktif'?'selected':''?>>Tampil OPAC</option><option value="nonaktif" <?=$status==='nonaktif'?'selected':''?>>Disembunyikan</option></select><button class="btn btn-sm btn-primary">Tampilkan</button><a class="btn btn-sm btn-outline-secondary" href="buku">Reset</a></form></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Buku</th><th>Kategori / Koleksi</th><th>Eksemplar</th><th>Ketersediaan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada koleksi buku.</td></tr><?php endif;?>
<?php foreach($rows as $row):$payload=htmlspecialchars(json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8');?><tr><td><div class="book-cell"><?php if($row['sampul']):?><img class="cover" src="../uploads/perpus/buku/<?=rawurlencode(basename($row['sampul']))?>" alt=""><?php else:?><div class="cover d-flex align-items-center justify-content-center"><i data-feather="book"></i></div><?php endif;?><div><div class="book-title"><?=perpus_h($row['judul'])?></div><div class="book-meta"><?=perpus_h($row['pengarang_nama']?:'Pengarang belum diisi')?> · ISBN <?=perpus_h($row['isbn']?:'-')?></div><div class="book-meta">Panggil: <?=perpus_h($row['nomor_panggil']?:'-')?> · <?=perpus_h($row['penerbit_nama']?:'-')?> <?=$row['tahun_terbit']?'· '.perpus_h($row['tahun_terbit']):''?></div></div></div></td><td><?=perpus_h($row['kategori_nama']?:'-')?><div class="book-meta"><?=perpus_h($row['tipe_koleksi_nama']?:'-')?><?= $row['gmd_nama']?' · '.perpus_h($row['gmd_nama']):'' ?></div></td><td><strong><?=number_format((int)$row['total_eksemplar'],0,',','.')?></strong></td><td><span class="badge bg-success"><?=(int)$row['tersedia']?> tersedia</span> <?php if((int)$row['dipinjam']):?><span class="badge bg-warning text-dark"><?=(int)$row['dipinjam']?> dipinjam</span><?php endif;?></td><td><?=$row['status_opac']?'<span class="badge bg-primary">Tampil</span>':'<span class="badge bg-secondary">Disembunyikan</span>'?></td><td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" data-book-id="<?= (int)$row['id'] ?>" data-book-title="<?= perpus_h($row['judul']) ?>" onclick="openCopiesModal(this)">Eksemplar</button> <?php if($perpusCanManage):?><button class="btn btn-sm btn-outline-primary" type="button" data-row="<?=$payload?>" onclick="openBookModal(this)">Edit</button><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></div></div>

<div class="modal fade" id="bookModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="post" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title" id="bookModalTitle">Tambah Buku</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="bookId"><div class="row g-3">
<div class="col-md-8"><label class="form-label">Judul Buku</label><input class="form-control" name="judul" id="bookTitle" required></div><div class="col-md-4"><label class="form-label">Kategori</label><select class="form-select" name="kategori_id" id="bookCategory"><option value="0">Tanpa kategori</option><?php foreach($categories as $category):?><option value="<?=(int)$category['id']?>"><?=perpus_h($category['nama'])?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label">Pengarang Utama</label><select class="form-select" name="pengarang_id" id="bookAuthor"><option value="0">Belum dipilih</option><?php foreach($authors as $author):?><option value="<?=(int)$author['id']?>"><?=perpus_h($author['nama'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label class="form-label">Tipe Koleksi</label><select class="form-select" name="tipe_koleksi_id" id="bookCollection"><option value="0">Belum dipilih</option><?php foreach($collections as $collection):?><option value="<?=(int)$collection['id']?>"><?=perpus_h($collection['nama'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label class="form-label">GMD / Bentuk Dokumen</label><select class="form-select" name="gmd_id" id="bookGmd"><option value="0">Belum dipilih</option><?php foreach($gmds as $gmd):?><option value="<?=(int)$gmd['id']?>"><?=perpus_h($gmd['nama'])?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label">ISBN</label><input class="form-control" name="isbn" id="bookIsbn"></div><div class="col-md-4"><label class="form-label">Barcode Induk</label><input class="form-control" name="barcode_induk" id="bookBarcode"></div><div class="col-md-4"><label class="form-label">Nomor Panggil</label><input class="form-control" name="nomor_panggil" id="bookCall"></div>
<div class="col-md-4"><label class="form-label">Penerbit Referensi</label><select class="form-select" name="penerbit_id" id="bookPublisherId"><option value="0">Tulis manual</option><?php foreach($publishers as $publisherRow):?><option value="<?=(int)$publisherRow['id']?>"><?=perpus_h($publisherRow['nama'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label class="form-label">Nama Penerbit</label><input class="form-control" name="penerbit_teks" id="bookPublisher"></div><div class="col-md-2"><label class="form-label">Tahun</label><input class="form-control" name="tahun_terbit" id="bookYear" maxlength="9"></div><div class="col-md-2"><label class="form-label">Edisi</label><input class="form-control" name="edisi" id="bookEdition"></div>
<div class="col-md-3"><label class="form-label">Klasifikasi</label><input class="form-control" name="klasifikasi" id="bookClass"></div><div class="col-md-3"><label class="form-label">Bahasa</label><input class="form-control" name="bahasa" id="bookLanguage"></div><div class="col-md-3"><label class="form-label">Tempat Terbit</label><input class="form-control" name="tempat_terbit" id="bookPlace"></div><div class="col-md-3"><label class="form-label">Tambah Eksemplar</label><input type="number" min="0" max="500" class="form-control" name="jumlah_eksemplar_baru" value="0"></div>
<div class="col-md-6"><label class="form-label">Prefix Barcode Eksemplar</label><input class="form-control" name="prefix_barcode" placeholder="Kosongkan untuk otomatis"></div><div class="col-md-6"><label class="form-label">Sampul Buku</label><input type="file" class="form-control" name="sampul" accept="image/jpeg,image/png,image/webp"><div class="form-check mt-2" id="removeCoverWrap" style="display:none"><input class="form-check-input" type="checkbox" name="hapus_sampul" value="1" id="removeCover"><label class="form-check-label" for="removeCover">Hapus sampul lama</label></div></div>
<div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="status_opac" value="1" id="bookOpac" checked><label class="form-check-label" for="bookOpac">Tampilkan pada katalog/OPAC</label></div></div><div class="col-12"><label class="form-label">Deskripsi Fisik</label><textarea class="form-control" name="deskripsi_fisik" id="bookDesc" rows="2"></textarea></div></div></div><div class="modal-footer justify-content-between"><button type="submit" class="btn btn-danger" id="bookDelete" name="action" value="delete" onclick="return confirm('Hapus buku dan seluruh eksemplar yang belum memiliki transaksi?')" style="display:none">Hapus</button><div class="ms-auto"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button> <button class="btn btn-primary">Simpan</button></div></div></form></div></div></div>

<div class="modal fade" id="copiesModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">Daftar Eksemplar</h5><div class="small text-muted" id="copiesBookTitle"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table class="table table-hover align-middle copy-table"><thead><tr><th>Barcode</th><th>Tanggal Masuk</th><th>Status</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead><tbody id="copiesBody"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
<script>
const copiesByBook=<?=json_encode($copiesByBook,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
function pModal(id){const element=document.getElementById(id);if(!element||!window.bootstrap)return null;return typeof bootstrap.Modal.getOrCreateInstance==='function'?bootstrap.Modal.getOrCreateInstance(element):new bootstrap.Modal(element)}
function openBookModal(button){const row=button?JSON.parse(button.dataset.row||'{}'):{};document.getElementById('bookModalTitle').textContent=row.id?'Edit Buku':'Tambah Buku';document.getElementById('bookId').value=row.id||0;document.getElementById('bookTitle').value=row.judul||'';document.getElementById('bookCategory').value=row.kategori_id||0;document.getElementById('bookCollection').value=row.tipe_koleksi_id||0;document.getElementById('bookGmd').value=row.gmd_id||0;document.getElementById('bookAuthor').value=row.pengarang_id||0;document.getElementById('bookPublisherId').value=row.penerbit_id||0;document.getElementById('bookIsbn').value=row.isbn||'';document.getElementById('bookBarcode').value=row.barcode_induk||'';document.getElementById('bookCall').value=row.nomor_panggil||'';document.getElementById('bookPublisher').value=row.penerbit_teks||row.penerbit_nama||'';document.getElementById('bookYear').value=row.tahun_terbit||'';document.getElementById('bookEdition').value=row.edisi||'';document.getElementById('bookClass').value=row.klasifikasi||'';document.getElementById('bookLanguage').value=row.bahasa||'';document.getElementById('bookPlace').value=row.tempat_terbit||'';document.getElementById('bookDesc').value=row.deskripsi_fisik||'';document.getElementById('bookOpac').checked=!row.id||String(row.status_opac)==='1';document.getElementById('bookDelete').style.display=row.id?'inline-block':'none';document.getElementById('removeCoverWrap').style.display=row.sampul?'block':'none';document.getElementById('removeCover').checked=false;const modal=pModal('bookModal');if(modal)modal.show()}
function escapeHtml(value){const div=document.createElement('div');div.textContent=value??'';return div.innerHTML}
function openCopiesModal(button){const bookId=Number(button?.dataset?.bookId||0);const title=button?.dataset?.bookTitle||'';document.getElementById('copiesBookTitle').textContent=title;const body=document.getElementById('copiesBody');const rows=copiesByBook[bookId]||[];if(!rows.length){body.innerHTML='<tr><td colspan="5" class="text-center text-muted py-4">Belum ada eksemplar.</td></tr>';}else{body.innerHTML=rows.map(copy=>{const borrowed=copy.status==='dipinjam';const options=['tersedia','rusak','hilang','nonaktif'].map(status=>`<option value="${status}" ${copy.status===status?'selected':''}>${status.charAt(0).toUpperCase()+status.slice(1)}</option>`).join('');return `<tr><td><code>${escapeHtml(copy.barcode)}</code></td><td>${escapeHtml(copy.tanggal_masuk||'-')}</td><td>${borrowed?'<span class="badge bg-warning text-dark">Dipinjam</span>':`<form method="post" class="d-flex gap-2"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="copy_status"><input type="hidden" name="copy_id" value="${copy.id}"><select class="form-select form-select-sm" name="copy_status">${options}</select><button class="btn btn-sm btn-primary">Simpan</button></form>`}</td><td>${escapeHtml(copy.catatan||'-')}</td><td class="text-end">${!borrowed?`<form method="post" class="d-inline" onsubmit="return confirm('Hapus eksemplar ini?')"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="delete_copy"><input type="hidden" name="copy_id" value="${copy.id}"><button class="btn btn-sm btn-outline-danger">Hapus</button></form>`:''}</td></tr>`;}).join('');}const modal=pModal('copiesModal');if(modal)modal.show()}
</script>
