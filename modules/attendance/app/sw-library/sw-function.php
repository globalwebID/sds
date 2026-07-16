<?php
$date     = DATE('Y-m-d');
$day      = DATE('d');
$day_en   = DATE('l');
$month_en = DATE('F');
$month    = DATE('m');
$year     = DATE('Y');
$time     = DATE('H:i:s');
$time_absen = DATE('H:i:s');
$waktu_sekarang = DATE('H:i:s');
$timeNow  = DATE('Y-m-d H:i:s');
$timein   = time();
setlocale(LC_ALL, 'id_ID');
$no=0;


function hari_aja(){
  $seminggu = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
  $hari     = date("w");
  $hari_ini = $seminggu[$hari];
  return $hari_ini;
}

$hari_indo = hari_aja();

function hari_in(){
 $today_in = hari().", ".tgl_indo(date('Y-m-d'));
 return $today_in;
}

function hari_en(){
 $today_en = date('l').", ".date('F')." ".date('d').", ".date('Y');
 return $today_en;
}

$tgl_sekarang = date("Ymd");
$thn_sekarang = date("Y");
$time_sekarang = date("H:i:s");

$tanggal=date("Y-m-d");
$tgl_jam = date("Y-m-d H:i:s");

function jam_id($tgl){
  $tanggal = substr($tgl,11,5);
  return $tanggal;
}

function jam_indo($timeNow){
  $tgl_jam = substr($timeNow,11,8);
  return $tgl_jam;
}



function hari_ini(){
  $hari = date ("D");
  switch($hari){
    case 'Sun':
      $hari_ini = "Minggu";
    break;
 
    case 'Mon':     
      $hari_ini = "Senin";
    break;
 
    case 'Tue':
      $hari_ini = "Selasa";
    break;
 
    case 'Wed':
      $hari_ini = "Rabu";
    break;
 
    case 'Thu':
      $hari_ini = "Kamis";
    break;
 
    case 'Fri':
      $hari_ini = "Jumat";
    break;
 
    case 'Sat':
      $hari_ini = "Sabtu";
    break;
    
    default:
      $hari_ini = "Tidak di ketahui";   
    break;
  }
 
  return "".$hari_ini."";
 
}
$hari_ini = hari_ini();


function getNamaHariIndonesia($tanggal) {
    $hariInggris = date('l', strtotime($tanggal));
    $hariIndo = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];
    return $hariIndo[$hariInggris] ?? 'Tidak diketahui';
}


function format_hari_tanggal($waktu){
    $hari_array = array(
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu'
    );
    $hr = date('w', strtotime($waktu));
    $hari = $hari_array[$hr];
    $tanggal = date('j', strtotime($waktu));
    $bulan_array = array(
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    );
    $bl = date('n', strtotime($waktu));
    $bulan = $bulan_array[$bl];
    $tahun = date('Y', strtotime($waktu));
    $jam = date( 'H:i:s', strtotime($waktu));
    
    //untuk menampilkan hari, tanggal bulan tahun jam
    //return "$hari, $tanggal $bulan $tahun $jam";

    //untuk menampilkan hari, tanggal bulan tahun
    return "$hari, $tanggal $bulan $tahun";
}


// Maret 2021
function bulan_indo($tgl){
  $tanggal = substr($tgl,8,2);
  $bulan   = ambilbulan(substr($tgl,5,2));
  $tahun   = substr($tgl,0,4);
  return $bulan.' '.$tahun;
}



// 14 Maret 2014
function tgl_indo($tgl){
  $tanggal = substr($tgl,8,2);
  $bulan   = ambilbulan(substr($tgl,5,2));
  $tahun   = substr($tgl,0,4);
  return $tanggal.' '.$bulan.' '.$tahun;
}
function tgl_ind($tgl){
  $tanggal = substr($tgl,8,2);
  $bulan   = ambil_bulan(substr($tgl,5,2));
  $tahun   = substr($tgl,0,4);
  return $tanggal.' '.$bulan.' '.$tahun;
}

