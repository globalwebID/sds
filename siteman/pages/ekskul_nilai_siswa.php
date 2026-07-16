<?php
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$ekskul_id = isset($_GET['ekskul_id']) && is_numeric($_GET['ekskul_id']) ? (int)$_GET['ekskul_id'] : 0;
$mode = isset($_GET['mode']) && $_GET['mode'] === 'massal' ? 'massal' : 'individu';

// Tambahkan semester
$semester = $_GET['semester'] ?? 'Ganjil';
$semesterList = ['Ganjil', 'Genap'];

if ($ekskul_id <= 0) {
    $_SESSION['error'] = "Parameter ekstrakurikuler tidak valid!";
    header("Location: ekskul_lihat_siswa?id=" . $ekskul_id);
    exit;
}

// Ambil nama ekskul
$stmtEkskul = $conn->prepare("SELECT nama_ekskul FROM ekstrakurikuler WHERE id = ?");
$stmtEkskul->bind_param("i", $ekskul_id);
$stmtEkskul->execute();
$stmtEkskul->bind_result($namaEkskul);
$stmtEkskul->fetch();
$stmtEkskul->close();

if (!$namaEkskul) {
    $_SESSION['error'] = "Ekstrakurikuler tidak ditemukan.";
    header("Location: ekskul_lihat_siswa?id=" . $ekskul_id);
    exit;
}

// =========================
// Simpan Nilai Massal
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'massal') {
    $nilaiArr = $_POST['nilai'] ?? [];
    $keteranganArr = $_POST['keterangan'] ?? [];
    $siswaIdArr = $_POST['siswa_id'] ?? [];

    foreach ($siswaIdArr as $idx => $siswa_id) {
        $nilai = trim($nilaiArr[$idx]);
        $keterangan = trim($keteranganArr[$idx]);

        if (is_numeric($nilai) && $nilai >= 0 && $nilai <= 100) {
            $cek = $conn->prepare("SELECT id FROM nilai_ekskul WHERE siswa_id = ? AND ekskul_id = ? AND semester = ?");
            $cek->bind_param("iis", $siswa_id, $ekskul_id, $semester);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $update = $conn->prepare("UPDATE nilai_ekskul SET nilai = ?, keterangan = ? WHERE siswa_id = ? AND ekskul_id = ? AND semester = ?");
                $update->bind_param("isiis", $nilai, $keterangan, $siswa_id, $ekskul_id, $semester);
                $update->execute();
                $update->close();
            } else {
                $insert = $conn->prepare("INSERT INTO nilai_ekskul (siswa_id, ekskul_id, nilai, keterangan, semester, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $insert->bind_param("iiiss", $siswa_id, $ekskul_id, $nilai, $keterangan, $semester);
                $insert->execute();
                $insert->close();
            }
            $cek->close();
        }
    }

    $_SESSION['success'] = "Nilai berhasil disimpan.";
    header("Location: ekskul_nilai_siswa?id=$id&ekskul_id=$ekskul_id&mode=massal&semester=$semester");
    exit;
}

// =========================
// Simpan Nilai Individu
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'individu') {
    $nilai = isset($_POST['nilai']) ? trim($_POST['nilai']) : '';
    $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $error = "Nilai harus angka 0–100.";
    } else {
        $getDataStmt = $conn->prepare("SELECT siswa_id FROM ekstrakurikuler_siswa WHERE id = ?");
        $getDataStmt->bind_param("i", $id);
        $getDataStmt->execute();
        $getDataStmt->bind_result($siswa_id);
        if (!$getDataStmt->fetch()) {
            $error = "Data tidak ditemukan.";
        }
        $getDataStmt->close();

        if (!isset($error)) {
            $cek = $conn->prepare("SELECT id FROM nilai_ekskul WHERE siswa_id = ? AND ekskul_id = ? AND semester = ?");
            $cek->bind_param("iis", $siswa_id, $ekskul_id, $semester);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $update = $conn->prepare("UPDATE nilai_ekskul SET nilai = ?, keterangan = ? WHERE siswa_id = ? AND ekskul_id = ? AND semester = ?");
                $update->bind_param("isiis", $nilai, $keterangan, $siswa_id, $ekskul_id, $semester);
                $update->execute();
                $update->close();
            } else {
                $insert = $conn->prepare("INSERT INTO nilai_ekskul (siswa_id, ekskul_id, nilai, keterangan, semester, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $insert->bind_param("iiiss", $siswa_id, $ekskul_id, $nilai, $keterangan, $semester);
                $insert->execute();
                $insert->close();
            }
            $cek->close();

            $_SESSION['success'] = "Nilai berhasil disimpan.";
            header("Location: ekskul_nilai_siswa?id=$id&ekskul_id=$ekskul_id&semester=$semester");
            exit;
        }
    }
}
?>

