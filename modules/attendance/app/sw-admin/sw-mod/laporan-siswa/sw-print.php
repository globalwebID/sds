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
    $query = "SELECT COUNT(*) as jumlah FROM absen WHERE $filter AND $kolom = '$nilai'";
    $result = $connection->query($query);
    $data = $result->fetch_assoc();
    return (int) ($data['jumlah'] ?? 0);
}


$no = 0;
switch (@$_GET['action']){
case 'print':

$data_absen = null;    
$siswa    = !empty($_GET['siswa']) ? anti_injection(epm_decode($_GET['siswa'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND user_id = '$siswa'";

$data_siswa = null;
$query_siswa = "SELECT user_id,nama_lengkap,kelas FROM user WHERE user_id='$siswa' AND active='Y'";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0){
$data_siswa = $result_siswa->fetch_assoc();
$user_id = $data_siswa['user_id']??'0';
$data_wali = getWaliKelas($data_siswa['kelas'], $connection);

// Memanggil fungsi
$jumlah_hadir    = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat    = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin     = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_sakit     = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');

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

    $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";
    $result_libur = $connection->query($query_libur);
    if ($result_libur && $result_libur->num_rows > 0) {
        while ($row_libur = $result_libur->fetch_assoc()) {
            // Simpan dalam format lowercase untuk memudahkan perbandingan
            $libur_hari[] = strtolower($row_libur['hari']);
        }
    }
  $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
  
  // Cek absensi untuk tanggal tersebut
  $query_absen = "SELECT * FROM absen WHERE tanggal='$dateStr' AND user_id='$siswa'";
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
    $jam_masuk = null; $jam_pulang = null; $jam_toleransi = '-';
    $absen_in = null; $absen_out = null; 
    $status_masuk =null; $status_pulang = null;
    $durasi= '-'; 
    $keterangan = '-';
    $map_in = '-'; $map_out ='-';
    $jumlah_belum_absen++;
}

  $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
  // Menyimpan data tanggal dan status
  $data[] = [
      'no' => $no,
      'date' => tanggal_ind($dateStr),
      'siswa' => ($data_siswa['nama_lengkap'] ?? '-'),
      'kelas' => ($data_siswa['kelas'] ?? '-'),
      'jam_sekolah' => ($jam_masuk ?? '') . " - " . ($jam_pulang ?? ''),
      'toleransi' => $jam_kerja_toleransi??'-',
      'absen_masuk' => (strip_tags($absen_in??'')),
      'status_masuk' => $status_masuk,
      'absen_pulang' => (strip_tags($absen_out??'')),
      'status_pulang' => $status_pulang,
      'durasi' => $durasi,
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
  'data'            => $data, // ini data datatable biasa
  'jumlah_hadir'    => $jumlah_hadir,
  'jumlah_telat'    => $jumlah_telat,
  'jumlah_izin'     => $jumlah_izin,
  'jumlah_sakit'    => $jumlah_sakit,
  'jumlah_belum_absen' => $total_blm_absen
];

echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Laporan Absensi Siswa</title>
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

    .ttd th, td { border:0px; padding: 6px; text-align: center; }

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

    <h3 class="text-center" style="margin:0px;">LAPORAN KEHADIRAN SISWA</h3>
    <p class="text-center" style="margin:0px;">Tanggal : '.tanggal_ind($from).' - '.tanggal_ind($to).'</p>

      <table class="datatable mt-3">
          <thead>
            <tr>
              <th rowspan="2" class="text-center">No</th>
              <th rowspan="2">Tanggal</th>
              <th rowspan="2">Nama</th>
              <th rowspan="2">Kelas</th>
              <th rowspan="2">Jam Kerja</th>
              <th rowspan="2">Toleransi</th>
              <th colspan="2" class="text-center">Absen Masuk</th>
              <th colspan="2" class="text-center">Abse Pulang</th>
              <th rowspan="2">Kehadiran</th>
              <th colspan="2">Lokasi</th>
              <th rowspan="2">Keterangan</th>
            </tr>
            <tr>
              <th class="text-center">Jam</th>
              <th class="text-center">Status</th>
              <th class="text-center">Jam</th>
              <th class="text-center">Status</th>
              <th class="text-center">Masuk</th>
              <th class="text-center">Pulang</th>
            </tr>
          </thead>
          <tbody>';
            foreach ($data as $row) {
            echo '<tr class="'.$row['background'].'">
                <td>'.$row['no'].'</td>
                <td>'.$row['date'].'</td>
                <td>'.$row['siswa'].'</td>
                <td>'.$row['kelas'].'</td>
                <td>'.$row['jam_sekolah'].'</td>
                <td>'.$row['toleransi'].'</td>
                <td>'.$row['absen_masuk'].'</td>
                <td>'.$row['status_masuk'].'</td>
                <td>'.$row['absen_pulang'].'</td>
                <td>'.$row['status_pulang'].'</td>
                <td>'.$row['status'].'</td>
                <td>'.$row['titik_lokasi'].'</td>
                <td>'.$row['titik_lokasi_out'].'</td>
                <td>'.$row['keterangan'].'</td>
            </tr>';
            }
          echo'
          </tbody>
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

                  <td colspan="2" class="text-right">Total Belum Absen</td>
                  <td colspan="4" class="text-left"><span class="badge badge-danger belum-absen-cell">'.$total_blm_absen.'</span></td>
              </tr>
          </tfoot>
      </table>

    <table class="ttd" style="width:100%;margin-top:30px;border: 0px!important;">
        <tr>
        <td style="width:60%;text-align:left;vertical-align:top;">
        <strong>Wali Kelas</strong>
        <br><br><br><br><br><br><br><br>
        <br>
        <b>'.strip_tags($data_wali['nama_lengkap']??'-').'</b>
        <br>
        NIP. '.($data_wali['nip']??'-').'
        </td>

        <td style="width:40%;text-align:left;vertical-align:top;">
        '.$row_site['kabupaten'].', '.tgl_indo($date).'<br>
        <strong>Kepala Sekolah</strong><br>
        '.(file_exists("../../../sw-content/".strip_tags($row_site['stempel']??'stempel.png')) 
        ? '<img src="../../../sw-content/'.strip_tags($row_site['stempel']??'stempel.png').'" style="width:160px;height:auto;margin-left: -40px;"><br>' 
        : '').'
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
$siswa    = !empty($_GET['siswa']) ? anti_injection(epm_decode($_GET['siswa'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND user_id = '$siswa'";

$data_siswa = null;
$query_siswa = "SELECT user_id,nama_lengkap,kelas FROM user WHERE user_id='$siswa' AND active='Y'";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0){
$data_siswa = $result_siswa->fetch_assoc();
$user_id = $data_siswa['user_id']??'0';

$data_wali = getWaliKelas($data_siswa['kelas'], $connection);


// Memanggil fungsi
$jumlah_hadir    = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat    = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin     = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_sakit     = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');

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

  $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";
  $result_libur = $connection->query($query_libur);
  if ($result_libur && $result_libur->num_rows > 0) {
      while ($row_libur = $result_libur->fetch_assoc()) {
          // Simpan dalam format lowercase untuk memudahkan perbandingan
          $libur_hari[] = strtolower($row_libur['hari']);
      }
  }
  $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';

$query_absen = "SELECT * FROM absen WHERE tanggal='$dateStr' AND user_id='$siswa'";
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
  
    $jam_masuk = null; $jam_pulang = null; $jam_toleransi = '-';
    $absen_in = null; $absen_out = null; 
    $status_masuk =null; $status_pulang = null; $durasi ='-';
    $keterangan = '-';
    $map_in_url = '-'; $map_in_url ='-';
    $jumlah_belum_absen++;
}

  $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
  // Menyimpan data tanggal dan status

  $data[] = [
      'no' => $no,
      'date' => tanggal_ind($dateStr),
      'siswa' => ($data_iwa['nama_lengkap'] ?? '-'),
      'kelas' => ($data_siswa['kelas'] ?? '-'),
      'jam_sekolah' => ($jam_masuk ?? '') . " - " . ($jam_pulang ?? ''),
      'toleransi' => $jam_kerja_toleransi??'-',
      'absen_masuk' => (strip_tags($absen_in??'')),
      'status_masuk' => strip_tags($status_masuk??''),
      'absen_pulang' => (strip_tags($absen_out??'')),
      'status_pulang' => strip_tags($status_pulang??''),
      'status' => strip_tags($kehadiran??''), // Status Libur atau Kehadiran
      'titik_lokasi' => $map_in_url,
      'titik_lokasi_out' => $map_in_url,
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
$pdf->Cell(0, 7, 'LAPORAN KEHADIRAN SISWA', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Periode: ' . tgl_ind($from) . ' s.d. ' .tgl_ind($to), 0, 1, 'C');
$pdf->Ln(5);
// Hitung lebar kolom otomatis
$pdf->SetFont('Arial', 'B', 9);


$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 200, 200); // Warna abu-abu (light grey)
$header = [
    'No', 'Tanggal', 'Nama', 'Kelas', 'Jam Sekolah', 'Toleransi',
    'Absen Masuk', 'Absen Pulang', 'Kehadiran', 'Lokasi IN', 'Lokasi OUT', 'Keterangan'
];

$widths = [8, 25, 55, 20, 30, 20, 30, 30, 20, 20, 20, 55];

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
    $pdf->Cell($widths[2], 7, strip_tags($row['siswa']??''), 1, 0, 'L', $fill);
    $pdf->Cell($widths[3], 7, strip_tags($row['kelas']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[4], 7, strip_tags($row['jam_sekolah']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[5], 7, strip_tags($row['toleransi']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[6], 7, strip_tags($row['absen_masuk']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[7], 7, strip_tags($row['absen_pulang']??''), 1, 0, 'C', $fill);
    $pdf->Cell($widths[8], 7, strip_tags($row['status']??''), 1, 0, 'C', $fill);

    if ($row['titik_lokasi'] !=='-') {
        $pdf->SetTextColor(0, 0, 255); // Biru seperti hyperlink
        $pdf->SetFont('', 'U');        // Underline
        $pdf->Cell($widths[9], 7, 'IN', 1, 0, 'C', $fill, $row['titik_lokasi']);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        $pdf->Cell($widths[9], 7, '-', 1, 0, 'C', $fill);
    }

// Link OUT
     if ($row['titik_lokasi_out'] !=='-') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->SetFont('', 'U');
        $pdf->Cell($widths[10], 7, 'OUT', 1, 0, 'C', $fill, $row['titik_lokasi_out']);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        $pdf->Cell($widths[10], 7, '-', 1, 0, 'C', $fill);
    }

    $pdf->SetTextColor(0); // Hitam
    $pdf->SetFont('');
    $pdf->Cell($widths[11], 7, strip_tags($row['keterangan']??''), 1, 0, 'L', $fill);
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
    'Belum Absen' => $response['jumlah_belum_absen']
];

foreach ($rekap as $label => $jumlah) {
    $pdf->Cell(40, 6, $label, 1);
    $pdf->Cell(20, 6, $jumlah, 1, 1);
}


// Tanda tangan kiri (Pegawai) dan kanan (Kepala Sekolah)
  $pdf->SetFont('Arial', '', 10);
  $pdf->Ln(5);

  // Tanggal dan tempat di kanan
  $pdf->Cell(205, 6, '', 0, 0); // Kosongkan kiri
  $pdf->Cell(20); // Margin kiri (offset 20)
  $pdf->Cell(90, 6, $row_site['kabupaten'] . ', ' . tgl_indo($date), 0, 1, 'L');

  $pdf->Cell(20); // Margin kiri (offset 20)
  // Hormat Saya di kiri, Kepala Sekolah di kanan
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(20, 6, 'Wali Kelas,', 0, 0, 'L');
  $pdf->Cell(185, 6, '', 0, 0); // Spacer
  $pdf->Cell(90, 6, 'Kepala Sekolah', 0, 1, 'L');

  // Tambahkan stempel di atas nama kepala sekolah
  $stempelPath = '../../../sw-content/'.($row_site['stempel']??'stempel.png').'';
  if (file_exists($stempelPath)) {
      // Ambil ukuran gambar
      list($width, $height) = getimagesize($stempelPath);

      $desiredWidth = 45; // mm
      $desiredHeight = ($height / $width) * $desiredWidth;

      $xStempel = 20 + 20 + 95 + 90;
      $yStempel = $pdf->GetY() - 5; 
      
      // Gambar dengan ukuran yang dihitung
      $pdf->Image($stempelPath, $xStempel, $yStempel, $desiredWidth, $desiredHeight);
  }

  $pdf->Ln(25);

  // Nama pegawai di kiri, nama kepala sekolah di kanan
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(20); // Margin kiri (offset 20)
  $pdf->Cell(20, 6, strip_tags($data_wali['nama_lengkap'] ?? ''), 0, 0, 'L');
  $pdf->Cell(185, 6, '', 0, 0); // Spacer
  $pdf->Cell(90, 6, strip_tags($row_site['kepala_sekolah'] ?? '-'), 0, 1, 'L');
  
  // NIP pegawai di kiri, NIP kepala sekolah di kanan
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(20); // Margin kiri (offset 20)
  $pdf->Cell(20, 6, 'NIP. ' . ($data_wali['nip'] ?? '-'), 0, 0, 'L');
  $pdf->Cell(185, 6, '', 0, 0); // Spacer
  $pdf->Cell(90, 6, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'), 0, 1, 'L');

// Output PDF
$pdf->Output('I', 'Laporan_kehadiran_Siswa_'.$from.'-'.$to.'.pdf');


}else{
  echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
  <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}


/** Download Excel */
break;
case'excel';
require '../../../sw-library/PhpSpreadsheet/autoload.php';

$data_absen = null;    
$siswa    = !empty($_GET['siswa']) ? anti_injection(epm_decode($_GET['siswa'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
$filter_range = "tanggal >= '$from' AND tanggal <= '$to' AND user_id = '$siswa'";


$data_siswa = null;
$query_siswa = "SELECT user_id,nama_lengkap,kelas FROM user WHERE user_id='$siswa' AND active='Y'";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0){
$data_siswa = $result_siswa->fetch_assoc();
$user_id = $data_siswa['user_id']??'0';

$data_wali = getWaliKelas($data_siswa['kelas'], $connection);

// Memanggil fungsi
$jumlah_hadir    = countAbsen($connection, $filter_range, 'kehadiran', 'Hadir');
$jumlah_telat    = countAbsen($connection, $filter_range, 'status_masuk', 'Telat');
$jumlah_izin     = countAbsen($connection, $filter_range, 'kehadiran', 'Izin');
$jumlah_sakit     = countAbsen($connection, $filter_range, 'kehadiran', 'Sakit');

// Simpan data
$data = [];
$jumlah_belum_absen = 0;
$jumlah_libur_kantor = 0;
$jumlah_libur_nasional = 0;
$background = '';
$status = '-';

// Mendapatkan tanggal range dari 'from' dan 'to'
$startDate = new DateTime($from);
$endDate = new DateTime($to);

while ($startDate <= $endDate) {$no++;
  $dateStr = $startDate->format('Y-m-d');
  $dayNameEnglish = strtolower($startDate->format('l'));
  $dayName = $englishToIndonesianDays[$dayNameEnglish];
  $hari_libur = date('D',strtotime($dateStr));
  $liburInfo = cekLibur($connection, $dateStr);

  $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";
  $result_libur = $connection->query($query_libur);
  if ($result_libur && $result_libur->num_rows > 0) {
      while ($row_libur = $result_libur->fetch_assoc()) {
          // Simpan dalam format lowercase untuk memudahkan perbandingan
          $libur_hari[] = strtolower($row_libur['hari']);
      }
  }
  $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';

// Cek absensi untuk tanggal tersebut
$query_absen = "SELECT * FROM absen WHERE tanggal='$dateStr' AND user_id='$siswa'";
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
  
  $jam_masuk = null; $jam_pulang = null; $jam_toleransi = '-';
  $absen_in = null; $absen_out = null; 
  $status_masuk =null; $status_pulang = null; $durasi ='-';
  $keterangan = '-';
  $map_in_url = '-'; $map_in_url ='-';
  $jumlah_belum_absen++;
}

  $total_blm_absen = $jumlah_belum_absen - $jumlah_libur_kantor - $jumlah_libur_nasional;
  // Menyimpan data tanggal dan status
  $data[] = [
      'no' => $no,
      'date' => tanggal_ind($dateStr),
      'siswa' => ($data_siswa['nama_lengkap'] ?? '-'),
      'kelas' => ($data_siswa['kelas'] ?? '-'),
      'jam_sekolah' => ($jam_masuk ?? '') . " - " . ($jam_pulang ?? ''),
      'toleransi' => $jam_kerja_toleransi??'-',
      'absen_masuk' => (strip_tags($absen_in??'')),
      'status_masuk' => strip_tags($status_masuk??''),
      'absen_pulang' => (strip_tags($absen_out??'')),
      'status_pulang' => strip_tags($status_pulang??''),
      'status' => strip_tags($kehadiran??''), // Status Libur atau Kehadiran
      'titik_lokasi' => $map_in_url,
      'titik_lokasi_out' => $map_in_url,
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
  $sheet->setCellValue('A1', 'LAPORAN KEHADIRAN SISWA');
  $sheet->mergeCells('A1:N1');
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);

  // Tanggal
  $sheet->setCellValue('A2', 'Tanggal: ' . tgl_ind($from) . ' s.d. ' . tgl_ind($to));
  $sheet->mergeCells('A2:N2');
  

  // Mengatur alignment (ratakan ke tengah)
  $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

  // Tambahkan style untuk font dan ukuran teks
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
  $sheet->getStyle('A2')->getFont()->setSize(12);
  
  // === HEADER TABEL ===
  $headers = [
      'No', 'Tanggal', 'Nama Siswa', 'Kelas', 'Jam Sekolah', 'Toleransi',
      'Absen Masuk', 'Status Masuk', 'Absen Pulang', 'Status Pulang', 'Titik Lokasi Masuk',
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
      $sheet->setCellValue('C' . $row, $item['siswa']);
      $sheet->setCellValue('D' . $row, $item['kelas']);
      $sheet->setCellValue('E' . $row, $item['jam_sekolah']);
      $sheet->setCellValue('F' . $row, $item['toleransi']);
      $sheet->setCellValue('G' . $row, $item['absen_masuk']);
      $sheet->setCellValue('H' . $row, $item['status_masuk']);
      $sheet->setCellValue('I' . $row, $item['absen_pulang']);
      $sheet->setCellValue('J' . $row, $item['status_pulang']);
      if ($item['titik_lokasi'] !=='-') {
          $sheet->setCellValue('K' . $row, 'Lihat Lokasi Masuk');
          $sheet->getCell('K' . $row)->getHyperlink()->setUrl($item['titik_lokasi']); 
      } else {
          $sheet->setCellValue('K' . $row, '-');
      } 

      if ($item['titik_lokasi_out'] !=='-') {
          $sheet->setCellValue('L' . $row, 'Lihat Lokasi Pulang');
          $sheet->getCell('L' . $row)->getHyperlink()->setUrl($item['titik_lokasi_out']);
      } else {
          $sheet->setCellValue('L' . $row, '-');
      }

      $sheet->setCellValue('M' . $row, $item['status']);
      $sheet->setCellValue('N' . $row, $item['keterangan']);

      // Tambahkan border untuk kolom A sampai O di baris ini
        $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
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
            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FF0000'], // Warna merah
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'], // Warna font putih untuk kontras
                ]
            ]);
        }
        
      $row++;
  } 

  // === REKAPITULASI ===
  $row += 4; // Spasi 2 baris
  $sheet->setCellValue('A' . $row, 'Rekapitulasi Kehadiran');
  $sheet->mergeCells("A$row:B$row");

  $rekap = [
      'Jumlah Hadir' => $response['jumlah_hadir'],
      'Jumlah Telat' => $response['jumlah_telat'],
      'Jumlah Izin' => $response['jumlah_izin'],
      'Jumlah Sakit' => $response['jumlah_sakit'],
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

  
  // Atur lebar kolom
    foreach (range('A', 'K') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $lastColIndex = count($headers);
    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);
    $lastRow = $row - 1; // baris terakhir yang berisi data
    $range = 'A3:' . $lastColumn . $lastRow;
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000']
            ]
        ]
    ];

    $sheet->getStyle($range)->applyFromArray($borderStyle);

    // Baris untuk tanggal (sekitar di atas tanda tangan, kanan)
    $row = $row + 3;

    $sheet->setCellValue('H' . $row, ''.$row_site['kabupaten'].', ' . tgl_indo(date('Y-m-d')) . '');
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Label kolom tanda tangan: kiri Wali Kelas, kanan Kepala Sekolah
    $row++;
    $sheet->setCellValue('A' . $row, 'Wali Kelas');
    $sheet->mergeCells('A' . $row . ':G' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('H' . $row, 'Kepala Sekolah');
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('H' . $row)->getFont()->setItalic(true);

    // Ruang kosong untuk tanda tangan (beberapa baris)
    $row++;
    $sheet->mergeCells('A' . $row . ':G' . $row); // kosong
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $row++;
    $sheet->mergeCells('A' . $row . ':G' . $row); // kosong
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $row++;
    $sheet->mergeCells('A' . $row . ':G' . $row); // kosong
    $sheet->mergeCells('H' . $row . ':N' . $row);

    // Nama Wali Kelas (kiri) dan Nama Kepala Sekolah (kanan)
    $row++;
    $sheet->setCellValue('A' . $row, ''.strip_tags($data_wali['nama_lengkap']??'').'' );
    $sheet->mergeCells('A' . $row . ':G' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('H' . $row, ''.strip_tags($row_site['kepala_sekolah']??'').'' );
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getFont()->setBold(true);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // NIP Wali Kelas dan NIP Kepala Sekolah
    $row++;
    $sheet->setCellValue('A' . $row, 'NIP. ' . ($data_wali['nip'] ?? '-'));
    $sheet->mergeCells('A' . $row . ':G' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('H' . $row, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'));
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

  $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

  // Simpan sebagai file Excel
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="Laporan_Absensi_Siswa_'.$from.'-'.$to.'.xlsx"');
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