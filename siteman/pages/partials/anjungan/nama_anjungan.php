<div class="sds-card">
    <div class="sds-card-header">
        <div>
            <h5>Tampilan Utama</h5>
            <div class="sds-mini">Atur identitas, media utama, background, dan status publik Anjungan.</div>
        </div>
    </div>
    <div class="sds-card-body">
        <form method="post" enctype="multipart/form-data" id="formTampilanAnjungan">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="anjungan_action" value="save_main">

            <div class="sds-form-grid">
                <div class="full">
                    <label class="form-label">Nama Anjungan</label>
                    <input type="text" name="nama_anjungan" class="form-control" maxlength="100" required value="<?= htmlspecialchars((string)($anjungan['nama_anjungan'] ?? '')) ?>" placeholder="Contoh: Anjungan Informasi SMKN 1 Probolinggo">
                    <div class="form-text">Nama sekolah dan logo tetap mengambil data dari Pengaturan Profil Sekolah.</div>
                </div>

                <div>
                    <label class="form-label">Media Utama</label>
                    <select name="media_type" id="anjunganMediaType" class="form-select">
                        <option value="video" <?= ($anjunganSettings['media_type'] ?? 'video') === 'video' ? 'selected' : '' ?>>Video YouTube / Video URL</option>
                        <option value="tanpa_video" <?= ($anjunganSettings['media_type'] ?? '') === 'tanpa_video' ? 'selected' : '' ?>>Tanpa video</option>
                    </select>
                </div>
                <div id="anjunganVideoField">
                    <label class="form-label">URL Video</label>
                    <input type="text" name="video" class="form-control" value="<?= htmlspecialchars((string)($anjungan['video'] ?? '')) ?>" placeholder="https://www.youtube.com/watch?v=...">
                    <div class="form-text">URL YouTube biasa akan diubah otomatis menjadi URL embed.</div>
                </div>

                <div class="full">
                    <label class="form-label">Background Anjungan</label>
                    <div class="sds-preview-box mb-2">
                        <?php if (!empty($anjungan['background'])): ?>
                            <img src="../anjungan/assets/uploads/background/<?= rawurlencode(basename((string)$anjungan['background'])) ?>" alt="Background Anjungan">
                        <?php else: ?>
                            <div class="text-center text-muted p-4">
                                <strong>Belum ada background khusus</strong><br>
                                <small>Anjungan akan menggunakan warna latar bawaan.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="background" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">Disarankan 1920 × 1080 piksel. Maksimal 6 MB.</div>
                </div>

                <div class="full">
                    <div class="sds-switch-row">
                        <div>
                            <strong>Aktifkan Anjungan</strong>
                            <small>Jika dinonaktifkan, halaman publik tidak menampilkan layanan Anjungan.</small>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="aktif" value="1" <?= (int)($anjungan['aktif'] ?? 1) === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
                <button type="submit" class="btn btn-success">Simpan Tampilan Utama</button>
            </div>
        </form>

        <?php if (!empty($anjungan['background'])): ?>
            <form method="post" class="mt-2 text-end" onsubmit="return confirm('Kembalikan background ke tampilan default?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="anjungan_action" value="remove_background">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Background</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    function syncMediaField() {
        const type = document.getElementById('anjunganMediaType');
        const field = document.getElementById('anjunganVideoField');
        if (!type || !field) return;
        field.style.display = type.value === 'video' ? '' : 'none';
    }
    document.addEventListener('DOMContentLoaded', function () {
        const type = document.getElementById('anjunganMediaType');
        if (type) type.addEventListener('change', syncMediaField);
        syncMediaField();
    });
})();
</script>
