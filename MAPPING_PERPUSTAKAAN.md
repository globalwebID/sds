# Lokasi Modul

Seluruh halaman operasional berada di folder `/perpustakaan`, sejajar dengan `/absensi`. Folder `siteman` hanya menyediakan link masuk dan pengaturan hak akses.

# Pemetaan Integrasi Perpustakaan ke SDS

| Data lama | Tujuan terintegrasi | Catatan |
|---|---|---|
| `admin` / login Perpustakaan | `admins` + `app_admin_access` | Login mandiri dan SSO tidak digunakan |
| `anggota`, `anggotac` | `perpus_anggota` + relasi SDS | Biodata utama tetap dari siswa/pegawai SDS |
| `jurusan` lama | Referensi jurusan/rombel SDS | Nama lama hanya dipakai membantu migrasi |
| `tipe_member` | `perpus_tipe_member` | Menyimpan aturan jumlah/periode/denda |
| `kategori_buku` | `perpus_kategori_buku` | Domain Perpustakaan |
| `buku` | `perpus_buku` | Record bibliografi |
| `detail_buku` | `perpus_buku_eksemplar` | Satu record per barcode/eksemplar |
| `mst_author` | `perpus_pengarang` | Metadata bibliografi |
| `mst_publisher` | `perpus_penerbit` | Metadata bibliografi |
| `mst_gmd` | `perpus_gmd` | Bentuk dokumen |
| `mst_subyek` | `perpus_subyek` | Subjek buku |
| `mst_tipekoleksi` | `perpus_tipe_koleksi` | Tipe koleksi |
| `peminjaman` | `perpus_peminjaman` | Header transaksi |
| `detail_pinjam` | `perpus_peminjaman_detail` | Item pinjaman dan pengembalian |
| `pengunjung` | `perpus_kunjungan` | Riwayat kunjungan |
| `sso_nonces` | Tidak dimigrasi | SSO dihapus |
| `tbinstansi.kodeapp` | Filter sumber migrasi saja | Tidak menjadi kolom tenant baru |

## Master SDS yang digunakan

- Peserta didik: `pendaftaran_siswa`
- Pengajar/pegawai: `pegawai`
- Rombel/kelas: `siswa_kelas`, `kelas`, `tingkat_kelas`
- Tahun ajaran: master Tahun Ajaran SDS
- Akun aplikasi: `admins`, `app_admin_access`
- Kartu aktif: `kartu_rfid`
- Riwayat kartu: `kartu_rfid_riwayat`

## Prinsip data

`perpus_anggota` hanya menyimpan atribut keanggotaan Perpustakaan, seperti nomor anggota, tipe member, status, dan masa berlaku. Nama, kelas, jabatan, foto, dan UID aktif tidak diduplikasi sebagai master baru.
