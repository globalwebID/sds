<?php
declare(strict_types=1);

return [
    'core' => [
        'admins','berkas_pelanggaran','berkas_tambahan','cetak_ttd_daftar_ulang',
        'ekskul_absensi','ekskul_materi','ekstrakurikuler','ekstrakurikuler_siswa',
        'formulir','form_fields','jurusan','kelas','log_aktivitas','nilai_ekskul',
        'pendaftaran_siswa','pengaturan','pengaturan_nipd','siswa_kelas',
        'template_kartu','tingkat_kelas','tmp_pendaftaran_siswa',
    ],
    'attendance' => ['absen','absensi_absen','absensi_user','log_notifikasi_wa','log_scan_rfid','user'],
    'canteen' => ['kantin','transaksi_kantin'],
    'emoney' => [
        'fcm_tokens','game_brands','game_callback_logs','game_margin_brand','game_products',
        'game_transactions','log_transfer','penarikan','topup','users',
    ],
    'kiosk' => [
        'anjungan','anjungan_berita','anjungan_instagram_video','anjungan_menu',
        'anjungan_topright','expo_visitors','informasi','informasi_user','survey_kepuasan',
    ],
    // Sarpras masih merupakan fitur SDS lama yang belum mempunyai paket produk sendiri.
    // Dipisahkan secara logis agar tidak ikut dijual diam-diam sebagai bagian Core.
    'sarpras' => [
        'sp_inventaris','sp_inventaris_ruangan','sp_kategori','sp_laporan_sarpras',
        'sp_masalah','sp_ruangan','sp_sub_kategori','sp_users',
    ],
];
