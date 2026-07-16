<?php
declare(strict_types=1);

return [
    'modules/library/database/migrations/010_perpus_integrated.sql' => [
        'tables'=>['app_admin_access','kartu_rfid','kartu_rfid_riwayat','perpus_tipe_member','perpus_anggota','perpus_buku','perpus_buku_eksemplar','perpus_peminjaman','perpus_peminjaman_detail','perpus_kunjungan','perpus_pengaturan','perpus_migrasi_log'],
    ],
    'modules/library/database/migrations/011_perpus_adminlte_feature_phase1.sql' => [
        'tables'=>['perpus_perpanjangan','perpus_denda_pembayaran'],
        'columns'=>['perpus_tipe_member'=>['maksimal_perpanjangan','hari_perpanjangan'],'perpus_peminjaman_detail'=>['jumlah_perpanjangan','denda_status','kondisi_kembali','catatan_kembali']],
    ],
    'modules/library/database/migrations/012_perpus_data_massal_opac.sql' => [
        'tables'=>['perpus_import_batch'],
        'columns'=>['perpus_buku_eksemplar'=>['nomor_inventaris','lokasi_rak','kondisi_fisik','sumber_pengadaan','harga','tanggal_pengadaan']],
    ],
    'modules/library/database/migrations/013_perpus_reservasi_notifikasi_kiosk.sql' => [
        'tables'=>['perpus_reservasi','perpus_notifikasi','perpus_pengingat_log'],
        'columns'=>['perpus_kunjungan'=>['sumber']],
        'column_contains'=>['perpus_kunjungan.sumber'=>'kiosk'],
    ],
    'modules/library/database/migrations/014_perpus_laporan_audit_saran.sql' => [
        'tables'=>['perpus_saran','perpus_audit_log','perpus_audit_check'],
    ],
    'modules/library/database/migrations/014_perpus_standalone_auth.sql' => [
        'tables'=>['perpus_users'],
        'columns'=>['perpus_users'=>['sds_admin_id','username','password','role','status']],
    ],
];
