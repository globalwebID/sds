<?php
require '_config.php';
requireAuth();

$context = integratedLibraryStudent($conn, (int)($_SESSION['id_siswa'] ?? 0));
$memberId = (int)$context['member']['id'];
$idPinjam = (int)($_GET['id_pinjam'] ?? 0);
$idBuku = (int)($_GET['id_buku'] ?? 0);
if ($idPinjam <= 0 || $idBuku <= 0) response(false, 'Parameter pinjaman atau buku tidak valid');

$sql = "SELECT p.id id_pinjam,p.tanggal_pinjam,p.status status_transaksi,
        d.buku_id,d.kode_resi,d.tanggal_jatuh_tempo,d.tanggal_kembali,d.denda,d.status status_buku,
        b.judul,b.isbn,b.barcode_induk,b.penerbit_teks,b.tahun_terbit,b.edisi,b.klasifikasi,
        b.nomor_panggil,b.tempat_terbit,b.deskripsi_fisik,b.sampul,
        kb.nama kategori,
        GROUP_CONCAT(DISTINCT pg.nama ORDER BY pg.nama SEPARATOR ', ') pengarang
        FROM perpus_peminjaman p
        JOIN perpus_peminjaman_detail d ON d.peminjaman_id=p.id
        JOIN perpus_buku b ON b.id=d.buku_id
        LEFT JOIN perpus_kategori_buku kb ON kb.id=b.kategori_id
        LEFT JOIN perpus_buku_pengarang bp ON bp.buku_id=b.id
        LEFT JOIN perpus_pengarang pg ON pg.id=bp.pengarang_id
        WHERE p.anggota_id=? AND p.id=? AND d.buku_id=?
        GROUP BY d.id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $memberId, $idPinjam, $idBuku);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) response(false, 'Detail tidak ditemukan atau bukan milik Anda');

$cover = trim((string)($row['sampul'] ?? ''));
if ($cover !== '' && !preg_match('~^https?://~i', $cover)) {
    $cover = sds_base_url('uploads/perpus/buku/' . rawurlencode(basename($cover)));
}

response(true, 'Detail buku', ['detail'=>[
    'id_pinjam' => (string)$row['id_pinjam'],
    'id_buku' => (string)$row['buku_id'],
    'judul' => (string)$row['judul'],
    'pengarang' => (string)($row['pengarang'] ?? ''),
    'penerbit' => (string)($row['penerbit_teks'] ?? ''),
    'tahun' => (string)($row['tahun_terbit'] ?? ''),
    'isbn' => (string)($row['isbn'] ?? ''),
    'barcode' => (string)($row['barcode_induk'] ?? ''),
    'kategori' => (string)($row['kategori'] ?? ''),
    'edisi' => (string)($row['edisi'] ?? ''),
    'tempat' => (string)($row['tempat_terbit'] ?? ''),
    'deskripsi' => (string)($row['deskripsi_fisik'] ?? ''),
    'klasifikasi' => (string)($row['klasifikasi'] ?? ''),
    'no_panggil' => (string)($row['nomor_panggil'] ?? ''),
    'foto' => $cover,
    'tgl_pinjam' => (string)($row['tanggal_pinjam'] ?? ''),
    'tgl_kembali' => (string)($row['tanggal_jatuh_tempo'] ?? ''),
    'resi' => (string)($row['kode_resi'] ?? ''),
    'denda' => (float)($row['denda'] ?? 0),
    'status' => integratedLibraryStatus([
        'status_buku'=>$row['status_buku'],
        'status_transaksi'=>$row['status_transaksi'],
        'tgl_kembali'=>$row['tanggal_jatuh_tempo'],
    ]),
]]);
