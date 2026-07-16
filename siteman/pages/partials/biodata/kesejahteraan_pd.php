<div class="card br-0">
    <div class="card-body">
        <form method="POST" action="edit_proses" id="formKesejahteraan" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="kesejahteraan">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <div class="row m-n3">
                <div class="top-tab mt-0">
                    <div class="container-fluid p-0">
                        <div class="row modal-dialog-centered">
                            <div class="col-auto d-sm-block">
                                <h5 class="card-title mb-0">Data Kesejahteraan</h5>
                            </div>
                            <div class="col-auto ms-auto text-end">
                                <button type="button" id="editBtnKesejahteraan" class="btn btn-primary" onclick="toggleEditKesejahteraan()">Edit Kesejahteraan</button>
                                <button type="submit" id="saveBtnKesejahteraan" class="btn btn-success d-none">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="dataKesejahteraanTable">
                        <tr>
                            <td class="label">Nomor KIP</td>
                            <td>:</td>
                            <td>
                                <span><?= show($student['nomor_kip'] ?? '') ?></span>
                                <input type="text" name="nomor_kip" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_kip'] ?? '', ENT_QUOTES) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Nomor KPS</td>
                            <td>:</td>
                            <td>
                                <span><?= show($student['nomor_kps'] ?? '') ?></span>
                                <input type="text" name="nomor_kps" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_kps'] ?? '', ENT_QUOTES) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Nomor PKH</td>
                            <td>:</td>
                            <td>
                                <span><?= show($student['nomor_pkh'] ?? '') ?></span>
                                <input type="text" name="nomor_pkh" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_pkh'] ?? '', ENT_QUOTES) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Nomor KIS</td>
                            <td>:</td>
                            <td>
                                <span><?= show($student['nomor_kis'] ?? '') ?></span>
                                <input type="text" name="nomor_kis" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_kis'] ?? '', ENT_QUOTES) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Nomor KKS</td>
                            <td>:</td>
                            <td>
                                <span><?= show($student['nomor_kks'] ?? '') ?></span>
                                <input type="text" name="nomor_kks" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_kks'] ?? '', ENT_QUOTES) ?>">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditingKesejahteraan = false;

    function toggleEditKesejahteraan() {
        const editBtn = document.getElementById('editBtnKesejahteraan');
        const saveBtn = document.getElementById('saveBtnKesejahteraan');
        const inputs = document.querySelectorAll('#dataKesejahteraanTable input, #dataKesejahteraanTable select, #dataKesejahteraanTable textarea');

        isEditingKesejahteraan = !isEditingKesejahteraan;

        inputs.forEach(input => {
            const span = input.parentElement.querySelector('span');
            if (isEditingKesejahteraan) {
                input.classList.remove('d-none');
                if (span) span.classList.add('d-none');
            } else {
                input.classList.add('d-none');
                if (span) span.classList.remove('d-none');
            }
        });

        saveBtn.classList.toggle('d-none', !isEditingKesejahteraan);
        editBtn.textContent = isEditingKesejahteraan ? 'Batal' : 'Edit Kesejahteraan';
    }
</script>
