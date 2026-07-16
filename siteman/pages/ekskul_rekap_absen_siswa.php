<?php
$siswa_id = $_GET['siswa'] ?? null;
$ekskul_id = $_GET['ekskul'] ?? null;
$filter = $_GET['filter'] ?? 'all';

if (!$siswa_id || !$ekskul_id) {
    exit("ID Siswa atau Ekskul tidak ditemukan.");
}

// Ambil nama siswa dan ekskul + tahun ajaran
$stmt = $conn->prepare("
    SELECT ps.nama_lengkap, ek.nama_ekskul, ek.tahun_ajaran, ps.nipd
    FROM pendaftaran_siswa ps
    JOIN ekstrakurikuler_siswa es ON es.siswa_id = ps.id
    JOIN ekstrakurikuler ek ON ek.id = es.ekstrakurikuler_id
    JOIN ekskul_absensi ea ON ea.siswa_id = ps.id AND ea.ekskul_id = ek.id
    WHERE ps.id = ? 
      AND ek.id = ?
      AND ea.status IN ('H', 'I', 'S', 'A')
");

$stmt->bind_param("ii", $siswa_id, $ekskul_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$nama_siswa = $data['nama_lengkap'] ?? '-';
$nama_ekskul = $data['nama_ekskul'] ?? '-';
$tahun_ajaran = $data['tahun_ajaran'] ?? '-';
$nipd = $data['nipd'] ?? '-'; // tambahan

// Filter tanggal
$whereClause = "";
if ($filter === 'mingguan') {
    $whereClause = "AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
} elseif ($filter === 'bulanan') {
    $whereClause = "AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
} elseif ($filter === 'tahunan') {
    $whereClause = "AND YEAR(tanggal) = YEAR(CURDATE())";
}

// Ambil data absensi siswa
$query = "
    SELECT ea.tanggal, ea.status, ea.keterangan, em.judul AS materi, em.isi
    FROM ekskul_absensi ea
    LEFT JOIN ekskul_materi em 
        ON ea.ekskul_id = em.ekskul_id AND ea.tanggal = em.tanggal
    WHERE ea.siswa_id = ? AND ea.ekskul_id = ? $whereClause
    ORDER BY ea.tanggal ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $siswa_id, $ekskul_id);
$stmt->execute();
$result = $stmt->get_result();

$jumlahH = $jumlahI = $jumlahS = $jumlahA = 0;
$absensi = [];
$displayStatus = $absensi;

while ($row = $result->fetch_assoc()) {
    switch ($row['status']) {
        case 'H':
        case 'HADIR':
            $jumlahH++;
            $row['display_status'] = 'Hadir';
            break;
        case 'I':
            $jumlahI++;
            $row['display_status'] = 'Izin';
            break;
        case 'S':
            $jumlahS++;
            $row['display_status'] = 'Sakit';
            break;
        case 'A':
            $jumlahA++;
            $row['display_status'] = 'Alpa';
            break;
        default:
            $row['display_status'] = '-';
    }
    $absensi[] = $row;
}

?>
<div class="topbar d-print-none">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto d-sm-block">
                <a href="student_view?id=<?= $siswa_id ?>#ekskul" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="col-auto align-self-end ms-auto text-end">
                <form method="get" class="row row-cols-md-auto align-items-center g-1">
                    <input type="hidden" name="siswa" value="<?= $siswa_id ?>">
                    <input type="hidden" name="ekskul" value="<?= $ekskul_id ?>">
                    <span>Filter:</span>
                    <div class="col-auto">
                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua</option>
                            <option value="mingguan" <?= $filter === 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                            <option value="bulanan" <?= $filter === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                            <option value="tahunan" <?= $filter === 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                        </select>
                    </div>
                    <div class="col-auto align-self-end">
                        <a href="javascript:window.print();" class="btn btn-primary">🖨️ Cetak</a>
                        <!-- <a href="export_excel_rekap_ekskul_absen.php?ekskul_id=<?= $ekskul_id ?>&awal=<?= $tanggal_awal ?>&akhir=<?= $tanggal_akhir ?>" class="btn btn-success">📥 Export Excel</a> -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card mt-5">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-sm w-50 mb-4">
                        <tr>
                            <td><strong>Nama Peserta Didik</strong></td>
                            <td>: <?= htmlspecialchars($nama_siswa) ?></td>
                        </tr>
                        <tr>
                            <td><strong>NIPD</strong></td>
                            <td>: <?= htmlspecialchars($nipd) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Ekstrakurikuler</strong></td>
                            <td>: <?= htmlspecialchars($nama_ekskul) ?></td>
                        </tr>
                    </table>
                    <table class="table table-bordered table-sm table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tahun Ajaran</th>
                                <th>Tanggal</th>
                                <th>Materi</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($absensi) === 0): ?>
                                <tr>
                                    <td colspan="5">Tidak ada data.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($absensi as $abs): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($tahun_ajaran) ?></td>
                                        <td><?= date('d/m/Y', strtotime($abs['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($abs['materi'] . ' - ' . $abs['isi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($abs['display_status']) ?></td>
                                        <td><?= htmlspecialchars($abs['keterangan'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background-color:#f5f5f5;">
                                <td colspan="6"><strong>Jumlah Status</strong></td>
                            </tr>
                            <tr>
                                <td style="border-right: none;"><strong>Hadir</strong></td>
                                <td colspan="5" style="border-left: none;">: <?= $jumlahH ?></td>
                            </tr>
                            <tr>
                                <td style="border-right: none;"><strong>Izin</strong></td>
                                <td colspan="5" style="border-left: none;">: <?= $jumlahI ?></td>
                            </tr>
                            <tr>
                                <td style="border-right: none;"><strong>Sakit</strong></td>
                                <td colspan="5" style="border-left: none;">: <?= $jumlahS ?></td>
                            </tr>
                            <tr>
                                <td style="border-right: none;"><strong>Alpa</strong></td>
                                <td colspan="5" style="border-left: none;">: <?= $jumlahA ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>