<div class="card br-0">
    <div class="card-body">
        <form method="POST" action="edit_proses" id="formWali" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="wali">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row m-n3">
                <div class="top-tab mt-0">
                    <div class="container-fluid p-0">
                        <div class="row modal-dialog-centered">
                            <div class="col-auto d-sm-block">
                                <h5 class="card-title mb-0">Data Wali</h5>
                            </div>
                            <div class="col-auto ms-auto text-end">
                                <button type="button" id="editBtnWali" class="btn btn-primary" onclick="toggleEditWali()">
                                    <?= !empty($student['nama_wali']) ? 'Edit Data Wali' : 'Tambah Data Wali' ?>
                                </button>
                                <button type="submit" id="saveBtnWali" class="btn btn-success d-none">Simpan</button>
                                <?php if (!empty($student['nama_wali'])): ?>
                                    <a href="hapus_wali?id=<?= $id ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus data wali siswa ini?');">
                                        Hapus Wali
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="dataWaliTable">
                        <tr>
                            <td class="label">Nama Wali</td>
                            <td>:</td>
                            <td id="td_nama_wali">
                                <span><?= show($student['nama_wali'] ?? '') ?></span>
                                <input type="text" name="nama_wali" class="form-control d-none" value="<?= htmlspecialchars($student['nama_wali'] ?? '') ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">NIK Wali</td>
                            <td>:</td>
                            <td id="td_nik_wali">
                                <span><?= show($student['nik_wali'] ?? '') ?></span>
                                <input type="number" name="nik_wali" class="form-control d-none" value="<?= htmlspecialchars($student['nik_wali'] ?? '') ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Tahun Lahir</td>
                            <td>:</td>
                            <td id="td_tahun_lahir_wali">
                                <span><?= show($student['tahun_lahir_wali'] ?? '') ?></span>
                                <input type="number" name="tahun_lahir_wali" class="form-control d-none" min="1900" max="2100" value="<?= htmlspecialchars($student['tahun_lahir_wali'] ?? '') ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Pendidikan</td>
                            <td>:</td>
                            <td id="td_pendidikan_wali">
                                <span><?= show($student['pendidikan_wali'] ?? '') ?></span>
                                <select id="pendidikan_wali" name="pendidikan_wali" class="form-select d-none">
                                    <?= getOptionsPendidikan($student['pendidikan_wali'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Pekerjaan</td>
                            <td>:</td>
                            <td id="td_pekerjaan_wali">
                                <span><?= show($student['pekerjaan_wali'] ?? '') ?></span>
                                <select id="pekerjaan_wali" name="pekerjaan_wali" class="form-select d-none">
                                    <?= getOptionsPekerjaan($student['pekerjaan_wali'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Penghasilan</td>
                            <td>:</td>
                            <td id="td_penghasilan_wali">
                                <span><?= show($student['penghasilan_wali'] ?? '') ?></span>
                                <select id="penghasilan_wali" name="penghasilan_wali" class="form-select d-none">
                                    <?= getOptionsPenghasilan($student['penghasilan_wali'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditingWali = false; // status toggle global

    function toggleEditWali() {
        const editBtnWali = document.getElementById('editBtnWali');
        const saveBtnWali = document.getElementById('saveBtnWali');
        const inputs = document.querySelectorAll('#dataWaliTable input, #dataWaliTable select, #dataWaliTable textarea');

        isEditingWali = !isEditingWali;

        inputs.forEach(input => {
            if (isEditingWali) {
                input.classList.remove('d-none');
                const span = input.parentElement.querySelector('span');
                if (span) span.classList.add('d-none');
            } else {
                input.classList.add('d-none');
                const span = input.parentElement.querySelector('span');
                if (span) span.classList.remove('d-none');
            }
        });

        // Tampilkan/ sembunyikan tombol
        saveBtnWali.classList.toggle('d-none', !isEditingWali);

        // Ganti teks tombol edit sesuai kondisi wali
        const originalLabel = <?= json_encode(!empty($student['nama_wali']) ? 'Edit Data Wali' : 'Tambah Data Wali') ?>;
        editBtnWali.textContent = isEditingWali ? 'Batal' : originalLabel;
        editBtnWali.classList.toggle('d-none', isEditingWali); // hilangkan tombol edit saat mode edit aktif (optional, bisa dihapus jika Anda tetap ingin tombol muncul)
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // pageshow akan dipicu setiap kali halaman ditampilkan,
        // termasuk ketika datang dari tombol Back / Forward.
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) { // true â‡’ berasal dari bfcache
                const form = document.getElementById('formWali');
                if (form) form.reset(); // kosongkan seluruh field
            }
        });
    });
</script>