function tanggal_ind($tanggal) {
   $pisah   = explode('-',$tanggal);
   $larik   = array($pisah[2],$pisah[1],$pisah[0]);
   $satukan = implode('-',$larik);
   return $satukan;
}

function tanggal_en($tanggal) {
   $pisah   = explode('-',$tanggal);
   $larik   = array($pisah[2],$pisah[1],$pisah[0]);
   $satukan = implode('-',$larik);
   return $satukan;
}

function ambilbulan($bln){
  if     ($bln=="01") return "Januari";
  elseif ($bln=="02") return "Februari";
  elseif ($bln=="03") return "Maret";
  elseif ($bln=="04") return "April";
  elseif ($bln=="05") return "Mei";
  elseif ($bln=="06") return "Juni";
  elseif ($bln=="07") return "Juli";
  elseif ($bln=="08") return "Agustus";
  elseif ($bln=="09") return "September";
  elseif ($bln=="10") return "Oktober";
  elseif ($bln=="11") return "November";
  elseif ($bln=="12") return "Desember";
}

function ambil_bulan($bln){
  if ($bln=="01") return "Jan";
  elseif ($bln=="02") return "Feb";
  elseif ($bln=="03") return "Mar";
  elseif ($bln=="04") return "Apr";
  elseif ($bln=="05") return "Mei";
  elseif ($bln=="06") return "Jun";
  elseif ($bln=="07") return "Jul";
  elseif ($bln=="08") return "Agu";
  elseif ($bln=="09") return "Sep";
  elseif ($bln=="10") return "Okt";
  elseif ($bln=="11") return "Nov";
  elseif ($bln=="12") return "Des";
}

function ubah_tgl($tanggal) {
   $pisah   = explode('/',$tanggal);
   $larik   = array($pisah[2],$pisah[1],$pisah[0]);
   $satukan = implode('-',$larik);
   return $satukan;
}

function current_date(){
  $tGL=date("Y-m-d");
  $time=date("H:i:s");
  $tgljam=$tGL." ".$time;
  return "$tgljam";
}

function getformat($tGl){
$pisah   = explode(' ',$tGl);
$aray = array($pisah[0]);
$get = format_indo($aray);
return $get;
}

function jam($time) {
   $pisah   = explode(':',$time);
   $larik   = array($pisah[0],$pisah[1]);
   $satukan = implode(':',$larik);
   return $satukan;
}


function format_angka($angka) {
    $hasil =  number_format($angka,0, ",",".");
    return $hasil;
}

function format_nomer($angka2) {
    $hasil2 =  number_format($angka2,3, ".",",");
    return $hasil2;
}


function time_since($original)
{
  date_default_timezone_set('Asia/Jakarta');
  $chunks = array(
      array(60 * 60 * 24 * 365, 'tahun'),
      array(60 * 60 * 24 * 30, 'bulan'),
      array(60 * 60 * 24 * 7, 'minggu'),
      array(60 * 60 * 24, 'hari'),
      array(60 * 60, 'jam'),
      array(60, 'menit'),
  );
 
  $today = time();
  $since = $today - $original;
 
  if ($since > 604800)
  {
    $print = date("M jS", $original);
    if ($since > 31536000)
    {
      $print .= ", " . date("Y", $original);
    }
    return $print;
  }
 
  for ($i = 0, $j = count($chunks); $i < $j; $i++)
  {
    $seconds = $chunks[$i][0];
    $name = $chunks[$i][1];
 
    if (($count = floor($since / $seconds)) != 0)
      break;
  }
 
  $print = ($count == 1) ? '1 ' . $name : "$count {$name}";
  return $print . ' yang lalu';
}


// Ucapa Selamat Pagi siang sore malam
$time_info = date('H:i');
if ($time_info > '06:30' && $time_info < '10:59') {
    $salam = 'Pagi';
    $time_info_kerja = 'Masuk';
} elseif ($time_info >= '11:00' && $time_info < '14:59') {
    $salam = 'Siang';
    $time_info_kerja = 'Pulang';
} elseif ($time_info >= '15:00' && $time_info < '18:59') {
    $salam = 'Sore';
} else {
    $salam = 'Malam';
}

