<?php
if (($_SESSION['admin_role'] ?? '') === 'kesiswaan') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Akses ditolak.</div>';
    return;
}

if (empty($_SESSION['ekbm_csrf'])) {
    $_SESSION['ekbm_csrf'] = bin2hex(random_bytes(24));
}

$message = '';
$error = '';
$openModal = '';
$subjectInput = ['id' => 0, 'kode' => '', 'nama_mapel' => ''];
$scheduleInput = ['id' => 0, 'hari' => 'Senin', 'pegawai' => '', 'mata_pelajaran' => '', 'kelas_id' => '', 'dari_jam' => '', 'sampai_jam' => ''];
$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if (!hash_equals($_SESSION['ekbm_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Token tidak valid. Muat ulang halaman lalu coba kembali.');
        }

        if ($action === 'subject') {
            $id = (int)($_POST['id'] ?? 0);
            $code = trim((string)($_POST['kode'] ?? ''));
            $name = trim((string)($_POST['nama_mapel'] ?? ''));
            $subjectInput = ['id' => $id, 'kode' => $code, 'nama_mapel' => $name];

            if ($name === '') {
                throw new RuntimeException('Nama mata pelajaran wajib diisi.');
            }

            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE mata_pelajaran SET kode=?,nama_mapel=? WHERE id=?');
                $stmt->bind_param('ssi', $code, $name, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO mata_pelajaran (kode,nama_mapel) VALUES (?,?)');
                $stmt->bind_param('ss', $code, $name);
            }
            $stmt->execute();

            $message = $id > 0
                ? 'Mata pelajaran berhasil diperbarui.'
                : 'Mata pelajaran berhasil ditambahkan.';
            $subjectInput = ['id' => 0, 'kode' => '', 'nama_mapel' => ''];
        } elseif ($action === 'subject_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $subjectInput = [
                'id' => $id,
                'kode' => trim((string)($_POST['kode'] ?? '')),
                'nama_mapel' => trim((string)($_POST['nama_mapel'] ?? '')),
            ];
            if ($id <= 0) {
                throw new RuntimeException('Mata pelajaran yang akan dihapus tidak valid.');
            }

            $stmt = $conn->prepare('DELETE FROM mata_pelajaran WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $message = 'Mata pelajaran berhasil dihapus.';
            $subjectInput = ['id' => 0, 'kode' => '', 'nama_mapel' => ''];
        } elseif ($action === 'schedule') {
            $id = (int)($_POST['id'] ?? 0);
            $hari = trim((string)($_POST['hari'] ?? ''));
            $teacher = (int)($_POST['pegawai'] ?? 0);
            $subject = (int)($_POST['mata_pelajaran'] ?? 0);
            $classId = (int)($_POST['kelas_id'] ?? 0);
            $from = trim((string)($_POST['dari_jam'] ?? ''));
            $to = trim((string)($_POST['sampai_jam'] ?? ''));

            $scheduleInput = [
                'id' => $id,
                'hari' => $hari,
                'pegawai' => (string)$teacher,
                'mata_pelajaran' => (string)$subject,
                'kelas_id' => (string)$classId,
                'dari_jam' => $from,
                'sampai_jam' => $to,
            ];

            if (!in_array($hari, $days, true)) {
                throw new RuntimeException('Hari mengajar tidak valid.');
            }
            if (!$teacher || !$subject || !$classId) {
                throw new RuntimeException('Pengajar, mata pelajaran, dan kelas wajib dipilih.');
            }
            if ($from === '' || $to === '') {
                throw new RuntimeException('Jam mulai dan jam selesai wajib diisi.');
            }
            if ($to <= $from) {
                throw new RuntimeException('Jam selesai harus lebih besar daripada jam mulai.');
            }

            $stmt = $conn->prepare('SELECT nama_kelas,tingkat_id FROM kelas WHERE id=?');
            $stmt->bind_param('i', $classId);
            $stmt->execute();
            $class = $stmt->get_result()->fetch_assoc();
            if (!$class) {
                throw new RuntimeException('Kelas yang dipilih tidak ditemukan.');
            }

            $tingkat = (string)($class['tingkat_id'] ?? '');
            $className = (string)$class['nama_kelas'];
            $teacherText = (string)$teacher;
            $subjectText = (string)$subject;

            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE jadwal_mengajar SET hari=?,pegawai=?,mata_pelajaran=?,tingkat=?,kelas=?,dari_jam=?,sampai_jam=? WHERE jadwal_id=?');
                $stmt->bind_param('sssssssi', $hari, $teacherText, $subjectText, $tingkat, $className, $from, $to, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO jadwal_mengajar (hari,pegawai,mata_pelajaran,tingkat,kelas,dari_jam,sampai_jam) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('sssssss', $hari, $teacherText, $subjectText, $tingkat, $className, $from, $to);
            }
            $stmt->execute();

            $message = $id > 0
                ? 'Jadwal mengajar berhasil diperbarui.'
                : 'Jadwal mengajar berhasil ditambahkan.';
            $scheduleInput = ['id' => 0, 'hari' => 'Senin', 'pegawai' => '', 'mata_pelajaran' => '', 'kelas_id' => '', 'dari_jam' => '', 'sampai_jam' => ''];
        } elseif ($action === 'schedule_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $scheduleInput = [
                'id' => $id,
                'hari' => trim((string)($_POST['hari'] ?? 'Senin')),
                'pegawai' => (string)(int)($_POST['pegawai'] ?? 0),
                'mata_pelajaran' => (string)(int)($_POST['mata_pelajaran'] ?? 0),
                'kelas_id' => (string)(int)($_POST['kelas_id'] ?? 0),
                'dari_jam' => trim((string)($_POST['dari_jam'] ?? '')),
                'sampai_jam' => trim((string)($_POST['sampai_jam'] ?? '')),
            ];
            if ($id <= 0) {
                throw new RuntimeException('Jadwal mengajar yang akan dihapus tidak valid.');
            }

            $stmt = $conn->prepare('DELETE FROM jadwal_mengajar WHERE jadwal_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $message = 'Jadwal mengajar berhasil dihapus.';
            $scheduleInput = ['id' => 0, 'hari' => 'Senin', 'pegawai' => '', 'mata_pelajaran' => '', 'kelas_id' => '', 'dari_jam' => '', 'sampai_jam' => ''];
        }
    } catch (Throwable $e) {
        $rawError = $e->getMessage();
        $error = str_contains(strtolower($rawError), 'duplicate')
            ? 'Data yang sama sudah tersedia.'
            : $rawError;
        if (str_starts_with($action, 'subject')) $openModal = 'subject';
        if (str_starts_with($action, 'schedule')) $openModal = 'schedule';
    }
}

