<div class="card br-0">
    <div class="card-body">
        <form method="POST" action="edit_proses" id="formAyah" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="ayah">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row m-n3">
                <div class="top-tab mt-0">
                    <div class="container-fluid p-0">
                        <div class="row modal-dialog-centered">
                            <div class="col-auto d-sm-block">
                                <h5 class="card-title mb-0">Data Ayah</h5>
                            </div>
                            <div class="col-auto ms-auto text-end">
                                <!-- BUTTON EDIT/SIMPAN -->
                                <button type="button" id="editBtnAyah" class="btn btn-primary" onclick="toggleEditAyah()">Edit Data Ayah</button>
                                <button type="submit" id="saveBtnAyah" class="btn btn-success d-none">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="dataAyahTable">
                        <tr>
                            <td class="label">Nama Ayah</td>
                            <td>:</td>
                            <td id="td_nama_ayah">
                                <span><?= show($student['nama_ayah']); ?></span>
                                <input type="text" name="nama_ayah" class="form-control d-none" value="<?= htmlspecialchars($student['nama_ayah']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">NIK Ayah</td>
                            <td>:</td>
                            <td id="td_nik_ayah">
                                <span><?= show($student['nik_ayah']); ?></span>
                                <input type="number" name="nik_ayah" class="form-control d-none" value="<?= htmlspecialchars($student['nik_ayah']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Tahun Lahir</td>
                            <td>:</td>
                            <td id="td_tahun_lahir_ayah">
                                <span><?= show($student['tahun_lahir_ayah']); ?></span>
                                <input type="number" name="tahun_lahir_ayah" class="form-control d-none" min="1900" max="2100" value="<?= htmlspecialchars($student['tahun_lahir_ayah']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Pendidikan</td>
                            <td>:</td>
                            <td id="td_pendidikan_ayah">
                                <span><?= show($student['pendidikan_ayah']); ?></span>
                                <select id="pendidikan_ayah" name="pendidikan_ayah" class="form-select d-none">
                                    <?= getOptionsPendidikan($student['pendidikan_ayah'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Pekerjaan</td>
                            <td>:</td>
                            <td id="td_pekerjaan_ayah">
                                <span><?= show($student['pekerjaan_ayah']); ?></span>
                                <select id="pekerjaan_ayah" name="pekerjaan_ayah" class="form-select d-none">
                                    <?= getOptionsPekerjaan($student['pekerjaan_ayah'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Penghasilan</td>
                            <td>:</td>
                            <td id="td_penghasilan_ayah">
                                <span><?= show($student['penghasilan_ayah']); ?></span>
                                <select id="penghasilan_ayah" name="penghasilan_ayah" class="form-select d-none">
                                    <?= getOptionsPenghasilan($student['penghasilan_ayah'] ?? '') ?>
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
    function toggleEditAyah() {
        const editBtnAyah = document.getElementById('editBtnAyah');
        const saveBtnAyah = document.getElementById('saveBtnAyah');

        // Ambil elemen dalam tabel Data Ayah
        const inputs = document.querySelectorAll('#dataAyahTable input, #dataAyahTable select, #dataAyahTable textarea');

        inputs.forEach(input => {
            input.classList.remove('d-none');

            // Sembunyikan span sebelumnya jika ada (mode view)
            const span = input.parentElement.querySelector('span');
            if (span) span.classList.add('d-none');
        });

        editBtnAyah.classList.add('d-none');
        saveBtnAyah.classList.remove('d-none');
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // pageshow akan dipicu setiap kali halaman ditampilkan,
        // termasuk ketika datang dari tombol Back / Forward.
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) { // true â‡’ berasal dari bfcache
                const form = document.getElementById('formAyah');
                if (form) form.reset(); // kosongkan seluruh field
            }
        });
    });
</script>