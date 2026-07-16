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
                'status_libur' => 'danger'
            ];
        }

        return [
            'is_libur' => false,
            'keterangan' => '',
            'status_libur' => ''
        ];
    }

    function countAbsen($connection, $filter, $kolom, $nilai) {
        $query = "SELECT COUNT(*) as jumlah FROM absen WHERE $filter AND $kolom = '$nilai'";
        $result = $connection->query($query);
        $data = $result->fetch_assoc();
        return (int) ($data['jumlah'] ?? 0);
    }

    function hitung_total_jam_kerja($connection, $user_id, $from, $to) {
        // Buat query SQL
        $query = "SELECT absen_in, absen_out 
                FROM absen WHERE user_id = '$user_id' 
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

switch (@$_GET['action']){
case 'print':

$no=0;
$data_absen = null; 
$data = [];
$jumlah_alpha = 0;
$jumlah_belum_absen = 0;
$jumlah_libur_kantor = 0;
$jumlah_libur_nasional = 0;
$result_libur = '';
$status = '-';

$data_absen = null;    
    $kelas     = !empty($_GET['kelas']) ? anti_injection($_GET['kelas']) : null;
    $siswa    = !empty($_GET['siswa']) ? anti_injection(epm_decode($_GET['siswa'])) : null;
    $from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
    $to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;


if(!empty($_GET['kelas']) && !empty($_GET['siswa'])){
    $filter = "WHERE user.user_id='$siswa' AND user.kelas='$kelas'";

}elseif(!empty($_GET['kelas'])){
    $filter= "WHERE user.kelas='$kelas'";

}elseif(!empty($_GET['siswa'])){
    $filter = "WHERE user.user_id='$siswa' AND user.active='Y'";

}else{
    $filter = "WHERE user.active='Y'";
}

$startDate = new DateTime($from);
$endDate = new DateTime($to);
$tempDate = clone $startDate;
while ($tempDate <= $endDate) {
    $dateStr = $tempDate->format('Y-m-d');

    $dayNameEnglish = strtolower($tempDate->format('l'));
    $dayName = $englishToIndonesianDays[$dayNameEnglish];
    $hari_libur = date('D',strtotime($dateStr));

    $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";
    $result_libur = $connection->query($query_libur);
    if ($result_libur && $result_libur->num_rows > 0) {
        while ($row_libur = $result_libur->fetch_assoc()) {
            // Simpan dalam format lowercase untuk memudahkan perbandingan
            $libur_hari[] = strtolower($row_libur['hari']);
        }
    }
    
    $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
    $liburInfo = cekLibur($connection, $dateStr);
    /** End Cek Libur */

    if ($holidayName=='Libur') {
        $result_libur = 'danger'; 
        $kehadiran = 'Libur';
        $jumlah_libur_kantor++; // Libur kantor
    } else {
        if ($liburInfo['is_libur']) {
            $result_libur = 'danger'; // Libur nasional, beri status_libur merah
            $kehadiran = $liburInfo['keterangan'];
            $jumlah_libur_nasional++;
        }
    }
    $tempDate->modify('+1 day');
}


$query_siswa = "SELECT user_id,nama_lengkap,kelas FROM user $filter";
$result_siswa = $connection->query($query_siswa);
$siswaData = [];    
/** Start Pegawai */
while ($siswa = $result_siswa->fetch_assoc()) {$no++;
    $siswaEntry['hadir'] = $siswaEntry['izin'] = $siswaEntry['sakit'] = $siswaEntry['telat'] = 0;

    $siswaEntry = [
        'no' => $no,  // Nomor urut
        'nama_siswa' => $siswa['nama_lengkap'],
        'kelas' => $siswa['kelas'],
        'keterangan' => 'Hadir',  // Default keterangan
        'tanggal' => [],
        'result_libur' => '',  // hanya 1 status untuk 1 baris
        'hadir' => 0, // Menyimpan jumlah hadir
        'telat' => 0, // Menyimpan jumlah telat
        'izin' => 0,   // Menyimpan jumlah izin
        'sakit' => 0,
        'alpha' => 0, // Menyimpan jumlah alpha*/
        'libur_kantor' => 0, // Menyimpan jumlah libur kantor
        'libur_nasional' => 0, // Menyimpan jumlah libur nasional
    ];

    // Loop untuk rentang tanggal
    $tempDate = clone $startDate;
    while ($tempDate <= $endDate) {
        $dateStr = $tempDate->format('Y-m-d');
        
        $data_absen[] = null;
        $data_absen = null;
        $query_absen = "SELECT * FROM absen WHERE tanggal='$dateStr' AND user_id='{$siswa['user_id']}'";
        $result_absen = $connection->query($query_absen);
        
        if ($result_absen && $result_absen->num_rows > 0) {
            $data_absen = $result_absen->fetch_assoc();
            $absen_in = $data_absen['absen_in'] ?? '';
            $absen_out = $data_absen['absen_out'] ?? '';

            $status_masuk = ($data_absen['status_masuk'] === 'Tepat Waktu') 
        ? '<span class="badge badge-success">'.$data_absen['status_masuk'].'</span>' 
        : (($data_absen['status_masuk'] === 'Telat')  // Cek jika status_masuk 'Telat'
            ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
            : ($data_absen['status_masuk'] !== '' 
                ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
                : '<span class="badge badge-warning">Belum Absen</span>'));  // Default jika kosong

        // Status Pulang
        $status_pulang = $data_absen['absen_out'] ===  NULL 
            ? '' 
            : ($data_absen['status_pulang'] === 'Tepat Waktu' 
                ? '<span class="badge badge-success">'.$data_absen['status_pulang'].'</span>' 
                : '<span class="badge badge-danger">'.$data_absen['status_pulang'].'</span>');

        // Status Kehadiran
            $kehadiran = $holidayName ? 'Libur' : 
            ($data_absen['kehadiran'] !== '' 
                ? '<span class="badge badge-info">'.strip_tags($data_absen['kehadiran']??'').'</span>' 
                : '<span class="badge badge-warning">Tidak Hadir</span>');


            // Cek status kehadiran
                if ($data_absen['kehadiran'] == 'Hadir') {
                    $result_libur = 'success'; // Hadir, beri status_libur hijau
                    $kehadiran = 'Hadir';
                    $siswaEntry['tanggal'][] = ['in' => ''.$absen_in.'<br>'.$status_masuk, 'out' => ''.$absen_out.'<br>'.$status_pulang, 'result_libur' => $result_libur];
                    $siswaEntry['hadir']++;

                }

                if ($data_absen['kehadiran'] == 'Izin') {
                    $result_libur = 'warning'; // Izin, beri status_libur kuning
                    $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']??'') . '</span>';
                    $siswaEntry['tanggal'][] = ['in' => $kehadiran, 'out' => $kehadiran, 'result_libur' => $result_libur];
                    $siswaEntry['izin']++;
                }

                if ($data_absen['kehadiran'] == 'Sakit') {
                    $result_libur = 'warning';
                    $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']??'') . '</span>';
                    $siswaEntry['tanggal'][] = ['in' => $kehadiran, 'out' => $kehadiran, 'result_libur' => $result_libur];
                    $siswaEntry['sakit']++;
                }


                if($absen_in != NULL && $data_absen['status_masuk'] == 'Telat') {
                    $siswaEntry['telat']++; // Hitung jumlah telat
                }

    } else {
        /** Jika data tidak ditemukan */
        $result_libur = 'white'; // Alpha, beri status_libur merah
        $kehadiran = 'Alpha';
        $siswaEntry['tanggal'][] = ['in' => 'x', 'out' => 'x', 'result_libur' => $result_libur];
    }

    $totalHari = $startDate->diff($endDate)->days + 1;
// Hitung jumlah alpha
    $siswaEntry['alpha'] = $totalHari - (
    $siswaEntry['hadir'] + 
    $siswaEntry['izin'] + 
    $siswaEntry['sakit'] + $jumlah_libur_nasional + $jumlah_libur_kantor);
    $siswaEntry['libur_nasional'] = $jumlah_libur_nasional;

    $tempDate->modify('+1 day');
    }
    $siswaData[] = $siswaEntry;
    
}

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

    body { font-family: Arial, sans-serif; font-size:8px; }
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
        .bg-danger{ background-color: #dc3545; color: white; }
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

    <h3 class="text-center" style="margin:0px;">Laporan Absensi Siswa</h3>
    <p class="text-center" style="margin:0px;">Tanggal : '.tanggal_ind($from).' - '.tanggal_ind($to).'</p>

      <table class="datatable mt-3">
          <thead>

                <tr>
                    <th rowspan="2" class="text-center">No</th>
                    <th rowspan="2" >Nama Siswa</th>
                    <th rowspan="2">Kelas</th>';
                    $startDate  = new DateTime($from);
                    while ($startDate <= $endDate) {
                     $dateStr =$startDate ->format('Y-m-d');
                      $dayNameEnglish = strtolower($startDate->format('l'));
                      $dayName = $englishToIndonesianDays[$dayNameEnglish];
                      $hari_libur = date('D',strtotime($dateStr));

                        $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";
                        $result_libur = $connection->query($query_libur);
                        if ($result_libur && $result_libur->num_rows > 0) {
                            while ($row_libur = $result_libur->fetch_assoc()) {
                                // Simpan dalam format lowercase untuk memudahkan perbandingan
                                $libur_hari[] = strtolower($row_libur['hari']);
                            }
                        }
                    
                        $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
                        $liburInfo = cekLibur($connection, $dateStr);
                        /** End Cek Libur */
                        $backgroundColor = '';
                        if ($holidayName=='Libur') {
                            $backgroundColor = 'bg-danger text-white';
                        } else {
                            if ($liburInfo['is_libur']) {
                                $backgroundColor = 'bg-danger text-white';
                            }
                        }
                             
                        echo'<th colspan="2" class="text-center '.$backgroundColor.'">'.format_hari_tanggal($dateStr).'</th>';
                        $startDate->modify('+1 day');
                    }
                   echo'
                    <th rowspan="2" class="text-center">Hadir</th>
                    <th rowspan="2" class="text-center">Telat</th>
                    <th rowspan="2" class="text-center">Izin</th>
                    <th rowspan="2" class="text-center">Sakit</th>
                    <th rowspan="2" class="text-center">Alpha</th>
                </tr>
                
                <tr class="bg-primary text-white">';
                  $startDate  = new DateTime($from);
                   while ($startDate <= $endDate) {
                    echo'
                    <th class="text-center">IN</th>
                    <th class="text-center">OUT</th>';
                      $startDate->modify('+1 day');
                   }
                   echo'
                </tr>
          </thead>
          <tbody>';?>
<?php foreach ($siswaData as $siswa): ?>
<tr>
    <td><?= $siswa['no'] ?></td>
    <td class="text-left"><?= ($siswa['nama_siswa']??'-') ?></td>
    <td class="text-left"><?= ($siswa['kelas']??'-') ?></td>
    <?php foreach ($siswa['tanggal'] as $tgl): ?>
    <td><?= $tgl['in'] ?></td>
    <td><?= $tgl['out'] ?></td>
    <?php endforeach; ?>
    <td><?= $siswa['hadir'] ?></td>
    <td><?= $siswa['telat'] ?></td>
    <td><?= $siswa['izin'] ?></td>
    <td><?= $siswa['sakit'] ?></td>
    <td><?= $siswa['alpha'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php
    echo'
      <table class="ttd" style="width:100%;margin-top:30px;border: 0px!important;">
        <tr>
        <td width="60%"></td>
        <td style="width:40%;text-align:left;vertical-align:top;">
        '.$row_site['kabupaten'].', '.tgl_indo($date).'<br>
        <strong>Kepala Sekolah</strong><br>
        '.(file_exists("../../../sw-content/".strip_tags($row_site['stempel']??'stempel.png')) 
        ? '<img src="../../../sw-content/'.strip_tags($row_site['stempel']??'stempel.png').'" style="width:160px;height:auto;margin-left: -30px;"><br>' 
        : '').'
        <b>'.strip_tags($row_site['kepala_sekolah']??'').'</b><br>
        NIP. '.($row_site['nip_kepala_sekolah']??'-').'
        </td>
    </tr>
    </table>
  </div>
</body>
</html>';


/** Download Excel */
break;
case'excel';
require '../../../sw-library/PhpSpreadsheet/autoload.php';
$no=0;
$data_absen = null; 
$data = [];
$jumlah_alpha = 0;
$jumlah_belum_absen = 0;
$jumlah_libur_kantor = 0;
$jumlah_libur_nasional = 0;
$result_libur = '';
$status = '-';

$data_absen = null;    
$kelas     = !empty($_GET['kelas']) ? anti_injection($_GET['kelas']) : null;
$siswa    = !empty($_GET['siswa']) ? anti_injection(epm_decode($_GET['siswa'])) : null;
$from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
$to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;


if(!empty($_GET['kelas']) && !empty($_GET['siswa'])){
    $filter = "WHERE user.user_id='$siswa' AND user.kelas='$kelas'";

}elseif(!empty($_GET['kelas'])){
    $filter= "WHERE user.kelas='$kelas'";

}elseif(!empty($_GET['siswa'])){
    $filter = "WHERE user.user_id='$siswa' AND user.active='Y'";

}else{
    $filter = "WHERE user.active='Y'";
}

$startDate = new DateTime($from);
$endDate = new DateTime($to);
$tempDate = clone $startDate;
while ($tempDate <= $endDate) {
    $dateStr = $tempDate->format('Y-m-d');

    $dayNameEnglish = strtolower($tempDate->format('l'));
    $dayName = $englishToIndonesianDays[$dayNameEnglish];
    $hari_libur = date('D',strtotime($dateStr));

    $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";    
    $result_libur = $connection->query($query_libur);
    if ($result_libur && $result_libur->num_rows > 0) {
        while ($row_libur = $result_libur->fetch_assoc()) {
            // Simpan dalam format lowercase untuk memudahkan perbandingan
            $libur_hari[] = strtolower($row_libur['hari']);
        }
    }
    
    $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
    $liburInfo = cekLibur($connection, $dateStr);
    /** End Cek Libur */

    if ($holidayName=='Libur') {
        $result_libur = 'danger'; 
        $kehadiran = 'Libur';
        $jumlah_libur_kantor++; // Libur kantor
    } else {
        if ($liburInfo['is_libur']) {
            $result_libur = 'danger'; // Libur nasional, beri status_libur merah
            $kehadiran = $liburInfo['keterangan'];
            $jumlah_libur_nasional++;
        }
    }
    $tempDate->modify('+1 day');
}


$query_siswa = "SELECT user_id,nama_lengkap,kelas FROM user $filter";
$result_siswa = $connection->query($query_siswa);
$siswaData = [];    
/** Start Pegawai */
while ($siswa = $result_siswa->fetch_assoc()) {$no++;
    $siswaEntry['hadir'] = $siswaEntry['izin'] = $siswaEntry['sakit'] = $siswaEntry['telat'] = 0;

    $siswaEntry = [
        'no' => $no,  // Nomor urut
        'nama_siswa' => $siswa['nama_lengkap'],
        'kelas' => $siswa['kelas'],
        'keterangan' => 'Hadir',  // Default keterangan
        'tanggal' => [],
        'result_libur' => '',  // hanya 1 status untuk 1 baris
        'hadir' => 0, // Menyimpan jumlah hadir
        'telat' => 0, // Menyimpan jumlah telat
        'izin' => 0,   // Menyimpan jumlah izin
        'sakit' => 0,
        'alpha' => 0, // Menyimpan jumlah alpha*/
        'libur_kantor' => 0, // Menyimpan jumlah libur kantor
        'libur_nasional' => 0, // Menyimpan jumlah libur nasional
    ];

    // Loop untuk rentang tanggal
    $tempDate = clone $startDate;
    while ($tempDate <= $endDate) {
        $dateStr = $tempDate->format('Y-m-d');
        
        $data_absen[] = null;
        $data_absen = null;
        $query_absen = "SELECT * FROM absen WHERE tanggal='$dateStr' AND user_id='{$siswa['user_id']}'";
        $result_absen = $connection->query($query_absen);
        
        if ($result_absen && $result_absen->num_rows > 0) {
            $data_absen = $result_absen->fetch_assoc();
            $absen_in = $data_absen['absen_in'] ?? '';
            $absen_out = $data_absen['absen_out'] ?? '';

            $status_masuk = ($data_absen['status_masuk'] === 'Tepat Waktu') 
        ? '<span class="badge badge-success">'.$data_absen['status_masuk'].'</span>' 
        : (($data_absen['status_masuk'] === 'Telat')  // Cek jika status_masuk 'Telat'
            ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
            : ($data_absen['status_masuk'] !== '' 
                ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
                : '<span class="badge badge-warning">Belum Absen</span>'));  // Default jika kosong

        // Status Pulang
        $status_pulang = $data_absen['absen_out'] === NULL 
            ? '' 
            : ($data_absen['status_pulang'] === 'Tepat Waktu' 
                ? '<span class="badge badge-success">'.$data_absen['status_pulang'].'</span>' 
                : '<span class="badge badge-danger">'.$data_absen['status_pulang'].'</span>');

        // Status Kehadiran
            $kehadiran = $holidayName ? 'Libur' : 
            ($data_absen['kehadiran'] !== '' 
                ? '<span class="badge badge-info">'.strip_tags($data_absen['kehadiran']??'').'</span>' 
                : '<span class="badge badge-warning">Tidak Hadir</span>');

            // Cek status kehadiran
                if ($data_absen['kehadiran'] == 'Hadir') {
                    $result_libur = 'success'; // Hadir, beri status_libur hijau
                    $kehadiran = 'Hadir';
                    $siswaEntry['tanggal'][] = ['in' => ''.$absen_in.'<br>'.$status_masuk, 'out' => ''.$absen_out.'<br>'.$status_pulang, 'result_libur' => $result_libur];
                    $siswaEntry['hadir']++;

                }

                if ($data_absen['kehadiran'] == 'Izin') {
                    $result_libur = 'warning'; // Izin, beri status_libur kuning
                    $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']??'') . '</span>';
                    $siswaEntry['tanggal'][] = ['in' => $kehadiran, 'out' => $kehadiran, 'result_libur' => $result_libur];
                    $siswaEntry['izin']++;
                }

                if ($data_absen['kehadiran'] == 'Sakit') {
                    $result_libur = 'warning'; // Izin, beri status_libur kuning
                    $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']??'') . '</span>';
                    $siswaEntry['tanggal'][] = ['in' => $kehadiran, 'out' => $kehadiran, 'result_libur' => $result_libur];
                    $siswaEntry['sakit']++;
                }

                if($absen_in != '00:00:00' && $data_absen['status_masuk'] == 'Telat') {
                    $siswaEntry['telat']++; // Hitung jumlah telat
                }

    } else {
        /** Jika data tidak ditemukan */
        $result_libur = 'white'; // Alpha, beri status_libur merah
        $kehadiran = 'Alpha';
        if($result_libur =='danger'){
            $siswaEntry['tanggal'][] = ['in' => 'Libur', 'out' => '', 'result_libur' => 'danger'];
        }else{
            $siswaEntry['tanggal'][] = ['in' => 'x', 'out' => 'x', 'result_libur' => $result_libur];
        }
        
        
    }

    $totalHari = $startDate->diff($endDate)->days + 1;
        // Hitung jumlah alpha
        $siswaEntry['alpha'] = $totalHari - (
        $siswaEntry['hadir'] + 
        $siswaEntry['izin'] + $siswaEntry['sakit'] + $jumlah_libur_nasional + $jumlah_libur_kantor);
        $siswaEntry['libur_nasional'] = $jumlah_libur_nasional;

    $tempDate->modify('+1 day');
    }
    $siswaData[] = $siswaEntry;
    
}

// Inisialisasi spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Style Header
$style_col = [
    'font' => [
        'bold' => true,
        'size' => 12
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'top' => ['borderStyle' => Border::BORDER_THIN],
        'right' => ['borderStyle' => Border::BORDER_THIN],
        'bottom' => ['borderStyle' => Border::BORDER_THIN],
        'left' => ['borderStyle' => Border::BORDER_THIN]
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFD9D9D9'] // abu-abu terang
    ]
];

// Judul laporan
$sheet->setCellValue('A1', 'LAPORAN KEHADIRAN SISWA (Tanggal: ' . tgl_ind($from) . ' s.d. ' . tgl_ind($to) . ')');
$sheet->mergeCells('A1:Z1'); // perkirakan kolom hingga Z
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Header kolom
$colIndex = 0;
$headers = ['No', 'Nama Siswa', 'Kelas'];
$headers2 = ['Hadir', 'Telat', 'Izin', 'Sakit', 'Alpha'];
// Daftar tanggal sebagai header
$tanggalList = [];
$tempDate = clone $startDate;
while ($tempDate <= $endDate) {
    $tanggalList[] = $tempDate->format('d-m-Y');
    $tempDate->modify('+1 day');
}

$headers = array_merge($headers, $tanggalList, $headers2);

// Set Header Kolom dan Terapkan Style
foreach ($headers as $header) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
    $sheet->setCellValue($colLetter . '3', $header);
    $sheet->getStyle($colLetter . '3')->applyFromArray($style_col);
    $sheet->getColumnDimension($colLetter)->setWidth(20); // Lebarkan kolom
}

$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
$row = 4;
foreach ($siswaData as $siswa) {
    $col = 'A'; // ← INISIALISASI DI SINI
    $sheet->setCellValue($col++ . $row, $siswa['no']);
    $sheet->setCellValue($col++ . $row, $siswa['nama_siswa']);
    $sheet->setCellValue($col++ . $row, $siswa['kelas']);

    foreach ($siswa['tanggal'] as $absen) {
        $cell = $col . $row;

        if (isset($absen['in']) && isset($absen['out'])) {
            $in = strip_tags($absen['in']);
            $out = strip_tags($absen['out']);
            $sheet->setCellValue($cell, "$in/$out");
        } else {
            $sheet->setCellValue($cell, "$kehadiran");
        }

        // Cek result_libur
        if (isset($absen['result_libur']) && $absen['result_libur'] === 'success') {
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('d4edda'); 
        }

        if (isset($absen['result_libur']) && $absen['result_libur'] === 'warning') {
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('fff3cd'); 
        }

        $col++; // naik ke kolom berikutnya
    }
    $sheet->setCellValue($col++ . $row, $siswa['hadir']);
    $sheet->setCellValue($col++ . $row, $siswa['telat']);
    $sheet->setCellValue($col++ . $row, $siswa['izin']);
    $sheet->setCellValue($col++ . $row, $siswa['sakit']);
    $sheet->setCellValue($col++ . $row, $siswa['alpha']);

    $row++;
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


$row = $row + 3;

    $sheet->setCellValue('H' . $row, ''. $row_site['kabupaten'] .', ' . tgl_indo(date('Y-m-d')) . '');
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Hanya Kepala Sekolah
    $row++;
    $sheet->setCellValue('H' . $row, 'Kepala Sekolah');
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getFont()->setItalic(true);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Ruang kosong untuk tanda tangan (beberapa baris)
    $row++;
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $row++;
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $row++;
    $sheet->mergeCells('H' . $row . ':N' . $row);

    // Nama Kepala Sekolah
    $row++;
    $sheet->setCellValue('H' . $row, ''.strip_tags($row_site['kepala_sekolah']??'').'' );
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getFont()->setBold(true);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // NIP Kepala Sekolah
    $row++;
    $sheet->setCellValue('H' . $row, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'));
    $sheet->mergeCells('H' . $row . ':N' . $row);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Simpan file
    $writer = new Xlsx($spreadsheet);
    $filename = 'Laporan_Kehadiran_Siswa_'.$from.'_'.$to.'.xlsx';

// Output ke browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;

break;
}}