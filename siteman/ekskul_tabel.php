<?php
session_start();
require '../db.php';

$tahun_ajaran = $_GET['tahun'] ?? '';
$stmt = $conn->prepare("
    SELECT e.*,
           (SELECT COUNT(*) FROM ekstrakurikuler_siswa es WHERE es.ekstrakurikuler_id = e.id) AS jumlah_anggota
    FROM ekstrakurikuler e
    WHERE e.tahun_ajaran = ?
    ORDER BY e.nama_ekskul ASC
");
$stmt->bind_param('s', $tahun_ajaran);
$stmt->execute();
$data = $stmt->get_result();

if ($data && $data->num_rows > 0):
    $no = 1;
    while ($row = $data->fetch_assoc()): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($row['nama_ekskul']) ?></strong></td>
            <td class="text-center"><span class="sds-badge info"><?= (int)$row['jumlah_anggota'] ?> siswa</span></td>
            <td><?= htmlspecialchars($row['nama_pembina'] ?: '-') ?></td>
            <td class="text-end">
                <div class="sds-actions">
                    <a href="#"
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditEkskul"
                        data-id="<?= (int)$row['id'] ?>"
                        data-nama="<?= htmlspecialchars($row['nama_ekskul']) ?>"
                        data-pembina="<?= htmlspecialchars($row['nama_pembina']) ?>">Edit</a>
                    <a href="ekskul_hapus&id=<?= (int)$row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus Ekstrakurikuler?')">Hapus</a>
                    <a href="ekskul_tambah_siswa?id=<?= (int)$row['id'] ?>" class="btn btn-warning btn-sm">Tambah Siswa</a>
                    <a href="ekskul_lihat_siswa?id=<?= (int)$row['id'] ?>" class="btn btn-primary btn-sm">Anggota</a>
                    <a href="ekskul_cetak_absen.php?id=<?= (int)$row['id'] ?>" class="btn btn-success btn-sm">Cetak Absensi</a>
                </div>
            </td>
        </tr>
    <?php endwhile;
else: ?>
    <tr>
        <td colspan="5" class="text-center text-muted py-4">Tidak ada data ekstrakurikuler.</td>
    </tr>
<?php endif; ?>
