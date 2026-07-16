<?php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['tingkat_action'])) {
    $redirectTahun = trim((string)($_POST['tahun'] ?? $tahunAjaran));
    try {
        if (!sds_csrf_verify((string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Token formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        }
        if (($_SESSION['admin_role'] ?? '') !== 'superadmin') {
            throw new RuntimeException('Hanya superadmin yang dapat mengelola tingkat kelas.');
        }
        $action = (string)$_POST['tingkat_action'];
        $id = (int)($_POST['tingkat_id'] ?? 0);
        if ($action === 'delete') {
            if ($id <= 0) throw new RuntimeException('Tingkat kelas tidak valid.');
            $stmt = $conn->prepare('SELECT COUNT(*) total FROM kelas WHERE tingkat_id=?');
            $stmt->bind_param('i', $id);$stmt->execute();
            $used = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);$stmt->close();
            if ($used > 0) throw new RuntimeException('Tingkat kelas masih digunakan oleh '.$used.' rombel dan tidak dapat dihapus.');
            $stmt = $conn->prepare('DELETE FROM tingkat_kelas WHERE id=?');
            $stmt->bind_param('i', $id);$stmt->execute();$stmt->close();
            $_SESSION['success'] = 'Tingkat kelas berhasil dihapus.';
        } else {
            $name = trim((string)($_POST['nama_tingkat'] ?? ''));
            $order = (int)($_POST['urutan_tingkat'] ?? 0);
            if ($name === '' || mb_strlen($name) > 10) throw new RuntimeException('Nama tingkat wajib diisi dan maksimal 10 karakter.');
            if ($order < 1 || $order > 99) throw new RuntimeException('Urutan tingkat harus antara 1 sampai 99.');
            if ($action === 'update') {
                if ($id <= 0) throw new RuntimeException('Tingkat kelas tidak valid.');
                $stmt = $conn->prepare('UPDATE tingkat_kelas SET nama_tingkat=?,urutan_tingkat=? WHERE id=?');
                $stmt->bind_param('sii', $name, $order, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO tingkat_kelas(nama_tingkat,urutan_tingkat) VALUES(?,?)');
                $stmt->bind_param('si', $name, $order);
            }
            $stmt->execute();$stmt->close();
            $_SESSION['success'] = $action === 'update' ? 'Tingkat kelas berhasil diperbarui.' : 'Tingkat kelas berhasil ditambahkan.';
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = $e->getCode() === 1062 ? 'Nama tingkat kelas sudah tersedia.' : 'Tingkat kelas gagal disimpan.';
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: kuota_kelas?tahun=' . urlencode($redirectTahun));
    exit;
}

$tingkatRows = [];
$tingkatResult = $conn->query('SELECT tk.id,tk.nama_tingkat,tk.urutan_tingkat,COUNT(k.id) jumlah_rombel FROM tingkat_kelas tk LEFT JOIN kelas k ON k.tingkat_id=tk.id GROUP BY tk.id,tk.nama_tingkat,tk.urutan_tingkat ORDER BY tk.urutan_tingkat,tk.nama_tingkat');
while ($tingkatRow = $tingkatResult->fetch_assoc()) $tingkatRows[] = $tingkatRow;

$result = $conn->query("
    SELECT 
        k.*, 
        tk.nama_tingkat 
    FROM 
        kelas k
    LEFT JOIN 
        tingkat_kelas tk ON k.tingkat_id = tk.id
    ORDER BY 
        k.tahun_ajaran DESC, k.nama_kelas ASC
");


// Ambil tahun ajaran dari GET atau tentukan default otomatis
// if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
//     $tahunAjaran = $_GET['tahun'];
// } else {
//     $tahunSekarang = date("Y");
//     $bulanSekarang = date("n");
//     $tahunAjaran = ($bulanSekarang >= 7) ? "$tahunSekarang/" . ($tahunSekarang + 1) : ($tahunSekarang - 1) . "/$tahunSekarang";
// }

$data = getDataPengisianKelas($conn, $tahunAjaran);
// foreach ($data['jumlah'] as $kelas) {
//     echo "Kelas: {$kelas['nama_kelas']} - Terisi: {$kelas['terisi']} / {$kelas['kuota']} ";
//     echo $kelas['penuh'] ? "(Penuh)" : "(Tersisa: {$kelas['tersisa']})";
//     echo "<br>";
// }
$dataKelas = $data['jumlah'];
$kuotaData = $data['kuota'];

$totalRombel = count($dataKelas);
$totalKuota = 0;
$totalTerisi = 0;
$totalSisa = 0;
$totalPenuh = 0;
$totalKosong = 0;
foreach ($dataKelas as $kelasStat) {
    $kuotaStat = (int)($kelasStat['kuota'] ?? 0);
    $terisiStat = (int)($kelasStat['terisi'] ?? 0);
    $sisaStat = (int)($kelasStat['tersisa'] ?? max(0, $kuotaStat - $terisiStat));
    $totalKuota += $kuotaStat;
    $totalTerisi += $terisiStat;
    $totalSisa += $sisaStat;
    if ($kuotaStat > 0 && $terisiStat >= $kuotaStat) {
        $totalPenuh++;
    }
    if ($terisiStat <= 0) {
        $totalKosong++;
    }
}


?>

<style>
    /* Style disamakan dengan update Jurusan v13: kotak, ringan, compact, dan dekat dengan dashboard. */
    .sds-dashboard-ref{padding:0}
    .sds-dashboard-ref .sds-hero{background:#fff;border:1px solid #dee2e6;border-radius:0;padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:0;box-shadow:unset}
    .sds-dashboard-ref .sds-hero h2{margin:0 0 .25rem;font-size:1.25rem;font-weight:600;color:#334151}
    .sds-dashboard-ref .sds-hero p{margin:0;color:#6c757d;font-size:.875rem}
    .sds-dashboard-ref .sds-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
    .sds-dashboard-ref .sds-card,.sds-dashboard-ref .sds-stat-card{background:#fff;border:1px solid #dee2e6;border-radius:0;box-shadow:unset}
    .sds-dashboard-ref .sds-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin-bottom:0}
    .sds-dashboard-ref .sds-stats.three{grid-template-columns:repeat(3,minmax(0,1fr))}
    .sds-dashboard-ref .sds-stat-card{padding:1rem;min-height:104px}
    .sds-dashboard-ref .sds-stat-card small{display:block;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem}
    .sds-dashboard-ref .sds-stat-card strong{display:block;font-size:1.55rem;line-height:1.1;margin-top:.25rem;color:#212529;font-weight:700}
    .sds-dashboard-ref .sds-stat-card span{display:block;color:#6c757d;font-size:.78rem;margin-top:.25rem}
    .sds-dashboard-ref .sds-card-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid #dee2e6;background:#f8f9fa}
    .sds-dashboard-ref .sds-card-header h5{margin:0;font-weight:600;color:#334151;font-size:1rem}
    .sds-dashboard-ref .sds-card-body{padding:1rem}
    .sds-dashboard-ref .sds-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem}
    .sds-dashboard-ref .sds-toolbar form{margin:0}
    .sds-dashboard-ref .sds-toolbar .form-select,.sds-dashboard-ref .sds-toolbar .form-control{min-height:31px}
    .sds-dashboard-ref .sds-table-wrap{overflow:auto}
    .sds-dashboard-ref .sds-table{width:100%;border-collapse:collapse;background:#fff;min-width:980px;border:1px solid #eee;}
    .sds-dashboard-ref .sds-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;background:#f8f9fa;padding:.55rem .65rem;border-bottom:1px solid #dee2e6;white-space:nowrap}
    .sds-dashboard-ref .sds-table td{padding:0px 10px;border-bottom:1px solid #edf1f5;vertical-align:middle;color:#334151}
    .sds-dashboard-ref .sds-table tfoot th,.sds-dashboard-ref .sds-table tfoot td{background:#f8f9fa;border-top:1px solid #dee2e6;border-bottom:0;color:#334151;font-weight:700}
    .sds-dashboard-ref .sds-table tr:last-child td{border-bottom:0}
    .sds-dashboard-ref .sds-mini{font-size:.78rem;color:#6c757d}
    .sds-dashboard-ref .sds-code{display:inline-flex;align-items:center;border:1px solid #dee2e6;background:#f8f9fa;border-radius:.25rem;padding:.25rem .45rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:.78rem;color:#334151;white-space:nowrap}
    .sds-dashboard-ref .sds-badge{display:inline-flex;align-items:center;gap:5px;border-radius:.25rem;padding:.35rem .5rem;font-size:.75rem;font-weight:600;white-space:nowrap}
    .sds-dashboard-ref .sds-badge.ok{background:#d1e7dd;color:#0f5132}
    .sds-dashboard-ref .sds-badge.warn{background:#fff3cd;color:#664d03}
    .sds-dashboard-ref .sds-badge.info{background:#cff4fc;color:#055160}
    .sds-dashboard-ref .sds-badge.danger{background:#f8d7da;color:#842029}
    .sds-dashboard-ref .sds-actions{display:flex;align-items:center;gap:.35rem;justify-content:flex-end;white-space:nowrap;flex-wrap:wrap}
    .sds-dashboard-ref .sds-actions .btn{padding:.28rem .55rem;font-size:.78rem}
    .sds-dashboard-ref .sds-filter-chip{display:inline-flex;align-items:center;gap:.35rem;background:#f8f9fa;border:1px solid #dee2e6;color:#495057;padding:.25rem .5rem;border-radius:.25rem;font-size:.78rem;margin:.15rem}
    .sds-dashboard-ref .sds-filter-chip a{color:#dc3545;text-decoration:none;font-weight:700}
    .sds-dashboard-ref .sds-photo{width:34px;height:40px;object-fit:cover;border-radius:0;border:1px solid #dee2e6;background:#f8f9fa}
    .sds-dashboard-ref .alert{margin:1rem 1rem 0}
    @media(max-width:1200px){.sds-dashboard-ref .sds-stats,.sds-dashboard-ref .sds-stats.three{grid-template-columns:repeat(2,1fr)}.sds-dashboard-ref .sds-hero{display:block}.sds-dashboard-ref .sds-hero-actions{justify-content:flex-start;margin-top:.75rem}}
    @media(max-width:700px){.sds-dashboard-ref{padding:0 6px}.sds-dashboard-ref .sds-stats,.sds-dashboard-ref .sds-stats.three{grid-template-columns:1fr}.sds-dashboard-ref .sds-toolbar{display:block}.sds-dashboard-ref .sds-toolbar form{margin-top:.75rem}.sds-dashboard-ref .sds-hero-actions .btn{width:100%}.sds-dashboard-ref .sds-toolbar .form-select,.sds-dashboard-ref .sds-toolbar .form-control,.sds-dashboard-ref .sds-toolbar .btn{width:100%}}
</style>

<div class="sds-dashboard-ref sds-kuota-kelas">
    <div class="sds-hero">
        <div>
            <h2>Rombongan Belajar</h2>
            <!--<p>Kelola kuota, penempatan, wali kelas, dan daftar siswa per kelas tahun ajaran <?= htmlspecialchars($tahunAjaran) ?>.</p>-->
        </div>
        <div class="sds-hero-actions">
            <?php if (($_SESSION['admin_role'] ?? '') === 'superadmin'): ?>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTingkatKelas">Tingkat Kelas</button>
            <?php endif; ?>
            <a href="#" onclick="bukaModalTambahKelas('<?= htmlspecialchars($tahunAjaran) ?>')" class="btn btn-primary">Tambah Kelas</a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalGeneratePenempatan">Generate Kelas</button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalResetPenempatan">Reset Kelas</button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNaikKelas">Proses Naik Kelas</button>
            <a href="siswa_tidak_naik?tahun_ajaran=<?= urlencode($tahunAjaran) ?>" class="btn btn-danger">Siswa Tidak Naik</a>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card"><small>Total Rombel</small><strong><?= number_format($totalRombel, 0, ',', '.') ?></strong><span>Tahun ajaran <?= htmlspecialchars($tahunAjaran) ?></span></div>
        <div class="sds-stat-card"><small>Total Kuota</small><strong><?= number_format($totalKuota, 0, ',', '.') ?></strong><span>Kapasitas seluruh rombel</span></div>
        <div class="sds-stat-card"><small>Terisi Aktif</small><strong><?= number_format($totalTerisi, 0, ',', '.') ?></strong><span>Siswa aktif yang masuk rombel</span></div>
        <div class="sds-stat-card"><small>Sisa Kuota</small><strong><?= number_format($totalSisa, 0, ',', '.') ?></strong><span>Penuh: <?= (int)$totalPenuh ?> · Kosong: <?= (int)$totalKosong ?></span></div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Rombongan Belajar</h5>
            <span class="sds-mini">Tahun <?= htmlspecialchars($tahunAjaran) ?></span>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message"><?= $_SESSION['error'] ?></div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message">
                    <?= $_SESSION['success'] ?>
                    <?php if (isset($_SESSION['naik_kelas_rekap'])): ?>
                        <div class="alert-message pb-0 mt-2">
                            <h6 class="mb-2">Rekap Naik Kelas</h6>
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['naik_kelas_rekap'] as $r): ?>
                                    <li>
                                        <?= angkaKeRomawi($r['nama_tingkat_lama']) ?> <?= htmlspecialchars($r['kelas_lama']) ?> →
                                        <?= angkaKeRomawi($r['nama_tingkat_baru']) ?> <?= htmlspecialchars($r['kelas_lama']) ?>:
                                        <strong><?= (int)$r['jumlah'] ?></strong> siswa naik
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['naik_kelas_rekap']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-mini">Menampilkan <strong><?= number_format($totalRombel, 0, ',', '.') ?></strong> rombel.</div>
                <form method="get" action="kuota_kelas" class="d-flex align-items-center gap-2">
                    <select class="form-select" name="tahun" id="tahun">
                        <?php
                        $tahunList = $conn->query("SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC");
                        while ($tahun = $tahunList->fetch_assoc()):
                            $selected = ($tahun['tahun_ajaran'] === $tahunAjaran) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($tahun['tahun_ajaran']) ?>" <?= $selected ?>><?= htmlspecialchars($tahun['tahun_ajaran']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-success">Tampilkan</button>
                </form>
            </div>

            <div class="sds-table-wrap">
                <table class="sds-table">
                    <thead>
                        <tr>
                            <th style="width:48px;">#</th>
                            <th class="text-center">Tingkat</th>
                            <th>Nama Rombel</th>
                            <th class="text-center">Kuota</th>
                            <th class="text-center">Terisi</th>
                            <th class="text-center">Sisa PD</th>
                            <th>Wali Kelas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($dataKelas as $kelas): ?>
                            <?php
                                $kuota = (int)($kelas['kuota'] ?? 0);
                                $terisi = (int)($kelas['terisi'] ?? 0);
                                $tersisa = (int)($kelas['tersisa'] ?? max(0, $kuota - $terisi));
                                $isPenuh = $kuota > 0 && $terisi >= $kuota;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="text-center"><span class="sds-badge info"><?= htmlspecialchars($kelas['nama_tingkat']) ?></span></td>
                                <td><strong><?= htmlspecialchars($kelas['nama_kelas']) ?></strong></td>
                                <td class="text-center"><?= number_format($kuota, 0, ',', '.') ?></td>
                                <td class="text-center"><strong><?= number_format($terisi, 0, ',', '.') ?></strong></td>
                                <td class="text-center"><span class="sds-badge <?= $isPenuh ? 'danger' : 'ok' ?>"><?= number_format($tersisa, 0, ',', '.') ?></span></td>
                                <td><?= htmlspecialchars($kelas['wali_kelas'] ?: '-') ?></td>
                                <td>
                                    <div class="sds-actions">
                                        <a href="#" class="btn btn-primary btn-sm btn-edit-kelas"
                                            data-bs-toggle="modal" data-bs-target="#modalEditKelas"
                                            data-id="<?= $kelas['id'] ?>"
                                            data-nama="<?= htmlspecialchars($kelas['nama_kelas']) ?>"
                                            data-kuota="<?= htmlspecialchars($kelas['kuota']) ?>"
                                            data-walas="<?= htmlspecialchars($kelas['wali_kelas']) ?>"
                                            data-tahun="<?= htmlspecialchars($kelas['tahun_ajaran']) ?>">Edit</a>
                                        <?php if ($terisi > 0): ?>
                                            <a href="kuota_kelas_siswa?kelas_id=<?= $kelas['id'] ?>" class="btn btn-warning btn-sm">Lihat Siswa</a>
                                            <a href="#" class="btn btn-danger btn-sm disabled" title="Tidak bisa dihapus, masih ada siswa">Hapus</a>
                                        <?php else: ?>
                                            <button class="btn btn-warning btn-sm" disabled title="Belum ada siswa di kelas ini">Lihat Siswa</button>
                                            <form method="post" action="kuota_kelas_hapus" class="d-inline" onsubmit="return confirm('Yakin hapus?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$kelas['id'] ?>"><input type="hidden" name="tahun" value="<?= htmlspecialchars($tahunAjaran, ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="btn btn-danger btn-sm">Hapus</button></form>
                                        <?php endif; ?>
                                        <a href="cetak_absensi_pdf.php?kelas_id=<?= $kelas['id'] ?>" target="_blank" class="btn btn-success btn-sm">Cetak Absensi</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dataKelas)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Rombel belum dibuat untuk tahun ajaran ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">TOTAL</th>
                            <th class="text-center"><?= number_format($totalKuota, 0, ',', '.') ?></th>
                            <th class="text-center"><?= number_format($totalTerisi, 0, ',', '.') ?></th>
                            <th class="text-center"><?= number_format($totalSisa, 0, ',', '.') ?></th>
                            <th colspan="2" class="text-end">Total Rombel: <?= number_format($totalRombel, 0, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Kelas -->

<div class="modal fade" id="modalEditKelas" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="pages/kuota_kelas_edit.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="tahun_ajaran" id="edit-tahun-ajaran">

                    <div class="mb-3">
                        <label for="edit-nama-kelas" class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" name="nama_kelas" id="edit-nama-kelas" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-kuota" class="form-label">Jumlah Siswa Dalam Kelas</label>
                        <input type="number" class="form-control" name="kuota" id="edit-kuota" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-walas" class="form-label">Nama Wali Kelas</label>
                        <input type="text" class="form-control" name="wali_kelas" id="edit-walas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (($_SESSION['admin_role'] ?? '') === 'superadmin'): ?>
<div class="modal fade" id="modalTingkatKelas" tabindex="-1" aria-labelledby="modalTingkatKelasLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTingkatKelasLabel">Kelola Tingkat Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="kuota_kelas" class="row g-2 align-items-end mb-4">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="tingkat_action" value="create">
                    <input type="hidden" name="tahun" value="<?= htmlspecialchars($tahunAjaran, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-md-6"><label class="form-label">Nama Tingkat</label><input class="form-control" name="nama_tingkat" maxlength="10" placeholder="Contoh: X, XI, XII" required></div>
                    <div class="col-md-3"><label class="form-label">Urutan</label><input type="number" class="form-control" name="urutan_tingkat" min="1" max="99" value="<?= count($tingkatRows) + 1 ?>" required></div>
                    <div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Tambah Tingkat</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light"><tr><th>Nama Tingkat</th><th style="width:110px">Urutan</th><th style="width:110px">Rombel</th><th style="width:190px">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($tingkatRows as $tingkat): ?>
                            <?php $tingkatFormId = 'tingkat-form-' . (int)$tingkat['id']; ?>
                            <tr>
                                <td>
                                    <form id="<?= $tingkatFormId ?>" method="post" action="kuota_kelas"></form>
                                    <input form="<?= $tingkatFormId ?>" type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input form="<?= $tingkatFormId ?>" type="hidden" name="tingkat_id" value="<?= (int)$tingkat['id'] ?>">
                                    <input form="<?= $tingkatFormId ?>" type="hidden" name="tahun" value="<?= htmlspecialchars($tahunAjaran, ENT_QUOTES, 'UTF-8') ?>">
                                    <input form="<?= $tingkatFormId ?>" class="form-control form-control-sm" name="nama_tingkat" maxlength="10" value="<?= htmlspecialchars((string)$tingkat['nama_tingkat'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </td>
                                <td><input form="<?= $tingkatFormId ?>" type="number" class="form-control form-control-sm" name="urutan_tingkat" min="1" max="99" value="<?= (int)$tingkat['urutan_tingkat'] ?>" required></td>
                                <td><?= (int)$tingkat['jumlah_rombel'] ?></td>
                                <td class="text-nowrap">
                                    <button form="<?= $tingkatFormId ?>" class="btn btn-success btn-sm" name="tingkat_action" value="update" type="submit">Simpan</button>
                                    <button form="<?= $tingkatFormId ?>" class="btn btn-danger btn-sm" name="tingkat_action" value="delete" type="submit" formnovalidate onclick="return confirm('Hapus tingkat kelas ini?')" <?= (int)$tingkat['jumlah_rombel'] > 0 ? 'disabled title="Masih digunakan rombel"' : '' ?>>Hapus</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$tingkatRows): ?><tr><td colspan="4" class="text-center text-muted">Belum ada tingkat kelas.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- Modal Tambah Kelas -->
<div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-labelledby="modalTambahKelasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="index?page=kuota_kelas_tambah">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahKelasLabel">Tambah Kelas <strong>Tahun Ajaran: <?= htmlspecialchars($tahunAjaran) ?></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <!-- <input type="hidden" name="tahun_ajaran" > -->
                <input type="hidden" name="tahun_ajaran" id="inputTahunAjaranKelas">
                <p></p>

                <div class="mb-3">
                    <label for="jurusan_id" class="form-label">Jurusan</label>
                    <select class="form-select" name="jurusan_id" id="jurusan_id" required>
                        <option value="">-- Pilih Jurusan --</option>
                        <?php
                        // Ambil tahun ajaran terbaru dari tabel jurusan
                        $qTahun = mysqli_query($conn, "SELECT MAX(tahun_ajaran) AS tahun_terbaru FROM jurusan");
                        $dataTahun = mysqli_fetch_assoc($qTahun);
                        $tahunTerbaru = $dataTahun['tahun_terbaru'];

                        // Ambil data jurusan berdasarkan tahun ajaran terbaru
                        $result = mysqli_query($conn, "SELECT * FROM jurusan WHERE tahun_ajaran = '$tahunTerbaru' ORDER BY nama_jurusan ASC");
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_jurusan']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="tingkat_id" class="form-label">Tingkat Kelas</label>
                    <select name="tingkat_id" id="tingkat_id" class="form-select" required>
                        <option value="">-- Pilih Tingkat --</option>
                        <?php
                        $result = $conn->query("SELECT id, nama_tingkat FROM tingkat_kelas ORDER BY nama_tingkat ASC");
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_tingkat']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="kelas" class="form-label">Nama Kelas</label>
                    <input type="text" class="form-control" id="kelas" name="nama_kelas" required>
                </div>

                <div class="mb-3">
                    <label for="kuota" class="form-label">Jumlah Siswa Dalam Kelas</label>
                    <input type="number" class="form-control" id="kuota" name="kuota" required>
                </div>
                <div class="mb-3">
                    <label for="walas" class="form-label">Wali Kelas:</label>
                    <input type="text" class="form-control" id="walas" name="wali_kelas" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const editButtons = document.querySelectorAll(".btn-edit-kelas");

        editButtons.forEach(button => {
            button.addEventListener("click", function() {
                document.getElementById("edit-id").value = this.dataset.id;
                document.getElementById("edit-nama-kelas").value = this.dataset.nama;
                document.getElementById("edit-kuota").value = this.dataset.kuota;
                document.getElementById("edit-walas").value = this.dataset.walas;
                document.getElementById("edit-tahun-ajaran").value = this.dataset.tahun;
            });
        });
    });
</script>


<script>
    function bukaModalTambahKelas(tahunAjaran) {
        document.getElementById('inputTahunAjaranKelas').value = tahunAjaran;

        // Panggil modal Bootstrap
        var myModal = new bootstrap.Modal(document.getElementById('modalTambahKelas'));
        myModal.show();
    }
</script>

<!-- Modal Pilih Tahun Ajaran -->
<div class="modal fade" id="modalNaikKelas" tabindex="-1" aria-labelledby="modalNaikKelasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="naik_kelas" onsubmit="return confirm('Yakin ingin memproses kenaikan kelas secara otomatis?');">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNaikKelasLabel">Proses Naik Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tahunLama" class="form-label">Tahun Ajaran Lama</label>
                        <select class="form-select" name="tahun_lama" id="tahunLama" required>
                            <option value="">-- Pilih Tahun Lama --</option>
                            <?php
                            // Ambil tahun_ajaran unik dari tabel kelas
                            $query = "SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $tahun = htmlspecialchars($row['tahun_ajaran']);
                                echo "<option value=\"$tahun\">$tahun</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tahunBaru" class="form-label">Tahun Ajaran Baru</label>
                        <select class="form-select" name="tahun_baru" id="tahunBaru" required>
                            <option value="">-- Pilih Tahun Baru --</option>
                            <?php
                            // Ambil tahun_ajaran unik dari tabel kelas
                            $query = "SELECT DISTINCT tahun_ajaran FROM jurusan ORDER BY tahun_ajaran DESC";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $tahun = htmlspecialchars($row['tahun_ajaran']);
                                echo "<option value=\"$tahun\">$tahun</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Proses</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Generate Penempatan -->
<div class="modal fade" id="modalGeneratePenempatan" tabindex="-1" aria-labelledby="modalGenerateLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="generate_penempatan_kelas" onsubmit="return confirm('Generate penempatan kelas berdasarkan tingkat dan tahun ajaran?')">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGenerateLabel">Generate Penempatan Kelas Tahun Ajaran:</h5>&nbsp;<strong><?= htmlspecialchars($tahunAjaran) ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($tahunAjaran) ?>">
                <div class="mb-3"><label for="generate_tingkat_id" class="form-label">Tingkat Kelas</label><select name="tingkat_id" id="generate_tingkat_id" class="form-select" required><option value="">Pilih tingkat</option><?php $tingkatQ=$conn->prepare('SELECT DISTINCT tk.id,tk.nama_tingkat FROM tingkat_kelas tk JOIN kelas k ON k.tingkat_id=tk.id WHERE k.tahun_ajaran=? ORDER BY CAST(tk.nama_tingkat AS UNSIGNED),tk.nama_tingkat');$tingkatQ->bind_param('s',$tahunAjaran);$tingkatQ->execute();$tingkatResult=$tingkatQ->get_result();while($t=$tingkatResult->fetch_assoc()):?><option value="<?=(int)$t['id']?>"><?=htmlspecialchars((string)$t['nama_tingkat'])?></option><?php endwhile;$tingkatQ->close();?></select></div>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="alert-message">
                        <strong>Informasi:</strong> Sistem hanya menempatkan peserta didik aktif yang belum memiliki rombel pada tahun ajaran <strong><?= htmlspecialchars($tahunAjaran) ?></strong>. Kuota dan penempatan yang sudah ada tetap dipertahankan.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Mulai Generate</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Penempatan -->
<div class="modal fade" id="modalResetPenempatan" tabindex="-1" aria-labelledby="modalResetLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="reset_penempatan" onsubmit="return confirm('Yakin ingin mereset penempatan kelas? Semua data penempatan untuk tingkat ini akan dihapus!')">
            <div class="modal-header">
                <h5 class="modal-title" id="modalResetLabel">Reset Penempatan Kelas Tahun Ajaran:</h5>&nbsp;<strong><?= htmlspecialchars($tahunAjaran) ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($tahunAjaran) ?>">
                <div class="mb-3"><label for="reset_tingkat_id" class="form-label">Tingkat Kelas</label><select name="tingkat_id" id="reset_tingkat_id" class="form-select" required><option value="">Pilih tingkat</option><?php $tingkatQ=$conn->prepare('SELECT DISTINCT tk.id,tk.nama_tingkat FROM tingkat_kelas tk JOIN kelas k ON k.tingkat_id=tk.id WHERE k.tahun_ajaran=? ORDER BY CAST(tk.nama_tingkat AS UNSIGNED),tk.nama_tingkat');$tingkatQ->bind_param('s',$tahunAjaran);$tingkatQ->execute();$tingkatResult=$tingkatQ->get_result();while($t=$tingkatResult->fetch_assoc()):?><option value="<?=(int)$t['id']?>"><?=htmlspecialchars((string)$t['nama_tingkat'])?></option><?php endwhile;$tingkatQ->close();?></select></div>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <div class="alert-message">
                        <strong>Perhatian!</strong> Semua penempatan peserta didik pada tingkat yang dipilih untuk tahun ajaran <strong><?= htmlspecialchars($tahunAjaran) ?></strong> akan dilepas. Data rombel tidak ikut dihapus.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Reset Penempatan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>
