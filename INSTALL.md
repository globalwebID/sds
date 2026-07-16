# Instalasi SDS Gabungan

Panduan ini berlaku untuk SDS Core modular beserta Absensi, E-Money, mKantin,
Anjungan, dan E-Perpustakaan.

Formulir pendaftaran merupakan bagian wajib SDS Core dan source-nya berada di
`modules/enrollment/app`. URL lama seperti `/formulir`, `/upload.php`, dan halaman
cetak tetap tersedia melalui routing kompatibilitas.

## Persyaratan server

- Apache dengan `mod_rewrite` dan dukungan `.htaccess` (`AllowOverride All`).
- PHP 8.1 atau 8.2.
- Ekstensi PHP: `mysqli`, `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `zip`, dan `SimpleXML`.
- MySQL/MariaDB dengan database berkarakter `utf8mb4`.
- Folder `config`, `storage`, `uploads`, dan `tmp_dompdf` dapat ditulis oleh proses Apache saat instalasi.
- Dependensi Composer pada folder `vendor` tersedia.

Untuk XAMPP, aktifkan ekstensi yang belum tersedia pada `php.ini`, lalu restart Apache.

## Sebelum instalasi

1. Salin seluruh source ke satu folder web, misalnya `htdocs/sds_gabung`.
2. Pastikan file `.htaccess` ikut tersalin.
3. Jangan menaruh folder aplikasi di dalam aplikasi SDS lama.
4. Untuk migrasi server lama, buat backup source dan database terlebih dahulu.
5. Jangan memakai dump database produksi pada lingkungan publik tanpa menghapus data sensitif.

## Instalasi baru

1. Buka `/install/` melalui browser, misalnya `http://localhost/sds_gabung/install/`.
2. Pastikan seluruh pemeriksaan server berwarna hijau.
3. Isi Base URL sesuai alamat akhir aplikasi tanpa garis miring di belakang.
4. Isi koneksi database dan akun superadmin pertama.
5. Biarkan **Buat database** aktif untuk database baru. Akun MySQL harus memiliki izin membuat database.
6. Pilih modul sesuai lisensi sekolah, lalu jalankan instalasi. Installer mengimpor schema Core dari `install/schema/core.sql`, schema produk dari `modules/<id>/database/schema.sql`, dan migrasi modul secara berurutan.
7. Setelah berhasil, installer membuat `config/app.php` dan `storage/installed.lock`, sehingga halaman installer otomatis terkunci.

Jangan menghapus `storage/installed.lock` atau membuka kembali installer pada server aktif.

## Migrasi data yang sudah ada

Gunakan database tujuan kosong dan unggah dump SQL melalui installer, atau pulihkan dump terlebih dahulu kemudian jalankan installer terhadap database tersebut. Migrasi di `install/migrations` dirancang untuk melengkapi schema yang ada. Selalu uji pada salinan database sebelum server produksi.

## Verifikasi setelah instalasi

1. Login superadmin melalui `/siteman/login`.
2. Buka Absensi, mKantin, dan Perpustakaan dari SDS.
3. Pastikan Perpustakaan terbuka pada tab baru dan superadmin langsung masuk.
4. Buat satu admin Perpustakaan, lalu uji login mandiri melalui `/perpustakaan/login`.
5. Uji OPAC `/perpustakaan/opac/`, akun anggota dengan kartu pelajar RFID, kiosk kunjungan, peminjaman, pengembalian, reservasi, laporan, dan ekspor.
6. Pastikan URL tanpa `.php` berfungsi.
7. Periksa log Apache/PHP dan pastikan tidak ada fatal error.
8. Ubah hak akses `config/app.php` agar hanya akun server yang memerlukannya dapat membaca file tersebut.

## Keamanan produksi

- Gunakan HTTPS dan password database khusus aplikasi dengan hak minimum.
- Jangan mengunggah `config/app.php`, dump `.sql`, log, atau backup ke repository publik.
- Pastikan Apache mengizinkan aturan `.htaccess`; aturan proyek memblokir file konfigurasi, dump, log, dan daftar direktori.
- Batasi akses tulis hanya untuk folder yang memang memerlukannya.
- Jadwalkan backup database dan folder unggahan.

## Rollback

Pulihkan source dan database dari backup yang dibuat sebelum instalasi. Jangan menghapus tabel atau kolom migrasi secara manual pada database yang sudah menerima transaksi baru.
