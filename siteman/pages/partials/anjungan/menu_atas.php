<?php
$quickResult = $conn->query("SELECT * FROM `anjungan_topright` ORDER BY `urutan` ASC, `id` ASC");
$quickRows = $quickResult instanceof mysqli_result ? $quickResult->fetch_all(MYSQLI_ASSOC) : [];
$hasDirectOpen = sdsAnjunganColumnExists($conn, 'anjungan_topright', 'buka_langsung');
?>

<div class="sds-card">
    <div class="sds-card-header">
        <div>
            <h5>Akses Cepat</h5>
            <div class="sds-mini">Ikon tindakan yang tampil pada bagian kanan atas Anjungan.</div>
        </div>
        <button type="button" class="btn btn-primary" id="btnTambahAksesCepat">Tambah Akses</button>
    </div>
    <div class="sds-card-body">
        <div class="sds-section-note mb-3">Untuk popup internal, operator cukup memilih nama popup. ID teknis tidak perlu ditulis manual.</div>
        <div class="sds-toolbar">
            <div class="sds-toolbar-left"><span class="sds-mini"><?= count($quickRows) ?> akses tersimpan</span></div>
            <div class="sds-toolbar-right"><input type="search" class="form-control sds-table-filter" placeholder="Cari akses cepat..." data-table-search="#tableAksesCepat"></div>
        </div>
        <div class="sds-table-wrap">
            <table class="sds-table wide" id="tableAksesCepat">
                <thead>
                    <tr>
                        <th style="width:58px">Ikon</th>
                        <th>Nama Akses</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                        <th>Tujuan</th>
                        <th>Status</th>
                        <th>Urutan</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$quickRows): ?>
                    <tr><td colspan="8" class="sds-empty">Belum ada Akses Cepat.</td></tr>
                <?php else: ?>
                    <?php foreach ($quickRows as $row):
                        $actionType = ($row['tipe'] ?? 'link') === 'modal'
                            ? 'modal'
                            : (($hasDirectOpen && (int)($row['buka_langsung'] ?? 0) === 1) ? 'langsung' : 'iframe');
                        $actionLabel = ['modal' => 'Popup', 'langsung' => 'URL langsung', 'iframe' => 'Dalam Anjungan'][$actionType];
                        $targetText = $actionType === 'modal' ? (string)($row['target_modal'] ?? '') : (string)($row['link_url'] ?? '');
                        $iconName = basename((string)($row['icon_url'] ?? ''));
                    ?>
                    <tr data-search-row>
                        <td>
                            <?php if ($iconName !== ''): ?>
                                <img class="sds-icon-preview" src="../anjungan/assets/uploads/topright/<?= rawurlencode($iconName) ?>" alt="">
                            <?php else: ?>
                                <span class="sds-icon-preview d-inline-flex align-items-center justify-content-center text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars((string)$row['nama']) ?></strong></td>
                        <td><span class="sds-mini"><?= htmlspecialchars((string)($row['deskripsi'] ?? '')) ?></span></td>
                        <td><span class="sds-badge info"><?= $actionLabel ?></span></td>
                        <td><span class="sds-url" title="<?= htmlspecialchars($targetText) ?>"><?= htmlspecialchars($targetText !== '' ? $targetText : '-') ?></span></td>
                        <td><span class="sds-badge <?= ($row['status'] ?? '') === 'aktif' ? 'ok' : 'muted' ?>"><?= ($row['status'] ?? '') === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><?= (int)($row['urutan'] ?? 0) ?></td>
                        <td>
                            <div class="sds-actions">
                                <button type="button" class="btn btn-sm btn-warning btn-edit-akses-cepat"
                                    data-id="<?= (int)$row['id'] ?>"
                                    data-nama="<?= htmlspecialchars((string)$row['nama'], ENT_QUOTES) ?>"
                                    data-deskripsi="<?= htmlspecialchars((string)($row['deskripsi'] ?? ''), ENT_QUOTES) ?>"
                                    data-link="<?= htmlspecialchars((string)($row['link_url'] ?? ''), ENT_QUOTES) ?>"
                                    data-aksi="<?= htmlspecialchars($actionType, ENT_QUOTES) ?>"
                                    data-target="<?= htmlspecialchars((string)($row['target_modal'] ?? ''), ENT_QUOTES) ?>"
                                    data-urutan="<?= (int)($row['urutan'] ?? 0) ?>"
                                    data-status="<?= htmlspecialchars((string)$row['status'], ENT_QUOTES) ?>"
                                    data-icon="<?= htmlspecialchars($iconName, ENT_QUOTES) ?>">Edit</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade sds-master-modal" id="modalAksesCepat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" enctype="multipart/form-data" class="modal-content" id="formAksesCepat">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalAksesCepat">Tambah Akses Cepat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="anjungan_action" value="save_quick">
                <input type="hidden" name="id" id="aksesCepatId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nama Akses</label>
                        <input type="text" name="nama" id="aksesCepatNama" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" id="aksesCepatUrutan" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea name="deskripsi" id="aksesCepatDeskripsi" class="form-control" rows="2" maxlength="255"></textarea>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Aksi ketika diklik</label>
                        <select name="aksi" id="aksesCepatAksi" class="form-select">
                            <option value="iframe">Buka dalam Anjungan</option>
                            <option value="langsung">Buka URL langsung</option>
                            <option value="modal">Buka popup internal</option>
                        </select>
                    </div>
                    <div class="col-md-7" id="aksesCepatLinkWrap">
                        <label class="form-label">Halaman / URL Tujuan</label>
                        <input type="text" name="link_url" id="aksesCepatLink" class="form-control" list="anjunganQuickLinks" placeholder="Contoh: ../absensi/ atau https://...">
                        <datalist id="anjunganQuickLinks">
                            <option value="rekap_sekolah.php">
                            <option value="sebaran_siswa.php">
                            <option value="denah.php">
                            <option value="../absensi/">
                        </datalist>
                    </div>
                    <div class="col-md-7" id="aksesCepatModalWrap" style="display:none">
                        <label class="form-label">Popup Tujuan</label>
                        <select name="target_modal" id="aksesCepatTarget" class="form-select">
                            <option value="">Pilih popup</option>
                            <option value="survey">Survei Layanan Sekolah</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Ikon</label>
                        <input type="file" name="icon_url" id="aksesCepatIcon" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text" id="aksesCepatIconHelp">Wajib untuk data baru. Maksimal 2 MB.</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Status</label>
                        <select name="status" id="aksesCepatStatus" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnHapusAksesCepat" style="display:none">Hapus</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formHapusAksesCepat" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="anjungan_action" value="delete_quick">
    <input type="hidden" name="id" id="hapusAksesCepatId">