//fungsi untuk mengkonversi size file
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 


function timezoneList(){
    $timezoneIdentifiers = DateTimeZone::listIdentifiers();
    $utcTime = new DateTime('now', new DateTimeZone('UTC'));
    $tempTimezones = array();
    foreach($timezoneIdentifiers as $timezoneIdentifier){
        $currentTimezone = new DateTimeZone($timezoneIdentifier);
        $tempTimezones[] = array(
            'offset' => (int)$currentTimezone->getOffset($utcTime),
            'identifier' => $timezoneIdentifier
        );
    }
    function sort_list($a, $b){
        return ($a['offset'] == $b['offset'])
            ? strcmp($a['identifier'], $b['identifier'])
            : $a['offset'] - $b['offset'];
    }
    usort($tempTimezones, "sort_list");
    $timezoneList = array();
    foreach($tempTimezones as $tz){
        $sign = ($tz['offset'] > 0) ? '+' : '-';
        $offset = gmdate('H:i', abs($tz['offset']));
        $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' .
            $tz['identifier'];
    }
    return $timezoneList;
}

function anti_injection($string) {
$string = str_replace("'", " ", $string);
$string = str_replace('"', ' ', $string);
$string = str_replace('=', ' ', $string);
$string = str_replace('}', ' ', $string);
$string = str_replace('{', ' ', $string);
$string = str_replace('[', ' ', $string);
$string = str_replace(']', ' ', $string);
$string = str_replace('.php', ' ', $string);
$string = str_replace('.txt', ' ', $string);
$string = str_replace('.jpg', ' ', $string);
$string = str_replace('.png', ' ', $string);
$string = str_replace('.jpeg', ' ', $string);
$string = stripslashes($string);
$string = strip_tags($string);
$string =stripslashes(strip_tags(htmlspecialchars(addslashes($string))));
return $string;
}


function seo_title($s){
  $c = array (' ');
  $d = array ('-','/','\\',',','.','#',':',';','\'','"','[',']','{','}',')','(','|','`','~','!','@','%','$','^','&','*','=','?','+');
  $s = str_replace($d, '', $s);
  $s = strtolower(str_replace($c, '-', $s));
  return $s;
}


function minify_html($string){
  $string = preg_replace('/<!--(?!\[if|\<\!\[endif)(.|\s)*?-->/', '', $string);
  $string = preg_replace('/\t+/', '', $string);
  $string = preg_replace('/\n+/', '', $string);
  $string = preg_replace('/>\r+/', '>', $string);
  $string = preg_replace('/\r+</', '<', $string);
  $string = preg_replace('/>\s+</', '><', $string);
  return $string;
}
function minify_js($buffer){
  $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
  $buffer = str_replace(array("\r\n","\r","\t","\n",'  ','    ','     '), '', $buffer);
  $buffer = preg_replace(array('(( )+\))','(\)( )+)'), ')', $buffer);
  return $buffer;
}


 function getExtension($str) {
         $i = strrpos($str,".");
         if (!$i) { return ""; }
         $l = strlen($str) - $i;
         $ext = substr($str,$i+1,$l);
         return $ext;
 }
 


 /* 
 * Custom function to compress image size and 
 * upload to the server using PHP 
 */ 
function compressImage($source, $destination, $quality) { 
  // Get image info 
  $imgInfo = getimagesize($source); 
  $mime = $imgInfo['mime']; 
   
  // Create a new image from file 
  switch($mime){ 
      case 'image/jpeg': 
          $image = imagecreatefromjpeg($source); 
          break; 
      case 'image/png': 
          $image = imagecreatefrompng($source); 
          break; 
      case 'image/gif': 
          $image = imagecreatefromgif($source); 
          break; 
      default: 
          $image = imagecreatefromjpeg($source); 
  } 
   
  // Save image 
  imagejpeg($image, $destination, $quality); 
   
  // Return compressed image 
  return $destination; 
} 



