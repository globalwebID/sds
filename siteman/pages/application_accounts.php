<?php
if (($_SESSION['admin_role'] ?? '') !== 'superadmin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Hanya superadmin SDS yang dapat mengelola akun aplikasi.</div>';
    return;
}

// Halaman ini dapat dibuka sebelum modul Perpustakaan pertama kali dijalankan.
sds_perpus_ensure_access_schema($conn);
$libraryAuth = sds_root_path('modules/library/app/auth.php');
if (!is_file($libraryAuth)) {
    throw new RuntimeException('Modul Perpustakaan belum terpasang lengkap. File autentikasi modul tidak ditemukan.');
}
require_once $libraryAuth;
perpus_ensure_user_schema($conn);

if (empty($_SESSION['app_accounts_csrf'])) {
    $_SESSION['app_accounts_csrf'] = bin2hex(random_bytes(24));
}

$applications = [
    'absensi' => [
        'label' => 'Absensi',
        'roles' => ['superadmin' => 'Superadmin', 'admin' => 'Admin', 'operator' => 'Operator'],
    ],
    'mkantin' => [
        'label' => 'mKantin',
        'roles' => ['superadmin' => 'Superadmin', 'admin' => 'Admin', 'operator' => 'Operator'],
    ],
    'library' => [
        'label' => 'Perpustakaan',
        'roles' => ['admin' => 'Admin Perpustakaan'],
    ],
];

