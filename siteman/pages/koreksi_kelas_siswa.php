<?php
// Halaman koreksi kelas khusus untuk siswa yang terdeteksi:
// 1) masih berstatus tidak naik kelas pada tahun ajaran aktif (naik_kelas = 0), atau
// 2) sudah terlanjur diatur kelasnya, sehingga tingkat kelas aktif tidak naik dibanding tahun sebelumnya.
$tahunAjaran = (string)($tahunAjaran ?? '');
$tahunAjaranSebelumnya = sds_academic_year_previous_label($tahunAjaran);

function kks_h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

$q = trim((string) ($_GET['q'] ?? ''));
$rows = [];
$like = '%' . $q . '%';

// Kandidat yang ditampilkan sengaja dibatasi agar tidak membingungkan:
// - current sk.naik_kelas = 0  -> belum selesai diatur / masih tidak naik
// - current sk.naik_kelas = 1 tetapi tingkat aktif <= tingkat tahun sebelumnya -> kemungkinan sudah terlanjur diatur ke kelas rendah/salah
$sql = "
    SELECT
        ps.id,
        ps.nama_lengkap,
        ps.nisn,
        ps.nipd,
        ps.jenis_kelamin,
        ps.status_aktif,
        sk.id AS siswa_kelas_id,
        sk.kelas_id,
        sk.naik_kelas,
        sk.tahun_ajaran,
        k.nama_kelas,
        tk.nama_tingkat,
        tk.urutan_tingkat,
        j.nama_jurusan,
        sk_prev.kelas_id AS kelas_id_sebelumnya,
        k_prev.nama_kelas AS nama_kelas_sebelumnya,
        tk_prev.nama_tingkat AS nama_tingkat_sebelumnya,
        tk_prev.urutan_tingkat AS urutan_tingkat_sebelumnya,
        j_prev.nama_jurusan AS nama_jurusan_sebelumnya,
        CASE
            WHEN sk.naik_kelas = 0 THEN 'Masih ditandai tidak naik kelas'
            WHEN sk.naik_kelas = 1 AND sk_prev.id IS NOT NULL AND tk.urutan_tingkat <= tk_prev.urutan_tingkat THEN 'Sudah diatur, tetapi tingkat aktif tidak naik dari tahun sebelumnya'
            ELSE 'Perlu dicek manual'
        END AS alasan_deteksi
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    LEFT JOIN siswa_kelas sk_prev
        ON sk_prev.siswa_id = ps.id
       AND BINARY sk_prev.tahun_ajaran = BINARY ?
    LEFT JOIN kelas k_prev ON k_prev.id = sk_prev.kelas_id
    LEFT JOIN tingkat_kelas tk_prev ON tk_prev.id = k_prev.tingkat_id
    LEFT JOIN jurusan j_prev ON j_prev.id = k_prev.jurusan_id
    WHERE ps.status_aktif = 1
      AND BINARY sk.tahun_ajaran = BINARY ?
      AND BINARY k.tahun_ajaran = BINARY ?
      AND (
            sk.naik_kelas = 0
            OR (
                sk.naik_kelas = 1
                AND sk_prev.id IS NOT NULL
                AND tk.urutan_tingkat <= tk_prev.urutan_tingkat
            )
      )
";
$types = 'sss';
$params = [$tahunAjaranSebelumnya, $tahunAjaran, $tahunAjaran];

if ($q !== '') {
    $sql .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR CAST(ps.id AS CHAR) = ?
        OR k.nama_kelas LIKE ?
        OR j.nama_jurusan LIKE ?
        OR k_prev.nama_kelas LIKE ?
        OR j_prev.nama_jurusan LIKE ?
      )
    ";
    $types .= 'ssssssss';
    array_push($params, $like, $like, $like, $q, $like, $like, $like, $like);
}

