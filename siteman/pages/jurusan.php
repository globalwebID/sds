<?php
// Cek koneksi (pastikan $conn sudah dibuat sebelumnya)
if (!isset($conn)) {
    die("Koneksi database belum diinisialisasi.");
}

// Ambil tahun ajaran dari GET atau tentukan default otomatis
// if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
//     $tahunAjaran = $_GET['tahun'];
// } else {
//     $tahunSekarang = date("Y");
//     $bulanSekarang = date("n");
//     $tahunAjaran = ($bulanSekarang >= 7) ? "$tahunSekarang/" . ($tahunSekarang + 1) : ($tahunSekarang - 1) . "/$tahunSekarang";
// }

$stmt = $conn->prepare("
    SELECT 
        j.id, 
        j.nama_jurusan, 
        j.kode_jurusan, 
        j.tahun_ajaran,
        p.kode_depan, 
        p.urutan_awal,
        p.kode_akhir,
        p.urutan_akhir,
        (
            SELECT ps2.nipd
            FROM pendaftaran_siswa ps2
            JOIN siswa_kelas sk2
                ON sk2.siswa_id = ps2.id
                AND BINARY sk2.tahun_ajaran = BINARY ps2.tahun_ajaran
            JOIN kelas k2
                ON k2.id = sk2.kelas_id
                AND BINARY k2.tahun_ajaran = BINARY ps2.tahun_ajaran
            WHERE k2.jurusan_id = j.id
              AND BINARY k2.tahun_ajaran = BINARY j.tahun_ajaran
              AND k2.tingkat_id = (
                  SELECT tk_awal.id FROM tingkat_kelas tk_awal
                  ORDER BY tk_awal.urutan_tingkat,tk_awal.id LIMIT 1
              )
              AND ps2.status_aktif = 1
              AND ps2.nipd IS NOT NULL
              AND ps2.nipd <> ''
            ORDER BY
                CASE
                    WHEN SUBSTRING_INDEX(ps2.nipd, '/', 1) REGEXP '^[0-9]+$'
                    THEN CAST(SUBSTRING_INDEX(ps2.nipd, '/', 1) AS UNSIGNED)
                    ELSE 0
                END DESC,
                ps2.id DESC
            LIMIT 1
        ) AS nis_terakhir,
        COUNT(DISTINCT k.id) AS jumlah_kelas,
        COUNT(DISTINCT ps.id) AS jumlah_siswa
    FROM jurusan j
    LEFT JOIN pengaturan_nipd p ON j.id = p.jurusan_id AND p.tahun_ajaran = j.tahun_ajaran
    LEFT JOIN kelas k ON k.jurusan_id = j.id AND k.tahun_ajaran = j.tahun_ajaran
    LEFT JOIN siswa_kelas s ON s.kelas_id = k.id AND s.tahun_ajaran = j.tahun_ajaran
    LEFT JOIN pendaftaran_siswa ps ON ps.id = s.siswa_id AND ps.status_aktif = 1
    WHERE j.tahun_ajaran = ?
    GROUP BY j.id, j.nama_jurusan, j.kode_jurusan, j.tahun_ajaran, p.kode_depan, p.urutan_awal, p.kode_akhir, p.urutan_akhir
    ORDER BY
        CAST(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 1) AS UNSIGNED) ASC,
        CASE
            WHEN TRIM(j.kode_jurusan) REGEXP '^[0-9]+[.][0-9]+'
            THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 2), '.', -1) AS UNSIGNED)
            ELSE 0
        END ASC,
        CASE
            WHEN TRIM(j.kode_jurusan) REGEXP '^[0-9]+[.][0-9]+[.][0-9]+'
            THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 3), '.', -1) AS UNSIGNED)
            ELSE 0
        END ASC,
        TRIM(j.kode_jurusan) ASC,
        j.nama_jurusan ASC
");


if ($stmt) {
    $stmt->bind_param("s", $tahunAjaran);
    $stmt->execute();
    $data = $stmt->get_result();
} else {
    die("Query Error: " . $conn->error);
}

