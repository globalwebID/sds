<?php
require_once __DIR__ . '/../../config/perpus.php';
sds_perpus_ensure_schema($conn);

if (($_SESSION['admin_role'] ?? '') === 'kesiswaan') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Akses ditolak.</div>';
    return;
}

if (empty($_SESSION['teacher_csrf'])) {
    $_SESSION['teacher_csrf'] = bin2hex(random_bytes(24));
}

$message = '';
$error = '';
$edit = [
    'pegawai_id' => 0,
    'nip' => '',
    'rfid' => '',
    'nama_lengkap' => '',
    'email' => '',
    'jenis_kelamin' => 'Laki-laki',
    'jabatan' => '',
    'telp' => '',
    'active' => 'Y',
];
$openModal = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? 'save');
    $id = (int)($_POST['id'] ?? 0);

    try {
        if (!hash_equals($_SESSION['teacher_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Token tidak valid. Muat ulang halaman lalu coba kembali.');
        }

        if ($action === 'delete') {
            if ($id <= 0) {
                throw new RuntimeException('Data pengajar atau pegawai tidak valid.');
            }

            $stmt = $conn->prepare('SELECT nama_lengkap,rfid FROM pegawai WHERE pegawai_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$target) {
                throw new RuntimeException('Data pengajar atau pegawai tidak ditemukan.');
            }

            $stmt = $conn->prepare("SELECT pa.id,
                (SELECT COUNT(*) FROM perpus_peminjaman p WHERE p.anggota_id=pa.id) transaksi,
                (SELECT COUNT(*) FROM perpus_kunjungan k WHERE k.anggota_id=pa.id) kunjungan
                FROM perpus_anggota pa WHERE pa.pemilik_tipe='pegawai' AND pa.pemilik_id=? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $libraryMember = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($libraryMember && ((int)$libraryMember['transaksi'] > 0 || (int)$libraryMember['kunjungan'] > 0)) {
                throw new RuntimeException('Pegawai sudah memiliki riwayat Perpustakaan dan tidak dapat dihapus. Ubah statusnya menjadi nonaktif agar riwayat tetap aman.');
            }

            if (trim((string)($target['rfid'] ?? '')) !== '') {
                sds_rfid_remove($conn, 'pegawai', $id, (int)($_SESSION['admin_id'] ?? 0), 'dilepas', 'Pegawai dihapus dari master SDS');
            }
            if ($libraryMember) {
                $memberId = (int)$libraryMember['id'];
                $stmt = $conn->prepare('DELETE FROM perpus_anggota WHERE id=?');
                $stmt->bind_param('i', $memberId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare('DELETE FROM pegawai WHERE pegawai_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $message = 'Data ' . (string)($target['nama_lengkap'] ?? 'pengajar/pegawai') . ' berhasil dihapus.';
            $edit = [
                'pegawai_id' => 0,
                'nip' => '',
                'rfid' => '',
                'nama_lengkap' => '',
                'email' => '',
                'jenis_kelamin' => 'Laki-laki',
                'jabatan' => '',
                'telp' => '',
                'active' => 'Y',
            ];
        } else {
            $nip = trim((string)($_POST['nip'] ?? ''));
            $name = trim((string)($_POST['nama_lengkap'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $rfid = trim((string)($_POST['rfid'] ?? ''));
            $gender = trim((string)($_POST['jenis_kelamin'] ?? ''));
            $jabatan = trim((string)($_POST['jabatan'] ?? ''));
            $telp = trim((string)($_POST['telp'] ?? ''));
            $active = !empty($_POST['active']) ? 'Y' : 'N';
            $password = (string)($_POST['password'] ?? '');

            if ($gender === 'L') $gender = 'Laki-laki';
            if ($gender === 'P') $gender = 'Perempuan';

            $edit = [
                'pegawai_id' => $id,
                'nip' => $nip,
                'rfid' => $rfid,
                'nama_lengkap' => $name,
                'email' => $email,
                'jenis_kelamin' => $gender ?: 'Laki-laki',
                'jabatan' => $jabatan,
                'telp' => $telp,
                'active' => $active,
            ];

            if ($name === '') {
                throw new RuntimeException('Nama pengajar atau pegawai wajib diisi.');
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Email tidak valid.');
            }
            if (!in_array($gender, ['Laki-laki', 'Perempuan'], true)) {
                throw new RuntimeException('Jenis kelamin tidak valid.');
            }
            if ($id === 0 && strlen($password) < 8) {
                throw new RuntimeException('Password akun baru minimal 8 karakter.');
            }
            if ($id > 0 && $password !== '' && strlen($password) < 8) {
                throw new RuntimeException('Password baru minimal 8 karakter.');
            }

            // NIP, email, dan UID harus unik pada master SDS.
            if ($nip !== '') {
                $stmt = $conn->prepare('SELECT pegawai_id FROM pegawai WHERE nip=? AND pegawai_id<>? LIMIT 1');
                $stmt->bind_param('si', $nip, $id);
                $stmt->execute();
                $duplicateNip = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($duplicateNip) throw new RuntimeException('NIP sudah digunakan pegawai lain.');
            }
            if ($email !== '') {
                $stmt = $conn->prepare('SELECT pegawai_id FROM pegawai WHERE email=? AND pegawai_id<>? LIMIT 1');
                $stmt->bind_param('si', $email, $id);
                $stmt->execute();
                $duplicateEmail = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($duplicateEmail) throw new RuntimeException('Email sudah digunakan pegawai lain.');
            }

            $isNew = $id <= 0;
            $oldRfid = '';
            if (!$isNew) {
                $stmt = $conn->prepare('SELECT rfid FROM pegawai WHERE pegawai_id=? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $existingTeacher = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$existingTeacher) throw new RuntimeException('Data pengajar atau pegawai tidak ditemukan.');
                $oldRfid = trim((string)($existingTeacher['rfid'] ?? ''));
            }

            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE pegawai SET nip=?,nama_lengkap=?,email=?,password=?,jenis_kelamin=?,jabatan=?,telp=?,active=? WHERE pegawai_id=?');
                    $stmt->bind_param('ssssssssi', $nip, $name, $email, $hash, $gender, $jabatan, $telp, $active, $id);
                } else {
                    $stmt = $conn->prepare('UPDATE pegawai SET nip=?,nama_lengkap=?,email=?,jenis_kelamin=?,jabatan=?,telp=?,active=? WHERE pegawai_id=?');
                    $stmt->bind_param('sssssssi', $nip, $name, $email, $gender, $jabatan, $telp, $active, $id);
                }
                $stmt->execute();
                $stmt->close();
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $now = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO pegawai (nip,rfid,nama_lengkap,email,password,jenis_kelamin,jabatan,lokasi,tanggal_registrasi,tanggal_login,status,active,telp) VALUES (?,NULL,?,?,?,?,?,0,?,?,'Offline',?,?)");
                $stmt->bind_param('ssssssssss', $nip, $name, $email, $hash, $gender, $jabatan, $now, $now, $active, $telp);
                $stmt->execute();
                $id = (int)$conn->insert_id;
                $stmt->close();
            }

            try {
                if ($rfid !== $oldRfid) {
                    if ($rfid === '') {
                        sds_rfid_remove($conn, 'pegawai', $id, (int)($_SESSION['admin_id'] ?? 0), 'dilepas', 'Diperbarui dari master Pengajar & Pegawai');
                    } else {
                        sds_rfid_assign($conn, 'pegawai', $id, $rfid, (int)($_SESSION['admin_id'] ?? 0), 'Diperbarui dari master Pengajar & Pegawai');
                    }
                }
            } catch (Throwable $cardError) {
                if ($isNew && $id > 0) {
                    $cleanup = $conn->prepare('DELETE FROM pegawai WHERE pegawai_id=?');
                    $cleanup->bind_param('i', $id);
                    $cleanup->execute();
                    $cleanup->close();
                }
                throw $cardError;
            }

            if ($active === 'Y') {
                sds_perpus_ensure_member($conn, 'pegawai', $id, true);
            } else {
                $stmt = $conn->prepare("UPDATE perpus_anggota SET status_keanggotaan='nonaktif' WHERE pemilik_tipe='pegawai' AND pemilik_id=?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }

            $message = $isNew ? 'Pengajar atau pegawai berhasil ditambahkan.' : 'Data pengajar atau pegawai berhasil diperbarui.';
            $edit = [
                'pegawai_id' => 0,
                'nip' => '',
                'rfid' => '',
                'nama_lengkap' => '',
                'email' => '',
                'jenis_kelamin' => 'Laki-laki',
                'jabatan' => '',
                'telp' => '',
                'active' => 'Y',
            ];
        }
    } catch (Throwable $e) {
        $rawError = $e->getMessage();
        $error = str_contains(strtolower($rawError), 'duplicate')
            ? 'NIP, RFID, atau email tersebut sudah digunakan.'
            : $rawError;
        $openModal = $id > 0 || $action !== 'delete';
    }
}

$editId = (int)($_GET['id'] ?? 0);
if ($editId > 0 && !$openModal) {
    $stmt = $conn->prepare('SELECT pegawai_id,nip,rfid,nama_lengkap,email,jenis_kelamin,jabatan,telp,active FROM pegawai WHERE pegawai_id=?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: $edit;
    if (($edit['jenis_kelamin'] ?? '') === 'L') $edit['jenis_kelamin'] = 'Laki-laki';
    if (($edit['jenis_kelamin'] ?? '') === 'P') $edit['jenis_kelamin'] = 'Perempuan';
    $openModal = (int)$edit['pegawai_id'] > 0;
}

$teacherRows = [];
$result = $conn->query('SELECT pegawai_id,nip,rfid,nama_lengkap,email,jenis_kelamin,jabatan,telp,active FROM pegawai ORDER BY nama_lengkap');
while ($row = $result->fetch_assoc()) {
    $teacherRows[] = $row;
}

$totalTeachers = count($teacherRows);
$totalActive = 0;
$totalInactive = 0;
$totalGuru = 0;
foreach ($teacherRows as $row) {
    if (($row['active'] ?? 'N') === 'Y') $totalActive++; else $totalInactive++;
    if (str_contains(strtolower((string)($row['jabatan'] ?? '')), 'guru')) $totalGuru++;
}

require __DIR__ . '/partials/shared/master_page_style.php';
?>
<div class="sds-master-page" id="teachersPage">
    <div class="sds-hero">
        <div>
            <h2>Pengajar &amp; Pegawai</h2>
            <p>Master pengajar dan pegawai yang dipakai bersama oleh Absensi Pegawai dan E-KBM.</p>
        </div>
        <div class="sds-hero-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTeacher" onclick="resetTeacherForm()">
                Tambah Pengajar/Pegawai
            </button>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card">
            <small>Total Data</small>
            <strong><?= number_format($totalTeachers, 0, ',', '.') ?></strong>
            <span>Seluruh pengajar dan pegawai</span>
        </div>
        <div class="sds-stat-card">
            <small>Status Aktif</small>
            <strong><?= number_format($totalActive, 0, ',', '.') ?></strong>
            <span>Dapat memakai layanan terintegrasi</span>
        </div>
        <div class="sds-stat-card">
            <small>Nonaktif</small>
            <strong><?= number_format($totalInactive, 0, ',', '.') ?></strong>
            <span>Akun tidak aktif</span>
        </div>
        <div class="sds-stat-card">
            <small>Jabatan Guru</small>
            <strong><?= number_format($totalGuru, 0, ',', '.') ?></strong>
            <span>Berdasarkan data jabatan</span>
        </div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Pengajar &amp; Pegawai</h5>
            <span class="sds-mini">Menampilkan <strong id="teacherVisibleCount"><?= number_format($totalTeachers, 0, ',', '.') ?></strong> data.</span>
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
                    <select id="teacherStatusFilter" class="form-select form-select-sm" aria-label="Filter status">
                        <option value="">Semua Status</option>
                        <option value="Y">Aktif</option>
                        <option value="N">Nonaktif</option>
                    </select>
                </div>
                <div class="sds-toolbar-right">
                    <input type="search" id="teacherSearch" class="form-control form-control-sm sds-search" placeholder="Cari nama, NIP, RFID, jabatan..." aria-label="Cari pengajar atau pegawai">
                </div>
            </div>

            <div class="sds-table-wrap">
                <table class="sds-table wide" id="teacherTable">
                    <thead>
                        <tr>
                            <th style="width:60px">No.</th>
                            <th>Pengajar/Pegawai</th>
                            <th>NIP &amp; RFID</th>
                            <th>Jabatan</th>
                            <th>Kontak</th>
                            <th>Jenis Kelamin</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$teacherRows): ?>
                        <tr><td colspan="8" class="sds-empty">Belum ada data pengajar atau pegawai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($teacherRows as $index => $row): ?>
                            <?php
                            $active = ($row['active'] ?? 'N') === 'Y';
                            $searchText = strtolower(implode(' ', [
                                (string)($row['nama_lengkap'] ?? ''),
                                (string)($row['nip'] ?? ''),
                                (string)($row['rfid'] ?? ''),
                                (string)($row['email'] ?? ''),
                                (string)($row['jabatan'] ?? ''),
                                (string)($row['telp'] ?? ''),
                            ]));
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchText) ?>" data-status="<?= $active ? 'Y' : 'N' ?>">
                                <td><?= number_format($index + 1, 0, ',', '.') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string)$row['nama_lengkap']) ?></strong><br>
                                    <span class="sds-mini"><?= htmlspecialchars((string)($row['email'] ?: '-')) ?></span>
                                </td>
                                <td>
                                    <span class="sds-code"><?= htmlspecialchars((string)($row['nip'] ?: '-')) ?></span><br>
                                    <span class="sds-mini">RFID: <?= htmlspecialchars((string)($row['rfid'] ?: '-')) ?></span>
                                </td>
                                <td><?= htmlspecialchars((string)($row['jabatan'] ?: '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['telp'] ?: '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['jenis_kelamin'] ?: '-')) ?></td>
                                <td><span class="sds-badge <?= $active ? 'ok' : 'muted' ?>"><?= $active ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td>
                                    <div class="sds-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-id="<?= (int)$row['pegawai_id'] ?>"
                                            data-name="<?= htmlspecialchars((string)$row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-position="<?= htmlspecialchars((string)$row['jabatan'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-nip="<?= htmlspecialchars((string)$row['nip'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-rfid="<?= htmlspecialchars((string)$row['rfid'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars((string)$row['telp'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-gender="<?= htmlspecialchars((string)$row['jenis_kelamin'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-active="<?= $active ? 'Y' : 'N' ?>"
                                            onclick="openTeacherEdit(this)"
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

<div class="modal fade sds-master-modal" id="modalTeacher" tabindex="-1" aria-labelledby="modalTeacherLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" id="teacherForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTeacherLabel"><?= (int)$edit['pegawai_id'] > 0 ? 'Edit Pengajar/Pegawai' : 'Tambah Pengajar/Pegawai' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['teacher_csrf']) ?>">
                    <input type="hidden" name="id" id="teacherId" value="<?= (int)$edit['pegawai_id'] ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherName" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="teacherName" name="nama_lengkap" maxlength="60" value="<?= htmlspecialchars((string)$edit['nama_lengkap']) ?>" required autofocus>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherPosition" class="form-label">Jabatan</label>
                            <input type="text" class="form-control" id="teacherPosition" name="jabatan" maxlength="45" placeholder="Contoh: Guru, Tata Usaha" value="<?= htmlspecialchars((string)$edit['jabatan']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherNip" class="form-label">NIP</label>
                            <input type="text" class="form-control" id="teacherNip" name="nip" maxlength="30" value="<?= htmlspecialchars((string)$edit['nip']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherRfid" class="form-label">RFID</label>
                            <input type="text" class="form-control" id="teacherRfid" name="rfid" maxlength="50" value="<?= htmlspecialchars((string)$edit['rfid']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="teacherEmail" name="email" maxlength="60" value="<?= htmlspecialchars((string)$edit['email']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherPhone" class="form-label">Telepon</label>
                            <input type="text" class="form-control" id="teacherPhone" name="telp" maxlength="15" value="<?= htmlspecialchars((string)$edit['telp']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherGender" class="form-label">Jenis Kelamin</label>
                            <select class="form-select" id="teacherGender" name="jenis_kelamin" required>
                                <option value="Laki-laki" <?= in_array((string)$edit['jenis_kelamin'], ['Laki-laki', 'L'], true) ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= in_array((string)$edit['jenis_kelamin'], ['Perempuan', 'P'], true) ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherPassword" class="form-label" id="teacherPasswordLabel">Password <?= (int)$edit['pegawai_id'] > 0 ? 'Baru (Opsional)' : '' ?></label>
                            <input type="password" class="form-control" id="teacherPassword" name="password" autocomplete="new-password" <?= (int)$edit['pegawai_id'] > 0 ? '' : 'required minlength="8"' ?>>
                            <div class="form-text"><?= (int)$edit['pegawai_id'] > 0 ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.' ?></div>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="teacherActive" name="active" value="1" <?= ($edit['active'] ?? 'Y') === 'Y' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="teacherActive">Akun aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="submit"
                        name="action"
                        value="delete"
                        id="deleteTeacherButton"
                        class="btn btn-outline-danger me-auto<?= (int)$edit['pegawai_id'] > 0 ? '' : ' d-none' ?>"
                        formnovalidate
                        onclick="return confirm('Hapus pengajar/pegawai ini secara permanen? Riwayat pada modul lain tidak ikut terhapus.')"
                    >Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="action" value="save" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTeacherModal() {
    const run = function () {
        const modalElement = document.getElementById('modalTeacher');
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

function setTeacherPasswordMode(isEdit) {
    const password = document.getElementById('teacherPassword');
    const passwordLabel = document.getElementById('teacherPasswordLabel');
    if (!password) return;

    password.value = '';
    password.required = !isEdit;
    password.minLength = 8;
    if (passwordLabel) passwordLabel.textContent = isEdit ? 'Password Baru (Opsional)' : 'Password';
    const help = password.parentElement.querySelector('.form-text');
    if (help) help.textContent = isEdit ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.';
}

function openTeacherEdit(button) {
    const values = {
        teacherId: button.dataset.id || '0',
        teacherName: button.dataset.name || '',
        teacherPosition: button.dataset.position || '',
        teacherNip: button.dataset.nip || '',
        teacherRfid: button.dataset.rfid || '',
        teacherEmail: button.dataset.email || '',
        teacherPhone: button.dataset.phone || ''
    };

    Object.keys(values).forEach(function (fieldId) {
        const field = document.getElementById(fieldId);
        if (field) field.value = values[fieldId];
    });

    const title = document.getElementById('modalTeacherLabel');
    const gender = document.getElementById('teacherGender');
    const active = document.getElementById('teacherActive');
    const deleteButton = document.getElementById('deleteTeacherButton');
    let genderValue = button.dataset.gender || 'Laki-laki';
    if (genderValue === 'L') genderValue = 'Laki-laki';
    if (genderValue === 'P') genderValue = 'Perempuan';

    if (title) title.textContent = 'Edit Pengajar/Pegawai';
    if (gender) gender.value = genderValue;
    if (active) active.checked = (button.dataset.active || 'N') === 'Y';
    if (deleteButton) deleteButton.classList.remove('d-none');
    setTeacherPasswordMode(true);
    showTeacherModal();
}

function resetTeacherForm() {
    const form = document.getElementById('teacherForm');
    if (form) form.reset();

    const fieldsToClear = ['teacherName', 'teacherPosition', 'teacherNip', 'teacherRfid', 'teacherEmail', 'teacherPhone'];
    fieldsToClear.forEach(function (fieldId) {
        const field = document.getElementById(fieldId);
        if (field) field.value = '';
    });

    const id = document.getElementById('teacherId');
    const title = document.getElementById('modalTeacherLabel');
    const gender = document.getElementById('teacherGender');
    const active = document.getElementById('teacherActive');
    const deleteButton = document.getElementById('deleteTeacherButton');
    if (id) id.value = '0';
    if (title) title.textContent = 'Tambah Pengajar/Pegawai';
    if (gender) gender.value = 'Laki-laki';
    if (active) active.checked = true;
    if (deleteButton) deleteButton.classList.add('d-none');
    setTeacherPasswordMode(false);
}

(function () {
    const search = document.getElementById('teacherSearch');
    const status = document.getElementById('teacherStatusFilter');
    const visibleCount = document.getElementById('teacherVisibleCount');

    function filterTeachers() {
        const keyword = (search ? search.value : '').toLowerCase().trim();
        const statusValue = status ? status.value : '';
        let count = 0;

        document.querySelectorAll('#teacherTable tbody tr[data-search]').forEach(function (row) {
            const matchesKeyword = row.dataset.search.includes(keyword);
            const matchesStatus = !statusValue || row.dataset.status === statusValue;
            const show = matchesKeyword && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) count++;
        });

        if (visibleCount) visibleCount.textContent = new Intl.NumberFormat('id-ID').format(count);
    }

    if (search) search.addEventListener('input', filterTeachers);
    if (status) status.addEventListener('change', filterTeachers);

    <?php if ($openModal): ?>
    showTeacherModal();
    <?php endif; ?>
})();
</script>