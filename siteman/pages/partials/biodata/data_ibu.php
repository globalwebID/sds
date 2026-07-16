<div class="card br-0">
    <div class="card-body">
        <form method="POST" action="edit_proses" id="formIbu" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="ibu">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row m-n3">
                <div class="top-tab mt-0">
                    <div class="container-fluid p-0">
                        <div class="row modal-dialog-centered">
                            <div class="col-auto d-sm-block">
                                <h5 class="card-title mb-0">Data Ibu</h5>
                            </div>
                            <div class="col-auto ms-auto text-end">
                                <!-- BUTTON EDIT/SIMPAN -->
                                <button type="button" id="editBtnIbu" class="btn btn-primary" onclick="toggleEditIbu()">Edit Data Ibu</button>
                                <button type="submit" id="saveBtnIbu" class="btn btn-success d-none">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="dataIbuTable">
                        <tr>
                            <td class="label">Nama Ibu</td>
                            <td>:</td>
                            <td id="td_nama_ibu">
                                <span><?= show($student['nama_ibu']); ?></span>
                                <input type="text" name="nama_ibu" class="form-control d-none" value="<?= htmlspecialchars($student['nama_ibu']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">NIK Ibu</td>
                            <td>:</td>
                            <td id="td_nik_ibu">
                                <span><?= show($student['nik_ibu']); ?></span>
                                <input type="number" name="nik_ibu" class="form-control d-none" value="<?= htmlspecialchars($student['nik_ibu']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Tahun Lahir</td>
                            <td>:</td>
                            <td id="td_tahun_lahir_ibu">
                                <span><?= show($student['tahun_lahir_ibu']); ?></span>
                                <input type="number" name="tahun_lahir_ibu" class="form-control d-none" min="1900" max="2100" value="<?= htmlspecialchars($student['tahun_lahir_ibu']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Pendidikan</td>
                            <td>:</td>
                            <td id="td_pendidikan_ibu">
                                <span><?= show($student['pendidikan_ibu']); ?></span>
                                <select id="pendidikan_ibu" name="pendidikan_ibu" class="form-select d-none">
                                    <?= getOptionsPendidikan($student['pendidikan_ibu'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Pekerjaan</td>
                            <td>:</td>
                            <td id="td_pekerjaan_ibu">
                                <span><?= show($student['pekerjaan_ibu']); ?></span>
                                <select id="pekerjaan_ibu" name="pekerjaan_ibu" class="form-select d-none">
                                    <?= getOptionsPekerjaan($student['pekerjaan_ibu'] ?? '') ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Penghasilan</td>
                            <td>:</td>
                            <td id="td_penghasilan_ibu">
                                <span><?= show($student['penghasilan_ibu']); ?></span>
                                <select id="penghasilan_ibu" name="penghasilan_ibu" class="form-select d-none">
                                    <?= getOptionsPenghasilan($student['penghasilan_ibu'] ?? '') ?>
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
    function toggleEditIbu() {
        const editBtnIbu = document.getElementById('editBtnIbu');
        const saveBtnIbu = document.getElementById('saveBtnIbu');

        // Ambil elemen dalam tabel Data Ayah
        const inputs = document.querySelectorAll('#dataIbuTable input, #dataIbuTable select, #dataIbuTable textarea');

        inputs.forEach(input => {
            input.classList.remove('d-none');

            // Sembunyikan span sebelumnya jika ada (mode view)
            const span = input.parentElement.querySelector('span');
            if (span) span.classList.add('d-none');
        });

        editBtnIbu.classList.add('d-none');
        saveBtnIbu.classList.remove('d-none');
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // pageshow akan dipicu setiap kali halaman ditampilkan,
        // termasuk ketika datang dari tombol Back / Forward.
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) { // true â‡’ berasal dari bfcache
                const form = document.getElementById('formIbu');
                if (form) form.reset(); // kosongkan seluruh field
            }
        });
    });
</script>