<form method="post">
    <div class="topbar mb-3">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-auto d-sm-block">
                    <a href="ekskul_lihat_siswa?id=<?= $ekskul_id ?>" class="btn btn-secondary">Kembali</a>

                </div>
                <div class="col-auto ms-auto">
                    <button type="submit" class="btn btn-success">Simpan Nilai</button>
                    <!-- <select name="semester" onchange="window.location.href='?id=<?= $id ?>&ekskul_id=<?= $ekskul_id ?>&mode=<?= $mode ?>&semester=' + this.value" class="form-select">
                        <?php foreach ($semesterList as $sem): ?>
                            <option value="<?= $sem ?>" <?= $semester === $sem ? 'selected' : '' ?>>Semester <?= $sem ?></option>
                        <?php endforeach; ?>
                    </select> -->
                </div>
            </div>
        </div>
    </div>

    <?php if ($mode === 'individu'): ?>
        <?php
        $stmt = $conn->prepare("
            SELECT ps.nama_lengkap, ps.nipd, k.nama_kelas, k.tahun_ajaran, ne.nilai, ne.keterangan
            FROM ekstrakurikuler_siswa es
            JOIN pendaftaran_siswa ps ON es.siswa_id = ps.id
            JOIN kelas k ON ps.kelas_id = k.id
            LEFT JOIN nilai_ekskul ne ON ne.siswa_id = es.siswa_id AND ne.ekskul_id = es.ekstrakurikuler_id AND ne.semester = ?
            WHERE es.id = ? AND es.ekstrakurikuler_id = ?
        ");
        $stmt->bind_param("sii", $semester, $id, $ekskul_id);
        $stmt->execute();
        $siswa = $stmt->get_result()->fetch_assoc();
        ?>
        <div class="card mt-5">
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message">
                        <?= $_SESSION['error'] ?>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message">
                        <?= $_SESSION['success'] ?>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <div class="card-header pb-0">
                <h4 class="card-title">Input Nilai Ekstrakurikuler: <?= htmlspecialchars($namaEkskul) ?></h4>
                <div class="card-subtitle text-muted">
                    <strong>Catatan:</strong> Silakan masukkan nilai untuk setiap siswa di bawah ini. Nilai harus antara 0 hingga 100.
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($siswa['nama_lengkap']) ?> (<?= htmlspecialchars($siswa['nipd']) ?>)</h5>
                <p>Kelas: <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
                <label>Nilai</label>
                <input type="number" class="form-control" name="nilai" value="<?= htmlspecialchars($siswa['nilai'] ?? '') ?>" min="0" max="100">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control"><?= htmlspecialchars($siswa['keterangan'] ?? '') ?></textarea>
            </div>
        </div>
    <?php else: ?>
        <?php
        $result = $conn->prepare("
            SELECT ps.id AS siswa_id, ps.nama_lengkap, ps.nipd, ne.nilai, ne.keterangan
            FROM ekstrakurikuler_siswa es
            JOIN pendaftaran_siswa ps ON es.siswa_id = ps.id
            LEFT JOIN nilai_ekskul ne ON ne.siswa_id = ps.id AND ne.ekskul_id = ? AND ne.semester = ?
            WHERE es.ekstrakurikuler_id = ?
        ");
        $result->bind_param("isi", $ekskul_id, $semester, $ekskul_id);
        $result->execute();
        $data = $result->get_result();
        ?>
        <div class="card mt-5">
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message">
                        <?= $_SESSION['error'] ?>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message">
                        <?= $_SESSION['success'] ?>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <div class="card-header pb-0">
                <h4 class="card-title">Input Nilai Ekstrakurikuler: <?= htmlspecialchars($namaEkskul) ?></h4>
                <div class="card-subtitle text-muted">
                    <strong>Catatan:</strong> Silakan masukkan nilai untuk setiap siswa di bawah ini. Nilai harus antara 0 hingga 100.
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIPD</th>
                                <th>Nilai</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            while ($row = $data->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($row['nipd']) ?></td>
                                    <td>
                                        <input type="number" name="nilai[]" class="form-control" min="0" max="100" value="<?= htmlspecialchars($row['nilai']) ?>">
                                        <input type="hidden" name="siswa_id[]" value="<?= $row['siswa_id'] ?>">
                                    </td>
                                    <td><textarea name="keterangan[]" rows="1" class="form-control"><?= htmlspecialchars($row['keterangan']) ?></textarea></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</form>