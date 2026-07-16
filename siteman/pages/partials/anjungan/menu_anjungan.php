<?php
$menuResult = $conn->query("SELECT * FROM `anjungan_menu` ORDER BY `urutan` ASC, `id` ASC");
$menuRows = $menuResult instanceof mysqli_result ? $menuResult->fetch_all(MYSQLI_ASSOC) : [];
$hasMenuDestinationType = sdsAnjunganColumnExists($conn, 'anjungan_menu', 'jenis_tujuan');
?>

<div class="sds-card">
    <div class="sds-card-header">
        <div>
            <h5>Menu Layanan</h5>
            <div class="sds-mini">Ikon layanan yang tampil pada carousel bagian bawah Anjungan.</div>
        </div>
        <button type="button" class="btn btn-primary" id="btnTambahMenuLayanan">Tambah Layanan</button>
    </div>
    <div class="sds-card-body">
        <div class="sds-section-note mb-3">Gunakan <strong>Buka dalam Anjungan</strong> untuk halaman internal/iframe dan <strong>URL langsung</strong> untuk situs yang harus dibuka sebagai halaman baru.</div>
        <div class="sds-toolbar">
            <div class="sds-toolbar-left">
                <span class="sds-mini"><?= count($menuRows) ?> menu tersimpan</span>
            </div>
            <div class="sds-toolbar-right">
                <input type="search" class="form-control sds-table-filter" placeholder="Cari nama atau tujuan..." data-table-search="#tableMenuLayanan">
            </div>
        </div>
        <div class="sds-table-wrap">
            <table class="sds-table" id="tableMenuLayanan">
                <thead>
                    <tr>
                        <th style="width:58px">Ikon</th>
                        <th>Nama Layanan</th>
                        <th>Tujuan</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Urutan</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$menuRows): ?>
                    <tr><td colspan="7" class="sds-empty">Belum ada Menu Layanan.</td></tr>
                <?php else: ?>
                    <?php foreach ($menuRows as $row):
                        $destinationType = $hasMenuDestinationType ? (string)($row['jenis_tujuan'] ?? 'iframe') : 'iframe';
                        $iconName = basename((string)($row['icon'] ?? ''));
                    ?>
                    <tr data-search-row>
                        <td>
                            <?php if ($iconName !== ''): ?>
                                <img class="sds-icon-preview" src="../anjungan/assets/uploads/menu/<?= rawurlencode($iconName) ?>" alt="">
                            <?php else: ?>
                                <span class="sds-icon-preview d-inline-flex align-items-center justify-content-center text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars((string)$row['nama_menu']) ?></strong></td>
                        <td><span class="sds-url" title="<?= htmlspecialchars((string)$row['link']) ?>"><?= htmlspecialchars((string)$row['link']) ?></span></td>
                        <td><span class="sds-badge info"><?= $destinationType === 'eksternal' ? 'URL langsung' : 'Dalam Anjungan' ?></span></td>
                        <td><span class="sds-badge <?= ($row['status'] ?? '') === 'aktif' ? 'ok' : 'muted' ?>"><?= ($row['status'] ?? '') === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><?= (int)($row['urutan'] ?? 0) ?></td>
                        <td>
                            <div class="sds-actions">
                                <button type="button" class="btn btn-sm btn-warning btn-edit-menu-layanan"
                                    data-id="<?= (int)$row['id'] ?>"
                                    data-nama="<?= htmlspecialchars((string)$row['nama_menu'], ENT_QUOTES) ?>"
                                    data-link="<?= htmlspecialchars((string)$row['link'], ENT_QUOTES) ?>"
                                    data-jenis="<?= htmlspecialchars($destinationType, ENT_QUOTES) ?>"
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

<div class="modal fade sds-master-modal" id="modalMenuLayanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" enctype="multipart/form-data" class="modal-content" id="formMenuLayanan">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalMenuLayanan">Tambah Menu Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="anjungan_action" value="save_menu">
                <input type="hidden" name="id" id="menuLayananId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nama Layanan</label>
                        <input type="text" name="nama_menu" id="menuLayananNama" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" id="menuLayananUrutan" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Cara Membuka</label>
                        <select name="jenis_tujuan" id="menuLayananJenis" class="form-select">
                            <option value="iframe">Buka dalam Anjungan</option>
                            <option value="eksternal">URL langsung</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Halaman / URL Tujuan</label>
                        <input type="text" name="link" id="menuLayananLink" class="form-control" list="anjunganInternalLinks" required placeholder="Contoh: sebaran_siswa.php atau https://...">
                        <datalist id="anjunganInternalLinks">
                            <option value="rekap_sekolah.php">
                            <option value="sebaran_siswa.php">
                            <option value="denah.php">
                            <option value="survey/">
                        </datalist>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Ikon</label>
                        <input type="file" name="icon" id="menuLayananIcon" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text" id="menuLayananIconHelp">Wajib untuk data baru. Maksimal 2 MB.</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Status</label>
                        <select name="status" id="menuLayananStatus" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnHapusMenuLayanan" style="display:none">Hapus</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formHapusMenuLayanan" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="anjungan_action" value="delete_menu">
    <input type="hidden" name="id" id="hapusMenuLayananId">
</form>

<script>
(function () {
    function showMenuModal() {
        const modal = document.getElementById('modalMenuLayanan');
        if (typeof window.sdsShowModal === 'function') window.sdsShowModal(modal);
        else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') new window.bootstrap.Modal(modal).show();
    }
    function resetMenuModal() {
        document.getElementById('formMenuLayanan').reset();
        document.getElementById('menuLayananId').value = '';
        document.getElementById('menuLayananUrutan').value = '0';
        document.getElementById('judulModalMenuLayanan').textContent = 'Tambah Menu Layanan';
        document.getElementById('btnHapusMenuLayanan').style.display = 'none';
        document.getElementById('menuLayananIconHelp').textContent = 'Wajib untuk data baru. Maksimal 2 MB.';
    }
    document.addEventListener('DOMContentLoaded', function () {
        const addButton = document.getElementById('btnTambahMenuLayanan');
        if (addButton) addButton.addEventListener('click', function () { resetMenuModal(); showMenuModal(); });

        document.querySelectorAll('.btn-edit-menu-layanan').forEach(function (button) {
            button.addEventListener('click', function () {
                resetMenuModal();
                document.getElementById('judulModalMenuLayanan').textContent = 'Edit Menu Layanan';
                document.getElementById('menuLayananId').value = button.dataset.id || '';
                document.getElementById('menuLayananNama').value = button.dataset.nama || '';
                document.getElementById('menuLayananLink').value = button.dataset.link || '';
                document.getElementById('menuLayananJenis').value = button.dataset.jenis || 'iframe';
                document.getElementById('menuLayananUrutan').value = button.dataset.urutan || '0';
                document.getElementById('menuLayananStatus').value = button.dataset.status || 'aktif';
                document.getElementById('menuLayananIconHelp').textContent = button.dataset.icon ? 'Ikon saat ini: ' + button.dataset.icon + '. Kosongkan bila tidak diganti.' : 'Pilih ikon baru bila diperlukan.';
                document.getElementById('btnHapusMenuLayanan').style.display = '';
                showMenuModal();
            });
        });

        const deleteButton = document.getElementById('btnHapusMenuLayanan');
        if (deleteButton) deleteButton.addEventListener('click', function () {
            const id = document.getElementById('menuLayananId').value;
            if (!id || !confirm('Hapus Menu Layanan ini?')) return;
            document.getElementById('hapusMenuLayananId').value = id;
            document.getElementById('formHapusMenuLayanan').submit();
        });
    });
})();
</script>