</form>

<script>
(function () {
    function showQuickModal() {
        const modal = document.getElementById('modalAksesCepat');
        if (typeof window.sdsShowModal === 'function') window.sdsShowModal(modal);
        else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') new window.bootstrap.Modal(modal).show();
    }
    function syncQuickAction() {
        const action = document.getElementById('aksesCepatAksi').value;
        document.getElementById('aksesCepatLinkWrap').style.display = action === 'modal' ? 'none' : '';
        document.getElementById('aksesCepatModalWrap').style.display = action === 'modal' ? '' : 'none';
    }
    function resetQuickModal() {
        document.getElementById('formAksesCepat').reset();
        document.getElementById('aksesCepatId').value = '';
        document.getElementById('aksesCepatUrutan').value = '0';
        document.getElementById('judulModalAksesCepat').textContent = 'Tambah Akses Cepat';
        document.getElementById('btnHapusAksesCepat').style.display = 'none';
        document.getElementById('aksesCepatIconHelp').textContent = 'Wajib untuk data baru. Maksimal 2 MB.';
        syncQuickAction();
    }
    document.addEventListener('DOMContentLoaded', function () {
        const actionSelect = document.getElementById('aksesCepatAksi');
        if (actionSelect) actionSelect.addEventListener('change', syncQuickAction);

        const addButton = document.getElementById('btnTambahAksesCepat');
        if (addButton) addButton.addEventListener('click', function () { resetQuickModal(); showQuickModal(); });

        document.querySelectorAll('.btn-edit-akses-cepat').forEach(function (button) {
            button.addEventListener('click', function () {
                resetQuickModal();
                document.getElementById('judulModalAksesCepat').textContent = 'Edit Akses Cepat';
                document.getElementById('aksesCepatId').value = button.dataset.id || '';
                document.getElementById('aksesCepatNama').value = button.dataset.nama || '';
                document.getElementById('aksesCepatDeskripsi').value = button.dataset.deskripsi || '';
                document.getElementById('aksesCepatAksi').value = button.dataset.aksi || 'iframe';
                document.getElementById('aksesCepatLink').value = button.dataset.link || '';
                document.getElementById('aksesCepatTarget').value = button.dataset.target || '';
                document.getElementById('aksesCepatUrutan').value = button.dataset.urutan || '0';
                document.getElementById('aksesCepatStatus').value = button.dataset.status || 'aktif';
                document.getElementById('aksesCepatIconHelp').textContent = button.dataset.icon ? 'Ikon saat ini: ' + button.dataset.icon + '. Kosongkan bila tidak diganti.' : 'Pilih ikon baru bila diperlukan.';
                document.getElementById('btnHapusAksesCepat').style.display = '';
                syncQuickAction();
                showQuickModal();
            });
        });

        const deleteButton = document.getElementById('btnHapusAksesCepat');
        if (deleteButton) deleteButton.addEventListener('click', function () {
            const id = document.getElementById('aksesCepatId').value;
            if (!id || !confirm('Hapus Akses Cepat ini?')) return;
            document.getElementById('hapusAksesCepatId').value = id;
            document.getElementById('formHapusAksesCepat').submit();
        });
        syncQuickAction();
    });
})();
</script>