$message = '';
$error = '';
$openModal = false;
$edit = ['id' => 0, 'username' => '', 'email' => '', 'full_name' => '', 'role' => 'staff'];
$editAccess = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $transactionStarted = false;
    $action = (string)($_POST['action'] ?? 'save');
    $id = (int)($_POST['id'] ?? 0);

    try {
        if (!hash_equals($_SESSION['app_accounts_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Token formulir tidak valid. Muat ulang halaman lalu coba kembali.');
        }

        if ($action === 'delete') {
            if ($id <= 0) {
                throw new RuntimeException('Akun yang akan dihapus tidak valid.');
            }
            if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
                throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
            }

            $stmt = $conn->prepare('SELECT username,full_name,role FROM admins WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            if (!$target) {
                throw new RuntimeException('Akun tidak ditemukan.');
            }

            if (($target['role'] ?? '') === 'superadmin') {
                $result = $conn->query("SELECT COUNT(*) AS total FROM admins WHERE role='superadmin'");
                $superadminTotal = (int)($result->fetch_assoc()['total'] ?? 0);
                if ($superadminTotal <= 1) {
                    throw new RuntimeException('Superadmin terakhir tidak dapat dihapus.');
                }
            }

            $conn->begin_transaction();
            $transactionStarted = true;

            $stmt = $conn->prepare('DELETE FROM app_admin_access WHERE admin_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE perpus_users SET status='nonaktif' WHERE sds_admin_id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $stmt = $conn->prepare('DELETE FROM admins WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $conn->commit();
            $transactionStarted = false;

            catatLog(
                $conn,
                (int)$_SESSION['admin_id'],
                'Akun Aplikasi',
                'Menghapus akun pusat ' . (string)($target['username'] ?? '')
            );
            $message = 'Akun ' . (string)($target['full_name'] ?: $target['username']) . ' berhasil dihapus.';
            $edit = ['id' => 0, 'username' => '', 'email' => '', 'full_name' => '', 'role' => 'staff'];
            $editAccess = [];
        } else {
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $sdsRole = (string)($_POST['sds_role'] ?? 'staff');

            $edit = [
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $sdsRole,
            ];
            foreach ($applications as $app => $definition) {
                if (empty($_POST['access'][$app])) continue;
                $editAccess[$app] = (string)($_POST['app_role'][$app] ?? array_key_first($definition['roles']));
            }

            if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
                throw new RuntimeException('Username minimal 3 karakter dan hanya boleh memakai huruf, angka, titik, garis bawah, atau minus.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Email tidak valid.');
            }
            if ($fullName === '') {
                throw new RuntimeException('Nama lengkap wajib diisi.');
            }
            if (!in_array($sdsRole, ['superadmin', 'staff', 'kesiswaan'], true)) {
                throw new RuntimeException('Role SDS tidak valid.');
            }
            if ($id === 0 && strlen($password) < 8) {
                throw new RuntimeException('Password akun baru minimal 8 karakter.');
            }
            if ($id > 0 && $password !== '' && strlen($password) < 8) {
                throw new RuntimeException('Password baru minimal 8 karakter.');
            }
            if ($id > 0 && $id === (int)$_SESSION['admin_id']) {
                $sdsRole = 'superadmin';
                $edit['role'] = 'superadmin';
            }

            $conn->begin_transaction();
            $transactionStarted = true;

            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE admins SET username=?, email=?, full_name=?, role=?, password=? WHERE id=?');
                    $stmt->bind_param('sssssi', $username, $email, $fullName, $sdsRole, $hash, $id);
                } else {
                    $stmt = $conn->prepare('UPDATE admins SET username=?, email=?, full_name=?, role=? WHERE id=?');
                    $stmt->bind_param('ssssi', $username, $email, $fullName, $sdsRole, $id);
                }
                $stmt->execute();
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO admins (username,email,password,full_name,role) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssss', $username, $email, $hash, $fullName, $sdsRole);
                $stmt->execute();
                $id = (int)$conn->insert_id;
            }

            $delete = $conn->prepare('DELETE FROM app_admin_access WHERE admin_id=?');
            $delete->bind_param('i', $id);
            $delete->execute();

            $insert = $conn->prepare("INSERT INTO app_admin_access (admin_id,application,app_role,active) VALUES (?,?,?,'Y')");
            foreach ($applications as $app => $definition) {
                if (empty($_POST['access'][$app])) continue;
                $role = (string)($_POST['app_role'][$app] ?? array_key_first($definition['roles']));
                if (!array_key_exists($role, $definition['roles'])) {
                    throw new RuntimeException("Role {$definition['label']} tidak valid.");
                }
                $insert->bind_param('iss', $id, $app, $role);
                $insert->execute();
            }

            $libraryAdmin = !empty($_POST['access']['library']) && (string)($_POST['app_role']['library'] ?? 'admin') === 'admin';
            if ($libraryAdmin) {
                $stmt=$conn->prepare('SELECT username,email,password,full_name FROM admins WHERE id=? LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$central=$stmt->get_result()->fetch_assoc();$stmt->close();
                if(!$central) throw new RuntimeException('Akun pusat tidak ditemukan setelah disimpan.');
                $emailValue=trim((string)$central['email']);$name=(string)($central['full_name']?:$central['username']);$usernameValue=(string)$central['username'];$hashValue=(string)$central['password'];
                $stmt=$conn->prepare('SELECT id FROM perpus_users WHERE sds_admin_id=? OR username=? ORDER BY sds_admin_id IS NULL,id LIMIT 1');$stmt->bind_param('is',$id,$usernameValue);$stmt->execute();$local=$stmt->get_result()->fetch_assoc();$stmt->close();
                if($local){$localId=(int)$local['id'];$stmt=$conn->prepare("UPDATE perpus_users SET sds_admin_id=?,username=?,email=?,password=?,nama_lengkap=?,role='admin',status='aktif' WHERE id=?");$stmt->bind_param('issssi',$id,$usernameValue,$emailValue,$hashValue,$name,$localId);}
                else{$stmt=$conn->prepare("INSERT INTO perpus_users(sds_admin_id,username,email,password,nama_lengkap,role,status) VALUES(?,?,?,?,?,'admin','aktif')");$stmt->bind_param('issss',$id,$usernameValue,$emailValue,$hashValue,$name);}
                $stmt->execute();$stmt->close();
            } else {
                $stmt=$conn->prepare("UPDATE perpus_users SET status='nonaktif' WHERE sds_admin_id=?");$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();
            }

            $conn->commit();
            $transactionStarted = false;
            catatLog($conn, (int)$_SESSION['admin_id'], 'Akun Aplikasi', "Menyimpan akun pusat {$username}");
            $message = (int)($_POST['id'] ?? 0) > 0
                ? 'Akun dan akses aplikasi berhasil diperbarui.'
                : 'Akun dan akses aplikasi berhasil ditambahkan.';
            $edit = ['id' => 0, 'username' => '', 'email' => '', 'full_name' => '', 'role' => 'staff'];
            $editAccess = [];
        }
    } catch (Throwable $e) {
        if ($transactionStarted) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
        }
        $rawError = $e->getMessage();
        $error = str_contains(strtolower($rawError), 'duplicate')
            ? 'Username atau email sudah digunakan.'
            : $rawError;
        $openModal = $id > 0 || $action !== 'delete';
    }
}