function getBrowser(){
    $u_agent  = $_SERVER['HTTP_USER_AGENT'];
    $bname    = 'Unknown';
    $platform = 'Unknown';
    $version  = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Internet Explorer';
        $ub    = "MSIE";
    }
    elseif(preg_match('/Firefox/i',$u_agent))
    {
        $bname = 'Mozilla Firefox';
        $ub    = "Firefox";
    }
    elseif(preg_match('/Chrome/i',$u_agent))
    {
        $bname = 'Google Chrome';
        $ub    = "Chrome";
    }
    elseif(preg_match('/Safari/i',$u_agent))
    {
        $bname = 'Apple Safari';
        $ub    = "Safari";
    }
    elseif(preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Opera';
        $ub    = "Opera";
    }
    elseif(preg_match('/Netscape/i',$u_agent))
    {
        $bname = 'Netscape';
        $ub    = "Netscape";
    }

    // finally get the correct version number
    $known   = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }

    // check if we have a number
    if ($version==null || $version=="") {$version="?";}

    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'   => $pattern,
        'browser'   => $ub
    );
}



function getUserIP() {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      // IP dari shared internet
      $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // IP dari proxy
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
      // IP langsung
      $ip = $_SERVER['REMOTE_ADDR'];
  }

  // Konversi IPv6 loopback (::1) ke IPv4 loopback (127.0.0.1)
  if ($ip === '::1') {
      $ip = '127.0.0.1';
  }

  return $ip;
}

function getDeviceName($user_agent) {
  // Jika Windows
  if (preg_match('/Windows NT 10.0/', $user_agent)) return 'Windows 10/11 PC';
  if (preg_match('/Windows NT 6.3/', $user_agent)) return 'Windows 8.1';
  if (preg_match('/Windows NT 6.2/', $user_agent)) return 'Windows 8';
  if (preg_match('/Windows NT 6.1/', $user_agent)) return 'Windows 7';
  if (preg_match('/Windows NT 5.1/', $user_agent)) return 'Windows XP';

  // Jika MacOS
  if (preg_match('/Mac OS X 10_15/', $user_agent)) return 'MacBook (Catalina)';
  if (preg_match('/Mac OS X 11/', $user_agent)) return 'MacBook (Big Sur)';
  if (preg_match('/Mac OS X 12/', $user_agent)) return 'MacBook (Monterey)';

  // Jika iPhone/iPad
  if (preg_match('/iPhone/', $user_agent)) return 'iPhone';
  if (preg_match('/iPad/', $user_agent)) return 'iPad';

  // Jika Android
  if (preg_match('/Android/', $user_agent)) {
      preg_match('/Android (\d+(\.\d+)?)/', $user_agent, $matches);
      return isset($matches[1]) ? 'Android ' . $matches[1] : 'Android Device';
  }

  // Jika Linux
  if (preg_match('/Linux/', $user_agent)) return 'Linux PC';

  return 'Unknown Device';
}

$user_agent   = $_SERVER['HTTP_USER_AGENT'];
$device_name  = getDeviceName($user_agent);
$ip 		      = getUserIP();


function epm_encode($id){
  $a   = array("0","1","2","3","4","5","6","7","8","9");
  $b   = array("Plz","OkX","Ijc","UhV","Ygb","TfN","RdZ","Esx","WaC","Qmv");
  $r   = str_replace($a, $b, $id);
  $enc = rand(10,99).base64_encode(base64_encode($r));
  return $enc;
}

