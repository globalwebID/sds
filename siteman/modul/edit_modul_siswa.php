<?php
$data = [];
$fields = [
    'nama_lengkap',
    'email',
    'nisn',
    'sekolah_asal',
    'nomor_ijazah',
    'jenis_kelamin',
    'tempat_lahir',
    'tanggal_lahir',
    'no_kk',
    'nik',
    'no_registrasi_akta',
    'kebutuhan_khusus',
    'agama',
    'alamat',
    'desa',
    'kecamatan',
    'kota',
    'provinsi',
    'latitude',
    'longitude',
    'tempat_tinggal',
    'moda_transportasi',
    'anak_ke',
    'jumlah_saudara_kandung',
    'tinggi_badan',
    'berat_badan',
    'hobi',
    'cita_cita',
    'nomor_kip',
    'nohp_ortu',
    'nohp_siswa',
    'pernyataan_setuju'
];

foreach ($fields as $f) {
    $data[$f] = post($f);
}

$data['latitude'] = floatval($data['latitude']);
$data['longitude'] = floatval($data['longitude']);
$data['anak_ke'] = intval($data['anak_ke']);
$data['jumlah_saudara_kandung'] = intval($data['jumlah_saudara_kandung']);
$data['tinggi_badan'] = intval($data['tinggi_badan']);
$data['berat_badan'] = intval($data['berat_badan']);
$data['pernyataan_setuju'] = isset($_POST['pernyataan_setuju']) ? 1 : 0;

$data['kelas_id'] = $new_kelas_id;
$data['file_kip'] = $file_kip;
$data['file_kk'] = $file_kk;
$data['file_ijazah'] = $file_ijazah;
$data['tahun_ajaran'] = $tahunAjaran;
