# Changelog v2.4.0

## Perbaikan Data Anggota

- Menghapus query `UNION ALL` antara siswa, pegawai, dan anggota legacy.
- Masing-masing sumber dibaca melalui query terpisah lalu digabungkan di PHP.
- Menghindari fatal error `Illegal mix of collations for operation 'UNION'`.
- Perbaikan v2.2 dimasukkan permanen agar tidak tertimpa patch tampilan berikutnya.

## Data Massal & Excel

- Template Excel resmi untuk import koleksi bibliografi.
- Template Excel resmi untuk import eksemplar/barcode.
- Pembuatan master pengarang, penerbit, kategori, subyek, GMD, bahasa, tempat
  terbit, dan tipe koleksi secara otomatis bila belum tersedia.
- Pencocokan koleksi berdasarkan ID, ISBN, kemudian judul + tahun + penerbit.
- Import ulang tidak menggandakan barcode yang sama; metadata eksemplar diperbarui.
- Kolom kosong tidak menghapus bibliografi atau relasi lama.
- Validasi barcode unik, tanggal, status, kondisi, dan batas jumlah baris.
- Riwayat hasil import beserta ringkasan berhasil/diperbarui/dilewati/gagal.
- Export Excel: anggota, koleksi, eksemplar, pinjaman aktif, riwayat,
  keterlambatan, denda, kunjungan, dan anggota yang belum memiliki RFID.
- Aktivasi keanggotaan massal untuk seluruh siswa aktif, satu rombel, atau
  seluruh pegawai aktif.
- Sinkronisasi anggota lulus/mutasi dan pegawai nonaktif.

## OPAC Publik

- Katalog publik tanpa login pada `/perpustakaan/opac/`.
- Pencarian judul, ISBN, pengarang, penerbit, dan subyek.
- Filter kategori, tipe koleksi, tahun terbit, dan ketersediaan.
- Detail bibliografi, lokasi rak, kondisi, serta status eksemplar.
- Koleksi populer dan tampilan responsif.
- Halaman Pinjaman Saya menggunakan nomor anggota + kartu RFID.
- Pengaturan status OPAC, judul OPAC, dan koleksi populer.

## Database

- Tabel `perpus_import_batch`.
- Metadata eksemplar: nomor inventaris, lokasi rak, kondisi fisik, sumber
  pengadaan, harga, dan tanggal pengadaan.
- Indeks nomor inventaris dan lokasi rak.