function epm_decode($enc) {
  $tr  = substr($enc,2,strlen($enc));
  $str = base64_decode(base64_decode($tr));
  $b   =  array("Plz","OkX","Ijc","UhV","Ygb","TfN","RdZ","Esx","WaC","Qmv");
  $a   = array("0","1","2","3","4","5","6","7","8","9");
  $id  = str_replace($b, $a, $str);
  if(!preg_match("/^[0-9]+$/", $id)){
    $id = 0;
  }
  return $id;
}

  
function convert($action, $string) {
  $output = false;
  $encrypt_method = "AES-256-CBC";
  $secret_key = 'rer54etrg5eysdkj9832h2rh3784y632hr';
  $secret_iv = 'g5gtghh45dsnf53785728372hjhfb38b83fb873fb8';
  // hash
  $key = hash('sha256', $secret_key);
  // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
  $iv = substr(hash('sha256', $secret_iv), 0, 16);
  if( $action == 'encrypt') {
      $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
      $output = base64_encode($output);
  }
  else if( $action == 'decrypt' ){
      $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
  }
  return $output;
}

function validasiNama($nama) {
    // Cek apakah hanya mengandung huruf, angka, dan spasi
    if (preg_match('/^[a-zA-Z0-9 ]+$/', $nama)) {
        return true;
    } else {
        return false;
    }
}

function processImage($imageType, $filePath, $width, $height, $destPath): bool {
    // Validasi dukungan format
    $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    if (!in_array($imageType, $supportedTypes)) {
        throw new RuntimeException("Format gambar tidak didukung oleh server ini.");
    }

    // Load gambar sumber sesuai format
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $resource = @imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $resource = @imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_GIF:
            $resource = @imagecreatefromgif($filePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $resource = @imagecreatefromwebp($filePath);
            } else {
                throw new RuntimeException("Server PHP tidak mendukung pemrosesan gambar WEBP.");
            }
            break;
    }

    if (!$resource) {
        throw new RuntimeException("Gagal memuat gambar sumber. File mungkin rusak atau formatnya tidak valid.");
    }

    // Resize (jika diperlukan)
    $resized = resizeImage($resource, $width, $height, $imageType);

    // Simpan gambar sesuai tipe
    $saved = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($resized, $destPath, 90);
            break;
        case IMAGETYPE_PNG:
            $saved = imagepng($resized, $destPath);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($resized, $destPath);
            break;
        case IMAGETYPE_WEBP:
            $saved = imagewebp($resized, $destPath);
            break;
    }

    // Hapus dari memori
    imagedestroy($resource);
    imagedestroy($resized);

    if (!$saved) {
        throw new RuntimeException("Gagal menyimpan gambar ke $destPath. Periksa izin folder atau kapasitas disk.");
    }

    return true;
}



function addTextWatermark($src, $watermark, $save = null) {
    $source_width = imagesx($src);
    $source_height = imagesy($src);
    $ratio = $source_height / $source_width;

    $new_width = 300;
    $new_height = (int) ($ratio * 300);

    $image_color = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($image_color, $src, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);

    $txtcolor = imagecolorallocate($image_color, 255, 225, 225);

    $fontPath = realpath('../../../sw-library/Roboto-Regular.ttf');
    if (!$fontPath) {
      $fontPath = realpath('../sw-library/Roboto-Regular.ttf');
    }

    $font_size = 8;
    $x = 10;
    $y = 20;

    imagettftext($image_color, $font_size, 0, $x, $y, $txtcolor, $fontPath, $watermark);

    if (!empty($save)) {
        imagejpeg($image_color, $save, 100);
    } else {
        header('Content-Type: image/jpeg');
        imagejpeg($image_color, null, 90); // kualitas 90 bagus & aman
    }

    imagedestroy($src);
    imagedestroy($image_color);
}

// Fungsi bantu: wrap teks agar tidak melebihi lebar gambar
function wordWrapTtf($text, $font, $font_size, $max_width) {
    $words = explode(' ', $text);
    $lines = '';
    $line = '';

    foreach ($words as $word) {
        $test_line = trim($line . ' ' . $word);
        $bbox = imagettfbbox($font_size, 0, $font, $test_line);
        $text_width = $bbox[2] - $bbox[0];

        if ($text_width > $max_width && $line !== '') {
            $lines .= $line . "\n";
            $line = $word;
        } else {
            $line = $test_line;
        }
    }
    $lines .= $line;
    return $lines;
}