$rowsJurusan = [];
$totalKelas = 0;
$totalSiswa = 0;
$totalSudahGenerate = 0;
$totalBelumGenerate = 0;

if ($data) {
    while ($row = $data->fetch_assoc()) {
        $row['jumlah_kelas'] = (int)($row['jumlah_kelas'] ?? 0);
        $row['jumlah_siswa'] = (int)($row['jumlah_siswa'] ?? 0);
        $totalKelas += $row['jumlah_kelas'];
        $totalSiswa += $row['jumlah_siswa'];
        if (!empty($row['nis_terakhir'])) {
            $totalSudahGenerate++;
        } else {
            $totalBelumGenerate++;
        }
        $rowsJurusan[] = $row;
    }
}

if (!function_exists('jur_e')) {
    function jur_e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('jur_int')) {
    function jur_int($value) {
        return number_format((int)$value, 0, ',', '.');
    }
}

?>
<!-- HTML Dimulai -->
<style>
    /* Halaman Jurusan mengikuti gaya dashboard: ringan, kotak, compact, dan tidak mengubah template global. */
    .sds-jurusan{padding:0}
    .sds-jurusan .sds-hero{background:#fff;border:1px solid #dee2e6;border-radius:0;padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:0;box-shadow:unset}
    .sds-jurusan .sds-hero h2{margin:0 0 .25rem;font-size:1.25rem;font-weight:600;color:#334151}
    .sds-jurusan .sds-hero p{margin:0;color:#6c757d;font-size:.875rem}
    .sds-jurusan .sds-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
    .sds-jurusan .sds-card,.sds-jurusan .sds-stat-card{background:#fff;border:1px solid #dee2e6;border-radius:0;box-shadow:unset}
    .sds-jurusan .sds-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin-bottom:0}
    .sds-jurusan .sds-stat-card{padding:1rem;min-height:104px}
    .sds-jurusan .sds-stat-card small{display:block;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem}
    .sds-jurusan .sds-stat-card strong{display:block;font-size:1.55rem;line-height:1.1;margin-top:.25rem;color:#212529;font-weight:700}
    .sds-jurusan .sds-stat-card span{display:block;color:#6c757d;font-size:.78rem;margin-top:.25rem}
    .sds-jurusan .sds-card-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid #dee2e6;background:#f8f9fa}
    .sds-jurusan .sds-card-header h5{margin:0;font-weight:600;color:#334151;font-size:1rem}
    .sds-jurusan .sds-card-body{padding:1rem}
    .sds-jurusan .sds-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem}
    .sds-jurusan .sds-toolbar form{margin:0}
    .sds-jurusan .sds-toolbar .form-select{min-width:autopx}
    .sds-jurusan .sds-table-wrap{overflow:auto}
    .sds-jurusan .sds-table{width:100%;border-collapse:collapse;background:#fff;min-width:980px;border: 1px solid #eee;}
    .sds-jurusan .sds-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;background:#f8f9fa;padding:.55rem .65rem;border-bottom:1px solid #dee2e6;white-space:nowrap}
    .sds-jurusan .sds-table td{padding:.5rem .65rem;border-bottom:1px solid #edf1f5;vertical-align:middle;color:#334151}
    .sds-jurusan .sds-table tfoot th{background:#f8f9fa;border-top:1px solid #dee2e6;border-bottom:0;color:#334151}
    .sds-jurusan .sds-table tr:last-child td{border-bottom:0}
    .sds-jurusan .sds-mini{font-size:.78rem;color:#6c757d}
    .sds-jurusan .sds-code{display:inline-flex;align-items:center;border:1px solid #dee2e6;background:#f8f9fa;border-radius:.25rem;padding:.25rem .45rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:.78rem;color:#334151;white-space:nowrap}
    .sds-jurusan .sds-badge{display:inline-flex;align-items:center;gap:5px;border-radius:.25rem;padding:.35rem .5rem;font-size:.75rem;font-weight:600;white-space:nowrap}
    .sds-jurusan .sds-badge.ok{background:#d1e7dd;color:#0f5132}
    .sds-jurusan .sds-badge.warn{background:#fff3cd;color:#664d03}
    .sds-jurusan .sds-badge.info{background:#cff4fc;color:#055160}
    .sds-jurusan .sds-actions{display:flex;align-items:center;gap:.35rem;justify-content:flex-end;white-space:nowrap}
    .sds-jurusan .sds-actions .btn{padding:.28rem .55rem;font-size:.78rem}
    .sds-jurusan .alert{margin:1rem 1rem 0}
    @media(max-width:1200px){.sds-jurusan .sds-stats{grid-template-columns:repeat(2,1fr)}.sds-jurusan .sds-hero{display:block}.sds-jurusan .sds-hero-actions{justify-content:flex-start;margin-top:.75rem}}
    @media(max-width:700px){.sds-jurusan{padding:0 6px}.sds-jurusan .sds-stats{grid-template-columns:1fr}.sds-jurusan .sds-toolbar{display:block}.sds-jurusan .sds-toolbar form{margin-top:.75rem}.sds-jurusan .sds-hero-actions .btn{width:100%}.sds-jurusan .sds-toolbar .form-select,.sds-jurusan .sds-toolbar .btn{width:100%}}
</style>

<div class="sds-jurusan">
    <div class="sds-hero">
        <div>
            <h2>Kompetensi Keahlian</h2>
            <p>Data jurusan/spektrum, pengaturan NIS/NIPD, jumlah kelas, dan jumlah peserta didik tahun ajaran <?= jur_e($tahunAjaran) ?>.</p>
        </div>
        <div class="sds-hero-actions">
            <a href="#" onclick="bukaModalTambahJurusan('<?= jur_e($tahunAjaran) ?>')" class="btn btn-primary">
                <i class="align-middle" data-feather="plus"></i> Tambah Jurusan
            </a>
            <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSalinJurusan">
                <i class="align-middle" data-feather="copy"></i> Salin Data Periodik
            </a>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card">
            <small>Total Jurusan</small>
            <strong><?= jur_int(count($rowsJurusan)) ?></strong>
            <span>Tahun ajaran <?= jur_e($tahunAjaran) ?></span>
        </div>
        <div class="sds-stat-card">
            <small>Total Kelas</small>
            <strong><?= jur_int($totalKelas) ?></strong>
            <span>Rombel dari semua jurusan</span>
        </div>
        <div class="sds-stat-card">
            <small>Total PD Aktif</small>
            <strong><?= jur_int($totalSiswa) ?></strong>
            <span>Sesuai rombel tahun ajaran</span>
        </div>
        <div class="sds-stat-card">
            <small>Status NIS</small>
            <strong><?= jur_int($totalSudahGenerate) ?></strong>
            <span><?= jur_int($totalBelumGenerate) ?> jurusan belum generate</span>
        </div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Kompetensi Keahlian</h5>
            <span class="sds-mini">Urut berdasarkan kode spektrum</span>
        </div>

        <?php
        if (isset($_SESSION['penyalinan_status'])) {
            echo $_SESSION['penyalinan_status'];
            unset($_SESSION['penyalinan_status']);
        }
        ?>
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
                <div class="alert-message"><?= $_SESSION['success'] ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-mini">
                    Menampilkan <strong><?= jur_int(count($rowsJurusan)) ?></strong> jurusan untuk tahun ajaran aktif/filter.
                </div>
                <form method="get" action="jurusan" class="d-flex align-items-center gap-2">
                    <select class="form-select" name="tahun" id="tahun">
                        <?php
                        $tahunList = $conn->query("SELECT DISTINCT tahun_ajaran FROM jurusan ORDER BY tahun_ajaran DESC");
                        while ($tahun = $tahunList->fetch_assoc()):
                            $selected = ($tahun['tahun_ajaran'] === $tahunAjaran) ? 'selected' : '';
                        ?>
                            <option value="<?= jur_e($tahun['tahun_ajaran']) ?>" <?= $selected ?>>
                                <?= jur_e($tahun['tahun_ajaran']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-success">Tampilkan</button>
                </form>
            </div>

            <div class="sds-table-wrap">
                <table class="sds-table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Kode Jurusan / Spektrum</th>
                            <th>Nama Konli</th>
                            <th>Nomor NIS Terakhir</th>
                            <th class="text-center">JML. Kelas</th>
                            <th class="text-center">JML. PD</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rowsJurusan)):
                            $no = 1;
                            foreach ($rowsJurusan as $row):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="sds-code"><?= jur_e($row['kode_jurusan']) ?></span></td>
                                <td>
                                    <strong><?= jur_e($row['nama_jurusan']) ?></strong>
                                    <div class="sds-mini">Tahun ajaran <?= jur_e($row['tahun_ajaran']) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($row['nis_terakhir'])): ?>
                                        <span class="sds-code"><?= jur_e((string)$row['nis_terakhir']) ?></span>
                                    <?php else: ?>
                                        <span class="sds-badge warn">Belum generate</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><strong><?= jur_int($row['jumlah_kelas']) ?></strong></td>
                                <td class="text-center">
                                    <a href="kuota_kelas_siswa?jurusan_id=<?= (int)$row['id'] ?>&tahun=<?= urlencode($row['tahun_ajaran']) ?>" class="sds-badge info text-decoration-none" title="Lihat daftar siswa jurusan <?= jur_e($row['nama_jurusan']) ?>">
                                        <?= jur_int($row['jumlah_siswa']) ?> siswa
                                    </a>
                                </td>
                                <td>
                                    <div class="sds-actions">
                                        <button type="button" class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalAturNIPD"
                                            data-jurusan-id="<?= (int)$row['id'] ?>"
                                            data-tahun-ajaran="<?= jur_e($row['tahun_ajaran']) ?>"
                                            data-kode-jurusan="<?= jur_e((string)$row['kode_jurusan']) ?>"
                                            data-nama-jurusan="<?= jur_e($row['nama_jurusan']) ?>"
                                            data-kode-depan="<?= (int)($row['kode_depan'] ?? 0) ?>"
                                            data-urutan-awal="<?= (int)($row['urutan_awal'] ?? 0) ?>"
                                            data-kode-akhir="<?= (int)($row['kode_akhir'] ?? 0) ?>"
                                            data-urutan-akhir="<?= (int)($row['urutan_akhir'] ?? 0) ?>">
                                            Set NIPD
                                        </button>
                                        <a href="#" class="btn btn-primary btn-sm btn-edit-jurusan"
                                            data-bs-toggle="modal" data-bs-target="#modalEditJurusan"
                                            data-id="<?= (int)$row['id'] ?>"
                                            data-kode="<?= jur_e($row['kode_jurusan']) ?>"
                                            data-nama="<?= jur_e($row['nama_jurusan']) ?>"
                                            data-tahun="<?= jur_e($row['tahun_ajaran']) ?>">
                                            Edit
                                        </a>
                                        <form method="post" action="jurusan_hapus" class="d-inline" onsubmit="return confirm('Yakin hapus?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button type="submit" class="btn btn-danger btn-sm">Hapus</button></form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada data jurusan untuk tahun ajaran ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">TOTAL</th>
                            <th class="text-center"><?= jur_int($totalKelas) ?></th>
                            <th class="text-center"><?= jur_int($totalSiswa) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal Edit Jurusan -->
<div class="modal fade" id="modalEditJurusan" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="pages/jurusan_edit.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Jurusan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="tahun_ajaran" id="edit-tahun_ajaran">

                    <div class="mb-3">
                        <label for="edit-kode" class="form-label">Kode Jurusan / Spektrum</label>
                        <input type="text" class="form-control" name="kode_jurusan" id="edit-kode" required>
                        <div class="form-text">Kode ini menjadi bagian setelah titik pada NIS/NIPD dan boleh memakai kode Spektrum. Contoh RPL: 4.1.1, BD: 8.1.1, MP: 8.2.1, LP: 8.3.1, AK: 8.3.3.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-nama" class="form-label">Nama Jurusan</label>
                        <input type="text" class="form-control" name="nama_jurusan" id="edit-nama" required>
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

<!-- Modal Tambah Jurusan -->
<div class="modal fade" id="modalTambahJurusan" tabindex="-1" aria-labelledby="modalTambahJurusanLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="index?page=jurusan_tambah">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahJurusanLabel">Tambah Jurusan <strong>Tahun Ajaran: <?= htmlspecialchars($tahunAjaran) ?></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="tahun_ajaran" id="inputTahunAjaranJurusan">

                <div class="mb-3">
                    <label for="kode_jurusan" class="form-label">Kode Jurusan / Spektrum</label>
                    <input type="text" class="form-control" name="kode_jurusan" id="kode_jurusan" inputmode="decimal" pattern="[0-9]+([.][0-9]+)*" placeholder="Contoh: 8.2.1" required>
                    <div class="form-text">Kode ini menjadi bagian setelah titik pada NIS/NIPD dan boleh memakai kode Spektrum. Contoh RPL: 4.1.1, BD: 8.1.1, MP: 8.2.1, LP: 8.3.1, AK: 8.3.3.</div>
                </div>

                <div class="mb-3">
                    <label for="nama_jurusan" class="form-label">Nama Jurusan</label>
                    <input type="text" class="form-control" name="nama_jurusan" id="nama_jurusan" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Atur NIPD -->
<div class="modal fade" id="modalAturNIPD" tabindex="-1" aria-labelledby="modalAturNIPDLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="index?page=simpan_pengaturan_nipd" id="formPengaturanNIPD">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAturNIPDLabel">Pengaturan NIPD Jurusan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="jurusan_id" id="inputJurusanId">
                <input type="hidden" name="tahun_ajaran" id="inputTahunAjaranNIPD">

                <div class="alert alert-info small">
                    Format NIS/NIPD: <strong>nomor_global/nomor_tengah.kode_jurusan_spektrum</strong>.<br>
                    Contoh: <strong>16721/1303.8.2.1</strong>, maka <strong>8.2.1</strong> adalah Kode Jurusan/Spektrum, sedangkan <strong>1303</strong> adalah Nomor Tengah Awal yang bisa diset per jurusan.
                </div>

                <div class="alert alert-warning small">
                    <strong>Catatan:</strong> Nomor Tengah Awal adalah angka patokan manual yang akan dipakai saat generate berikutnya dan tidak otomatis berubah setelah generate.
                    Nomor Tengah Terakhir hanya riwayat hasil generate sebelumnya dan tidak otomatis menjadi angka awal baru.
                    Setelah mengubah Nomor Tengah Awal, lakukan <strong>Reset NIS Kelas X</strong> lalu <strong>Generate NIS Kelas X</strong> agar NIS siswa mengikuti pengaturan baru.
                </div>

                <div class="mb-3">
                    <label class="form-label">Jurusan</label>
                    <input type="text" class="form-control" id="displayJurusanNIPD" value="" readonly>
                </div>

                <div class="mb-3">
                    <label for="kode_depan" class="form-label">Nomor Global Awal / Contoh Depan</label>
                    <input type="number" class="form-control" name="kode_depan" id="kode_depan" value="16712" min="1" required>
                    <div class="form-text">Contoh bagian depan: <strong>16712</strong>/1303.8.2.1. Saat generate massal, nomor global utama tetap diisi dari modal Generate.</div>
                </div>

                <div class="mb-3">
                    <label for="urutan_awal" class="form-label">Nomor Tengah Awal Jurusan <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="urutan_awal" id="urutan_awal" value="1303" min="1" required>
                    <div class="form-text">Ini angka patokan manual untuk nomor tengah saat generate berikutnya. Generate hanya mengubah Nomor Tengah Terakhir, bukan angka ini. Contoh: 16712/<strong>1303</strong>.8.2.1.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="kode_akhir_display" class="form-label">Nomor Global Terakhir</label>
                        <input type="number" class="form-control" id="kode_akhir_display" value="0" min="0" readonly>
                        <div class="form-text">Riwayat hasil generate terakhir. Tidak ikut disimpan manual.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="urutan_akhir_display" class="form-label">Nomor Tengah Terakhir</label>
                        <input type="number" class="form-control" id="urutan_akhir_display" value="0" min="0" readonly>
                        <div class="form-text">Riwayat hasil generate terakhir. Tidak menjadi Nomor Tengah Awal baru dan tidak ikut disimpan manual.</div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" name="reset_hasil_generate" id="reset_hasil_generate">
                    <label class="form-check-label" for="reset_hasil_generate">
                        Kosongkan riwayat nomor terakhir hasil generate lama
                    </label>
                    <div class="form-text">Centang ini jika Nomor Tengah Terakhir masih hasil generate sebelum memakai Spektrum. Ini hanya mereset tampilan riwayat, bukan menghapus NIS siswa.</div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalAturNIPD');
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const jurusanId = button.getAttribute('data-jurusan-id') || '';
            const tahunAjaran = button.getAttribute('data-tahun-ajaran') || '';
            const kodeJurusan = button.getAttribute('data-kode-jurusan') || '';
            const namaJurusan = button.getAttribute('data-nama-jurusan') || '';
            const kodeDepan = parseInt(button.getAttribute('data-kode-depan')) || 16712;
            const urutanAwal = parseInt(button.getAttribute('data-urutan-awal')) || 1303;
            const kodeAkhir = parseInt(button.getAttribute('data-kode-akhir')) || 0;
            const urutanAkhir = parseInt(button.getAttribute('data-urutan-akhir')) || 0;

            document.getElementById('inputJurusanId').value = jurusanId;
            document.getElementById('inputTahunAjaranNIPD').value = tahunAjaran;
            document.getElementById('displayJurusanNIPD').value = namaJurusan + ' / Kode Spektrum: ' + kodeJurusan;
            document.getElementById('kode_depan').value = kodeDepan;
            document.getElementById('urutan_awal').value = urutanAwal;
            document.getElementById('kode_akhir_display').value = kodeAkhir;
            document.getElementById('urutan_akhir_display').value = urutanAkhir;
            const resetCheckbox = document.getElementById('reset_hasil_generate');
            if (resetCheckbox) resetCheckbox.checked = false;
        });
    });
</script>

<!-- <script>
    function bukaModalAturNIPD(jurusanId, tahunAjaran) {
        document.getElementById('inputJurusanId').value = jurusanId;
        document.getElementById('inputTahunAjaranNIPD').value = tahunAjaran;

        // Panggil modal Bootstrap
        var myModal = new bootstrap.Modal(document.getElementById('modalAturNIPD'));
        myModal.show();
    }
</script> -->


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const editButtons = document.querySelectorAll(".btn-edit-jurusan");

        editButtons.forEach(button => {
            button.addEventListener("click", function() {
                document.getElementById("edit-id").value = this.dataset.id;
                document.getElementById("edit-kode").value = this.dataset.kode;
                document.getElementById("edit-nama").value = this.dataset.nama;
                document.getElementById("edit-tahun_ajaran").value = this.dataset.tahun;
            });
        });
    });
</script>


<script>
    function bukaModalTambahJurusan(tahunAjaran) {
        document.getElementById('inputTahunAjaranJurusan').value = tahunAjaran;

        // Panggil modal Bootstrap
        var myModal = new bootstrap.Modal(document.getElementById('modalTambahJurusan'));
        myModal.show();
    }
</script>


<!-- Modal Salin Jurusan -->
<div class="modal fade" id="modalSalinJurusan" tabindex="-1" aria-labelledby="modalSalinJurusanLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post" onsubmit="return confirm('Yakin ingin menyalin semua data jurusan ke tahun baru?')">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSalinJurusanLabel">Salin Data Ekstrakurikuler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tahun_asal" class="form-label">Tahun Ajaran Asal:</label>
                        <select name="tahun_asal" id="tahun_asal" class="form-select" required>
                            <option value="">-- Pilih Tahun Asal --</option>
                            <?php
                            $tahun = $conn->query("SELECT DISTINCT tahun_ajaran FROM jurusan ORDER BY tahun_ajaran DESC");
                            while ($row = $tahun->fetch_assoc()) {
                                echo "<option value='{$row['tahun_ajaran']}'>{$row['tahun_ajaran']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="tahun_baru" value="<?= htmlspecialchars($tahunAjaran) ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="salin" class="btn btn-primary"><i class="align-middle" data-feather="copy"></i> Salin Data Jurusan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salin'])) {
    $tahun_asal = $_POST['tahun_asal'];
    $tahun_baru = $_POST['tahun_baru'];

    if ($tahun_asal === $tahun_baru) {
        $_SESSION['salin_status'] = "<div class='alert alert-warning'>Tahun asal dan tahun baru tidak boleh sama.</div>";
        header("Location: jurusan");
        exit;
    }

    $copiedJurusan = 0;

    // Ambil semua jurusan dari tahun asal
    $qJurusanAsal = $conn->query("SELECT * FROM jurusan WHERE tahun_ajaran = '$tahun_asal'");
    $mapJurusanBaru = [];

    while ($jur = $qJurusanAsal->fetch_assoc()) {
        $nama = $conn->real_escape_string($jur['nama_jurusan']);
        $kode = $conn->real_escape_string($jur['kode_jurusan']);

        $cek = $conn->query("SELECT id FROM jurusan WHERE nama_jurusan = '$nama' AND kode_jurusan = '$kode' AND tahun_ajaran = '$tahun_baru'");
        if ($cek->num_rows == 0) {
            $conn->query("INSERT INTO jurusan (nama_jurusan, kode_jurusan, tahun_ajaran) VALUES ('$nama', '$kode', '$tahun_baru')");
            $id_baru = $conn->insert_id;
            $copiedJurusan++;
        } else {
            $id_baru = $cek->fetch_assoc()['id'];
        }

        $mapJurusanBaru[$nama] = $id_baru;

        // Salin pengaturan NIPD jika ada
        $qAturNIPD = $conn->query("SELECT * FROM pengaturan_nipd WHERE jurusan_id = '{$jur['id']}' AND tahun_ajaran = '$tahun_asal'");
        if ($qAturNIPD->num_rows > 0) {
            $atur = $qAturNIPD->fetch_assoc();

            // Cek apakah sudah ada di tahun baru
            $stmtCek = $conn->prepare("SELECT COUNT(*) FROM pengaturan_nipd WHERE jurusan_id = ? AND tahun_ajaran = ?");
            $stmtCek->bind_param("is", $id_baru, $tahun_baru);
            $stmtCek->execute();
            $stmtCek->bind_result($jumlahAda);
            $stmtCek->fetch();
            $stmtCek->close();

            if ($jumlahAda == 0) {
                $kode_depan    = (int)$atur['kode_akhir'] + 1;      // lanjut dari akhir tahun lama
                $urutan_awal   = (int)$atur['urutan_akhir'] + 1;    // lanjut dari akhir tahun lama
                $kode_akhir    = (int)$atur['kode_akhir'] + 1;      // disamakan dulu
                $urutan_akhir  = (int)$atur['urutan_akhir'] + 1;    // disamakan dulu

                // Debug log
                // echo "Salin NIPD untuk jurusan_id $id_baru: depan=$kode_depan, awal=$urutan_awal, akhir=$kode_akhir, akhir_urutan=$urutan_akhir<br>";

                $stmt = $conn->prepare("INSERT INTO pengaturan_nipd 
                    (jurusan_id, tahun_ajaran, kode_depan, urutan_awal, kode_akhir, urutan_akhir)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiii", $id_baru, $tahun_baru, $kode_depan, $urutan_awal, $kode_akhir, $urutan_akhir);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $_SESSION['penyalinan_status'] =
        "<div class='alert alert-success alert-dismissible'>
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        <div class='alert-message'>
            ✅ Penyalinan selesai!<br>
            📁 Jurusan Telah disalin: <strong>$copiedJurusan</strong><br>
        </div>
    </div>";
    header("Location: jurusan");
    exit;
}
?>
