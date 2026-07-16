<?php
// Ambil ID Ekskul
$ekskul_id = $_GET['ekskul_id'] ?? null;
if (!$ekskul_id) {
    exit("ID Ekskul tidak ditemukan.");
}

// Ambil nama ekskul
$stmt = $conn->prepare("SELECT nama_ekskul FROM ekstrakurikuler WHERE id = ?");
$stmt->bind_param("i", $ekskul_id);
$stmt->execute();
$nama_ekskul = $stmt->get_result()->fetch_assoc()['nama_ekskul'] ?? '';

// Ambil daftar siswa
$siswaStmt = $conn->prepare("
    SELECT ps.id, ps.nama_lengkap, ps.nohp_ortu 
    FROM pendaftaran_siswa ps
    JOIN ekstrakurikuler_siswa es ON es.siswa_id = ps.id
    WHERE es.ekstrakurikuler_id = ?
    ORDER BY ps.nama_lengkap ASC
");
$siswaStmt->bind_param("i", $ekskul_id);
$siswaStmt->execute();
$siswaResult = $siswaStmt->get_result();

$siswaList = [];
while ($row = $siswaResult->fetch_assoc()) {
    $siswaList[$row['id']] = $row;
}

// Simpan absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = date('Y-m-d');
    if (empty($_POST['absen'])) {
        $_SESSION['error'] = "Tidak ada data absensi yang dikirim.";
        header("Location: ekskul_absen_siswa?ekskul_id=$ekskul_id");
        exit;
    }
    foreach ($_POST['absen'] as $siswa_id => $status) {
        $keterangan = $_POST['keterangan'][$siswa_id] ?? null;

        $stmt = $conn->prepare("
            INSERT INTO ekskul_absensi (siswa_id, ekskul_id, status, keterangan, tanggal)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)
        ");
        $stmt->bind_param("iisss", $siswa_id, $ekskul_id, $status, $keterangan, $tanggal);
        $stmt->execute();
    }

    $_SESSION['success'] = "Absensi berhasil disimpan.";
    header("Location: ekskul_absen_siswa?ekskul_id=$ekskul_id");
    exit;
}