function facebook_time_ago($timestamp) {  
  $time_ago = strtotime($timestamp);  
  $current_time = time();  
  $time_difference = $current_time - $time_ago;  
  $seconds = $time_difference;  
  $minutes      = round($seconds / 60 );           // value 60 is seconds  
  $hours           = round($seconds / 3600);           //value 3600 is 60 minutes * 60 sec  
  $days          = round($seconds / 86400);          //86400 = 24 * 60 * 60;  
  $weeks          = round($seconds / 604800);          // 7*24*60*60;  
  $months          = round($seconds / 2629440);     //((365+365+365+365+366)/5/12)*24*60*60  
  $years          = round($seconds / 31553280);     //(365+365+365+365+366)/5 * 24 * 60 * 60  
  if($seconds <= 60)  
  {  
 return "Just Now";  
}  
  else if($minutes <=60)  
  {  
 if($minutes==1)  
       {  
   return "one minute ago";  
 }  
 else  
       {  
   return "$minutes minutes ago";  
 }  
}  
  else if($hours <=24)  
  {  
 if($hours==1)  
       {  
   return "an hour ago";  
 }  
       else  
       {  
   return "$hours hrs ago";  
 }  
}  
  else if($days <= 7)  
  {  
 if($days==1)  
       {  
   return "yesterday";  
 }  
       else  
       {  
   return "$days days ago";  
 }  
}  
  else if($weeks <= 4.3) //4.3 == 52/12  
  {  
 if($weeks==1)  
       {  
   return "$weeks week ago";  
 }  
       else  
       {  
   return "$weeks weeks ago";  
 }  
}  
   else if($months <=12)  
  {  
 if($months==1)  
       {  
   return "a month ago";  
 }  
       else  
       {  
   return "$months months ago";  
 }  
}  
  else  
  {  
 if($years==1)  
       {  
   return "one year ago";  
 }  
       else  
       {  
   return "$years years ago";  
 }  
}  
}  

function randomPassword() {
  $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  $pass = array(); //remember to declare $pass as an array
  $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
  for ($i = 0; $i < 8; $i++) {
      $n = rand(0, $alphaLength);
      $pass[] = $alphabet[$n];
  }
  return implode($pass); //turn the array into a string
}


function cekMulaiAbsen($jam_masuk, $jam_pulang, $offset_menit) {
    $jamMasuk = strtotime($jam_masuk);
    $jamPulang = strtotime($jam_pulang);
    $waktuSekarang = time();
    $mulaiMasuk = strtotime("-$offset_menit minutes", $jamMasuk);
    $mulaiPulang = strtotime("-$offset_menit minutes", $jamPulang);
    if (($waktuSekarang >= $mulaiMasuk) ||
        ($waktuSekarang >= $mulaiPulang)) {
        return 'Y';
    }
    return [
        'masuk' => date('H:i', $mulaiMasuk),
        'pulang' => date('H:i', $mulaiPulang)
    ];
}


/** Jam Sekolah/Kerja s-widodo.com */
function getJam($connection, $hari, $tipe) {
  $query = "SELECT * FROM jam_sekolah WHERE hari='$hari' AND tipe='".htmlspecialchars($tipe, ENT_QUOTES, 'UTF-8')."'";
  $result = $connection->query($query);
  if ($result && $result->num_rows > 0) {
    return $result->fetch_assoc();
  }
  return null;
}

function getAbsenSiswa($connection, $user_id, $date) {
    $escaped_user_id = htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8');
    $query = "SELECT absen_in, absen_out,kehadiran FROM absen 
    WHERE tanggal='$date' AND user_id = '$escaped_user_id' LIMIT 1";
    $result = $connection->query($query);
    if($result->num_rows > 0){
      return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
}

function getAbsenPegawai($connection, $user_id, $date) {
  $escaped_user_id = htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8');
  $query = "SELECT absen_in, absen_out,kehadiran FROM absen_pegawai 
  WHERE tanggal='$date' AND pegawai_id = '$escaped_user_id' LIMIT 1";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
  }
}

