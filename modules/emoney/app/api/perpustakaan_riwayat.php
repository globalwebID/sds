<?php
require '_config.php';
requireAuth();

$context = integratedLibraryStudent($conn, (int)($_SESSION['id_siswa'] ?? 0));
$memberId = (int)$context['member']['id'];
$tglAwal = (string)($_GET['tglAwal'] ?? date('Y-m-d', strtotime('-30 days')));
$tglAkhir = (string)($_GET['tglAkhir'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) $tglAwal = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) $tglAkhir = date('Y-m-d');
if ($tglAwal > $tglAkhir) [$tglAwal, $tglAkhir] = [$tglAkhir, $tglAwal];

$sql = "SELECT p.id id_pinjam,p.tanggal_pinjam tgl_pinjam,p.status status_transaksi,
        d.buku_id id_buku,d.kode_resi resi,d.tanggal_jatuh_tempo tgl_kembali,
        d.denda,d.status status_buku,b.judul
        FROM perpus_peminjaman p
        JOIN perpus_peminjaman_detail d ON d.peminjaman_id=p.id
        LEFT JOIN perpus_buku b ON b.id=d.buku_id
        WHERE p.anggota_id=? AND p.tanggal_pinjam BETWEEN ? AND ?
        ORDER BY p.tanggal_pinjam DESC,d.id DESC LIMIT 200";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $memberId, $tglAwal, $tglAkhir);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id_pinjam' => (string)$row['id_pinjam'],
        'id_buku' => (string)($row['id_buku'] ?? ''),
        'resi' => (string)($row['resi'] ?? ''),
        'tgl_pinjam' => (string)($row['tgl_pinjam'] ?? ''),
        'judul' => (string)($row['judul'] ?? '(Judul tidak ditemukan)'),
        'tgl_kembali' => (string)($row['tgl_kembali'] ?? ''),
        'denda' => (float)($row['denda'] ?? 0),
        'status' => integratedLibraryStatus($row),
    ];
}
$stmt->close();

response(true, 'Data perpustakaan (riwayat)', [
    'riwayat' => $items,
    'filter' => ['tglAwal'=>$tglAwal, 'tglAkhir'=>$tglAkhir],
]);
