<?php
$newsResult = $conn->query("SELECT * FROM `anjungan_berita` ORDER BY `tanggal` DESC, `id` DESC");
$newsRows = $newsResult instanceof mysqli_result ? $newsResult->fetch_all(MYSQLI_ASSOC) : [];
$hasNewsPublishing = sdsAnjunganColumnExists($conn, 'anjungan_berita', 'status_tayang');
$hasNewsExpiry = sdsAnjunganColumnExists($conn, 'anjungan_berita', 'tanggal_berakhir');
$today = date('Y-m-d');
?>

<div class="sds-card">
    <div class="sds-card-header">
        <div>
            <h5>Berita &amp; Pengumuman</h5>
            <div class="sds-mini">Kelola konten informasi yang muncul pada panel artikel Anjungan.</div>
        </div>
        <button type="button" class="btn btn-primary" id="btnTambahBeritaAnjungan">Tambah Konten</button>
    </div>
    <div class="sds-card-body">
        <div class="sds-section-note mb-3">Jumlah dilihat dihitung otomatis ketika pengunjung membuka konten. Operator tidak perlu mengubah angka tayangan secara manual.</div>
        <div class="sds-toolbar">
            <div class="sds-toolbar-left"><span class="sds-mini"><?= count($newsRows) ?> konten tersimpan</span></div>
            <div class="sds-toolbar-right"><input type="search" class="form-control sds-table-filter" placeholder="Cari judul, jenis, atau status..." data-table-search="#tableBeritaAnjungan"></div>
        </div>
        <div class="sds-table-wrap">
            <table class="sds-table wide" id="tableBeritaAnjungan">
                <thead>
                    <tr>
                        <th style="width:86px">Gambar</th>
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Tanggal Tayang</th>
                        <th>Status Tayang</th>
                        <th>Prioritas</th>
                        <th>Dilihat</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$newsRows): ?>
                    <tr><td colspan="8" class="sds-empty">Belum ada berita atau pengumuman.</td></tr>
                <?php else: ?>
                    <?php foreach ($newsRows as $row):
                        $publishStatus = $hasNewsPublishing ? (string)($row['status_tayang'] ?? 'terbit') : 'terbit';
                        $expiryDate = $hasNewsExpiry ? (string)($row['tanggal_berakhir'] ?? '') : '';
                        if ($publishStatus === 'draft') {
                            $statusText = 'Draft';
                            $statusBadge = 'muted';
                        } elseif (!empty($row['tanggal']) && $row['tanggal'] > $today) {
                            $statusText = 'Terjadwal';
                            $statusBadge = 'info';
                        } elseif ($expiryDate !== '' && $expiryDate < $today) {
                            $statusText = 'Berakhir';
                            $statusBadge = 'warn';
                        } else {
                            $statusText = 'Terbit';
                            $statusBadge = 'ok';
                        }
                        $imageName = basename((string)($row['gambar'] ?? ''));
                    ?>
                    <tr data-search-row>
                        <td>
                            <?php if ($imageName !== ''): ?>
                                <img class="sds-thumb" src="../anjungan/assets/uploads/berita/<?= rawurlencode($imageName) ?>" alt="">
                            <?php else: ?>
                                <span class="sds-thumb d-inline-flex align-items-center justify-content-center text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars((string)$row['judul']) ?></strong>
                            <?php if (!empty($row['link'])): ?>
                                <a class="sds-mini d-block mt-1" href="<?= htmlspecialchars((string)$row['link']) ?>" target="_blank" rel="noopener">Buka tautan</a>
                            <?php endif; ?>
                        </td>
                        <td><span class="sds-badge info"><?= ($row['jenis'] ?? '') === 'pengumuman' ? 'Pengumuman' : 'Berita' ?></span></td>
                        <td>
                            <?= !empty($row['tanggal']) ? date('d-m-Y', strtotime((string)$row['tanggal'])) : '-' ?>
                            <?php if ($expiryDate !== ''): ?><div class="sds-mini">s.d. <?= date('d-m-Y', strtotime($expiryDate)) ?></div><?php endif; ?>
                        </td>
                        <td><span class="sds-badge <?= $statusBadge ?>"><?= $statusText ?></span></td>
                        <td><?= ucfirst(htmlspecialchars((string)($row['status'] ?? 'biasa'))) ?></td>
                        <td><?= number_format((int)($row['dilihat'] ?? 0), 0, ',', '.') ?></td>
                        <td>
                            <div class="sds-actions">
                                <button type="button" class="btn btn-sm btn-warning btn-edit-berita-anjungan"
                                    data-id="<?= (int)$row['id'] ?>"
                                    data-judul="<?= htmlspecialchars((string)$row['judul'], ENT_QUOTES) ?>"
                                    data-tanggal="<?= htmlspecialchars((string)($row['tanggal'] ?? ''), ENT_QUOTES) ?>"
                                    data-berakhir="<?= htmlspecialchars($expiryDate, ENT_QUOTES) ?>"
                                    data-link="<?= htmlspecialchars((string)($row['link'] ?? ''), ENT_QUOTES) ?>"
                                    data-jenis="<?= htmlspecialchars((string)($row['jenis'] ?? 'berita'), ENT_QUOTES) ?>"
                                    data-prioritas="<?= htmlspecialchars((string)($row['status'] ?? 'biasa'), ENT_QUOTES) ?>"
                                    data-tayang="<?= htmlspecialchars($publishStatus, ENT_QUOTES) ?>"
                                    data-gambar="<?= htmlspecialchars($imageName, ENT_QUOTES) ?>"
                                    data-dilihat="<?= (int)($row['dilihat'] ?? 0) ?>">Edit</button>
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

