<?php
// Tahun ajaran aktif disediakan oleh index.php dari master Tahun Ajaran SDS.
$tahunAjaran = (string)($tahunAjaran ?? '');

function snt_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT
        ps.id,
        ps.nisn,
        ps.nama_lengkap,
        ps.jenis_kelamin,
        ps.nipd,
        sk.kelas_id,
        k.nama_kelas,
        tk.nama_tingkat,
        tk.urutan_tingkat,
        j.nama_jurusan
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN tingkat_kelas tk ON k.tingkat_id = tk.id
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    WHERE sk.tahun_ajaran = ?
      AND k.tahun_ajaran = ?
      AND sk.naik_kelas = 0
      AND ps.status_aktif = 1
    ORDER BY k.nama_kelas ASC, ps.nama_lengkap ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $tahunAjaran, $tahunAjaran);
$stmt->execute();
$q = $stmt->get_result();
?>

<div class="topbar">
    <div class="container-fluid p-0">
        <div class="row align-items-center">
            <div class="col-auto d-sm-block mt-2">
                <h3>Daftar Siswa Tidak Naik Kelas (T.A <?= snt_h($tahunAjaran) ?>)</h3>
            </div>
            <div class="col-auto ms-auto text-end">
                <a href="koreksi_kelas_siswa" class="btn btn-warning me-2">Koreksi Kelas Siswa</a>
                <a href="kuota_kelas" class="btn btn-secondary">Kembali</a>
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
                    <div class="alert-message"><?= snt_h($_SESSION['error']) ?></div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <div class="alert-message"><?= snt_h($_SESSION['success']) ?></div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card-header">
                <h5 class="card-title mb-1">Siswa yang ditandai tidak naik kelas pada tahun ajaran aktif</h5>
                <div class="text-muted small">
                    Gunakan <b>Atur Kelas</b> jika siswa memang tidak naik dan harus ditempatkan di kelas lain.
                    Gunakan <b>Batalkan Tidak Naik</b> jika siswa ternyata tetap naik kelas.
                    Jika sudah terlanjur diatur ke kelas salah, gunakan tombol <b>Koreksi Kelas Siswa</b>.
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover">
                    <thead>
                        <tr>
                            <th>NO.</th>
                            <th>Nama</th>
                            <th>JK</th>
                            <th>NISN</th>
                            <th>NIPD</th>
                            <th>Jurusan</th>
                            <th>Kelas Saat Ditandai</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php if ($q && $q->num_rows > 0): ?>
                            <?php while ($row = $q->fetch_assoc()): ?>
                                <?php
                                $jkRaw = (string) ($row['jenis_kelamin'] ?? '');
                                $jk = ($jkRaw === 'Laki-laki' || strtoupper($jkRaw) === 'L') ? 'L' : (($jkRaw === 'Perempuan' || strtoupper($jkRaw) === 'P') ? 'P' : '-');
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><a href="student_view&id=<?= (int) $row['id'] ?>" title="Lihat Profil Siswa"><?= snt_h($row['nama_lengkap']) ?></a></td>
                                    <td><?= snt_h($jk) ?></td>
                                    <td><?= snt_h($row['nisn']) ?></td>
                                    <td><?= snt_h($row['nipd']) ?></td>
                                    <td><?= snt_h($row['nama_jurusan'] ?? '-') ?></td>
                                    <td><?= snt_h($row['nama_kelas'] ?? '-') ?></td>
                                    <td style="text-align: right;">
                                        <button
                                            class="btn btn-info"
                                            onclick="bukaModalPindahKelas(<?= (int) $row['id'] ?>, '<?= snt_h($tahunAjaran) ?>')"
                                            title="Atur kelas siswa tidak naik">
                                            Atur Kelas
                                        </button>
                                        <form action="siswa_tidak_naik_batal" method="post" style="display:inline;" onsubmit="return confirm('Batalkan status tidak naik kelas untuk siswa ini? Siswa akan tetap berada di kelas saat ini dan status naik_kelas dikembalikan menjadi aktif.');">
                                            <input type="hidden" name="siswa_id" value="<?= (int) $row['id'] ?>">
                                            <input type="hidden" name="kelas_id" value="<?= (int) $row['kelas_id'] ?>">
                                            <input type="hidden" name="tahun_ajaran" value="<?= snt_h($tahunAjaran) ?>">
                                            <button type="submit" class="btn btn-success" title="Batalkan tidak naik kelas">Batalkan Tidak Naik</button>
                                        </form>
                                        <a href="student_view&id=<?= (int) $row['id'] ?>" class="btn btn-primary">Profil</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada siswa yang ditandai tidak naik kelas untuk tahun ajaran <?= snt_h($tahunAjaran) ?>.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pindah Kelas -->
<div class="modal fade" id="modalPindahKelas" tabindex="-1" aria-labelledby="modalPindahKelasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formPindahKelas" method="post" action="siswa_tidak_naik_pindah_kelas_prosess">
            <input type="hidden" name="siswa_id" id="modal_siswa_id" value="">
            <input type="hidden" name="tahun_ajaran" id="modal_tahun_ajaran" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPindahKelasLabel">Atur Kelas Siswa Tidak Naik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        Pilih kelas tujuan pada tahun ajaran aktif. Setelah disimpan, siswa akan masuk ke kelas tujuan dan keluar dari daftar Siswa Tidak Naik Kelas.
                    </div>
                    <div class="mb-3">
                        <label for="tingkat_kelas" class="form-label">Tingkat Kelas</label>
                        <select id="tingkat_kelas" name="tingkat_id" class="form-select" required>
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
                        <label for="kelas_id" class="form-label">Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="form-select" required disabled>
                            <option value="">Pilih Tingkat Kelas terlebih dahulu</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Kelas</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function bukaModalPindahKelas(siswaId, tahunAjaran) {
        $('#modal_siswa_id').val(siswaId);
        $('#modal_tahun_ajaran').val(tahunAjaran);
        $('#tingkat_kelas').val('');
        $('#kelas_id').html('<option value="">Pilih Tingkat Kelas terlebih dahulu</option>').prop('disabled', true);
        $('#modalPindahKelas').modal('show');
    }

    $('#tingkat_kelas').on('change', function() {
        let tingkat = $(this).val();
        let tahunAjaran = $('#modal_tahun_ajaran').val();

        if (!tingkat || !tahunAjaran) {
            $('#kelas_id').html('<option value="">Pilih Tingkat Kelas terlebih dahulu</option>').prop('disabled', true);
            return;
        }

        $.ajax({
            url: 'load_kelas.php',
            method: 'GET',
            data: {
                tingkat_id: tingkat,
                tahun_ajaran: tahunAjaran
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="" disabled selected>Pilih Kelas</option>';
                    response.data.forEach(function(kelas) {
                        options += `<option value="${kelas.id}">${kelas.nama_kelas}</option>`;
                    });
                    $('#kelas_id').html(options).prop('disabled', false);
                } else {
                    $('#kelas_id').html('<option value="">Tidak ada kelas ditemukan</option>').prop('disabled', true);
                }
            },
            error: function() {
                $('#kelas_id').html('<option value="">Gagal memuat data kelas</option>').prop('disabled', true);
            }
        });
    });
</script>
<?php $stmt->close(); ?>