$subjectRows = [];
$result = $conn->query('SELECT id,kode,nama_mapel FROM mata_pelajaran ORDER BY nama_mapel');
while ($row = $result->fetch_assoc()) $subjectRows[] = $row;

$teacherRows = [];
$result = $conn->query("SELECT pegawai_id,nama_lengkap FROM pegawai WHERE active='Y' ORDER BY nama_lengkap");
while ($row = $result->fetch_assoc()) $teacherRows[] = $row;

$classRows = [];
$result = $conn->query('SELECT id,nama_kelas,tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC,nama_kelas');
while ($row = $result->fetch_assoc()) $classRows[] = $row;

$scheduleRows = [];
$result = $conn->query("SELECT j.*,p.nama_lengkap,m.nama_mapel,
    (SELECT k.id
       FROM kelas k
      WHERE k.nama_kelas=j.kelas
        AND CAST(k.tingkat_id AS CHAR)=CAST(j.tingkat AS CHAR)
      ORDER BY k.tahun_ajaran DESC,k.id DESC
      LIMIT 1) AS kelas_id
    FROM jadwal_mengajar j
    LEFT JOIN pegawai p ON p.pegawai_id=CAST(j.pegawai AS UNSIGNED)
    LEFT JOIN mata_pelajaran m ON m.id=CAST(j.mata_pelajaran AS UNSIGNED)
    ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),j.dari_jam");
