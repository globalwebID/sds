Patch: student_edit partial - kelas aktif & koordinat wilayah

File utama yang diganti:
- siteman/pages/partials/biodata/data_siswa.php

Perbaikan:
1. Kelas pada mode edit sekarang mengambil kelas aktif dari tabel siswa_kelas untuk tahun ajaran aktif.
   Ini mencegah siswa yang sudah naik kelas XI/XII tampil kembali sebagai kelas lama.
2. Hidden input provinsi, kota, kecamatan, desa sekarang langsung berisi data lama dari database.
   Jika API wilayah gagal match, data lama tidak hilang saat disimpan.
3. Select wilayah dibuat lebih toleran terhadap format nama, misalnya KABUPATEN/KOTA/KECAMATAN/DESA.
4. Koordinat otomatis mengikuti perubahan Provinsi, Kabupaten/Kota, Kecamatan, dan Desa.
5. Input koordinat manual juga menyinkronkan latitude dan longitude.

Catatan:
Jika setelah mengganti partial ini data kelas masih berubah saat disimpan, cek juga file:
- siteman/pages/edit_proses.php
karena proses simpan harus update siswa_kelas tahun ajaran aktif, bukan hanya pendaftaran_siswa.kelas_id.
