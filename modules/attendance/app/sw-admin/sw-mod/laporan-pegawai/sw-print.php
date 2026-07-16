<?php use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
require_once '../../login/user.php';

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

$no = 0;
switch (@$_GET['action']){
case 'print':

$data_absen = null;    
$pegawai    = !empty($_GET['pegawai']) ? anti_injection(epm_decode($_GET['pegawai'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND pegawai_id= '$pegawai'";


$data_pegawai = null;
$query_pegawai = "SELECT pegawai_id,nama_lengkap,jabatan FROM pegawai WHERE pegawai_id='$pegawai'";
$result_pegawai = $connection->query($query_pegawai);
if($result_pegawai->num_rows > 0){
$data_pegawai = $result_pegawai->fetch_assoc();
$pegawai_id = $data_pegawai['pegawai_id']??'';
$jabatan = $data_pegawai['jabatan']??'';

// Memanggil fungsi
$jumlah_hadir    = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat    = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin     = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_cuti     = countAbsen($connection, $filter_range, 'kehadiran', 'Cuti');
$jumlah_sakit     = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');

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
  $jam_masuk = $data_absen['jam_masuk'] ?? '';
  $jam_pulang = $data_absen['jam_pulang'] ?? '';
  $jam_toleransi = $data_absen['jam_toleransi'] ?? '';

  if ($holidayName=='Libur') {
      $background = 'danger';
      $kehadiran = 'Libur';
      
  } else {

      if ($liburInfo['is_libur']) {
          $background = $liburInfo['background'];
          $kehadiran = $liburInfo['keterangan'];
          //$jumlah_libur_kantor++;
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
            $kehadiran = 'A';
        }
    }
    $foto_masuk = '<a href="#" class="avatar rounded-circle mr-3">
        <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50"></a>';
    $foto_pulang = '<a href="#" class="avatar rounded-circle mr-3">
        <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';  
    $jam_masuk = $jam_pulang = $absen_in = $absen_out = $status_masuk = $status_pulang = null;
    $jam_toleransi = $durasi = $radius = $keterangan = $map_in = $map_out = '-';
    $jumlah_belum_absen++;
}

  $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
  // Menyimpan data tanggal dan status
  $data[] = [
      'no' => $no,
      'date' => tanggal_ind($dateStr),
      'pegawai' => ($data_pegawai['nama_lengkap'] ?? '-'),
      'jabatan' => ucfirst($data_pegawai['jabatan'] ?? '-'),
      'jam_kerja' => ($jam_masuk ?? '') . " - " . ($jam_pulang ?? ''),
      'toleransi' => $jam_toleransi??'-',
      'absen_masuk' => (strip_tags($absen_in??'')),
      'status_masuk' => $status_masuk,
      'absen_pulang' => (strip_tags($absen_out??'')),
      'status_pulang' => $status_pulang,
      'durasi' => $durasi,
      'radius' => $radius,
      'status' => $kehadiran, // Status Libur atau Kehadiran
      'titik_lokasi' => $map_in,
      'titik_lokasi_out' => $map_out,
      'keterangan' => $keterangan,
      'background' => $background,
  ];

  // Menambah 1 hari
  $startDate->modify('+1 day');
}

$response = [
  'data' => $data, // ini data datatable biasa
  'jumlah_hadir' => $jumlah_hadir,
  'jumlah_telat' => $jumlah_telat,
  'jumlah_izin' => $jumlah_izin,
  'jumlah_sakit'    => $jumlah_sakit,
  'jumlah_belum_absen' => $total_blm_absen // ini yang kamu pakai sekarang
];

echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Laporan Absensi Pegawai</title>
    <style>

    body { font-family: Arial, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: center; }
        th { background-color: #f2f2f2; }
        .danger { background-color: #f8d7da; }
        .success { background-color: #d4edda; }
        .warning { background-color: #fff3cd; }
        .white { background-color: #ffffff; }
        img.avatar { width: 50px; height: 50px; border-radius: 50%; }
        .badge { padding: 3px 5px; border-radius: 4px; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-warning { background-color: #ffc107; color: black; }
        .text-center { text-align: center; }
        .text-left{ text-align:left; }
        .text-right{ text-align:right; }
        h3 { font-size: 20px; text-transform: uppercase; }
        .kop{
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
            margin-bottom: 20px;
            text-align: center;
        }
        .kop img{
          width:90%;
          height: auto;
        }

        .ttd td { border:0px; padding: 6px; text-align: center; }
</style>';?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
$(document).ready(function($) {
    var ua = navigator.userAgent.toLowerCase();
    var isAndroid = ua.indexOf("android") > -1;

    if (isAndroid) {
        var gadget = new cloudprint.Gadget();
        gadget.setPrintDocument("url", $('title').html(), window.location.href, "utf-8");
        gadget.openPrintDialog();
    } else {
        window.print();
        window.onafterprint = window.close;
    }
    return false;
});
</script>

<?php echo'
</head>
<body>

  <div class="container">
    <div class="kop text-center">';
    if(file_exists("../../../sw-content/$site_kop")){
      echo'
      <img src="../../../sw-content/'.$site_kop.'">';
    }
    echo'
    </div>

    <h3 class="text-center" style="margin:0px;">LAPORAN ABSENSI PEGAWAI</h3>
    <p class="text-center" style="margin:0px;">Tanggal : '.tanggal_ind($from).' - '.tanggal_ind($to).'</p>

    <table class="datatable mt-3">
        <thead>
        <tr>
            <th rowspan="2" class="text-center">No</th>
            <th rowspan="2">Tanggal</th>
            <th rowspan="2">Nama</th>
            <th rowspan="2">Jabatan</th>
            <th rowspan="2">Jam Kerja</th>
            <th rowspan="2">Toleransi</th>
            <th colspan="2" class="text-center">Absen Masuk</th>
            <th colspan="2" class="text-center">Abse Pulang</th>
            <th rowspan="2">Durasi</th>
            <th rowspan="2">Keterangan</th>
        </tr>
        <tr>
            <th class="text-center">Jam</th>
            <th class="text-center">Status</th>
            <th class="text-center">Jam</th>
            <th class="text-center">Status</th>
        </tr>
        </thead>
        <tbody>';
        foreach ($data as $row) {
        echo '<tr class="'.$row['background'].'">
            <td>'.$row['no'].'</td>
            <td>'.$row['date'].'</td>
            <td>'.$row['pegawai'].'</td>
            <td>'.$row['jabatan'].'</td>
            <td>'.$row['jam_kerja'].'</td>
            <td>'.$row['toleransi'].'</td>
            <td>'.$row['absen_masuk'].'</td>
            <td>'.$row['status_masuk'].'</td>
            <td>'.$row['absen_pulang'].'</td>
            <td>'.$row['status_pulang'].'</td>
            <td>'.$row['durasi'].'</td>
            <td style="text-align:left">'.$row['status'].'</td>
        </tr>';
        }
        echo'
        </tbody>
    </table>
    <table>
    <tfoot style="background:#f2f2f2;">
            <tr>
                <td class="text-right">Hadir</td>
                <td class="font-weight-bold"><span class="badge badge-success hadir-cell">'.$jumlah_hadir.'</span></td>

                <td class="text-right">Telat</td>
                <td><span class="badge badge-danger telat-cell">'.$jumlah_telat.'</span></td>

                <td class="text-right">Izin</td>
                <td><span class="badge badge-warning izin-cell">'.$jumlah_izin.'</span></td>

                <td class="text-right">Sakit</td>
                <td><span class="badge badge-info izin-cell">'.$jumlah_sakit.'</span></td>
            
                <td class="text-right">Total Jam Bekerja:</td>
                <td class="text-left"><span class="badge badge-info belum-absen-cell">'.$total_jam.'</span></td>

                <td class="text-right">Total Belum Absen</td>
                <td class="text-left"><span class="badge badge-danger belum-absen-cell">'.$total_blm_absen.'</span></td>
            </tr>
        </tfoot>
    </table>

    <table class="ttd" style="width:100%;margin-top:30px;border: 0px!important;">
        <tr>
            <td style="width:60%;"></td>

            <td style="width:40%;text-align:left;vertical-align:top;">
            '.$row_site['kabupaten'].', '.tgl_indo($date).'<br>
            <strong>Plt. Satuan Pendidikan</strong><br><br><br><br><br><br><br><br>
            
            <b>'.strip_tags($row_site['kepala_sekolah']??'').'</b><br>
            NIP. '.($row_site['nip_kepala_sekolah']??'-').'
            </td>
        </tr>
    </table>

      
  </div>
</body>
</html>';

}else{
  echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
  <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}


break;
case'pdf':
require_once '../../../sw-library/fpdf/fpdf.php';

$data_absen = null;    
$pegawai    = !empty($_GET['pegawai']) ? anti_injection(epm_decode($_GET['pegawai'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND pegawai_id = '$pegawai'";


$data_pegawai = null;
$query_pegawai = "SELECT pegawai_id,nama_lengkap,jabatan FROM pegawai WHERE pegawai_id='$pegawai'";
$result_pegawai = $connection->query($query_pegawai);
if($result_pegawai->num_rows > 0){
$data_pegawai = $result_pegawai->fetch_assoc();
$pegawai_id = $data_pegawai['pegawai_id']??'';
$jabatan = $data_pegawai['jabatan']??'';


// Memanggil fungsi
$jumlah_hadir   = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat   = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin    = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_sakit     = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');

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
  $jam_masuk = $data_absen['jam_masuk'] ?? '';
  $jam_pulang = $data_absen['jam_pulang'] ?? '';
  $jam_toleransi = $data_absen['jam_toleransi'] ?? '';

  if ($holidayName=='Libur') {
      $background = 'danger';
      $kehadiran = 'Libur';
  } else {
      if ($liburInfo['is_libur']) {
          $background = $liburInfo['background'];
          $kehadiran = $liburInfo['keterangan'];
          //$jumlah_libur_kantor++;
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
  if (!empty($data_absen['map_in']) && $data_absen['map_in'] !== '-') {
      $map_in_text = 'IN';
      $map_in_url = 'https://www.google.com/maps/place/' . $data_absen['map_in'];
  } else {
      $map_in_text = 'IN';
      $map_in_url = '-';
  }

  // Map OUT
  if (!empty($data_absen['map_out']) && $data_absen['map_out'] !== '-') {
      $map_out_text = 'OUT';
      $map_out_url = 'https://www.google.com/maps/place/' . $data_absen['map_out'];
  } else {
      $map_out_text = '-';
      $map_out_url = '-';
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
          $kehadiran = 'A';
      }
  }
  
    $jam_masuk = $jam_pulang = $absen_in = $absen_out = $status_masuk = $status_pulang = null;
    $jam_toleransi = $durasi = $radius = $keterangan = $map_in_url = $map_out_url = '-';
    $jumlah_belum_absen++;
}

  $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
  // Menyimpan data tanggal dan status
  $data[] = [
      'no' => $no,
      'date' => tanggal_ind($dateStr),
      'pegawai' => ($data_pegawai['nama_lengkap'] ?? '-'),
      'jabatan' => ucfirst($data_pegawai['jabatan'] ?? '-'),
      'jam_kerja' => ($jam_masuk ?? '') . " - " . ($jam_pulang ?? ''),
      'toleransi' => $jam_toleransi??'-',
      'absen_masuk' => (strip_tags($absen_in??'')),
      'status_masuk' => strip_tags($status_masuk??''),
      'absen_pulang' => (strip_tags($absen_out??'')),
      'status_pulang' => strip_tags($status_pulang??''),
      'durasi' => $durasi??'-',
      'radius' => $radius??'-',
      'status' => strip_tags($kehadiran??''), // Status Libur atau Kehadiran
      'titik_lokasi' => $map_in_url,
      'titik_lokasi_out' => $map_out_url,
      'keterangan' => $keterangan,
      'background' => $background,
  ];

  // Menambah 1 hari
  $startDate->modify('+1 day');
}

$response = [
  'data' => $data, // ini data datatable biasa
  'jumlah_hadir' => $jumlah_hadir,
  'jumlah_telat' => $jumlah_telat,
  'jumlah_izin' => $jumlah_izin,
  'jumlah_sakit'    => $jumlah_sakit,
  'total_jam_kerja' => $total_jam,
  'jumlah_belum_absen' => $total_blm_absen // ini yang kamu pakai sekarang
];

// PDF Setup
class PDFWithFooter extends FPDF {
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . ' dari {nb} | Dicetak pada ' . date('d-m-Y H:i'), 0, 0, 'C');
    }
}


if(file_exists("../../../sw-content/".strip_tags($site_kop??'logo.jpg')."")){
  $kopPath = '../../../sw-content/'.strip_tags($site_kop).'';
}else{
  $kopPath = '../../../sw-content/'.strip_tags($site_logo).'';
}

$pdf = new PDFWithFooter();
$pdf->AliasNbPages();
$pdf->AddPage('L', 'Legal');


$pageWidth = $pdf->GetPageWidth();
$imageWidth = 180;
$centerX = ($pageWidth - $imageWidth) / 2;
$pdf->Image($kopPath, $centerX, 10, $imageWidth);
$pdf->Ln(25);

$pdf->Line(10, $pdf->GetY(), 340, $pdf->GetY());
$pdf->Ln(5);

// Header Laporan
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, 'LAPORAN KEHADIRAN PEGAWAI', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Periode: ' . tgl_ind($from) . ' s.d. ' .tgl_ind($to), 0, 1, 'C');
$pdf->Ln(5);


$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 200, 200); 
$header = [
    'No', 'Tanggal', 'Nama', 'Jabatan', 'Jam Kerja', 'Toleransi',
    'Absen Masuk', 'Absen Pulang', 'Durasi', 'Radius', 'Kehadiran', 'Lokasi IN', 'Lokasi OUT'
];

$widths = [8, 25, 55, 30, 30, 20, 35, 35, 20, 20, 20, 20, 20];

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 8, $col, 1, 0, 'C', true);
}
$pdf->Ln();


// Data
$pdf->SetFont('Arial', '', 9);
foreach ($data as $row) {

 if ($row['background'] == 'danger') {
      $pdf->SetFillColor(255,99,71); // Warna merah
      $fill = true;
  }else if ($row['status'] == 'Hadir') {
      $pdf->SetFillColor(212, 237, 812);
      $fill = true;
  }elseif($row['status'] == 'Izin' OR $row['status'] == 'Cuti'){
      $pdf->SetFillColor(255, 243, 205);
      $fill = true;
  } else {
      $pdf->SetFillColor(255, 255, 255);
      $fill = false;
  }

    $pdf->Cell($widths[0], 7, $row['no'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[1], 7, $row['date'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[2], 7, strip_tags($row['pegawai']??''), 1, 0, 'L', $fill);
    $pdf->Cell($widths[3], 7, strip_tags($row['jabatan']??''), 1, 0, 'L', $fill);
    $pdf->Cell($widths[4], 7, strip_tags($row['jam_kerja']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[5], 7, strip_tags($row['toleransi']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[6], 7, ''.($row['absen_masuk']??'').' '.$row['status_masuk'].' ', 1, 0, 'C', $fill);
    $pdf->Cell($widths[7], 7, ''.($row['absen_pulang']??'').' '.$row['status_pulang'].'', 1, 0, 'C', $fill);
    $pdf->Cell($widths[8], 7, strip_tags($row['durasi']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[9], 7, ($row['radius']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[10], 7, strip_tags($row['status']??''), 1, 0, 'C', $fill);
    if ($row['titik_lokasi'] !=='-') {
        $pdf->SetTextColor(0, 0, 255); // Biru seperti hyperlink
        $pdf->SetFont('', 'U');        // Underline
        $pdf->Cell($widths[11], 7, $map_in_text, 1, 0, 'C', $fill, $row['titik_lokasi']);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        $pdf->Cell($widths[11], 7, '-', 1, 0, 'C', $fill);
    }

// Link OUT
     if ($row['titik_lokasi_out'] !=='-') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->SetFont('', 'U');
        $pdf->Cell($widths[12], 7, $map_out_text, 1, 0, 'C', $fill, $row['titik_lokasi_out']);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        $pdf->Cell($widths[12], 7, '-', 1, 0, 'C', $fill);
    }
    $pdf->SetTextColor(0); // Hitam
    $pdf->SetFont('');
    $pdf->Ln();
}

// Rekapitulasi
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Rekapitulasi:', 0, 1);
$pdf->SetFont('Arial', '', 9);

$rekap = [
    'Jumlah Hadir' => $response['jumlah_hadir'],
    'Jumlah Telat' => $response['jumlah_telat'],
    'Jumlah Izin' => $response['jumlah_izin'],
    'Jumlah Sakit' => $response['jumlah_sakit'],
    'Total Jam Kerja' => $response['total_jam_kerja'],
    'Belum Absen' => $response['jumlah_belum_absen']
];

foreach ($rekap as $label => $jumlah) {
    $pdf->Cell(40, 6, $label, 1);
    $pdf->Cell(20, 6, $jumlah, 1, 1);
}


  $pdf->Ln(20);
  // Posisi tanda tangan (geser kanan)
  $pdf->Cell(200, 6, '', 0, 0);
  $pdf->SetFont('Arial', '', 11);
  $pdf->Cell(0, 5, $row_site['kabupaten'] . ', ' . tgl_indo($date), 0, 1, 'L');
  $pdf->SetFont('Arial', 'B', 11);
  $pdf->Cell(200, 6, '', 0, 0);
  $pdf->Cell(0, 8, 'Kepala Sekolah', 0, 1, 'L');

  // Tambahkan stempel di atas nama kepala sekolah
  $stempelPath = '../../../sw-content/'.($row_site['stempel']??'stempel.png').'';
  if (file_exists($stempelPath)) {
      // Ambil ukuran gambar
      list($width, $height) = getimagesize($stempelPath);

      $desiredWidth = 45; // mm
      $desiredHeight = ($height / $width) * $desiredWidth;

      $xStempel = 20 + 20 + 100 + 60;
      $yStempel = $pdf->GetY() - 5; 
      
      // Gambar dengan ukuran yang dihitung
      $pdf->Image($stempelPath, $xStempel, $yStempel, $desiredWidth, $desiredHeight);
  }


  $pdf->Ln(22); // ruang untuk tanda tangan
  $pdf->SetFont('Arial', 'B', 11);
  $pdf->Cell(200, 6, '', 0, 0);
  $pdf->Cell(0, 6, strip_tags($row_site['kepala_sekolah'] ?? '-'), 0, 1, 'L');
  $pdf->SetFont('Arial', '', 11);
  $pdf->Cell(200, 6, '', 0, 0);
  $pdf->Cell(20, 6, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'), 0, 0, 'L');


// Output PDF
$pdf->Output('I', 'Laporan_kehadiran_Pegawai_'.$from.'-'.$to.'.pdf');


}else{
  echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
  <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}




/** Download Excel */
break;
case'excel';
require '../../../sw-library/PhpSpreadsheet/autoload.php';

$data_absen = null;    
$pegawai    = !empty($_GET['pegawai']) ? anti_injection(epm_decode($_GET['pegawai'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND pegawai_id = '$pegawai'";

$data_pegawai = null;
$query_pegawai = "SELECT pegawai_id,nama_lengkap,jabatan FROM pegawai WHERE pegawai_id='$pegawai'";
$result_pegawai = $connection->query($query_pegawai);
if($result_pegawai->num_rows > 0){
$data_pegawai = $result_pegawai->fetch_assoc();
$pegawai_id = $data_pegawai['pegawai_id']??'';
$jabatan = $data_pegawai['jabatan']??'';

// Memanggil fungsi
$jumlah_hadir = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat    = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin     = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_sakit     = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');

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
  $jam_masuk = $data_absen['jam_masuk'] ?? '';
  $jam_pulang = $data_absen['jam_pulang'] ?? '';
  $jam_toleransi = $data_absen['jam_toleransi'] ?? '';

  if ($holidayName=='Libur') {
      $background = 'danger';
      $kehadiran = 'Libur';
      
  } else {

      if ($liburInfo['is_libur']) {
          $background = $liburInfo['background'];
          $kehadiran = $liburInfo['keterangan'];
          //$jumlah_libur_kantor++;
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
  if (!empty($data_absen['map_in']) && $data_absen['map_in'] !== '-') {
      $map_in_url = 'https://www.google.com/maps/place/' . $data_absen['map_in'].'';
  } else {
      $map_in_url = '-';
  }

  // Map OUT
  if (!empty($data_absen['map_out']) && $data_absen['map_out'] !== '-') {
      $map_out_url = 'https://www.google.com/maps/place/' . $data_absen['map_out'].'';
  } else {
      $map_out_url = '-';
  }

   if($data_absen['absen_out'] !=='00:00:00' && $data_absen['absen_out'] !==''){
        $durasi_mulai = new DateTime(''.$data_absen['tanggal'].' '.$data_absen['absen_in'].'');
        $durasi_selesai = new DateTime(''.$data_absen['tanggal'].' '.$data_absen['absen_out'].'');
        $durasi = $durasi_mulai->diff($durasi_selesai);
        $durasi  = $durasi->format('%H:%i:%s');
    }else{
        $durasi= '-';
    }

    $radius = $data_absen['radius'];
    $keterangan = strip_tags($data_absen['keterangan']??'-');


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
          $kehadiran = 'A';
      }
  }
  
    $jam_masuk = $jam_pulang = $absen_in = $absen_out = $status_masuk = $status_pulang = null;
    $jam_toleransi = $durasi = $radius = $keterangan = $map_in_url = $map_out_url = '-';
    $jumlah_belum_absen++;
}

  $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
  // Menyimpan data tanggal dan status
  $data[] = [
      'no' => $no,
      'date' => tanggal_ind($dateStr),
      'pegawai' => ($data_pegawai['nama_lengkap'] ?? '-'),
      'jabatan' => ucfirst($data_pegawai['jabatan'] ?? '-'),
      'jam_kerja' => ($jam_masuk ?? '') . " - " . ($jam_pulang ?? ''),
      'toleransi' => $jam_toleransi??'-',
      'absen_masuk' => (strip_tags($absen_in??'')),
      'status_masuk' => $status_masuk,
      'absen_pulang' => (strip_tags($absen_out??'')),
      'status_pulang' => $status_pulang,
      'durasi' => $durasi,
      'radius' => $radius,
      'status' => $kehadiran, // Status Libur atau Kehadiran
      'titik_lokasi' => $map_in_url,
      'titik_lokasi_out' => $map_out_url,
      'keterangan' => $keterangan,
      'background' => $background,
  ];

  // Menambah 1 hari
  $startDate->modify('+1 day');
}

$response = [
  'data' => $data, // ini data datatable biasa
  'jumlah_hadir' => $jumlah_hadir,
  'jumlah_telat' => $jumlah_telat,
  'jumlah_izin' => $jumlah_izin,
  'jumlah_sakit'    => $jumlah_sakit,
  'total_jam_kerja' => $total_jam,
  'jumlah_belum_absen' => $total_blm_absen // ini yang kamu pakai sekarang
];
  

/** Config Excel s-widodo.com */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// Set header style
$style_col = [
  'font' => ['bold' => true],
   'font' => ['size' => 12],
  'alignment' => [
    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
  ],
  'borders' => [
    'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
  ],
  'fill' => [
    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    'startColor' => ['argb' => 'FFC0C0C0']
  ]
];

// Set row style
$style_row = [
  'alignment' => [
    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
  ],
  'borders' => [
    'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE],
    'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE],
    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE],
    'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE]
  ]
];


  // Set judul
  $sheet->setCellValue('A1', 'LAPORAN KEHADIRAN PEGAWAI');
  $sheet->mergeCells('A1:O1');
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);

  // Tanggal
  $sheet->setCellValue('A2', 'Tanggal: ' . tgl_ind($from) . ' s.d. ' . tgl_ind($to));
  $sheet->mergeCells('A2:O2');
  

  // Mengatur alignment (ratakan ke tengah)
  $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

  // Tambahkan style untuk font dan ukuran teks
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
  $sheet->getStyle('A2')->getFont()->setSize(12);
  
  // === HEADER TABEL ===
  $headers = [
      'No', 'Tanggal', 'Nama Pegawai', 'Jabatan', 'Jam Kerja', 'Toleransi',
      'Absen Masuk', 'Status Masuk', 'Absen Pulang', 'Status Pulang', 'Durasi', 'Radius', 'Titik Lokasi Masuk',
      'Titik Lokasi Pulang', 'Status Kehadiran', 'Keterangan'
  ];

  $col = 'A';
  foreach ($headers as $header) {
    $sheet->setCellValue($col . '3', $header);
    $sheet->getStyle($col . '3')->applyFromArray($style_col);
    $sheet->getColumnDimension($col)->setAutoSize(14); // Set auto width for each column
    $col++;
  }

  // Data
  $row = 4;
  $no = 1;
  foreach ($response['data'] as $item) {
      $sheet->setCellValue('A' . $row, $no++);
      $sheet->setCellValue('B' . $row, $item['date']);
      $sheet->setCellValue('C' . $row, $item['pegawai']);
      $sheet->setCellValue('D' . $row, $item['jabatan']);
      $sheet->setCellValue('E' . $row, $item['jam_kerja']);
      $sheet->setCellValue('F' . $row, $item['toleransi']);
      $sheet->setCellValue('G' . $row, $item['absen_masuk']);
      $sheet->setCellValue('H' . $row, $item['status_masuk']);
      $sheet->setCellValue('I' . $row, $item['absen_pulang']);
      $sheet->setCellValue('J' . $row, $item['status_pulang']);
      $sheet->setCellValue('K' . $row, $item['durasi']);
      $sheet->setCellValue('L' . $row, $item['radius']);

      if ($item['titik_lokasi'] !=='-') {
          $sheet->setCellValue('M' . $row, 'Lihat Lokasi Masuk');
          $sheet->getCell('M' . $row)->getHyperlink()->setUrl($item['titik_lokasi']); 
      } else {
          $sheet->setCellValue('M' . $row, '-');
      } 

      if ($item['titik_lokasi_out'] !=='-') {
          $sheet->setCellValue('N' . $row, 'Lihat Lokasi Pulang');
          $sheet->getCell('N' . $row)->getHyperlink()->setUrl($item['titik_lokasi_out']);
      } else {
          $sheet->setCellValue('N' . $row, '-');
      }

    // Tambahkan border untuk kolom A sampai O di baris ini
    $sheet->getStyle('A' . $row . ':P' . $row)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => '000000'],
            ],
        ],
    ]);

    // Menambahkan pewarnaan pada seluruh baris jika status adalah "Libur"
    if ($item['background'] === 'danger') {
        // Kolom A hingga O (misalnya, kolom terakhir adalah O)
        $sheet->getStyle('A' . $row . ':P' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF0000'], // Warna merah
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'], // Warna font putih untuk kontras
            ]
        ]);
    }


      $sheet->setCellValue('O' . $row, $item['status']);
      $sheet->setCellValue('P' . $row, $item['keterangan']);
      $row++;
  } 

  // === REKAPITULASI ===
  $row += 2; // Spasi 2 baris
  $sheet->setCellValue('A' . $row, 'Rekapitulasi Kehadiran');
  $sheet->mergeCells("A$row:B$row");

  $rekap = [
      'Jumlah Hadir' => $response['jumlah_hadir'],
      'Jumlah Telat' => $response['jumlah_telat'],
      'Jumlah Izin' => $response['jumlah_izin'],
      'Jumlah Sakit' => $response['jumlah_sakit'],
      'Total Jam Kerja' => $response['total_jam_kerja'],
      'Belum Absen' => $response['jumlah_belum_absen'],
  ];

  foreach ($rekap as $label => $value) {
      $row++;
      $sheet->setCellValue('A' . $row, $label);
      $sheet->setCellValue('B' . $row, $value);
  }

  // === FORMAT KOLOM ===
  foreach (range('A', 'L') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
  }

  $row += 3;
  // Baris: Tempat & tanggal
    $sheet->setCellValue('A' . $row, $row_site['kabupaten'] . ', ' . tgl_indo($date));
    $sheet->mergeCells('A' . $row . ':K' . $row);
    $sheet->getStyle('A' . $row)
        ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    // Baris berikut: Jabatan
    $row++;
    $sheet->setCellValue('A' . $row, 'Kepala Sekolah');
    $sheet->mergeCells('A' . $row . ':K' . $row);
    $sheet->getStyle('A' . $row)
        ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('A' . $row)->getFont()->setItalic(true);

    // Baris kosong (jarak tanda tangan)
    $row++;
    $sheet->setCellValue('A' . $row, '');
    $sheet->mergeCells('A' . $row . ':K' . $row);

    $row++;
    $sheet->setCellValue('A' . $row, '');
    $sheet->mergeCells('A' . $row . ':K' . $row);

    // Baris nama kepala sekolah
    $row++;
    $sheet->setCellValue('A' . $row, strip_tags($row_site['kepala_sekolah'] ?? ''));
    $sheet->mergeCells('A' . $row . ':K' . $row);
    $sheet->getStyle('A' . $row)
        ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);

    // Baris NIP kepala sekolah
    $row++;
    $nip = $row_site['nip_kepala_sekolah'] ?? '-';
    $sheet->setCellValue('A' . $row, 'NIP. ' . $nip);
    $sheet->mergeCells('A' . $row . ':K' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

  // Simpan sebagai file Excel
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="Laporan_Absensi_Pegawai_'.$from.'-'.$to.'.xlsx"');
  header('Cache-Control: max-age=0');

  $writer = new Xlsx($spreadsheet);
  $writer->save('php://output');
  exit;
} else {
    echo '<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
    <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}

break;
}}