<div class="modal fade sds-master-modal" id="modalBeritaAnjungan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" enctype="multipart/form-data" class="modal-content" id="formBeritaAnjungan">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalBeritaAnjungan">Tambah Berita atau Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="anjungan_action" value="save_news">
                <input type="hidden" name="id" id="beritaAnjunganId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Judul</label>
                        <input type="text" name="judul" id="beritaAnjunganJudul" class="form-control" maxlength="255" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Konten</label>
                        <select name="jenis" id="beritaAnjunganJenis" class="form-select">
                            <option value="berita">Berita</option>
                            <option value="pengumuman">Pengumuman</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Tayang</label>
                        <input type="date" name="tanggal" id="beritaAnjunganTanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Berakhir</label>
                        <input type="date" name="tanggal_berakhir" id="beritaAnjunganBerakhir" class="form-control">
                        <div class="form-text">Opsional.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Tayang</label>
                        <select name="status_tayang" id="beritaAnjunganTayang" class="form-select">
                            <option value="terbit">Terbit</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prioritas Tampilan</label>
                        <select name="status" id="beritaAnjunganPrioritas" class="form-select">
                            <option value="biasa">Biasa</option>
                            <option value="terbaru">Terbaru</option>
                            <option value="populer">Populer</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Link Detail</label>
                        <input type="text" name="link" id="beritaAnjunganLink" class="form-control" placeholder="https://... atau path halaman internal">
                        <div class="form-text">Boleh dikosongkan jika konten hanya berupa informasi ringkas.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Gambar</label>
                        <input type="file" name="gambar" id="beritaAnjunganGambar" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text" id="beritaAnjunganGambarHelp">Maksimal 4 MB.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jumlah Dilihat</label>
                        <input type="text" id="beritaAnjunganDilihat" class="form-control" value="0" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnHapusBeritaAnjungan" style="display:none">Hapus</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formHapusBeritaAnjungan" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="anjungan_action" value="delete_news">
    <input type="hidden" name="id" id="hapusBeritaAnjunganId">
</form>

<script>
(function () {
    function showNewsModal() {
        const modal = document.getElementById('modalBeritaAnjungan');
        if (typeof window.sdsShowModal === 'function') window.sdsShowModal(modal);
        else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') new window.bootstrap.Modal(modal).show();
    }
    function resetNewsModal() {
        document.getElementById('formBeritaAnjungan').reset();
        document.getElementById('beritaAnjunganId').value = '';
        document.getElementById('beritaAnjunganTanggal').value = '<?= date('Y-m-d') ?>';
        document.getElementById('beritaAnjunganDilihat').value = '0';
        document.getElementById('judulModalBeritaAnjungan').textContent = 'Tambah Berita atau Pengumuman';
        document.getElementById('btnHapusBeritaAnjungan').style.display = 'none';
        document.getElementById('beritaAnjunganGambarHelp').textContent = 'Maksimal 4 MB.';
    }
    document.addEventListener('DOMContentLoaded', function () {
        const addButton = document.getElementById('btnTambahBeritaAnjungan');
        if (addButton) addButton.addEventListener('click', function () { resetNewsModal(); showNewsModal(); });

        document.querySelectorAll('.btn-edit-berita-anjungan').forEach(function (button) {
            button.addEventListener('click', function () {
                resetNewsModal();
                document.getElementById('judulModalBeritaAnjungan').textContent = 'Edit Berita atau Pengumuman';
                document.getElementById('beritaAnjunganId').value = button.dataset.id || '';
                document.getElementById('beritaAnjunganJudul').value = button.dataset.judul || '';
                document.getElementById('beritaAnjunganTanggal').value = button.dataset.tanggal || '';
                document.getElementById('beritaAnjunganBerakhir').value = button.dataset.berakhir || '';
                document.getElementById('beritaAnjunganLink').value = button.dataset.link || '';
                document.getElementById('beritaAnjunganJenis').value = button.dataset.jenis || 'berita';
                document.getElementById('beritaAnjunganPrioritas').value = button.dataset.prioritas || 'biasa';
                document.getElementById('beritaAnjunganTayang').value = button.dataset.tayang || 'terbit';
                document.getElementById('beritaAnjunganDilihat').value = button.dataset.dilihat || '0';
                document.getElementById('beritaAnjunganGambarHelp').textContent = button.dataset.gambar ? 'Gambar saat ini: ' + button.dataset.gambar + '. Kosongkan bila tidak diganti.' : 'Maksimal 4 MB.';
                document.getElementById('btnHapusBeritaAnjungan').style.display = '';
                showNewsModal();
            });
        });

        const deleteButton = document.getElementById('btnHapusBeritaAnjungan');
        if (deleteButton) deleteButton.addEventListener('click', function () {
            const id = document.getElementById('beritaAnjunganId').value;
            if (!id || !confirm('Hapus berita atau pengumuman ini?')) return;
            document.getElementById('hapusBeritaAnjunganId').value = id;
            document.getElementById('formHapusBeritaAnjungan').submit();
        });
    });
})();
</script>