// Ambil data absensi hari ini
$tanggal_hari_ini = date('Y-m-d');
$absensiResult = $conn->query("
    SELECT siswa_id, status, keterangan
    FROM ekskul_absensi 
    WHERE ekskul_id = $ekskul_id 
    AND tanggal = '$tanggal_hari_ini'
    AND status IN ('H', 'I', 'S', 'A')
");

$absensi = [];
while ($r = $absensiResult->fetch_assoc()) {
    $absensi[$r['siswa_id']] = [
        'status' => $r['status'],
        'keterangan' => $r['keterangan']
    ];
}

// Ambil tanggal absensi hari ini, jika ada
$tanggal_materi_default = '';
$stmt = $conn->prepare("SELECT DISTINCT tanggal FROM ekskul_absensi WHERE ekskul_id = ? AND tanggal = ?");
$stmt->bind_param("is", $ekskul_id, $tanggal_hari_ini);
$stmt->execute();
$stmt->bind_result($tanggal_absensi);
if ($stmt->fetch()) {
    $tanggal_materi_default = $tanggal_absensi;
}
$stmt->close();



$formatter = new IntlDateFormatter(
    'id_ID',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'Asia/Jakarta',
    IntlDateFormatter::GREGORIAN,
    "EEEE, d MMMM yyyy"
);

$today = $formatter->format(new DateTime());
// Ambil daftar materi untuk ekskul ini
$materiStmt = $conn->prepare("SELECT tanggal, judul, isi FROM ekskul_materi WHERE ekskul_id = ? ORDER BY tanggal DESC");
$materiStmt->bind_param("i", $ekskul_id);
$materiStmt->execute();
$materiResult = $materiStmt->get_result();

$materiList = [];
while ($m = $materiResult->fetch_assoc()) {
    $materiList[] = $m;
}

?>
<form method="post" id="absenForm">
    <div class="topbar">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-auto d-sm-block">
                    <a href="ekskul_lihat_siswa?id=<?= $ekskul_id ?>" class="btn btn-secondary">Kembali</a>
                </div>
                <div class="col-auto ms-auto text-end">
                    <a href="#" onclick="openMateriModal()" class="btn btn-warning">Tambah Materi</a>
                    <a href="ekskul_rekap_absen?ekskul_id=<?= $ekskul_id ?>" class="btn btn-primary">Rekap Absen</a>
                    <button type="submit" class="btn btn-success">Simpan Absensi</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
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
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #eee;">
                    <h4 class="mb-0">
                        Absensi Siswa Ekskul <?= htmlspecialchars($nama_ekskul) ?>
                    </h4>
                    <h5 class="mb-0">
                        <strong>Hari, Tanggal</strong>
                        <strong>: <?= $today ?></strong>
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <!-- <tr>
                                <th colspan="6" style="background-color: #eee;">
                                    <strong>Hari/ Tanggal</strong>
                                    <strong>: <?= $today ?></strong>
                                </th>
                            </tr> -->
                            <tr>
                                <th colspan="6">
                                    <h5></h5>Materi yang Pernah Disampaikan:</h5>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($materiList)): ?>
                                <?php foreach ($materiList as $materi): ?>
                                    <tr>
                                        <td width="20%">Judul Materi</td>
                                        <td>: <?= htmlspecialchars($materi['judul']) ?></td>
                                    </tr>
                                    <tr>
                                        <td width="20%">Rangkuman Materi</td>
                                        <td>: <?= nl2br(htmlspecialchars($materi['isi'])) ?></td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td rowspan="6">Belum ada materi yang ditambahkan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="text-center" width="50">NO.</th>
                                <th>NAMA SISWA</th>
                                <th class="text-center" width="100">HADIR</th>
                                <th class="text-center" width="100">IZIN</th>
                                <th class="text-center" width="100">SAKIT</th>
                                <th class="text-center" width="100">ALPHA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            $no = 1;
                            foreach ($siswaList as $sid => $row):
                                $sid = $row['id'];
                                $status = $absensi[$sid]['status'] ?? '';
                                $ket = $absensi[$sid]['keterangan'] ?? '';
                            ?>
                                <tr>
                                    <td class="text-center" width="50"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                    <td class="text-center" width="100"><input type="radio" name="absen[<?= $sid ?>]" value="H" <?= ($status == 'H') ? 'checked' : '' ?>></td>
                                    <td class="text-center" width="100">
                                        <input type="radio" name="absen[<?= $sid ?>]" value="I" <?= ($status == 'I') ? 'checked' : '' ?> onclick="openModal(<?= $sid ?>)">
                                        <input type="hidden" name="keterangan[<?= $sid ?>]" id="keterangan_<?= $sid ?>" value="<?= htmlspecialchars($ket) ?>">
                                    </td>
                                    <td class="text-center" width="100"><input type="radio" name="absen[<?= $sid ?>]" value="S" <?= ($status == 'S') ? 'checked' : '' ?>></td>
                                    <td class="text-center" width="100"><input type="radio" name="absen[<?= $sid ?>]" value="A" <?= ($status == 'A') ? 'checked' : '' ?>></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Keterangan Izin -->
<div class="modal fade" id="izinModal" tabindex="-1" aria-labelledby="izinModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form onsubmit="submitIzin(event)">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Keterangan Izin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentSiswaId">
                    <div class="mb-3">
                        <label for="keteranganInput" class="form-label">Masukkan keterangan izin:</label>
                        <textarea class="form-control" id="keteranganInput" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    let currentSiswaId = null;

    function openModal(siswaId) {
        currentSiswaId = siswaId;
        document.getElementById('currentSiswaId').value = siswaId;
        document.getElementById('keteranganInput').value = document.getElementById('keterangan_' + siswaId).value;
        new bootstrap.Modal(document.getElementById('izinModal')).show();
    }

    function submitIzin(event) {
        event.preventDefault();
        const ket = document.getElementById('keteranganInput').value;
        document.getElementById('keterangan_' + currentSiswaId).value = ket;
        bootstrap.Modal.getInstance(document.getElementById('izinModal')).hide();
    }
</script>

<!-- Modal Tambah Materi -->
<div class="modal fade" id="modalTambahMateri" tabindex="-1" aria-labelledby="modalTambahMateriLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="ekskul_simpan_materi">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Materi Ekskul</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ekskul_id" value="<?= $ekskul_id ?>">
                    <div class="mb-3">
                        <label for="tanggalMateri" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" id="tanggalMateri" value="<?= $tanggal_materi_default ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="judulMateri" class="form-label">Judul Materi</label>
                        <input type="text" class="form-control" name="judul" id="judulMateri" required>
                    </div>
                    <div class="mb-3">
                        <label for="isiMateri" class="form-label">Isi Materi</label>
                        <textarea class="form-control" name="isi" id="isiMateri" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Materi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    function openMateriModal() {
        new bootstrap.Modal(document.getElementById('modalTambahMateri')).show();
    }
</script>