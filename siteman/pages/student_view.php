<?php
// ➜ Validasi parameter id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'ID siswa tidak valid!';
    header('Location: students');
    exit;
}

// Tahun ajaran aktif dan sebelumnya berasal dari master Tahun Ajaran SDS.
$tahunAjaranAktif = (string)$tahunAjaran;
$tahunAjaranLama = sds_academic_year_previous_label($tahunAjaranAktif);

// =======================
// AMBIL DATA SISWA + KELAS AKTIF (siswa_kelas)
// =======================
$stmt = $conn->prepare("
  SELECT
    p.*,
    COALESCE(kaktif.nama_kelas, kps.nama_kelas) AS nama_kelas,
    COALESCE(tkaktif.nama_tingkat, tkps.nama_tingkat) AS nama_tingkat,
    COALESCE(kaktif.id, kps.id) AS kelas_id_tampil
  FROM pendaftaran_siswa p
  LEFT JOIN siswa_kelas sk
    ON sk.siswa_id = p.id
   AND sk.tahun_ajaran = ?
  LEFT JOIN kelas kaktif ON kaktif.id = sk.kelas_id
  LEFT JOIN tingkat_kelas tkaktif ON tkaktif.id = kaktif.tingkat_id

  LEFT JOIN kelas kps ON kps.id = p.kelas_id
  LEFT JOIN tingkat_kelas tkps ON tkps.id = kps.tingkat_id

  WHERE p.id = ?
  LIMIT 1
");
$stmt->bind_param('si', $tahunAjaranAktif, $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$student) {
    $_SESSION['error'] = 'Data siswa tidak ditemukan!';
    header('Location: students');
    exit;
}


// =======================
// PASTIKAN KELAS YANG TAMPIL ADALAH KELAS AKTIF TAHUN AJARAN SEKARANG
// =======================
// Catatan:
// - pendaftaran_siswa.kelas_id bisa berisi kelas awal / kelas sebelum naik kelas.
// - Setelah fitur naik kelas berjalan, kelas aktif harus dibaca dari siswa_kelas
//   berdasarkan tahun ajaran aktif.
$stmtAktif = $conn->prepare("
    SELECT
        sk.kelas_id,
        k.nama_kelas,
        tk.nama_tingkat,
        j.id AS jurusan_id_aktif,
        j.nama_jurusan AS nama_jurusan_aktif,
        sk.naik_kelas
    FROM siswa_kelas sk
    JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    WHERE sk.siswa_id = ?
      AND sk.tahun_ajaran = ?
      AND k.tahun_ajaran = ?
    ORDER BY sk.naik_kelas DESC, sk.id DESC
    LIMIT 1
");
if ($stmtAktif) {
    $stmtAktif->bind_param('iss', $id, $tahunAjaranAktif, $tahunAjaranAktif);
    $stmtAktif->execute();
    $kelasAktif = $stmtAktif->get_result()->fetch_assoc();
    $stmtAktif->close();

    if ($kelasAktif) {
        $student['kelas_id'] = (int) $kelasAktif['kelas_id'];
        $student['kelas_id_tampil'] = (int) $kelasAktif['kelas_id'];
        $student['nama_kelas'] = $kelasAktif['nama_kelas'];
        $student['nama_tingkat'] = $kelasAktif['nama_tingkat'];
        $student['jurusan_id_aktif'] = (int) ($kelasAktif['jurusan_id_aktif'] ?? 0);
        $student['nama_jurusan_aktif'] = $kelasAktif['nama_jurusan_aktif'] ?? '';
    }
}

// Pakai kelas aktif untuk tombol "Lihat Kelas" supaya konsisten dengan rombel
if (!empty($student['kelas_id_tampil'])) {
    $student['kelas_id'] = (int)$student['kelas_id_tampil'];
}

// Variabel yang sebelumnya diambil dari query duplikat → ambil dari $student saja
$tahunAjaran  = $tahunAjaranAktif; // untuk tampilan/partial, pakai tahun ajaran aktif
$provinsi_id  = $student['provinsi'];
$kota_id      = $student['kota'];
$kecamatan_id = $student['kecamatan'];
$desa_id      = $student['desa'];

// =======================
// AMBIL RIWAYAT KELAS
// =======================
$stmt2 = $conn->prepare("
    SELECT 
        sk.tahun_ajaran,
        k.id AS id,
        k.nama_kelas,
        tk.nama_tingkat,
        tk.urutan_tingkat,
        j.nama_jurusan,
        sk.naik_kelas
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN tingkat_kelas tk ON k.tingkat_id = tk.id
    JOIN jurusan j ON k.jurusan_id = j.id
    WHERE sk.siswa_id = ?
    ORDER BY sk.tahun_ajaran ASC
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$student['riwayat_kelas'] = [];
while ($row = $result2->fetch_assoc()) {
    $student['riwayat_kelas'][] = $row;
}
$stmt2->close();

// =======================
// HITUNG STATUS ROMBEL YANG TAMPIL DI RIWAYAT
// =======================
// Catatan penting:
// sk.naik_kelas saja belum cukup untuk menentukan status pada riwayat.
// Contoh kasus: siswa tahun 2025/2026 X RPL 1, tahun 2026/2027 masih X RPL 1.
// Walaupun sk.naik_kelas bernilai 1 karena pernah dikoreksi/diatur kelas,
// secara riwayat siswa tersebut tetap harus ditampilkan sebagai "Tidak Naik"
// karena tingkat kelasnya tidak naik dari X ke XI.
$prevRombel = null;
foreach ($student['riwayat_kelas'] as $idx => $rk) {
    $statusRombel = '-';

    if ($prevRombel !== null) {
        $prevUrutan = isset($prevRombel['urutan_tingkat']) ? (int) $prevRombel['urutan_tingkat'] : 0;
        $currUrutan = isset($rk['urutan_tingkat']) ? (int) $rk['urutan_tingkat'] : 0;
        $naikFlag = isset($rk['naik_kelas']) ? (int) $rk['naik_kelas'] : 1;

        // Jika flag database menyatakan tidak naik, tampilkan Tidak Naik.
        if ($naikFlag !== 1) {
            $statusRombel = 'Tidak Naik';
        }
        // Jika tingkat kelas saat ini sama/lebih rendah dari tahun sebelumnya, berarti tidak naik.
        // Contoh: X RPL 1 (urutan 1) -> X RPL 1 (urutan 1) = Tidak Naik.
        elseif ($prevUrutan > 0 && $currUrutan > 0 && $currUrutan <= $prevUrutan) {
            $statusRombel = 'Tidak Naik';
        }
        // Jika urutan_tingkat tidak terbaca, fallback kasar dari awalan nama kelas.
        else {
            $mapTingkat = ['X' => 1, 'XI' => 2, 'XII' => 3];
            $prevNama = trim((string)($prevRombel['nama_tingkat'] ?: preg_replace('/\s+.*/', '', (string)($prevRombel['nama_kelas'] ?? ''))));
            $currNama = trim((string)($rk['nama_tingkat'] ?: preg_replace('/\s+.*/', '', (string)($rk['nama_kelas'] ?? ''))));
            $prevFallback = $mapTingkat[$prevNama] ?? 0;
            $currFallback = $mapTingkat[$currNama] ?? 0;

            if ($prevFallback > 0 && $currFallback > 0 && $currFallback <= $prevFallback) {
                $statusRombel = 'Tidak Naik';
            } else {
                $statusRombel = 'Naik';
            }
        }
    }

    $student['riwayat_kelas'][$idx]['status_rombel'] = $statusRombel;
    $prevRombel = $rk;
}

// =======================
// AMBIL EKSKUL
// =======================
$stmt3 = $conn->prepare("
    SELECT e.*, es.siswa_id 
    FROM ekstrakurikuler_siswa es
    JOIN ekstrakurikuler e ON e.id = es.ekstrakurikuler_id
    WHERE es.siswa_id = ?
    ORDER BY e.tahun_ajaran DESC, e.nama_ekskul ASC
");
$stmt3->bind_param("i", $id);
$stmt3->execute();
$resultEks = $stmt3->get_result();

$student['ekstrakurikuler'] = [];
while ($row = $resultEks->fetch_assoc()) {
    $row['terdaftar'] = true;
    $student['ekstrakurikuler'][] = $row;
}
$stmt3->close();

// Fungsi bantu untuk menampilkan teks jika kosong
function show($value)
{
    return $value !== '' && $value !== null ? htmlspecialchars((string)$value) : '-';
}
?>

<div class="topbar">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto d-sm-block">
                <h3><?= show($student['nama_lengkap']); ?>
                    <span class="d-none"><?= htmlspecialchars((string)$tahunAjaran) ?></span>
                    <label class="switch">
                        <input type="checkbox" onchange="toggleStatus(this, <?= (int)$student['id'] ?>)" <?= !empty($student['status_aktif']) ? 'checked' : '' ?>>
                        <span class="slider round" title="Status Siswa"></span>
                    </label>
                </h3>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $idPost = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                    $sudah_dapodik = isset($_POST['sudah_dapodik']) ? 1 : 0;

                    if ($idPost > 0) {
                        $stmtU = $conn->prepare("UPDATE pendaftaran_siswa SET sudah_dapodik = ? WHERE id = ?");
                        $stmtU->bind_param('ii', $sudah_dapodik, $idPost);
                        $stmtU->execute();
                        $stmtU->close();

                        // Simpan pesan ke session
                        if ($sudah_dapodik) {
                            $_SESSION['success'] = "✅ Peseta didik telah ditandai sebagai 'Sudah dimasukkan ke Dapodik'.";
                        } else {
                            $_SESSION['success'] = "ℹ️ Peserta didik telah ditandai sebagai 'Belum dimasukkan ke Dapodik'.";
                        }
                    }

                    // Redirect agar tidak double-submit
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
                ?>

                <!-- Form HTML -->
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?= (int)$student['id']; ?>">
                    <label>
                        <input type="checkbox"
                            name="sudah_dapodik"
                            value="1"
                            <?= !empty($student['sudah_dapodik']) ? 'checked' : '' ?>
                            onchange="setTimeout(() => this.form.submit(), 100);">
                        Centang jika sudah dimasukkan ke Dapodik
                    </label>
                    <button type="submit" class="btn btn-sm btn-success d-none">Simpan Status</button>
                </form>
            </div>

            <div class="col-auto ms-auto text-end d-none d-md-block">
                <a href="students" class="btn btn-primary">Daftar Siswa</a>
                <a href="kuota_kelas_siswa?kelas_id=<?= (int)$student['kelas_id'] ?>" class="btn btn-secondary">Lihat Kelas</a>
                <a href="student_pdf.php?id=<?= (int)$id ?>" class="btn btn-success">Unduh PDF</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-0" style="margin-top: 60px;">
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible mb-0" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-message">
                <?= $_SESSION['error'] ?>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible mb-0" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-message">
                <?= $_SESSION['success'] ?>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (empty($student['status_aktif'])): ?>
        <div style="background:#f8d7da; padding:10px; border-left:4px solid #dc3545;">
            <strong>Status Siswa:</strong> Tidak Aktif<br>
            <strong>Keterangan:</strong> <?= nl2br(htmlspecialchars((string)$student['alasan_nonaktif'])) ?>
        </div>
    <?php endif; ?>

    <!-- Sidebar tab (Desktop only) -->
    <div class="tab-wrapper d-none d-md-flex">
        <!-- Sidebar tab -->
        <div class="tab-sidebar bg-white">
            <div class="list-group list-group-flush" role="tablist">
                <a class="list-group-item list-group-item-action active" data-bs-toggle="tab" href="#account" role="tab">Data Peserta Didik</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#dataAyah" role="tab">Data Ayah</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#dataIbu" role="tab">Data Ibu</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#dataWali" role="tab">Data Wali</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#kesejahteraan" role="tab">Kesejahteraan</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#berkas" role="tab">Berkas / File</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#kehadiran" role="tab">Kehadiran</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#rfid" role="tab">Riwayat Transaksi</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#rombel" role="tab">Rombel</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#ekskul" role="tab">Ekstrakurikuler</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="tab" href="#pelanggaran" role="tab">Pelanggaran</a>
            </div>
        </div>

        <!-- Tab content -->
        <div class="tab-content-container">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="account" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/data_siswa.php'; ?>
                </div>
                <div class="tab-pane fade" id="dataAyah" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/data_ayah.php'; ?>
                </div>
                <div class="tab-pane fade" id="dataIbu" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/data_ibu.php'; ?>
                </div>
                <div class="tab-pane fade" id="dataWali" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/data_wali.php'; ?>
                </div>
                <div class="tab-pane fade" id="kesejahteraan" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/kesejahteraan_pd.php'; ?>
                </div>
                <div class="tab-pane fade" id="berkas" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/berkas_pd.php'; ?>
                </div>
                <div class="tab-pane fade" id="rfid" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/riwayat_rfid.php'; ?>
                </div>
                <div class="tab-pane fade" id="rombel" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/rombel_pd.php'; ?>
                </div>
                <div class="tab-pane fade" id="ekskul" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/ekskul_pd.php'; ?>
                </div>
                <div class="tab-pane fade" id="pelanggaran" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/pelanggaran_pd.php'; ?>
                </div>
                <div class="tab-pane fade" id="kehadiran" role="tabpanel">
                    <?php include __DIR__ . '/partials/biodata/kehadiran_pd.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Accordion (Mobile only) -->
    <div class="d-md-none col-12">
        <div class="accordion" id="accordionSiswa">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingAccount">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAccount" aria-expanded="true">
                        Data Siswa
                    </button>
                </h2>
                <div id="collapseAccount" class="accordion-collapse collapse" data-bs-parent="#accordionSiswa">
                    <?php include __DIR__ . '/partials/biodata/m_data_siswa.php'; ?>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingAyah">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAyah">
                        Data Ayah
                    </button>
                </h2>
                <div id="collapseAyah" class="accordion-collapse collapse" data-bs-parent="#accordionSiswa">
                    <div class="accordion-body">
                        <?php include __DIR__ . '/partials/biodata/data_ayah.php'; ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingIbu">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIbu">
                        Data Ibu
                    </button>
                </h2>
                <div id="collapseIbu" class="accordion-collapse collapse" data-bs-parent="#accordionSiswa">
                    <div class="accordion-body">
                        <?php include __DIR__ . '/partials/biodata/data_ibu.php'; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($student['nama_wali'])): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingWali">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWali">
                            Data Wali
                        </button>
                    </h2>
                    <div id="collapseWali" class="accordion-collapse collapse" data-bs-parent="#accordionSiswa">
                        <div class="accordion-body">
                            <?php include __DIR__ . '/partials/biodata/data_wali.php'; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingKesejahteraan">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKesejahteraan">
                        Kesejahteraan
                    </button>
                </h2>
                <div id="collapseKesejahteraan" class="accordion-collapse collapse" data-bs-parent="#accordionSiswa">
                    <div class="accordion-body">
                        <?php include __DIR__ . '/partials/biodata/kesejahteraan_pd.php'; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Input Alasan -->
<div id="modalNonAktif" style="display:none; position:fixed; top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:20px;border-radius:8px;width:400px;">
        <h3>Alasan Menonaktifkan Siswa</h3>
        <form id="formNonAktif">
            <input type="hidden" name="siswa_id" id="modalSiswaId" value="<?= (int)$student['id'] ?>">
            <textarea name="alasan" id="alasan" rows="4" style="width:100%;padding: 10px;"></textarea>
            <div style="margin-top:10px;text-align:right;">
                <button type="button" onclick="closeModal()" class="btn-warning">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const studentStatusCsrf = <?= json_encode(sds_csrf_token()) ?>;
    function toggleStatus(checkbox, siswaId) {
        if (!checkbox.checked) {
            // Jika dimatikan, tampilkan popup alasan
            document.getElementById('modalNonAktif').style.display = 'flex';
            document.getElementById('modalSiswaId').value = siswaId;
            checkbox.checked = true; // kembalikan dulu
        } else {
            // Jika diaktifkan ulang, langsung simpan
            fetch('index?page=update_status_siswa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `siswa_id=${siswaId}&status=1&csrf=${encodeURIComponent(studentStatusCsrf)}`
            }).then(() => location.reload());
        }
    }

    function closeModal() {
        document.getElementById('modalNonAktif').style.display = 'none';
    }

    document.getElementById('formNonAktif').onsubmit = function(e) {
        e.preventDefault();
        const siswaId = document.getElementById('modalSiswaId').value;
        const alasan = document.getElementById('alasan').value;

        fetch('index?page=update_status_siswa', {
            method: 'POST',
            headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `siswa_id=${siswaId}&status=0&alasan=${encodeURIComponent(alasan)}&csrf=${encodeURIComponent(studentStatusCsrf)}`
        }).then(() => location.reload());
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hash = window.location.hash;
        if (hash) {
            const tabTrigger = document.querySelector(`a[data-bs-toggle="tab"][href="${hash}"]`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        }
    });
</script>

<script>
  const id_siswa = <?= (int)$id ?>;
  let lastData = '';
  let timerRfid = null;

  function loadTransaksi() {
    const urlParams = new URLSearchParams(window.location.search);
    const bulan = urlParams.get('bulan') || '';
    const tahun = urlParams.get('tahun') || '';

    $.get('partials/biodata/transaksi_log.php', {
      id: id_siswa,
      bulan: bulan,
      tahun: tahun
    }, function (data) {
      const newHTML = (data || '').trim();
      if (lastData !== newHTML) {
        $('#transaksi-body').html(newHTML);

        const $firstRow = $('#transaksi-body tr').first();
        $firstRow.addClass('highlight-green');
        setTimeout(() => $firstRow.removeClass('highlight-green'), 3000);

        lastData = newHTML;
      }
    }).fail(function () {
      $('#transaksi-body').html('<tr><td colspan="4" class="text-danger text-center">Gagal memuat data.</td></tr>');
    });
  }

  function startRfidAutoRefresh() {
    if (timerRfid) return;
    loadTransaksi();
    timerRfid = setInterval(loadTransaksi, 5000);
  }

  function stopRfidAutoRefresh() {
    if (timerRfid) {
      clearInterval(timerRfid);
      timerRfid = null;
    }
  }

  $(document).ready(function () {
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
      const target = $(e.target).attr('href');
      if (target === '#rfid') startRfidAutoRefresh();
      else stopRfidAutoRefresh();
    });

    if (window.location.hash === '#rfid') {
      const trigger = document.querySelector('a[href="#rfid"]');
      if (trigger) new bootstrap.Tab(trigger).show();
      startRfidAutoRefresh();
    }
  });
</script>
