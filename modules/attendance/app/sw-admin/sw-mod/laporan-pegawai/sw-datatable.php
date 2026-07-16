<?php header('Content-Type: application/json');
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';

$englishToIndonesianDays = [
    'monday'    => 'senin',
    'tuesday'   => 'selasa',
    'wednesday' => 'rabu',
    'thursday'  => 'kamis',
    'friday'    => 'jumat',
    'saturday'  => 'sabtu',
    'sunday'    => 'minggu'
];
$libur_hari = [];


if(!empty($_POST['modifikasi'])){
    $modifikasi = convert('decrypt', $_POST['modifikasi']);
}else{
    $modifikasi ='N';
}

if(!empty($_POST['modifikasi'])){
    $hapus = convert('decrypt', $_POST['hapus']);
}else{
    $hapus ='N';
}

$data_absen = null;    
$pegawai    = !empty($_POST['pegawai']) ? anti_injection(epm_decode($_POST['pegawai'])) : null;
$from       = !empty($_POST['from']) ? date('Y-m-d', strtotime($_POST['from'])) : $date;
$to         = !empty($_POST['to']) ? date('Y-m-d', strtotime($_POST['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND pegawai_id = '$pegawai'";


function cekLibur($connection, $tanggal) {
    $query = "SELECT keterangan FROM libur_nasional WHERE libur_tanggal ='$tanggal'";
    $result = $connection->query($query);
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        return [
            'is_libur' => true,
            'keterangan' => strip_tags($data['keterangan'] ?? ''),
            'background' => 'danger'
        ];
    }

    return [
        'is_libur' => false,
        'keterangan' => '',
        'background' => ''
    ];
}

function countAbsen($connection, $filter, $kolom, $nilai) {
    $query = "SELECT COUNT(*) as jumlah FROM absen_pegawai WHERE $filter AND $kolom = '$nilai'";
    $result = $connection->query($query);
    $data = $result->fetch_assoc();
    return (int) ($data['jumlah'] ?? 0);
}

// Memanggil fungsi
$jumlah_hadir    = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat    = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin     = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_sakit    = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');
$jumlah_cuti     = countAbsen($connection, $filter_range, 'kehadiran', 'Cuti');


function hitung_total_jam_kerja($connection, $pegawai, $from, $to) {
    // Buat query SQL
    $query = "SELECT absen_in, absen_out 
              FROM absen_pegawai WHERE pegawai_id= '$pegawai' 
              AND DATE(tanggal) BETWEEN '$from' AND '$to'
              AND absen_in IS NOT NULL 
              AND absen_out IS NOT NULL";
    $result = mysqli_query($connection, $query);
    $total_seconds = 0;
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $in = strtotime($row['absen_in']);
            $out = strtotime($row['absen_out']);
            if ($in && $out && $out > $in) {
                $total_seconds += ($out - $in); // Selisih waktu dalam detik
            }
        }
    }else {
        return '00'; // Jika tidak ada data, kembalikan 0 jam
    }

    // Konversi ke format jam:menit:detik
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    $seconds = $total_seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
// Hitung total jam kerja
$total_jam = hitung_total_jam_kerja($connection, $pegawai, $from, $to);



// Mendapatkan tanggal range dari 'from' dan 'to'
$startDate = new DateTime($from);
$endDate = new DateTime($to);

// Simpan data
$data = [];
$jumlah_belum_absen = 0;
$jumlah_libur_kantor = 0;
$jumlah_libur_nasional = 0;
$background = '';
$status = '-';

$data_pegawai = null;
$query_pegawai = "SELECT pegawai_id,nama_lengkap,jabatan FROM pegawai WHERE pegawai_id='$pegawai'";
$result_pegawai = $connection->query($query_pegawai);
$data_pegawai = $result_pegawai->fetch_assoc();
$pegawai_id = $data_pegawai['pegawai_id']??'';
$jabatan = $data_pegawai['jabatan']??'';

$no=0;
while ($startDate <= $endDate) {$no++;
    $dateStr = $startDate->format('Y-m-d');
    $dayNameEnglish = strtolower($startDate->format('l'));
    $dayName = $englishToIndonesianDays[$dayNameEnglish];
    $hari_libur = date('D',strtotime($dateStr));
    $liburInfo = cekLibur($connection, $dateStr);

    $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='$jabatan'";
    $result_libur = $connection->query($query_libur);
    if ($result_libur && $result_libur->num_rows > 0) {
        while ($row_libur = $result_libur->fetch_assoc()) {
            // Simpan dalam format lowercase untuk memudahkan perbandingan
            $libur_hari[] = strtolower($row_libur['hari']);
        }
    }
    $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
    
    
    // Cek absensi untuk tanggal tersebut
    $query_absen = "SELECT * FROM absen_pegawai WHERE tanggal='$dateStr' AND pegawai_id='$pegawai_id'";
    $result_absen = $connection->query($query_absen);
    if ($result_absen->num_rows > 0) {
    $data_absen = $result_absen->fetch_assoc();
    $absen_in = $data_absen['absen_in'] ?? '';
    $absen_out = $data_absen['absen_out'] ?? '';
    $jam_kerja_in = $data_absen['jam_kerja_in'] ?? '';
    $jam_kerja_out = $data_absen['jam_kerja_out'] ?? '';
    $jam_kerja_toleransi = $data_absen['jam_kerja_toleransi'] ?? '';

    if ($holidayName=='Libur') {
        $background = 'danger';
        $kehadiran = 'Libur';
        
    } else {

        if ($liburInfo['is_libur']) {
            $background = $liburInfo['background'];
            $kehadiran = $liburInfo['keterangan'];
           // $jumlah_libur_kantor++;
        }else{
        // Cek status kehadiran
            if ($data_absen['kehadiran'] == 'Izin') {
                $background = 'warning'; // Izin, beri background kuning
                $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']) . '</span>';
            } else {
                $background = 'success'; // Hadir, beri background hijau
                $kehadiran = 'Hadir';
            }
        }
    }
    
    // Status Masuk
    $status_masuk = ($data_absen['status_masuk'] === 'Tepat Waktu') 
    ? '<span class="badge badge-success">'.$data_absen['status_masuk'].'</span>' 
    : (($data_absen['status_masuk'] === 'Telat')  // Cek jika status_masuk 'Telat'
        ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
        : ($data_absen['status_masuk'] !== '' 
            ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
            : '<span class="badge badge-warning">Belum Absen</span>'));  // Default jika kosong

    // Status Pulang
    $status_pulang = $data_absen['absen_out'] === '00:00:00' 
        ? '' 
        : ($data_absen['status_pulang'] === 'Tepat Waktu' 
            ? '<span class="badge badge-success">'.$data_absen['status_pulang'].'</span>' 
            : '<span class="badge badge-danger">'.$data_absen['status_pulang'].'</span>');

    // Status Kehadiran
    $kehadiran = $holidayName ? 'Libur' : 
        ($data_absen['kehadiran'] !== '' 
            ? '<span class="badge badge-info">'.strip_tags($data_absen['kehadiran']??'').'</span>' 
            : '<span class="badge badge-warning">Tidak Hadir</span>');

    // Map IN
    $map_in = ($data_absen['map_in'] && $data_absen['map_in'] !== '-') 
        ? '<a href="https://www.google.com/maps/place/'.$data_absen['map_in'].'" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-map-marker-alt"></i> IN</a>' 
        : '-';

    // Map OUT
    $map_out = ($data_absen['map_out'] && $data_absen['map_out'] !== '-') 
        ? '<a href="https://www.google.com/maps/place/'.$data_absen['map_out'].'" class="btn btn-info btn-sm" target="_blank"><i class="fas fa-map-marker-alt"></i> OUT</a>' 
        : '';


    $foto = strip_tags($data_absen['foto_in']??'avatar.jpg');
    if (!empty($foto) AND file_exists('../../../sw-content/absen/'.$foto.'')) {
        $foto_masuk ='<a href="../sw-content/absen/'.$foto.'" class="open-popup-link">
                        <img src="../sw-content/absen/'.$foto.'" class="avatar rounded-circle mr-3" height="50">
                    </a>';
    } else {
        $foto_masuk = '<a href="#" class="avatar rounded-circle mr-3">
            <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">
        </a>';
    }

    $foto_out = strip_tags($data_absen['foto_out']??'avatar.jpg');
    if (!empty($foto_out) AND file_exists('../../../sw-content/absen/'.$foto_out.'')) {
        $foto_pulang ='<a href="../sw-content/absen/'.$foto_out.'" class="open-popup-link">
                        <img src="../sw-content/absen/'.$foto_out.'" class="avatar rounded-circle mr-3" height="50">
                    </a>';
    } else {
        $foto_pulang = '<a href="#" class="avatar rounded-circle mr-3">
            <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">
        </a>';
    }


    if($data_absen['absen_out'] !== NULL && $data_absen['absen_out'] !==''){
        $durasi_mulai = new DateTime(''.$data_absen['tanggal'].' '.$data_absen['absen_in'].'');
        $durasi_selesai = new DateTime(''.$data_absen['tanggal'].' '.$data_absen['absen_out'].'');
        $durasi = $durasi_mulai->diff($durasi_selesai);
        $durasi  = $durasi->format('%H:%i:%s');
    }else{
        $durasi= '-';
    }

    $radius = $data_absen['radius'];
    $keterangan = strip_tags($data_absen['keterangan']??'-');

    if($modifikasi =='Y'){
        $btn_update = ' <a href="javascript:void(0)" class="btn btn-outline-default btn-sm btn-update btn-tooltip" data-toggle="tooltip" data-placement="top" title="Edit" data-id="'.epm_encode($data_absen['absen_id']).'">
        <i class="fas fa-edit"></i>
        </a>';
    }else{
        $btn_update ='<a href="javascript:void(0)" class="btn btn-outline-default btn-sm btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Edit">
        <i class="fas fa-edit"></i>
        </a>';
    }

 } else {
    // Tidak ada data absen
    if ($holidayName == 'Libur') {
        $background = 'danger';
        $kehadiran = 'Libur';
        $jumlah_libur_kantor++;
    } else {
        if ($liburInfo['is_libur']) {
            $background = $liburInfo['background'];
            $kehadiran = $liburInfo['keterangan'];
            $jumlah_libur_nasional++;
        }else{
            $background = 'white';
            $kehadiran = 'Tidak Hadir';
        }
    }
    $foto_masuk = '<a href="#" class="avatar rounded-circle mr-3">
        <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50"></a>';
    $foto_pulang = '<a href="#" class="avatar rounded-circle mr-3">
        <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';  
    $jam_kerja_in = null; $jam_kerja_out = null; $jam_kerja_toleransi = '-';
    $absen_in = null; $absen_out = null; 
    $status_masuk =null; $status_pulang = null;
    $durasi = '-';
    $radius = '-';
    $keterangan = '-';
    $map_in = '-'; $map_out =null;
    $btn_update = null;
    // Tidak ada absensi
    $jumlah_belum_absen++;
}

    $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
    // Menyimpan data tanggal dan status
    $data[] = [
        'no' => $no,
        'date' => tanggal_ind($dateStr),
        'pegawai' => ($data_pegawai['nama_lengkap'] ?? '-') . "<br>" . ucfirst($data_pegawai['jabatan'] ?? ''),
        'jam_kerja' => ($jam_kerja_in ?? '') . " - " . ($jam_kerja_out ?? ''),
        'toleransi' => $jam_kerja_toleransi??'-',
        'foto_masuk' => '<div class="text-center">'.$foto_masuk.'</div>',
        'absen_masuk' => (strip_tags($absen_in??'')).'<br>' . $status_masuk,
        'foto_pulang' => '<div class="text-center">'.$foto_pulang.'</div>',
        'absen_pulang' => (strip_tags($absen_out??'')).'<br>' . $status_pulang,
        'durasi' => $durasi,
        'radius' => $radius,
        'status' => $kehadiran, // Status Libur atau Kehadiran
        'titik_lokasi' => ''.$map_in.''.$map_out.'',
        'keterangan' => $keterangan,
        'aksi' => $btn_update,
        'background' => $background,
    ];

    // Menambah 1 hari
    $startDate->modify('+1 day');
}

    $response = [
        'data' => $data, // ini data datatable biasa
        'jumlah_hadir'  => $jumlah_hadir,
        'jumlah_telat'  => $jumlah_telat,
        'jumlah_izin'   => $jumlah_izin,
        'jumlah_sakit'  => $jumlah_sakit,
        'jumlah_belum_absen' => $jumlah_belum_absen,
        'total_jam'     => $total_jam,
    ];
    
    // Kirim response JSON untuk DataTables
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data,
        "jumlah_hadir"         => $jumlah_hadir,
        "jumlah_telat"         => $jumlah_telat,
        "jumlah_izin"          => $jumlah_izin,
        "jumlah_sakit"         => $jumlah_sakit,
        "jumlah_belum_absen"   => $total_blm_absen,
        "total_jam"            => $total_jam,
    ]);
  
}