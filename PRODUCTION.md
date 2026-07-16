# Panduan Rilis Produksi SDS

## Pemeriksaan sebelum rilis

Jalankan dari root proyek menggunakan PHP XAMPP:

```powershell
C:\xampp-8.2.4\php\php.exe tools\health-check.php
C:\xampp-8.2.4\php\php.exe tools\backup.php
C:\xampp-8.2.4\php\php.exe tools\verify-backup.php
C:\xampp-8.2.4\php\php.exe tools\backup-uploads.php
```

Rilis hanya dilanjutkan jika health check tidak memiliki `[FAIL]`, checksum cocok, dan uji restore berhasil.

## Konfigurasi server

1. Gunakan HTTPS dan ubah `app.base_url` di `config/app.php` ke URL HTTPS final.
2. Arahkan document root hanya ke folder aplikasi ini dan aktifkan `AllowOverride All`.
3. Pada konfigurasi PHP produksi: `display_errors=Off`, `log_errors=On`, `expose_php=Off`.
4. Pada Apache: gunakan `ServerTokens Prod` dan `ServerSignature Off`.
5. Batasi akses tulis akun service Apache hanya ke `uploads`, `storage`, dan `tmp_dompdf`.
6. Jangan membuka port MySQL ke internet. Batasi ke localhost/LAN yang diperlukan.
7. Simpan backup di media/server berbeda; folder backup lokal bukan satu-satunya salinan.

## Jadwal backup

- Database: setiap hari dan sebelum pembaruan.
- Upload: setiap hari atau setelah impor/upload massal.
- Retensi yang disarankan: harian 14 hari, mingguan 8 minggu, bulanan 12 bulan.
- Uji restore dilakukan minimal sebulan sekali dan sebelum rilis besar.

Gunakan Task Scheduler Windows untuk menjalankan perintah backup dengan akun service terbatas. Jangan meletakkan password database pada argumen task; skrip membaca konfigurasi lokal dan memakai file kredensial sementara yang langsung dihapus.

## Urutan deployment

1. Aktifkan halaman pemeliharaan atau batasi akses pengguna.
2. Buat dan verifikasi backup database serta uploads.
3. Salin source baru tanpa menimpa `config/app.php`, `uploads`, dan `storage/backups`.
4. Jalankan installer/migrasi hanya pada paket yang memang memiliki migrasi baru.
5. Jalankan `tools/health-check.php`.
6. Uji login dan fungsi inti untuk setiap peran.
7. Nonaktifkan mode pemeliharaan dan pantau log Apache/PHP.

## Rollback

1. Aktifkan mode pemeliharaan.
2. Kembalikan source versi sebelumnya.
3. Jika skema/data berubah, pulihkan dump database terakhir yang sudah diverifikasi.
4. Pulihkan arsip uploads bila perubahan menyentuh berkas.
5. Jalankan health check dan smoke test sebelum akses pengguna dibuka kembali.

Sinkronisasi e-Rapor tidak termasuk rilis ini dan akan dikembangkan terpisah.