$sql .= "
    ORDER BY
      CASE WHEN sk.naik_kelas = 0 THEN 0 ELSE 1 END ASC,
      k.nama_kelas ASC,
      ps.nama_lengkap ASC
    LIMIT 300
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    $stmt->close();
} else {
    $_SESSION['error'] = 'Query koreksi kelas gagal: ' . $conn->error;
}
?>
<div class="topbar">
    <div class="container-fluid p-0">
        <div class="row align-items-center">
            <div class="col-auto d-sm-block mt-2">
                <h3>Koreksi Kelas Siswa (T.A <?= kks_h($tahunAjaran) ?>)</h3>
            </div>
            <div class="col-auto ms-auto text-end">
                <a href="siswa_tidak_naik" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mt-5">
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message"><?= kks_h($_SESSION['error']) ?></div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message"><?= kks_h($_SESSION['success']) ?></div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card-header">
                <h5 class="card-title mb-1">Revisi kelas siswa yang perlu dikoreksi</h5>
                <div class="text-muted small">
                    Halaman ini hanya menampilkan siswa yang terdeteksi <b>masih tidak naik kelas</b> atau <b>sudah terlanjur diatur kelasnya tetapi tingkat aktif tidak naik dibanding tahun sebelumnya</b>.
                    Gunakan pencarian jika daftar terlalu banyak.
                </div>
            </div>
            <div class="card-body border-bottom">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-8 col-lg-6">
                        <label class="form-label mb-1"><strong>Cari Siswa</strong></label>
                        <input type="text" name="q" class="form-control" value="<?= kks_h($q) ?>" placeholder="Nama / NISN / NIPD / ID siswa / kelas / jurusan">
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <?php if ($q !== ''): ?><a href="koreksi_kelas_siswa" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover">
                    <thead>
                        <tr>
                            <th>NO.</th>
                            <th>Nama</th>
                            <th>NISN</th>
                            <th>NIPD</th>
                            <th>Kelas Tahun Sebelumnya</th>
                            <th>Kelas Aktif Sekarang</th>
                            <th>Deteksi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada siswa yang terdeteksi perlu koreksi kelas untuk tahun ajaran aktif.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><a href="student_view&id=<?= (int) $row['id'] ?>"><?= kks_h($row['nama_lengkap']) ?></a></td>
                                    <td><?= kks_h($row['nisn'] ?? '-') ?></td>
                                    <td><?= kks_h($row['nipd'] ?? '-') ?></td>
                                    <td>
                                        <?= kks_h($row['nama_kelas_sebelumnya'] ?? '-') ?><br>
                                        <small class="text-muted"><?= kks_h($row['nama_jurusan_sebelumnya'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <?= kks_h($row['nama_kelas'] ?? '-') ?><br>
                                        <small class="text-muted"><?= kks_h($row['nama_jurusan'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <?php if ((int)($row['naik_kelas'] ?? 1) === 0): ?>
                                            <span class="badge bg-danger">Tidak Naik</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Perlu Koreksi</span>
                                        <?php endif; ?>
                                        <div class="small text-muted mt-1"><?= kks_h($row['alasan_deteksi'] ?? '-') ?></div>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <button type="button" class="btn btn-warning" onclick="bukaModalKoreksi(<?= (int) $row['id'] ?>, '<?= kks_h($tahunAjaran) ?>')">Revisi Kelas</button>
                                        <a href="student_view&id=<?= (int) $row['id'] ?>" class="btn btn-primary">Profil</a>
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

<div class="modal fade" id="modalKoreksiKelas" tabindex="-1" aria-labelledby="modalKoreksiKelasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formKoreksiKelas" method="post" action="koreksi_kelas_siswa_proses">
            <input type="hidden" name="siswa_id" id="koreksi_siswa_id" value="">
            <input type="hidden" name="tahun_ajaran" id="koreksi_tahun_ajaran" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalKoreksiKelasLabel">Revisi Kelas Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Pilih kelas aktif yang benar. Setelah disimpan, siswa akan dipindahkan ke kelas tersebut dan statusnya dibuat naik/aktif.
                    </div>
                    <div class="mb-3">
                        <label for="koreksi_tingkat_kelas" class="form-label">Tingkat Kelas</label>
                        <select id="koreksi_tingkat_kelas" name="tingkat_id" class="form-select" required>
                            <option value="" selected disabled>Pilih Tingkat Kelas</option>
                            <?php
                            $result = $conn->query("SELECT id, nama_tingkat FROM tingkat_kelas ORDER BY id ASC");
                            while ($tingkat = $result->fetch_assoc()) {
                                echo '<option value="' . (int) $tingkat['id'] . '">' . htmlspecialchars($tingkat['nama_tingkat']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="koreksi_kelas_id" class="form-label">Kelas Tujuan</label>
                        <select name="kelas_id" id="koreksi_kelas_id" class="form-select" required disabled>
                            <option value="">Pilih Tingkat Kelas terlebih dahulu</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Simpan Revisi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function bukaModalKoreksi(siswaId, tahunAjaran) {
    $('#koreksi_siswa_id').val(siswaId);
    $('#koreksi_tahun_ajaran').val(tahunAjaran);
    $('#koreksi_tingkat_kelas').val('');
    $('#koreksi_kelas_id').html('<option value="">Pilih Tingkat Kelas terlebih dahulu</option>').prop('disabled', true);
    $('#modalKoreksiKelas').modal('show');
}
$('#koreksi_tingkat_kelas').on('change', function() {
    let tingkat = $(this).val();
    let tahunAjaran = $('#koreksi_tahun_ajaran').val();
    if (!tingkat || !tahunAjaran) {
        $('#koreksi_kelas_id').html('<option value="">Pilih Tingkat Kelas terlebih dahulu</option>').prop('disabled', true);
        return;
    }
    $.ajax({
        url: 'load_kelas.php',
        method: 'GET',
        data: { tingkat_id: tingkat, tahun_ajaran: tahunAjaran },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="" disabled selected>Pilih Kelas</option>';
                response.data.forEach(function(kelas) { options += `<option value="${kelas.id}">${kelas.nama_kelas}</option>`; });
                $('#koreksi_kelas_id').html(options).prop('disabled', false);
            } else {
                $('#koreksi_kelas_id').html('<option value="">Tidak ada kelas ditemukan</option>').prop('disabled', true);
            }
        },
        error: function() { $('#koreksi_kelas_id').html('<option value="">Gagal memuat kelas</option>').prop('disabled', true); }
    });
});
</script>
