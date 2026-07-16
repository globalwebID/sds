<?php
include 'inc/fungsi.php';
if (!in_array($role, ['superadmin'])) {
    header('Location: login.php?error=Akses ditolak');
    exit;
}

$users = mysqli_query($conn, "
    SELECT u.*, k.nama AS nama_kantin 
    FROM users u 
    LEFT JOIN kantin k ON u.id_kantin = k.id 
    ORDER BY FIELD(u.role, 'superadmin', 'admin', 'operator', 'kantin', 'siswa'), u.id DESC
");

$kantinResult = mysqli_query($conn, "SELECT id, nama FROM kantin");
$kantinList = [];
while ($k = mysqli_fetch_assoc($kantinResult)) {
    $kantinList[] = $k;
}


// Ambil daftar enum role dari kolom `role` tabel `users`
$enumRoles = [];
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$row = mysqli_fetch_assoc($result);

if (preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
    $roles = explode(",", $matches[1]);
    foreach ($roles as $r) {
        $enumRoles[] = trim($r, "'");
    }
}

?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Manajemen User</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahUser">+ Tambah User</button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-success">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Nama Kantin</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= $user['nama_kantin'] ?: '-' ?></td>
                        <td style="text-align: right;">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $user['id'] ?>">Edit</button>
                            <form action="hapus_user.php" method="post" class="d-inline" onsubmit="return confirm('Yakin hapus user ini?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form>
                        </td>
                    </tr>

                    <!-- Modal Edit User -->
                    <div class="modal fade" id="modalEditUser<?= $user['id'] ?>" tabindex="-1" aria-labelledby="modalEditUserLabel<?= $user['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <form action="update_user.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalEditUserLabel<?= $user['id'] ?>">Edit User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Username</label>
                                            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label>Password <small>(Kosongkan jika tidak diubah)</small></label>
                                            <input type="password" name="password" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label>Role</label>
                                            <select name="role" class="form-control role-select" data-target="#idKantinGroup<?= $user['id'] ?>" required>
                                                <?php foreach ($enumRoles as $role): ?>
                                                    <option value="<?= $role ?>" <?= $user['role'] === $role ? 'selected' : '' ?>>
                                                        <?= ucfirst($role) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="idKantinGroup<?= $user['id'] ?>">
                                            <label>Kantin</label>
                                            <select name="id_kantin" class="form-control">
                                                <option value="">-- Pilih Kantin --</option>
                                                <?php foreach ($kantinList as $k): ?>
                                                    <option value="<?= $k['id'] ?>" <?= $user['id_kantin'] == $k['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($k['nama']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Kosongkan jika bukan role "kantin"</small>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="tambah_user.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahUserLabel">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($enumRoles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>"><?= ucfirst($role) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="kantinGroupTambah">
                        <label for="id_kantin" class="form-label">Kantin (jika role kantin)</label>
                        <select name="id_kantin" class="form-control">
                            <option value="">-- Pilih Kantin --</option>
                            <?php foreach ($kantinList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Kosongkan jika bukan role "kantin"</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tambahRoleSelect = document.querySelector('#modalTambahUser select[name="role"]');
        const kantinGroupTambah = document.getElementById('kantinGroupTambah');

        function toggleTambahKantin() {
            if (tambahRoleSelect.value === 'kantin') {
                kantinGroupTambah.style.display = 'block';
            } else {
                kantinGroupTambah.style.display = 'none';
            }
        }

        tambahRoleSelect.addEventListener('change', toggleTambahKantin);
        toggleTambahKantin(); // initial
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.role-select').forEach(function(select) {
            const targetSelector = select.getAttribute('data-target');
            const target = document.querySelector(targetSelector);

            function toggleIdKantin() {
                if (select.value === 'kantin') {
                    target.style.display = 'block';
                } else {
                    target.style.display = 'none';
                }
            }

            select.addEventListener('change', toggleIdKantin);
            toggleIdKantin(); // initial state
        });
    });
</script>

<?php include 'inc/footer.php'; ?>
