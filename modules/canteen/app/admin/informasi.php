<?php
include 'inc/fungsi.php';

// UTF8MB4 biar emoji tidak rusak
if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

// Cek role
if (!in_array($role, ['admin', 'superadmin'])) {
    header('Location: dashboard.php');
    exit;
}

$pesan = '';
$error = '';

// Siapkan data edit (agar tidak undefined di modal)
$editData = ['id' => '', 'judul' => '', 'isi' => ''];
$isEdit = false;

if (isset($_GET['edit'])) {
    $isEdit = true;
    $idEdit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT id, judul, isi FROM informasi WHERE id = ?");
    $stmt->bind_param("i", $idEdit);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $editData = $res->fetch_assoc();
    } else {
        $isEdit = false;
        $editData = ['id' => '', 'judul' => '', 'isi' => ''];
        $error = "Data informasi untuk diedit tidak ditemukan.";
    }
    $stmt->close();
}

// ====== TAMBAH ======
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $judul = trim($_POST['judul'] ?? '');
    $isi   = trim($_POST['isi'] ?? '');
    $tanggal = date('Y-m-d H:i:s');

    if ($judul !== '' && $isi !== '') {
        $stmt = $conn->prepare("INSERT INTO informasi (judul, isi, tanggal) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $judul, $isi, $tanggal);
        $stmt->execute();

        $info_id = $stmt->insert_id;
        $stmt->close();

        // Insert ke informasi_user untuk semua kantin + semua siswa
        // Pakai prepared statement biar aman & cepat
        $ins = $conn->prepare("INSERT INTO informasi_user (user_id, informasi_id, dibaca) VALUES (?, ?, 0)");

        // Semua kantin
        $resKantin = $conn->query("SELECT id FROM kantin");
        if ($resKantin) {
            while ($k = $resKantin->fetch_assoc()) {
                $kid = (int)$k['id'];
                $ins->bind_param("ii", $kid, $info_id);
                $ins->execute();
            }
        }

        // Semua siswa
        $resSiswa = $conn->query("SELECT id FROM pendaftaran_siswa");
        if ($resSiswa) {
            while ($s = $resSiswa->fetch_assoc()) {
                $sid = (int)$s['id'];
                $ins->bind_param("ii", $sid, $info_id);
                $ins->execute();
            }
        }

        $ins->close();

        // Redirect biar tidak resubmit saat refresh
        header('Location: informasi.php?pesan=tambah');
        exit;
    } else {
        $error = "Judul dan isi tidak boleh kosong.";
    }
}

// ====== EDIT ======
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id    = intval($_POST['id'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $isi   = trim($_POST['isi'] ?? '');

    if ($id > 0 && $judul !== '' && $isi !== '') {
        $stmt = $conn->prepare("UPDATE informasi SET judul = ?, isi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $judul, $isi, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: informasi.php?pesan=update');
        exit;
    } else {
        $error = "Judul dan isi tidak boleh kosong.";
    }
}

// ====== HAPUS ======
if (isset($_GET['hapus'])) {
    $idHapus = intval($_GET['hapus']);

    if ($idHapus > 0) {
        // Hapus relasi dulu (opsional tapi aman kalau tidak pakai ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM informasi_user WHERE informasi_id = ?");
        $stmt->bind_param("i", $idHapus);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM informasi WHERE id = ?");
        $stmt->bind_param("i", $idHapus);
        $stmt->execute();
        $stmt->close();

        header('Location: informasi.php?pesan=hapus');
        exit;
    }
}

// Pesan dari redirect
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] === 'update') $pesan = "Informasi berhasil diperbarui.";
    if ($_GET['pesan'] === 'tambah') $pesan = "Informasi berhasil ditambahkan.";
    if ($_GET['pesan'] === 'hapus')  $pesan = "Informasi berhasil dihapus.";
}

// Ambil data informasi
$data = $conn->query("SELECT * FROM informasi ORDER BY tanggal DESC");

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Kirim Informasi ke Pengguna</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#formModal">
            Tambah Informasi
        </button>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <strong>Daftar Informasi Dikirim</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Judul</th>
                                <th>Informasi Dibaca</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data && $data->num_rows > 0): $no = 1; ?>
                                <?php while ($info = $data->fetch_assoc()): ?>
                                    <?php
                                    $id = (int)$info['id'];

                                    // Hitung target pembaca
                                    $qTarget = $conn->query("SELECT COUNT(*) as total FROM informasi_user WHERE informasi_id = $id");
                                    $target = $qTarget ? (int)$qTarget->fetch_assoc()['total'] : 0;

                                    // Hitung jumlah yang sudah membaca
                                    $qBaca = $conn->query("SELECT COUNT(*) as baca FROM informasi_user WHERE informasi_id = $id AND dibaca = 1");
                                    $dibaca = $qBaca ? (int)$qBaca->fetch_assoc()['baca'] : 0;

                                    $persen = ($target > 0) ? round(($dibaca / $target) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($info['judul']) ?></strong><br>
                                            <small class="text-muted"><?= date('d-m-Y H:i', strtotime($info['tanggal'])) ?></small><br>

                                            <!-- Tampilkan plain text rapi (enter jadi baris baru) -->
                                            <div style="font-size: 90%; white-space: pre-line;">
                                                <?= htmlspecialchars($info['isi']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?= $dibaca ?>/<?= $target ?>
                                            <small>(<?= $persen ?>%)</small>
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="?edit=<?= $id ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="?hapus=<?= $id ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('Yakin ingin menghapus informasi ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada informasi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Tambah/Edit (Plain Text) -->
<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="formModalLabel">
                        <?= $isEdit ? 'Edit Informasi' : 'Tambah Informasi' ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="aksi" value="<?= $isEdit ? 'edit' : 'tambah' ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id']) ?>">

                    <div class="mb-3">
                        <label for="judul" class="form-label">Judul</label>
                        <input type="text" name="judul" id="judul" class="form-control"
                               value="<?= htmlspecialchars($editData['judul']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="isi" class="form-label">Isi Informasi (Teks Biasa)</label>
                        <textarea name="isi" id="isi" class="form-control" rows="10" required><?= htmlspecialchars($editData['isi']) ?></textarea>
                        <small class="text-muted">Teks biasa. Enter akan menjadi baris baru.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <?= $isEdit ? 'Update' : 'Simpan' ?>
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="hapusParamUrl()">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isEdit): ?>
<script>
    window.addEventListener('load', function () {
        var myModal = new bootstrap.Modal(document.getElementById('formModal'));
        myModal.show();
    });
</script>
<?php endif; ?>

<script>
function hapusParamUrl() {
    const url = new URL(window.location.href);
    url.searchParams.delete('edit');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}
</script>

<?php include 'inc/footer.php'; ?>