function getWaliKelas($kelas, $connection) {
    $data_wali = NULL;
    $query_wali = "SELECT pegawai_id,nip,nama_lengkap FROM pegawai WHERE wali_kelas='$kelas' AND jabatan='guru'";
    $result_wali = $connection->query($query_wali);
    if ($result_wali->num_rows > 0) {
        $data_wali = $result_wali->fetch_assoc();
    }
    return $data_wali;
}

function getSiswa($connection, $nisn) {
    $escaped_user_id = htmlspecialchars($nisn, ENT_QUOTES, 'UTF-8');
    $query = "SELECT user_id,nama_lengkap,nisn,kelas,email,avatar FROM user 
    WHERE nisn= '$escaped_user_id' LIMIT 1";
    $result = $connection->query($query);
    if($result->num_rows > 0){
      return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
}

/**
 * =========================================================
 * WhatsApp Sender (POST/GET)
 * - POST: support kirim TEXT + IMAGE (media)
 * - GET : tetap seperti semula (umumnya hanya teks)
 * =========================================================
 */

if ($whatsapp_tipe == 'POST') {

  /**
   * Kirim WA via POST JSON.
   * Jika $imageUrl diisi, maka akan kirim gambar (preview) + caption ($msg).
   *
   * @param string $phone
   * @param string $msg
   * @param string $link
   * @param string $token
   * @param string $secret_key
   * @param string|null $imageUrl  URL publik gambar (https://...)
   * @return string|null response gateway
   */
  function KirimWa($phone, $msg, $link, $token, $secret_key, $imageUrl = null) {

    $item = [
      'phone'   => $phone,
      'message' => $msg,
      'delay'   => '1',
    ];

    // Jika gateway Anda mendukung image, aktifkan saat ada URL gambar
    if (!empty($imageUrl)) {
      $item['image'] = $imageUrl;
    }

    $payload = [
      "data" => [$item]
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      "Authorization: $token.$secret_key",
      "Content-Type: application/json"
    ]);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_URL, $link);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    $result = curl_exec($curl);
    curl_close($curl);

    // PENTING: jangan print_r di sini agar tidak merusak output endpoint absensi
    return $result;
  }
}

if ($whatsapp_tipe == 'GET') {

  /**
   * Kirim WA via GET (biasanya hanya teks).
   * Note: banyak gateway GET tidak support image.
   */
  function KirimWa($sender, $phone, $msg, $link, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "".$link."?api_key=".$token."&sender=".$sender."&number=".$phone."&message=".$msg."");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_wa = curl_exec($ch);
    curl_close($ch);
    return $response_wa;
  }
}

// if($whatsapp_tipe =='POST'){
//   function KirimWa($phone, $msg, $link, $token, $secret_key){
//     $curl = curl_init();
//     $payload = [
//         "data" => [
//             [
//                 'phone' => $phone,
//                 'message' => $msg,
//                 //'image' => $gambar,
//                 'delay' => '1', 
//             ]
//         ]
//     ];
//     curl_setopt($curl, CURLOPT_HTTPHEADER,
//         array(
//             "Authorization: $token.$secret_key",
//             "Content-Type: application/json"
//         )
//     );
//     curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
//     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
//     curl_setopt($curl, CURLOPT_URL,  $link);
//     curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
//     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
//     $result = curl_exec($curl);
//     curl_close($curl);
//     print_r($result);
//   }
// }

// if($whatsapp_tipe =='GET'){
//   function KirimWa($sender,$phone,$msg,$link,$token){
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, "".$link."?api_key=".$token."&sender=".$sender."&number=".$phone."&message=".$msg."");
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     $response_wa = curl_exec($ch);
//     curl_close($ch);
//     //echo "$response_wa\n";
//   }
// }