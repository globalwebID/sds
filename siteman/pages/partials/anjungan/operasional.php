<div class="sds-card">
    <div class="sds-card-header">
        <div>
            <h5>Pengaturan Operasional</h5>
            <div class="sds-mini">Atur perilaku Anjungan pada perangkat kiosk atau layar informasi.</div>
        </div>
    </div>
    <div class="sds-card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="anjungan_action" value="save_operational">

            <div class="sds-form-grid">
                <div>
                    <label class="form-label">Tema Warna Default</label>
                    <select name="tema_default" class="form-select">
                        <option value="nature" <?= ($anjunganSettings['tema_default'] ?? 'nature') === 'nature' ? 'selected' : '' ?>>Biru &amp; Hijau</option>
                        <option value="travel" <?= ($anjunganSettings['tema_default'] ?? '') === 'travel' ? 'selected' : '' ?>>Ungu &amp; Pink</option>
                        <option value="casual" <?= ($anjunganSettings['tema_default'] ?? '') === 'casual' ? 'selected' : '' ?>>Toska &amp; Oranye</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Muat Ulang Otomatis</label>
                    <div class="input-group">
                        <input type="number" name="refresh_menit" class="form-control" min="0" max="1440" value="<?= (int)($anjunganSettings['refresh_menit'] ?? 0) ?>">
                        <span class="input-group-text">menit</span>
                    </div>
                    <div class="form-text">Isi 0 untuk menonaktifkan.</div>
                </div>
                <div>
                    <label class="form-label">Kecepatan Carousel Menu</label>
                    <div class="input-group">
                        <input type="number" name="carousel_detik" class="form-control" min="2" max="60" value="<?= max(2, (int)($anjunganSettings['carousel_detik'] ?? 3)) ?>">
                        <span class="input-group-text">detik</span>
                    </div>
                </div>
                <div>
                    <label class="form-label">Kembali ke Halaman Utama</label>
                    <div class="input-group">
                        <input type="number" name="kembali_home_detik" class="form-control" min="0" max="7200" value="<?= (int)($anjunganSettings['kembali_home_detik'] ?? 0) ?>">
                        <span class="input-group-text">detik</span>
                    </div>
                    <div class="form-text">Menutup popup/iframe setelah tidak ada aktivitas. Isi 0 untuk menonaktifkan.</div>
                </div>

                <div class="full">
                    <div class="sds-switch-row">
                        <div>
                            <strong>Izinkan pengunjung mengganti tema</strong>
                            <small>Menampilkan tombol pilihan warna dan mode gelap.</small>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="izinkan_pilih_tema" value="1" <?= (int)($anjunganSettings['izinkan_pilih_tema'] ?? 1) === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="sds-switch-row">
                        <div>
                            <strong>Tampilkan jam dan tanggal</strong>
                            <small>Menampilkan waktu pada footer Anjungan.</small>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="tampilkan_jam" value="1" <?= (int)($anjunganSettings['tampilkan_jam'] ?? 1) === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="sds-switch-row">
                        <div>
                            <strong>Tampilkan kontrol layar penuh</strong>
                            <small>Browser tetap membutuhkan klik pengguna untuk masuk ke mode layar penuh.</small>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="tampilkan_fullscreen" value="1" <?= (int)($anjunganSettings['tampilkan_fullscreen'] ?? 1) === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="sds-switch-row">
                        <div>
                            <strong>Mode Pemeliharaan</strong>
                            <small>Menutup seluruh layanan publik sementara tanpa menghapus pengaturan.</small>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="maintenance" value="1" <?= (int)($anjunganSettings['maintenance'] ?? 0) === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-success">Simpan Pengaturan Operasional</button>
            </div>
        </form>
    </div>
</div>
