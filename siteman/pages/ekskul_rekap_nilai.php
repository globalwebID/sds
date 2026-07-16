<?php
// Tangkap parameter GET
$siswa_id = isset($_GET['siswa']) ? (int)$_GET['siswa'] : 0;
$ekskul_id = isset($_GET['ekskul']) ? (int)$_GET['ekskul'] : 0;

if ($siswa_id <= 0) {
    die('Parameter siswa tidak valid!');
}

// Ambil data siswa
$stmt = $conn->prepare("SELECT * FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    die('Data siswa tidak ditemukan!');
}

$nilaiList = [];
$groupedNilai = [];
$nama_ekskul_filter = null;

if ($ekskul_id > 0) {
    // Ambil data ekskul
    $stmt = $conn->prepare("SELECT * FROM ekstrakurikuler WHERE id = ?");
    $stmt->bind_param("i", $ekskul_id);
    $stmt->execute();
    $ekskul = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ekskul) {
        die('Data ekstrakurikuler tidak ditemukan!');
    }

    $nama_ekskul_filter = $ekskul['nama_ekskul'];

    // Ambil nilai berdasarkan ekskul tertentu
    $stmt = $conn->prepare("
        SELECT ne.nilai, ne.keterangan, ne.semester, ne.tanggal, ne.created_at,
               e.nama_ekskul, e.tahun_ajaran
        FROM nilai_ekskul ne
        JOIN ekstrakurikuler e ON ne.ekskul_id = e.id
        WHERE ne.siswa_id = ? AND ne.ekskul_id = ?
        ORDER BY e.tahun_ajaran DESC, ne.semester ASC, ne.tanggal DESC
    ");
    $stmt->bind_param("ii", $siswa_id, $ekskul_id);
} else {
    // Ambil semua nilai ekskul
    $stmt = $conn->prepare("
        SELECT ne.nilai, ne.keterangan, ne.semester, ne.tanggal, ne.created_at,
               e.id AS ekskul_id, e.nama_ekskul, e.tahun_ajaran
        FROM nilai_ekskul ne
        JOIN ekstrakurikuler e ON ne.ekskul_id = e.id
        WHERE ne.siswa_id = ?
        ORDER BY e.nama_ekskul ASC, e.tahun_ajaran DESC, ne.semester ASC, ne.tanggal DESC
    ");
    $stmt->bind_param("i", $siswa_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $nilaiList = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Jika tanpa filter, kelompokkan berdasarkan ekskul
if ($ekskul_id === 0) {
    foreach ($nilaiList as $row) {
        $groupedNilai[$row['ekskul_id']]['nama'] = $row['nama_ekskul'];
        $groupedNilai[$row['ekskul_id']]['data'][] = $row;
    }
}
?>

<!-- Tampilan -->
<div class="topbar">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto d-sm-block">
                <a href="student_view?id=<?= $siswa_id ?>#ekskul" class="btn btn-secondary">Kembali</a>
                <!-- <h4 class="mb-0">
                    Rekap Nilai Ekstrakurikuler
                    <?php if ($nama_ekskul_filter): ?>
                        - <?= htmlspecialchars($nama_ekskul_filter) ?>
                    <?php endif; ?>
                </h4> -->
            </div>
            <div class="col-auto ms-auto text-end">
                <a href="rekap_nilai_ekskul_pdf.php?siswa=<?= $siswa_id ?>&ekskul=<?= $ekskul_id ?>" class="btn btn-success">Cetak Nilai</a>
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
                            <td>: <?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>NISN</strong></td>
                            <td>: <?= htmlspecialchars($siswa['nisn']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>NIPD</strong></td>
                            <td>: <?= htmlspecialchars($siswa['nipd']) ?></td>
                        </tr>
                    </table>

                    <?php if ($ekskul_id > 0): ?>
                        <!-- Tabel nilai ekskul spesifik -->
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th colspan="6">
                                        <strong>Ekstrakurikuler</strong>
                                        : <a href="ekskul_lihat_siswa?id=<?= $ekskul_id ?>" class="text-decoration-none">
                                            <strong><?= htmlspecialchars($nama_ekskul_filter) ?></strong>
                                        </a>
                                    </th>
                                </tr>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Semester</th>
                                    <th>Nilai</th>
                                    <th>Keterangan</th>
                                    <th>Tanggal Penilaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($nilaiList) > 0): $no = 1;
                                    foreach ($nilaiList as $nilai): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($nilai['tahun_ajaran']) ?></td>
                                            <td><?= htmlspecialchars($nilai['semester']) ?></td>
                                            <td><?= htmlspecialchars($nilai['nilai']) ?></td>
                                            <td><?= htmlspecialchars($nilai['keterangan']) ?></td>
                                            <td><?= date('d M Y', strtotime($nilai['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada nilai ekstrakurikuler</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Tabel per ekskul -->
                        <?php if (count($groupedNilai) > 0): ?>
                            <?php foreach ($groupedNilai as $ekskul): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th colspan="6" style="background-color: #eee;">
                                                    <strong>Ekstrakurikuler</strong>
                                                    : <?php $firstData = current($ekskul['data']); ?>
                                                    <a href="ekskul_lihat_siswa?id=<?= $firstData['ekskul_id'] ?>" class="text-decoration-none">
                                                        <strong><?= htmlspecialchars($ekskul['nama']) ?></strong>
                                                    </a>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>No</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Semester</th>
                                                <th>Nilai</th>
                                                <th>Keterangan</th>
                                                <th>Tanggal Penilaian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1;
                                            foreach ($ekskul['data'] as $nilai): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= htmlspecialchars($nilai['tahun_ajaran']) ?></td>
                                                    <td><?= htmlspecialchars($nilai['semester']) ?></td>
                                                    <td><?= htmlspecialchars($nilai['nilai']) ?></td>
                                                    <td><?= htmlspecialchars($nilai['keterangan']) ?></td>
                                                    <td><?= date('d M Y', strtotime($nilai['created_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">Belum ada nilai ekstrakurikuler.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>