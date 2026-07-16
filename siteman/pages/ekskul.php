<?php
// // Ambil tahun ajaran dari GET atau tentukan default otomatis
// if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
//     $tahunAjaran = $_GET['tahun'];
// } else {
//     $result = $conn->query("SELECT DISTINCT tahun_ajaran FROM ekstrakurikuler ORDER BY tahun_ajaran DESC LIMIT 1");
//     if ($result && $result->num_rows > 0) {
//         $row = $result->fetch_assoc();
//         $tahunAjaran = $row['tahun_ajaran'];
//     } else {
//         // Jika database kosong, fallback ke tahun ajaran berdasarkan tanggal saat ini
//         $tahunSekarang = date("Y");
//         $bulanSekarang = date("n");
//         $tahunAjaran = ($bulanSekarang >= 7) ? "$tahunSekarang/" . ($tahunSekarang + 1) : ($tahunSekarang - 1) . "/$tahunSekarang";
//     }
// }

// Ambil tahun ajaran dari GET atau tentukan default otomatis
// if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
//     $tahunAjaran = $_GET['tahun'];
// } else {
//     $tahunSekarang = date("Y");
//     $bulanSekarang = date("n");
//     $tahunAjaran = ($bulanSekarang >= 7) ? "$tahunSekarang/" . ($tahunSekarang + 1) : ($tahunSekarang - 1) . "/$tahunSekarang";
// }


