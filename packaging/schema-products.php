<?php
declare(strict_types=1);

return [
    'core'=>[
        'patterns'=>['/^admins$/','/^app_admin_access$/','/^kartu_rfid(?:_riwayat)?$/','/^pegawai$/','/^setting$/','/^tahun_ajaran$/','/^sds_/'],
    ],
    'library'=>['patterns'=>['/^perpus_/']],
    'sarpras'=>['patterns'=>['/^sp_/']],
    'canteen'=>['tables'=>['kantin','transaksi_kantin']],
    'kiosk'=>['patterns'=>['/^anjungan/','/^expo_visitors$/','/^survey_kepuasan$/']],
    'emoney'=>['patterns'=>['/^game_/','/^fcm_tokens$/','/^log_transfer$/','/^penarikan$/','/^topup$/','/^users$/']],
    'attendance'=>[
        'patterns'=>['/^absen/','/^absensi_/','/^app_device/','/^artikel$/','/^bentuk_pelanggaran$/','/^cameras$/','/^chat/','/^izin/','/^jadwal_mengajar$/','/^jam_sekolah$/','/^kartu_nama$/','/^kategori(?:_pelanggaran)?$/','/^lain_lain$/','/^level$/','/^libur/','/^lokasi$/','/^mata_pelajaran$/','/^modul$/','/^notifikasi$/','/^pelanggaran$/','/^rfid_fix_map$/','/^role$/','/^sanksi_pelanggaran$/','/^slider$/','/^sso_nonces$/','/^template_surat$/','/^wali_murid$/','/^whatsapp_pesan$/','/^admin$/','/^user$/'],
    ],
];