$editId = (int)($_GET['id'] ?? 0);
if ($editId > 0 && !$openModal) {
    $stmt = $conn->prepare('SELECT id,username,email,full_name,role FROM admins WHERE id=?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: $edit;

    $stmt = $conn->prepare("SELECT application,app_role FROM app_admin_access WHERE admin_id=? AND active='Y'");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $editAccess[$row['application']] = $row['app_role'];
    }
    $openModal = (int)$edit['id'] > 0;
}

$accountRows = [];
$result = $conn->query("SELECT a.id,a.username,a.email,a.full_name,a.role,
    GROUP_CONCAT(CONCAT(x.application, ':', x.app_role) ORDER BY x.application SEPARATOR ',') AS app_access
    FROM admins a
    LEFT JOIN app_admin_access x ON x.admin_id=a.id AND x.active='Y'
    GROUP BY a.id
    ORDER BY a.full_name,a.username");
while ($row = $result->fetch_assoc()) {
    $accountRows[] = $row;
}

$totalAccounts = count($accountRows);
$totalSuperadmins = 0;
$totalWithAppAccess = 0;
$totalAccessAssignments = 0;
foreach ($accountRows as $row) {
    if (($row['role'] ?? '') === 'superadmin') $totalSuperadmins++;
    $accessString = trim((string)($row['app_access'] ?? ''));
    if ($accessString !== '') {
        $totalWithAppAccess++;
        $totalAccessAssignments += count(array_filter(explode(',', $accessString)));
    }
}

$roleLabels = ['superadmin' => 'Superadmin', 'staff' => 'Staff', 'kesiswaan' => 'Kesiswaan'];
require __DIR__ . '/partials/shared/master_page_style.php';
?>
<style>
#modalApplicationAccount .modal-content{max-height:calc(100vh - 2rem);overflow:hidden}
#modalApplicationAccount .modal-header,#modalApplicationAccount .modal-footer{flex:0 0 auto}
#modalApplicationAccount .modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
@media(max-width:767.98px){
    #modalApplicationAccount .modal-dialog{height:calc(100% - 1rem);margin:.5rem}
    #modalApplicationAccount .modal-content{max-height:100%}
    #modalApplicationAccount .modal-footer{gap:.5rem}
    #modalApplicationAccount .modal-footer .btn{margin:0}
}
</style>
<div class="sds-master-page" id="applicationAccountsPage">
    <div class="sds-hero">
        <div>
            <h2>Akun &amp; Akses Aplikasi</h2>
            <p>Satu identitas administrator untuk SDS, Absensi, mKantin, dan Perpustakaan.</p>
        </div>
        <div class="sds-hero-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalApplicationAccount" onclick="resetApplicationAccountForm()">
                Tambah Akun
            </button>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card">
            <small>Total Akun SDS</small>
            <strong><?= number_format($totalAccounts, 0, ',', '.') ?></strong>
            <span>Seluruh akun administrator pusat</span>
        </div>
        <div class="sds-stat-card">
            <small>Superadmin</small>
            <strong><?= number_format($totalSuperadmins, 0, ',', '.') ?></strong>
            <span>Akun dengan akses penuh SDS</span>
        </div>
        <div class="sds-stat-card">
            <small>Terhubung Aplikasi</small>
            <strong><?= number_format($totalWithAppAccess, 0, ',', '.') ?></strong>
            <span>Akun dengan minimal satu akses aplikasi</span>
        </div>
        <div class="sds-stat-card">
            <small>Total Hak Akses</small>
            <strong><?= number_format($totalAccessAssignments, 0, ',', '.') ?></strong>
            <span>Penugasan akses lintas aplikasi</span>
        </div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Akun Administrator</h5>
            <span class="sds-mini">Menampilkan <strong id="accountVisibleCount"><?= number_format($totalAccounts, 0, ',', '.') ?></strong> akun.</span>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-toolbar-left">
                    <select id="accountRoleFilter" class="form-select form-select-sm" aria-label="Filter role SDS">
                        <option value="">Semua Role SDS</option>
                        <option value="superadmin">Superadmin</option>
                        <option value="staff">Staff</option>
                        <option value="kesiswaan">Kesiswaan</option>
                    </select>
                </div>
                <div class="sds-toolbar-right">
                    <input type="search" id="accountSearch" class="form-control form-control-sm sds-search" placeholder="Cari nama, username, email..." aria-label="Cari akun aplikasi">
                </div>
            </div>

            <div class="sds-table-wrap">
                <table class="sds-table wide" id="accountTable">
                    <thead>
                        <tr>
                            <th style="width:60px">No.</th>
                            <th>Administrator</th>
                            <th>Username</th>
                            <th>Role SDS</th>
                            <th>Akses Aplikasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$accountRows): ?>
                        <tr><td colspan="6" class="sds-empty">Belum ada akun administrator.</td></tr>
                    <?php else: ?>
                        <?php foreach ($accountRows as $index => $account): ?>
                            <?php
                            $searchText = strtolower(implode(' ', [
                                (string)($account['full_name'] ?? ''),
                                (string)($account['username'] ?? ''),
                                (string)($account['email'] ?? ''),
                                (string)($account['role'] ?? ''),
                                (string)($account['app_access'] ?? ''),
                            ]));
                            $accessItems = array_filter(explode(',', (string)($account['app_access'] ?? '')));
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchText) ?>" data-role="<?= htmlspecialchars((string)$account['role']) ?>">
                                <td><?= number_format($index + 1, 0, ',', '.') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string)$account['full_name']) ?></strong><br>
                                    <span class="sds-mini"><?= htmlspecialchars((string)$account['email']) ?></span>
                                </td>
                                <td><span class="sds-code"><?= htmlspecialchars((string)$account['username']) ?></span></td>
                                <td><span class="sds-badge <?= ($account['role'] ?? '') === 'superadmin' ? 'danger' : (($account['role'] ?? '') === 'kesiswaan' ? 'warn' : 'info') ?>"><?= htmlspecialchars($roleLabels[$account['role']] ?? (string)$account['role']) ?></span></td>
                                <td>
                                    <?php if (!$accessItems): ?>
                                        <span class="sds-badge muted">SDS saja</span>
                                    <?php else: ?>
                                        <div class="sds-app-list">
                                            <?php foreach ($accessItems as $item): ?>
                                                <?php
                                                [$appKey, $appRole] = array_pad(explode(':', $item, 2), 2, '');
                                                $appLabel = $applications[$appKey]['label'] ?? $appKey;
                                                $roleLabel = $applications[$appKey]['roles'][$appRole] ?? $appRole;
                                                ?>
                                                <span class="sds-badge info"><?= htmlspecialchars($appLabel . ' · ' . $roleLabel) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="sds-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-id="<?= (int)$account['id'] ?>"
                                            data-full-name="<?= htmlspecialchars((string)$account['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars((string)$account['email'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-username="<?= htmlspecialchars((string)$account['username'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-role="<?= htmlspecialchars((string)$account['role'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-access="<?= htmlspecialchars((string)$account['app_access'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-can-delete="<?= (int)$account['id'] !== (int)($_SESSION['admin_id'] ?? 0) ? '1' : '0' ?>"
                                            onclick="openApplicationAccountEdit(this)"
                                        >Edit</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sds-master-modal" id="modalApplicationAccount" tabindex="-1" aria-labelledby="modalApplicationAccountLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" id="applicationAccountForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalApplicationAccountLabel"><?= (int)$edit['id'] > 0 ? 'Edit Akun & Akses' : 'Tambah Akun & Akses' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['app_accounts_csrf']) ?>">
                    <input type="hidden" name="id" id="applicationAccountId" value="<?= (int)$edit['id'] ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="accountFullName" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="accountFullName" name="full_name" maxlength="100" value="<?= htmlspecialchars((string)$edit['full_name']) ?>" required autofocus>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accountEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="accountEmail" name="email" maxlength="100" value="<?= htmlspecialchars((string)$edit['email']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="accountUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="accountUsername" name="username" minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" value="<?= htmlspecialchars((string)$edit['username']) ?>" required>
                            <div class="form-text">Huruf, angka, titik, garis bawah, atau minus.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accountPassword" class="form-label" id="accountPasswordLabel">Password <?= (int)$edit['id'] > 0 ? 'Baru (Opsional)' : '' ?></label>
                            <input type="password" class="form-control" id="accountPassword" name="password" autocomplete="new-password" <?= (int)$edit['id'] > 0 ? '' : 'required minlength="8"' ?>>
                            <div class="form-text"><?= (int)$edit['id'] > 0 ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.' ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="accountSdsRole" class="form-label">Role SDS</label>
                        <select class="form-select" id="accountSdsRole" name="sds_role" required>
                            <?php foreach ($roleLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string)$edit['role'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ((int)$edit['id'] === (int)($_SESSION['admin_id'] ?? 0) && (int)$edit['id'] > 0): ?>
                            <div class="form-text">Role akun yang sedang digunakan tetap Superadmin untuk mencegah kehilangan akses.</div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Akses Aplikasi</h6>
                        <span class="sds-mini">Centang aplikasi yang boleh diakses.</span>
                    </div>

                    <?php foreach ($applications as $app => $definition): ?>
                        <?php $selectedRole = $editAccess[$app] ?? array_key_first($definition['roles']); ?>
                        <div class="border p-3 mb-2">
                            <div class="row align-items-center g-2">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input app-access-toggle" type="checkbox" role="switch" id="access_<?= htmlspecialchars($app) ?>" name="access[<?= htmlspecialchars($app) ?>]" value="1" data-role-select="role_<?= htmlspecialchars($app) ?>" <?= isset($editAccess[$app]) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="access_<?= htmlspecialchars($app) ?>"><?= htmlspecialchars($definition['label']) ?></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" id="role_<?= htmlspecialchars($app) ?>" name="app_role[<?= htmlspecialchars($app) ?>]" <?= isset($editAccess[$app]) ? '' : 'disabled' ?>>
                                        <?php foreach ($definition['roles'] as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $selectedRole === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button
                        type="submit"
                        name="action"
                        value="delete"
                        id="deleteApplicationAccountButton"
                        class="btn btn-outline-danger me-auto<?= (int)$edit['id'] > 0 && (int)$edit['id'] !== (int)($_SESSION['admin_id'] ?? 0) ? '' : ' d-none' ?>"
                        formnovalidate
                        onclick="return confirm('Hapus akun ini secara permanen beserta seluruh hak akses aplikasinya?')"
                    >Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="action" value="save" class="btn btn-primary">Simpan Akun &amp; Akses</button>
                </div>
        </form>
    </div>
</div>

<script>
function showApplicationAccountModal() {
    const run = function () {
        const modalElement = document.getElementById('modalApplicationAccount');
        if (modalElement && typeof window.sdsShowModal === 'function') {
            window.sdsShowModal(modalElement);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }
}

function setApplicationAccountPasswordMode(isEdit) {
    const password = document.getElementById('accountPassword');
    const passwordLabel = document.getElementById('accountPasswordLabel');
    if (!password) return;

    password.value = '';
    password.required = !isEdit;
    password.minLength = 8;
    if (passwordLabel) passwordLabel.textContent = isEdit ? 'Password Baru (Opsional)' : 'Password';
    const help = password.parentElement.querySelector('.form-text');
    if (help) help.textContent = isEdit ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.';
}

function resetApplicationAccessFields() {
    document.querySelectorAll('.app-access-toggle').forEach(function (toggle) {
        toggle.checked = false;
        const select = document.getElementById(toggle.dataset.roleSelect);
        if (select) {
            select.selectedIndex = 0;
            select.disabled = true;
        }
    });
}

function openApplicationAccountEdit(button) {
    const id = document.getElementById('applicationAccountId');
    const fullName = document.getElementById('accountFullName');
    const email = document.getElementById('accountEmail');
    const username = document.getElementById('accountUsername');
    const role = document.getElementById('accountSdsRole');
    const title = document.getElementById('modalApplicationAccountLabel');
    const deleteButton = document.getElementById('deleteApplicationAccountButton');

    if (id) id.value = button.dataset.id || '0';
    if (fullName) fullName.value = button.dataset.fullName || '';
    if (email) email.value = button.dataset.email || '';
    if (username) username.value = button.dataset.username || '';
    if (role) role.value = button.dataset.role || 'staff';
    if (title) title.textContent = 'Edit Akun & Akses';
    if (deleteButton) deleteButton.classList.toggle('d-none', button.dataset.canDelete !== '1');

    resetApplicationAccessFields();
    const accessString = button.dataset.access || '';
    accessString.split(',').filter(Boolean).forEach(function (item) {
        const separator = item.indexOf(':');
        const app = separator >= 0 ? item.slice(0, separator) : item;
        const appRole = separator >= 0 ? item.slice(separator + 1) : '';
        const toggle = document.getElementById('access_' + app);
        const select = document.getElementById('role_' + app);
        if (toggle) toggle.checked = true;
        if (select) {
            select.disabled = false;
            if (appRole) select.value = appRole;
        }
    });

    setApplicationAccountPasswordMode(true);
    showApplicationAccountModal();
}

function resetApplicationAccountForm() {
    const form = document.getElementById('applicationAccountForm');
    if (form) form.reset();

    ['accountFullName', 'accountEmail', 'accountUsername'].forEach(function (fieldId) {
        const field = document.getElementById(fieldId);
        if (field) field.value = '';
    });

    const id = document.getElementById('applicationAccountId');
    const title = document.getElementById('modalApplicationAccountLabel');
    const role = document.getElementById('accountSdsRole');
    const deleteButton = document.getElementById('deleteApplicationAccountButton');

    if (id) id.value = '0';
    if (title) title.textContent = 'Tambah Akun & Akses';
    if (role) role.value = 'staff';
    if (deleteButton) deleteButton.classList.add('d-none');
    setApplicationAccountPasswordMode(false);
    resetApplicationAccessFields();
}

(function () {
    const search = document.getElementById('accountSearch');
    const role = document.getElementById('accountRoleFilter');
    const visibleCount = document.getElementById('accountVisibleCount');

    function filterAccounts() {
        const keyword = (search ? search.value : '').toLowerCase().trim();
        const roleValue = role ? role.value : '';
        let count = 0;

        document.querySelectorAll('#accountTable tbody tr[data-search]').forEach(function (row) {
            const show = row.dataset.search.includes(keyword) && (!roleValue || row.dataset.role === roleValue);
            row.style.display = show ? '' : 'none';
            if (show) count++;
        });

        if (visibleCount) visibleCount.textContent = new Intl.NumberFormat('id-ID').format(count);
    }

    if (search) search.addEventListener('input', filterAccounts);
    if (role) role.addEventListener('change', filterAccounts);

    document.querySelectorAll('.app-access-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const select = document.getElementById(this.dataset.roleSelect);
            if (select) select.disabled = !this.checked;
        });
    });

    <?php if ($openModal): ?>
    showApplicationAccountModal();
    <?php endif; ?>
})();
</script>