// Jalankan query untuk ambil data jurusan sesuai tahun ajaran
$result = $conn->prepare("
    SELECT * FROM ekstrakurikuler 
    WHERE tahun_ajaran = ?
    ORDER BY nama_ekskul ASC
");
$result->bind_param("s", $tahunAjaran);
$result->execute();
$data = $result->get_result();

if (!$result) {
    die("Query Error: " . $conn->error);
}

$totalEkskul = 0;
$totalAnggotaEkskul = 0;
$statEkskul = $conn->prepare("
    SELECT COUNT(DISTINCT e.id) AS total_ekskul, COUNT(es.id) AS total_anggota
    FROM ekstrakurikuler e
    LEFT JOIN ekstrakurikuler_siswa es ON es.ekstrakurikuler_id = e.id
    WHERE e.tahun_ajaran = ?
");
if ($statEkskul) {
    $statEkskul->bind_param("s", $tahunAjaran);
    $statEkskul->execute();
    $statRow = $statEkskul->get_result()->fetch_assoc();
    $totalEkskul = (int)($statRow['total_ekskul'] ?? 0);
    $totalAnggotaEkskul = (int)($statRow['total_anggota'] ?? 0);
    $statEkskul->close();
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

<div class="sds-dashboard-ref sds-ekskul-page">
    <div class="sds-hero">
        <div>
            <h2>Ekstrakurikuler</h2>
            <p>Kelola data ekstrakurikuler, pembina, dan anggota periodik tahun ajaran <?= htmlspecialchars($tahunAjaran) ?>.</p>
        </div>
        <div class="sds-hero-actions">
            <a href="#" onclick="bukaModalTambahEkskul('<?= htmlspecialchars($tahunAjaran) ?>')" class="btn btn-primary">Tambah Ekstrakurikuler</a>
            <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSalinEkskul"><i class="align-middle" data-feather="copy"></i> Salin Data Periodik</a>
        </div>
    </div>

    <div class="sds-stats three">
        <div class="sds-stat-card"><small>Total Ekskul</small><strong><?= number_format($totalEkskul, 0, ',', '.') ?></strong><span>Tahun ajaran <?= htmlspecialchars($tahunAjaran) ?></span></div>
        <div class="sds-stat-card"><small>Total Anggota</small><strong><?= number_format($totalAnggotaEkskul, 0, ',', '.') ?></strong><span>Relasi siswa-ekskul aktif di data</span></div>
        <div class="sds-stat-card"><small>Tahun Ajaran</small><strong style="font-size:1.2rem;"><?= htmlspecialchars($tahunAjaran) ?></strong><span>Filter tampilan saat ini</span></div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Ekstrakurikuler</h5>
            <span class="sds-mini">Tahun <?= htmlspecialchars($tahunAjaran) ?></span>
        </div>

        <?php
        if (isset($_SESSION['penyalinan_status'])) {
            echo '<div class="m-3 mb-0">' . $_SESSION['penyalinan_status'] . '</div>';
            echo "<script>setTimeout(loadEkskulTabel, 3000);</script>";
            unset($_SESSION['penyalinan_status']);
        }
        ?>
        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= $_SESSION['msg']['type'] ?> alert-dismissible">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message"><?= $_SESSION['msg']['text'] ?></div>
            </div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>
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
                <div class="sds-mini">Data dimuat otomatis sesuai tahun ajaran yang dipilih.</div>
                <form method="get" action="ekskul" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="page" value="ekskul">
                    <select class="form-select" name="tahun" id="tahun_ajaran" onchange="loadEkskulTabel()">
                        <?php
                        $tahunList = $conn->query("SELECT DISTINCT tahun_ajaran FROM ekstrakurikuler ORDER BY tahun_ajaran DESC");
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
                            <th>Nama Ekstrakurikuler</th>
                            <th class="text-center">Anggota</th>
                            <th>Pembina</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-ekskul"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">TOTAL</th>
                            <th class="text-center"><?= number_format($totalAnggotaEkskul, 0, ',', '.') ?></th>
                            <th colspan="2" class="text-end"><?= number_format($totalEkskul, 0, ',', '.') ?> ekstrakurikuler</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <script>
                function loadEkskulTabel() {
                    const tahun = document.getElementById('tahun_ajaran').value || '';
                    fetch('ekskul_tabel.php?tahun=' + encodeURIComponent(tahun))
                        .then(res => res.text())
                        .then(html => {
                            document.getElementById('tabel-ekskul').innerHTML = html;
                        });
                }
                document.addEventListener('DOMContentLoaded', function() {
                    loadEkskulTabel();
                });
            </script>
        </div>
    </div>
</div>

<!-- Modal Edit Ekskul -->

<div class="modal fade" id="modalEditEkskul" tabindex="-1" aria-labelledby="modalEditEkskulLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="index?page=ekskul_edit">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditEkskulLabel">Edit Ekstrakurikuler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id-ekskul">
                    <div class="mb-3"><label for="edit-nama-ekskul">Nama Ekstrakurikuler:</label>
                        <input type="text" name="nama_ekskul" id="edit-nama-ekskul" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Nama Pembina Lama:</label>
                        <input type="text" name="nama_pembina" id="nama-pembina" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const modalEditEkskul = document.getElementById('modalEditEkskul');
    if (modalEditEkskul) {
        modalEditEkskul.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-id-ekskul').value = button.getAttribute('data-id');
            document.getElementById('edit-nama-ekskul').value = button.getAttribute('data-nama');
            document.getElementById('nama-pembina').value = button.getAttribute('data-pembina');
        });
    }
</script>

<!-- Modal Tambah Ekskul -->
<div class="modal fade" id="modalTambahEkskul" tabindex="-1" aria-labelledby="modalTambahEkskulLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="ekskul_tambah">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahEkskulLabel">Tambah Ekstrakurikuler <strong><?= htmlspecialchars($tahunAjaran) ?></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="tahun_ajaran" id="inputTahunAjaranEkskul">

                <div class="mb-3">
                    <label for="nama_ekskul" class="form-label">Nama Ekstrakurikuler</label>
                    <input type="text" class="form-control" name="nama_ekskul" id="nama_ekskul" required>
                </div>

                <div class="mb-3">
                    <label for="nama_pembina" class="form-label">Nama Pembina Ekstrakurikuler</label>
                    <input type="text" class="form-control" name="nama_pembina" id="nama_pembina" required>
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
    function bukaModalTambahEkskul(tahunAjaran) {
        document.getElementById('inputTahunAjaranEkskul').value = tahunAjaran;
        const modal = new bootstrap.Modal(document.getElementById('modalTambahEkskul'));
        modal.show();
    }
</script>

<!-- Modal Salin Ekskul -->
<div class="modal fade" id="modalSalinEkskul" tabindex="-1" aria-labelledby="modalSalinEkskulLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post" onsubmit="return confirm('Yakin ingin menyalin semua data ekstrakurikuler dan siswanya ke tahun baru?')">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSalinEkskulLabel">Salin Data Ekstrakurikuler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tahun_asal" class="form-label">Tahun Ajaran Asal:</label>
                        <select name="tahun_asal" id="tahun_asal" class="form-select" required>
                            <option value="">-- Pilih Tahun Asal --</option>
                            <?php
                            $tahun = $conn->query("SELECT DISTINCT tahun_ajaran FROM ekstrakurikuler ORDER BY tahun_ajaran DESC");
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
                    <button type="submit" name="salin" class="btn btn-primary"><i class="align-middle" data-feather="copy"></i> Salin Data</button>
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
        header("Location: ekskul");
        exit;
    }

    $copiedEkskul = 0;
    $copiedRelasi = 0;

    // 1. Salin data ekstrakurikuler dari tahun lama ke tahun baru
    $qEkskulAsal = $conn->query("SELECT * FROM ekstrakurikuler WHERE tahun_ajaran = '$tahun_asal'");
    $mapEkskulBaru = [];

    while ($eks = $qEkskulAsal->fetch_assoc()) {
        $nama = $conn->real_escape_string($eks['nama_ekskul']);
        $nama_pembina = $conn->real_escape_string($eks['nama_pembina']);

        $cek = $conn->query("SELECT id FROM ekstrakurikuler WHERE nama_ekskul = '$nama' AND nama_pembina = '$nama_pembina' AND tahun_ajaran = '$tahun_baru'");
        if ($cek->num_rows == 0) {
            $conn->query("INSERT INTO ekstrakurikuler (nama_ekskul, nama_pembina, tahun_ajaran) VALUES ('$nama', '$nama_pembina', '$tahun_baru')");
            $id_baru = $conn->insert_id;
            $copiedEkskul++;
        } else {
            $id_baru = $cek->fetch_assoc()['id'];
        }

        $mapEkskulBaru[$nama] = $id_baru;
    }

    // 2. Salin relasi siswa-ekskul ke tahun baru
    $qRelasi = $conn->query("
        SELECT es.siswa_id, e.nama_ekskul
        FROM ekstrakurikuler_siswa es
        JOIN ekstrakurikuler e ON e.id = es.ekstrakurikuler_id
        WHERE e.tahun_ajaran = '$tahun_asal'
    ");

    while ($relasi = $qRelasi->fetch_assoc()) {
        $siswa_id = $relasi['siswa_id'];
        $nama_ekskul = $relasi['nama_ekskul'];

        if (!isset($mapEkskulBaru[$nama_ekskul])) continue;

        $id_ekskul_baru = $mapEkskulBaru[$nama_ekskul];

        // Cek apakah relasi sudah ada
        $cekRelasi = $conn->query("SELECT id FROM ekstrakurikuler_siswa WHERE siswa_id = '$siswa_id' AND ekstrakurikuler_id = '$id_ekskul_baru'");
        if ($cekRelasi->num_rows == 0) {
            $conn->query("INSERT INTO ekstrakurikuler_siswa (ekstrakurikuler_id, siswa_id) VALUES ('$id_ekskul_baru', '$siswa_id')");
            $copiedRelasi++;
        }
    }

    $_SESSION['penyalinan_status'] =
        "<div class='alert alert-success alert-dismissible'>
    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    <div class='alert-message'>
        ✅ Penyalinan selesai!<br>
        📁 Ekskul baru ditambahkan: <strong>$copiedEkskul</strong><br>
        👥 Relasi siswa-ekskul ditambahkan: <strong>$copiedRelasi</strong>
        </div>
    </div>";
    header("Location: ekskul");
    exit;
}
?>