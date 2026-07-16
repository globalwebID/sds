<?php
include 'inc/fungsi.php';
if (!in_array($role, ['admin', 'superadmin', 'operator'])) {
    header('Location: login.php?error=Akses ditolak');
    exit;
}

$result = mysqli_query($conn, "
    SELECT k.*, u.username 
    FROM kantin k 
    LEFT JOIN users u ON u.id_kantin = k.id
    ORDER BY k.id DESC
");

$kantin_result = mysqli_query($conn, "SELECT SUM(saldo) AS total FROM kantin");
$saldo_kantin = mysqli_fetch_assoc($kantin_result)['total'] ?? 0;
?>
<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>
<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="penarikan.php" style="text-decoration: none;">
                <div class="card bg-danger text-white shadow-sm p-3" style="transition: 0.3s; cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-wallet-fill card-icon me-3 fs-2 text-white"></i>
                        <div>
                            <h5>Permintaan Penarikan</h5>
                            <p class="mb-0 fw-bold">Klik untuk melihat permintaan</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <div style="text-decoration: none;" data-bs-toggle="modal" data-bs-target="#modalTambahKantin">
                <div class="card bg-success text-white shadow-sm p-3" style="transition: 0.3s; cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-people-fill card-icon me-3 fs-2 text-white"></i>
                        <div>
                            <h5>Tambah Kantin</h5>
                            <p class="mb-0 fw-bold">Klik untuk menambah kantin baru</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div id="card-saldo-kantin" class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 card-icon me-3"></i>
                    <div>
                        <h5>Total Saldo Kantin</h5>
                        <p class="mb-0 fw-bold"><?= number_format($saldo_kantin, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Title -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Data Kantin</h3>
    </div>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-success">
                <tr>
                    <th>#</th>
                    <th>Nama Kantin</th>
                    <th>Lokasi</th>
                    <th>Saldo Kantin</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['lokasi']) ?></td>
                            <td>Rp <?= number_format($row['saldo'], 0, ',', '.') ?></td>
                            <td style="text-align: right;">
                                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#gambarModal" data-img="../images/kantin/<?= htmlspecialchars($row['gambar']) ?>">
                                    Lihat Gambar
                                </button>
                                <button
                                    class="btn btn-warning btn-edit-kantin"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditKantin"
                                    data-id="<?= $row['id'] ?>"
                                    data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                    data-lokasi="<?= htmlspecialchars($row['lokasi']) ?>"
                                    data-username="<?= htmlspecialchars($row['username']) ?>"
                                    data-gambar="<?= htmlspecialchars($row['gambar']) ?>">
                                    Edit
                                </button>
                                <form action="hapus_kantin.php" method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger">Hapus</button></form>
                                <a href="transaksi_kantin.php?id=<?= $row['id'] ?>" class="btn btn-info">Lihat Transaksi</a>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Belum ada data kantin</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Modal Gambar -->
        <div class="modal fade" id="gambarModal" tabindex="-1" aria-labelledby="gambarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="gambarModalLabel">Gambar Kantin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="gambarPreview" src="" class="img-fluid rounded shadow" alt="Gambar Kantin">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Tambah Kantin -->
<div class="modal fade" id="modalTambahKantin" tabindex="-1" aria-labelledby="modalTambahKantinLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="tambah_kantin.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKantinLabel">Tambah Kantin dan User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <h6>Data Kantin</h6>
                    <div class="mb-3">
                        <label>Nama Kantin</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Gambar</label>
                        <input type="file" name="gambar" class="form-control" accept="image/*" required>
                    </div>

                    <hr class="my-4">

                    <h6>Data User Kantin</h6>
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Edit Kantin -->
<div class="modal fade" id="modalEditKantin" tabindex="-1" aria-labelledby="modalEditKantinLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="edit_kantin.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditKantinLabel">Edit Kantin dan User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">

                    <h6>Data Kantin</h6>
                    <div class="mb-3">
                        <label>Nama Kantin</label>
                        <input type="text" name="nama" id="edit-nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" id="edit-lokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Gambar Saat Ini</label><br>
                        <img id="edit-preview-gambar" src="" class="img-thumbnail mb-2" width="150">
                        <input type="file" name="gambar" class="form-control" accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar</small>
                    </div>

                    <hr class="my-4">

                    <h6>Data User Kantin</h6>
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" id="edit-username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password Baru (kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-edit-kantin');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Set data ke form
                document.getElementById('edit-id').value = this.dataset.id;
                document.getElementById('edit-nama').value = this.dataset.nama;
                document.getElementById('edit-lokasi').value = this.dataset.lokasi;

                // Set gambar
                const gambar = this.dataset.gambar;
                if (gambar) {
                    document.getElementById('edit-preview-gambar').src = "../images/kantin/" + gambar;
                }

                // Set username
                const username = this.dataset.username;
                if (username) {
                    document.getElementById('edit-username').value = username;
                }
            });
        });
    });
</script>


<!--script modal lihat gambar -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gambarModal = document.getElementById('gambarModal');
        const gambarPreview = document.getElementById('gambarPreview');

        gambarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const imgSrc = button.getAttribute('data-img');
            gambarPreview.src = imgSrc;
        });
    });
</script>

<?php include 'inc/footer.php'; ?>
