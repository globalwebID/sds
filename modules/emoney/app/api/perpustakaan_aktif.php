<?php
require '_config.php';
requireAuth();

$context = integratedLibraryStudent($conn, (int)($_SESSION['id_siswa'] ?? 0));
$student = $context['student'];
$member = $context['member'];
$memberId = (int)$member['id'];

$sql = "SELECT p.id id_pinjam,p.tanggal_pinjam tgl_pinjam,p.status status_transaksi,
        d.buku_id id_buku,d.kode_resi resi,d.tanggal_jatuh_tempo tgl_kembali,
        d.denda,d.status status_buku,b.judul
        FROM perpus_peminjaman p
        JOIN perpus_peminjaman_detail d ON d.peminjaman_id=p.id
        LEFT JOIN perpus_buku b ON b.id=d.buku_id
        WHERE p.anggota_id=? AND d.status='dipinjam'
        ORDER BY p.tanggal_pinjam DESC,d.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $memberId);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
$totalFine = 0;
while ($row = $result->fetch_assoc()) {
    $fine = (float)($row['denda'] ?? 0);
    $totalFine += $fine;
    $items[] = [
        'id_pinjam' => (string)$row['id_pinjam'],
        'id_buku' => (string)($row['id_buku'] ?? ''),
        'resi' => (string)($row['resi'] ?? ''),
        'tgl_pinjam' => (string)($row['tgl_pinjam'] ?? ''),
        'judul' => (string)($row['judul'] ?? '(Judul tidak ditemukan)'),
        'tgl_kembali' => (string)($row['tgl_kembali'] ?? ''),
        'denda' => $fine,
        'status' => integratedLibraryStatus($row),
    ];
}
$stmt->close();

response(true, 'Data perpustakaan (aktif)', [
    'siswa' => [
        'nama' => (string)$student['nama_lengkap'],
        'rfid' => (string)$student['rfid_uid'],
    ],
    'anggota' => [
        'id_anggota' => (string)$member['nomor_anggota'],
        'nis' => (string)($student['nisn'] ?: $student['nipd']),
        'nama' => (string)$student['nama_lengkap'],
        'kelas' => (string)$student['nama_kelas'],
    ],
    'ringkasan' => [
        'pinjaman_aktif' => count($items),
        'total_denda_aktif' => $totalFine,
    ],
    'pinjaman_aktif' => $items,
]);