while ($row = $result->fetch_assoc()) $scheduleRows[] = $row;

$reportRows = [];
$result = $conn->query("SELECT e.*,p.nama_lengkap,m.nama_mapel,u.nama_lengkap AS siswa
    FROM absen_ekbm e
    LEFT JOIN pegawai p ON p.pegawai_id=e.pegawai
    LEFT JOIN mata_pelajaran m ON m.id=CAST(e.pelajaran AS UNSIGNED)
    LEFT JOIN user u ON u.user_id=e.user_id
    ORDER BY e.tanggal DESC,e.time DESC
    LIMIT 100");
while ($row = $result->fetch_assoc()) $reportRows[] = $row;

$totalAttendance = 0;
$result = $conn->query('SELECT COUNT(*) AS total FROM absen_ekbm');
if ($result) $totalAttendance = (int)($result->fetch_assoc()['total'] ?? 0);

require __DIR__ . '/partials/shared/master_page_style.php';
?>
<div class="sds-master-page" id="ekbmPage">
    <div class="sds-hero">
        <div>
            <h2>E-KBM</h2>
            <p>Kelola mata pelajaran, jadwal mengajar, dan rekap kehadiran KBM dalam dashboard SDS.</p>
        </div>
        <div class="sds-hero-actions">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSubject" onclick="resetSubjectForm()">Tambah Mata Pelajaran</button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSchedule" onclick="resetScheduleForm()">Tambah Jadwal Mengajar</button>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card">
            <small>Mata Pelajaran</small>
            <strong><?= number_format(count($subjectRows), 0, ',', '.') ?></strong>
            <span>Master pelajaran tersedia</span>
        </div>
        <div class="sds-stat-card">
            <small>Jadwal Mengajar</small>
            <strong><?= number_format(count($scheduleRows), 0, ',', '.') ?></strong>
            <span>Seluruh jadwal aktif di master</span>
        </div>
        <div class="sds-stat-card">
            <small>Pengajar Aktif</small>
            <strong><?= number_format(count($teacherRows), 0, ',', '.') ?></strong>
            <span>Dapat dipilih pada jadwal</span>
        </div>
        <div class="sds-stat-card">
            <small>Rekam Kehadiran</small>
            <strong><?= number_format($totalAttendance, 0, ',', '.') ?></strong>
            <span>Total data E-KBM tersimpan</span>
        </div>
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

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Jadwal Mengajar</h5>
            <span class="sds-mini">Menampilkan <strong id="scheduleVisibleCount"><?= number_format(count($scheduleRows), 0, ',', '.') ?></strong> jadwal.</span>
        </div>
        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-toolbar-left">
                    <select id="scheduleDayFilter" class="form-select form-select-sm" aria-label="Filter hari jadwal">
                        <option value="">Semua Hari</option>
                        <?php foreach ($days as $day): ?><option value="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="sds-toolbar-right">
                    <input type="search" id="scheduleSearch" class="form-control form-control-sm sds-search" placeholder="Cari pelajaran, pengajar, kelas..." aria-label="Cari jadwal">
                </div>
            </div>
            <div class="sds-table-wrap">
                <table class="sds-table wide" id="scheduleTable">
                    <thead>
                        <tr>
                            <th style="width:60px">No.</th>
                            <th>Hari &amp; Jam</th>
                            <th>Mata Pelajaran</th>
                            <th>Pengajar</th>
                            <th>Kelas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$scheduleRows): ?>
                        <tr><td colspan="6" class="sds-empty">Belum ada jadwal mengajar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($scheduleRows as $index => $row): ?>
                            <?php
                            $searchText = strtolower(implode(' ', [
                                (string)($row['hari'] ?? ''),
                                (string)($row['nama_mapel'] ?? ''),
                                (string)($row['nama_lengkap'] ?? ''),
                                (string)($row['kelas'] ?? ''),
                            ]));
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchText) ?>" data-day="<?= htmlspecialchars((string)($row['hari'] ?? '')) ?>">
                                <td><?= number_format($index + 1, 0, ',', '.') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string)($row['hari'] ?? '-')) ?></strong><br>
                                    <span class="sds-code"><?= htmlspecialchars((string)($row['dari_jam'] ?? '-')) ?>–<?= htmlspecialchars((string)($row['sampai_jam'] ?? '-')) ?></span>
                                </td>
                                <td><?= htmlspecialchars((string)($row['nama_mapel'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['nama_lengkap'] ?? '-')) ?></td>
                                <td><span class="sds-badge info"><?= htmlspecialchars((string)($row['kelas'] ?? '-')) ?></span></td>
                                <td>
                                    <div class="sds-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalSchedule"
                                            data-id="<?= (int)$row['jadwal_id'] ?>"
                                            data-day="<?= htmlspecialchars((string)($row['hari'] ?? ''), ENT_QUOTES) ?>"
                                            data-teacher="<?= htmlspecialchars((string)($row['pegawai'] ?? ''), ENT_QUOTES) ?>"
                                            data-subject="<?= htmlspecialchars((string)($row['mata_pelajaran'] ?? ''), ENT_QUOTES) ?>"
                                            data-class-id="<?= (int)($row['kelas_id'] ?? 0) ?>"
                                            data-from="<?= htmlspecialchars(substr((string)($row['dari_jam'] ?? ''), 0, 5), ENT_QUOTES) ?>"
                                            data-to="<?= htmlspecialchars(substr((string)($row['sampai_jam'] ?? ''), 0, 5), ENT_QUOTES) ?>"
                                            onclick="editScheduleForm(this)"
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

    <div class="sds-card sds-section-gap">
        <div class="sds-card-header">
            <h5>Master Mata Pelajaran</h5>
            <span class="sds-mini"><strong><?= number_format(count($subjectRows), 0, ',', '.') ?></strong> mata pelajaran.</span>
        </div>
        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-toolbar-left"><span class="sds-mini">Kode mata pelajaran dapat dikosongkan bila belum tersedia.</span></div>
                <div class="sds-toolbar-right">
                    <input type="search" id="subjectSearch" class="form-control form-control-sm sds-search" placeholder="Cari kode atau mata pelajaran..." aria-label="Cari mata pelajaran">
                </div>
            </div>
            <div class="sds-table-wrap">
                <table class="sds-table" id="subjectTable">
                    <thead>
                        <tr>
                            <th style="width:60px">No.</th>
                            <th style="width:180px">Kode</th>
                            <th>Nama Mata Pelajaran</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$subjectRows): ?>
                        <tr><td colspan="4" class="sds-empty">Belum ada mata pelajaran.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subjectRows as $index => $row): ?>
                            <tr data-search="<?= htmlspecialchars(strtolower((string)($row['kode'] ?? '') . ' ' . (string)($row['nama_mapel'] ?? ''))) ?>">
                                <td><?= number_format($index + 1, 0, ',', '.') ?></td>
                                <td><span class="sds-code"><?= htmlspecialchars((string)($row['kode'] ?: '-')) ?></span></td>
                                <td><?= htmlspecialchars((string)$row['nama_mapel']) ?></td>
                                <td>
                                    <div class="sds-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalSubject"
                                            data-id="<?= (int)$row['id'] ?>"
                                            data-code="<?= htmlspecialchars((string)($row['kode'] ?? ''), ENT_QUOTES) ?>"
                                            data-name="<?= htmlspecialchars((string)($row['nama_mapel'] ?? ''), ENT_QUOTES) ?>"
                                            onclick="editSubjectForm(this)"
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

    <div class="sds-card sds-section-gap">
        <div class="sds-card-header">
            <h5>100 Kehadiran E-KBM Terbaru</h5>
            <span class="sds-mini">Urutan berdasarkan tanggal dan waktu terbaru.</span>
        </div>
        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-toolbar-left"><span class="sds-mini">Data tampil maksimal 100 rekaman terbaru.</span></div>
                <div class="sds-toolbar-right">
                    <input type="search" id="attendanceSearch" class="form-control form-control-sm sds-search" placeholder="Cari siswa, pengajar, kelas..." aria-label="Cari kehadiran E-KBM">
                </div>
            </div>
            <div class="sds-table-wrap">
                <table class="sds-table wide" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Tanggal &amp; Waktu</th>
                            <th>Pengajar</th>
                            <th>Pelajaran</th>
                            <th>Kelas</th>
                            <th>Siswa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$reportRows): ?>
                        <tr><td colspan="6" class="sds-empty">Belum ada rekam kehadiran E-KBM.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportRows as $row): ?>
                            <?php
                            $searchText = strtolower(implode(' ', [
                                (string)($row['tanggal'] ?? ''),
                                (string)($row['nama_lengkap'] ?? ''),
                                (string)($row['nama_mapel'] ?? ''),
                                (string)($row['kelas'] ?? ''),
                                (string)($row['siswa'] ?? ''),
                                (string)($row['keterangan'] ?? ''),
                            ]));
                            $status = strtoupper((string)($row['keterangan'] ?? ''));
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchText) ?>">
                                <td><?= htmlspecialchars(trim((string)($row['tanggal'] ?? '') . ' ' . (string)($row['time'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($row['nama_lengkap'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['nama_mapel'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['kelas'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['siswa'] ?? '-')) ?></td>
                                <td><span class="sds-badge <?= in_array($status, ['H', 'HADIR'], true) ? 'ok' : 'warn' ?>"><?= htmlspecialchars((string)($row['keterangan'] ?? '-')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sds-master-modal" id="modalSubject" tabindex="-1" aria-labelledby="modalSubjectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="subjectForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSubjectLabel"><?= (int)$subjectInput['id'] > 0 ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['ekbm_csrf']) ?>">
                    <input type="hidden" name="action" id="subjectAction" value="subject">
                    <input type="hidden" name="id" id="subjectId" value="<?= (int)$subjectInput['id'] ?>">
                    <div class="mb-3">
                        <label for="subjectCode" class="form-label">Kode Mata Pelajaran</label>
                        <input type="text" class="form-control" id="subjectCode" name="kode" maxlength="50" placeholder="Contoh: MTK" value="<?= htmlspecialchars($subjectInput['kode']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="subjectName" class="form-label">Nama Mata Pelajaran</label>
                        <input type="text" class="form-control" id="subjectName" name="nama_mapel" maxlength="50" value="<?= htmlspecialchars($subjectInput['nama_mapel']) ?>" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        id="deleteSubjectButton"
                        class="btn btn-outline-danger me-auto<?= (int)$subjectInput['id'] > 0 ? '' : ' d-none' ?>"
                        onclick="submitSubjectDelete()"
                    >Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mata Pelajaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade sds-master-modal" id="modalSchedule" tabindex="-1" aria-labelledby="modalScheduleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" id="scheduleForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalScheduleLabel"><?= (int)$scheduleInput['id'] > 0 ? 'Edit Jadwal Mengajar' : 'Tambah Jadwal Mengajar' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['ekbm_csrf']) ?>">
                    <input type="hidden" name="action" id="scheduleAction" value="schedule">
                    <input type="hidden" name="id" id="scheduleId" value="<?= (int)$scheduleInput['id'] ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduleDay" class="form-label">Hari</label>
                            <select class="form-select" id="scheduleDay" name="hari" required>
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= htmlspecialchars($day) ?>" <?= $scheduleInput['hari'] === $day ? 'selected' : '' ?>><?= htmlspecialchars($day) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="scheduleTeacher" class="form-label">Pengajar</label>
                            <select class="form-select" id="scheduleTeacher" name="pegawai" required>
                                <option value="">Pilih pengajar</option>
                                <?php foreach ($teacherRows as $row): ?>
                                    <option value="<?= (int)$row['pegawai_id'] ?>" <?= (string)$scheduleInput['pegawai'] === (string)$row['pegawai_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$row['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduleSubject" class="form-label">Mata Pelajaran</label>
                            <select class="form-select" id="scheduleSubject" name="mata_pelajaran" required>
                                <option value="">Pilih mata pelajaran</option>
                                <?php foreach ($subjectRows as $row): ?>
                                    <option value="<?= (int)$row['id'] ?>" <?= (string)$scheduleInput['mata_pelajaran'] === (string)$row['id'] ? 'selected' : '' ?>><?= htmlspecialchars(trim((string)($row['kode'] ?? '') . ' - ' . (string)$row['nama_mapel'], ' -')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="scheduleClass" class="form-label">Kelas/Rombel</label>
                            <select class="form-select" id="scheduleClass" name="kelas_id" required>
                                <option value="">Pilih kelas</option>
                                <?php foreach ($classRows as $row): ?>
                                    <option value="<?= (int)$row['id'] ?>" <?= (string)$scheduleInput['kelas_id'] === (string)$row['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$row['nama_kelas'] . ' · ' . (string)$row['tahun_ajaran']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduleFrom" class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" id="scheduleFrom" name="dari_jam" value="<?= htmlspecialchars($scheduleInput['dari_jam']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="scheduleTo" class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control" id="scheduleTo" name="sampai_jam" value="<?= htmlspecialchars($scheduleInput['sampai_jam']) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        id="deleteScheduleButton"
                        class="btn btn-outline-danger me-auto<?= (int)$scheduleInput['id'] > 0 ? '' : ' d-none' ?>"
                        onclick="submitScheduleDelete()"
                    >Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function bindSearch(inputId, rowSelector) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('input', function () {
            const keyword = this.value.toLowerCase().trim();
            document.querySelectorAll(rowSelector).forEach(function (row) {
                row.style.display = row.dataset.search.includes(keyword) ? '' : 'none';
            });
        });
    }

    const scheduleSearch = document.getElementById('scheduleSearch');
    const dayFilter = document.getElementById('scheduleDayFilter');
    const visibleCount = document.getElementById('scheduleVisibleCount');

    function filterSchedules() {
        const keyword = (scheduleSearch ? scheduleSearch.value : '').toLowerCase().trim();
        const day = dayFilter ? dayFilter.value : '';
        let count = 0;
        document.querySelectorAll('#scheduleTable tbody tr[data-search]').forEach(function (row) {
            const show = row.dataset.search.includes(keyword) && (!day || row.dataset.day === day);
            row.style.display = show ? '' : 'none';
            if (show) count++;
        });
        if (visibleCount) visibleCount.textContent = new Intl.NumberFormat('id-ID').format(count);
    }

    if (scheduleSearch) scheduleSearch.addEventListener('input', filterSchedules);
    if (dayFilter) dayFilter.addEventListener('change', filterSchedules);
    bindSearch('subjectSearch', '#subjectTable tbody tr[data-search]');
    bindSearch('attendanceSearch', '#attendanceTable tbody tr[data-search]');

    <?php if ($openModal === 'subject'): ?>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('modalSubject');
        if (modalElement && typeof window.sdsShowModal === 'function') window.sdsShowModal(modalElement);
    });
    <?php elseif ($openModal === 'schedule'): ?>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('modalSchedule');
        if (modalElement && typeof window.sdsShowModal === 'function') window.sdsShowModal(modalElement);
    });
    <?php endif; ?>
})();

function resetSubjectForm() {
    const form = document.getElementById('subjectForm');
    if (form) form.reset();

    const id = document.getElementById('subjectId');
    const action = document.getElementById('subjectAction');
    const code = document.getElementById('subjectCode');
    const name = document.getElementById('subjectName');
    const title = document.getElementById('modalSubjectLabel');
    const deleteButton = document.getElementById('deleteSubjectButton');

    if (id) id.value = '0';
    if (action) action.value = 'subject';
    if (code) code.value = '';
    if (name) name.value = '';
    if (title) title.textContent = 'Tambah Mata Pelajaran';
    if (deleteButton) deleteButton.classList.add('d-none');
}

function editSubjectForm(button) {
    const id = document.getElementById('subjectId');
    const action = document.getElementById('subjectAction');
    const code = document.getElementById('subjectCode');
    const name = document.getElementById('subjectName');
    const title = document.getElementById('modalSubjectLabel');
    const deleteButton = document.getElementById('deleteSubjectButton');

    if (id) id.value = button.dataset.id || '0';
    if (action) action.value = 'subject';
    if (code) code.value = button.dataset.code || '';
    if (name) name.value = button.dataset.name || '';
    if (title) title.textContent = 'Edit Mata Pelajaran';
    if (deleteButton) deleteButton.classList.remove('d-none');
}

function submitSubjectDelete() {
    if (!confirm('Hapus mata pelajaran ini? Jadwal atau riwayat E-KBM yang pernah memakai data ini tidak ikut terhapus.')) {
        return;
    }

    const form = document.getElementById('subjectForm');
    const action = document.getElementById('subjectAction');
    if (!form || !action) return;

    action.value = 'subject_delete';
    form.submit();
}

function resetScheduleForm() {
    const form = document.getElementById('scheduleForm');
    if (form) form.reset();

    const id = document.getElementById('scheduleId');
    const action = document.getElementById('scheduleAction');
    const title = document.getElementById('modalScheduleLabel');
    const deleteButton = document.getElementById('deleteScheduleButton');
    const day = document.getElementById('scheduleDay');
    const teacher = document.getElementById('scheduleTeacher');
    const subject = document.getElementById('scheduleSubject');
    const schoolClass = document.getElementById('scheduleClass');
    const from = document.getElementById('scheduleFrom');
    const to = document.getElementById('scheduleTo');

    if (id) id.value = '0';
    if (action) action.value = 'schedule';
    if (title) title.textContent = 'Tambah Jadwal Mengajar';
    if (deleteButton) deleteButton.classList.add('d-none');
    if (day) day.value = 'Senin';
    if (teacher) teacher.value = '';
    if (subject) subject.value = '';
    if (schoolClass) schoolClass.value = '';
    if (from) from.value = '';
    if (to) to.value = '';
}

function editScheduleForm(button) {
    const id = document.getElementById('scheduleId');
    const action = document.getElementById('scheduleAction');
    const title = document.getElementById('modalScheduleLabel');
    const deleteButton = document.getElementById('deleteScheduleButton');
    const day = document.getElementById('scheduleDay');
    const teacher = document.getElementById('scheduleTeacher');
    const subject = document.getElementById('scheduleSubject');
    const schoolClass = document.getElementById('scheduleClass');
    const from = document.getElementById('scheduleFrom');
    const to = document.getElementById('scheduleTo');

    if (id) id.value = button.dataset.id || '0';
    if (action) action.value = 'schedule';
    if (title) title.textContent = 'Edit Jadwal Mengajar';
    if (deleteButton) deleteButton.classList.remove('d-none');
    if (day) day.value = button.dataset.day || 'Senin';
    if (teacher) teacher.value = button.dataset.teacher || '';
    if (subject) subject.value = button.dataset.subject || '';
    if (schoolClass) schoolClass.value = button.dataset.classId || '';
    if (from) from.value = button.dataset.from || '';
    if (to) to.value = button.dataset.to || '';
}

function submitScheduleDelete() {
    if (!confirm('Hapus jadwal mengajar ini secara permanen?')) {
        return;
    }

    const form = document.getElementById('scheduleForm');
    const action = document.getElementById('scheduleAction');
    if (!form || !action) return;

    action.value = 'schedule_delete';
    form.submit();
}
</script>
