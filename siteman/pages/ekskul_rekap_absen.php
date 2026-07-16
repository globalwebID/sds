<?php
require '../db.php';

$ekskul_id = $_GET['ekskul_id'] ?? 0;
$tanggal_awal = $_GET['awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['akhir'] ?? date('Y-m-d');

if (!$ekskul_id) {
    echo "ID ekskul tidak ditemukan.";
    exit;
}

// Ambil nama ekskul
$stmt = $conn->prepare("SELECT nama_ekskul FROM ekstrakurikuler WHERE id = ?");
$stmt->bind_param("i", $ekskul_id);
$stmt->execute();
$stmt->bind_result($nama_ekskul);
$stmt->fetch();
$stmt->close();

// Total pertemuan
$stmt = $conn->prepare("SELECT COUNT(DISTINCT tanggal) FROM ekskul_absensi WHERE ekskul_id = ? AND tanggal BETWEEN ? AND ?");
$stmt->bind_param("iss", $ekskul_id, $tanggal_awal, $tanggal_akhir);
$stmt->execute();
$stmt->bind_result($total_pertemuan);
$stmt->fetch();
$stmt->close();

// Ambil semua data absensi siswa
$stmt = $conn->prepare("
    SELECT 
        ps.id AS siswa_id,
        ps.nama_lengkap,
        ea.status,
        ea.keterangan,
        ea.tanggal,
        ea.jam
    FROM ekskul_absensi ea
    JOIN pendaftaran_siswa ps ON ps.id = ea.siswa_id
    WHERE ea.ekskul_id = ? AND ea.tanggal BETWEEN ? AND ?
    ORDER BY ps.nama_lengkap, ea.tanggal ASC, ea.jam ASC
");
$stmt->bind_param("iss", $ekskul_id, $tanggal_awal, $tanggal_akhir);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['siswa_id'];
    $nama = $row['nama_lengkap'];
    $tgl = date('d/m/Y', strtotime($row['tanggal']));
    $jam = $row['jam'];
    $status = $row['status'];
    $ket = $row['keterangan'];

    if (!isset($data[$id])) {
        $data[$id] = [
            'nama' => $nama,
            'jam_datang' => [],
            'jam_pulang' => [],
            'H' => 0,
            'I' => 0,
            'S' => 0,
            'A' => 0,
            'keterangan' => []
        ];
    }

    if ($status === 'H') {
        $data[$id]['jam_datang'][$tgl] = $jam;
        $data[$id]['H']++;
    } elseif ($status === 'P') {
        $data[$id]['jam_pulang'][$tgl] = $jam;
    } elseif (in_array($status, ['I', 'S', 'A'])) {
        $data[$id][$status]++;
        if ($ket) {
            $data[$id]['keterangan'][] = "$tgl: $ket";
        }
    }
}
// Urutkan tanggal untuk konsistensi tampilan
$daftar_tanggal = [];
foreach ($data as $row) {
    foreach (array_keys($row['jam_datang']) as $tgl) {
        $daftar_tanggal[$tgl] = true;
    }
    foreach (array_keys($row['jam_pulang']) as $tgl) {
        $daftar_tanggal[$tgl] = true;
    }
}
$daftar_tanggal = array_keys($daftar_tanggal);
sort($daftar_tanggal);

?>
<div class="topbar d-print-none">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto d-sm-block">
                <a href="ekskul_lihat_siswa?id=<?= $ekskul_id ?>" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="col-auto ms-auto text-end">
                <form method="get" class="row row-cols-md-auto align-items-center g-1">
                    <input type="hidden" name="ekskul_id" value="<?= $ekskul_id ?>">
                    <div class="col-auto">
                        <input type="date" name="awal" value="<?= $tanggal_awal ?>" class="form-control">
                    </div>
                    <span>s/d</span>
                    <div class="col-auto">
                        <input type="date" name="akhir" value="<?= $tanggal_akhir ?>" class="form-control">
                    </div>
                    <div class="col-auto align-self-end">
                        <button class="btn btn-success">Tampilkan Filter</button>
                        <a href="javascript:window.print();" class="btn btn-primary">🖨️ Cetak</a>
                        <a href="export_excel_rekap_ekskul_absen.php?ekskul_id=<?= $ekskul_id ?>&awal=<?= $tanggal_awal ?>&akhir=<?= $tanggal_akhir ?>" class="btn btn-success">📥 Export Excel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Tampilan HTML -->
<div class="card mt-5">
    <div class="card-header">
        <h4 class="mb-0">Rekap Absensi Ekstrakurikuler <?= htmlspecialchars($nama_ekskul) ?></h4>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped">
            <thead>
                <tr>
                    <td colspan="10" style="background-color: #eee;">
                        Total Pertemuan: <strong><?= $total_pertemuan ?> Pertemuan</strong>
                    </td>
                </tr>
                <tr>
                    <th rowspan="2" class="text-center" style="vertical-align: middle;">No.</th>
                    <th rowspan="2" style="vertical-align: middle;">Nama Siswa</th>
                    <th rowspan="2" class="text-center" style="vertical-align: middle;">Tanggal</th>
                    <th colspan="2" class="text-center">Waktu Absen</th>
                    <th rowspan="2" class="text-center" style="vertical-align: middle;">Hadir</th>
                    <th rowspan="2" class="text-center" style="vertical-align: middle;">Izin</th>
                    <th rowspan="2" class="text-center" style="vertical-align: middle;">Sakit</th>
                    <th rowspan="2" class="text-center" style="vertical-align: middle;">Alpa</th>
                    <th rowspan="2" style="vertical-align: middle;">Keterangan</th>
                </tr>
                <tr>
                    <th class="text-center">Datang</th>
                    <th class="text-center">Pulang</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data): $no = 1; ?>
                    <?php foreach ($data as $id => $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><a href="student_view?id=<?= $id ?>#ekskul"><?= htmlspecialchars($row['nama']) ?></a></td>
                            <td class="text-center">
                                <?php foreach ($daftar_tanggal as $tgl): ?>
                                    <?= $tgl ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-center">
                                <?php foreach ($daftar_tanggal as $tgl): ?>
                                    <?= $row['jam_datang'][$tgl] ?? '-' ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-center">
                                <?php foreach ($daftar_tanggal as $tgl): ?>
                                    <?= $row['jam_pulang'][$tgl] ?? '-' ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-center"><?= $row['H'] ?></td>
                            <td class="text-center"><?= $row['I'] ?></td>
                            <td class="text-center"><?= $row['S'] ?></td>
                            <td class="text-center"><?= $row['A'] ?></td>
                            <td><?= implode('<br>', array_map('htmlspecialchars', $row['keterangan'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">Tidak ada data absensi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>