<?php 
include_once '../sw-library/sw-config.php';
require_once '../sw-library/sw-function.php';
include_once '../google/google-config.php';

$expired_cookie = time() + 60 * 60 * 24 * 60;
$iB 	= getBrowser();
$browser = $iB['name'].' '.$iB['version'];
$ip 	= $_SERVER['REMOTE_ADDR'];

if(isset($_GET['code'])){
	$gclient->authenticate($_GET['code']);
	$_SESSION['token'] = $gclient->getAccessToken();
	header('Location: ' . filter_var($redirect_url, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
	$gclient->setAccessToken($_SESSION['token']);
}

if ($gclient->getAccessToken()) {
	// Get user profile data from google
	$gpuserprofile 	= $google_oauthv2->userinfo->get();
	$nama_lengkap    = $gpuserprofile['given_name']." ".$gpuserprofile['family_name'];
	$email 			= $gpuserprofile['email'];

    if (isset($_GET['hak-akses'])) {
        if ($_GET['hak-akses'] == 'siswa') {
            // Buat query untuk mengecek apakah data SISWA dengan email tersebut sudah ada atau belum
			$query_user ="SELECT user_id,email FROM user WHERE email='$email' LIMIT 1";
			$result_user = $connection->query($query_user);
			$data_user   = $result_user->fetch_assoc();

			if(empty($data_user)){
				$_SESSION['hak-akses'] = 'siswa'; 
				// Jika User dengan email tersebut belum ada
				$add_siswa ="INSERT INTO user(
						email,
						nama_lengkap,
						tanggal_registrasi,
						tanggal_login,
						ip,
						browser,
						status) values(
						'$email',
						'$nama_lengkap',
						'$date $time',
						'$date $time',
						'$ip',
						'$browser',
						'Offline')";
		        $connection->query($add_siswa);
				$id = mysqli_insert_id($connection);
				$user_id_encrypted = convert("encrypt", strip_tags($id));
			}else{
				$user_id_encrypted = convert("encrypt", strip_tags($data_user['user_id']));
			}
            
            setcookie('siswa', $user_id_encrypted, $expired_cookie, '/');
    		header("location:../siswa");


        } elseif ($_GET['hak-akses'] == 'pegawai') {
			$_SESSION['hak-akses'] = 'pegawai'; 
			// Buat query untuk mengecek apakah data PEGAWAI dengan email tersebut sudah ada atau belum
            $query_pegawai ="SELECT pegawai_id,email FROM pegawai WHERE email='$email' LIMIT 1";
			$result_pegawai = $connection->query($query_pegawai);
			$data_pegawai   = $result_pegawai->fetch_assoc();

			if(empty($data_pegawai)){
				// Jika User dengan email tersebut belum ada
				$qrcode   = strtoupper(substr(hash('sha1', $nama_lengkap), 0, 10)); 
				$add_pegawai = "INSERT INTO pegawai (qrcode,
						nama_lengkap, 
						email, 
						tanggal_registrasi,
						tanggal_login,
						ip,
						browser,
						status) VALUES (
						'$qrcode',
						'{$nama_lengkap}',
						'{$email}',
						'$date $time',
						'$date $time',
						'$ip',
						'$browser',
						'Offline')";
				$connection->query($add_pegawai);
				$id = mysqli_insert_id($connection);
				$user_id_encrypted = convert("encrypt", strip_tags($id));
			}else{
				$user_id_encrypted = convert("encrypt", strip_tags($data_pegawai['pegawai_id']));
			}

			setcookie('pegawai', $user_id_encrypted, $expired_cookie, '/');
			header("location:../pegawai");

        }else{
			$_SESSION['hak-akses'] = 'wali-murid';
            // Buat query untuk mengecek apakah data PEGAWAI dengan email tersebut sudah ada atau belum
			$query_wali 	= "SELECT wali_murid_id,email FROM wali_murid WHERE email='$email' LIMIT 1";
			$result_wali 	= $connection->query($query_pegawai);
			$data_wali  	= $result_pegawai->fetch_assoc();
			if(empty($data_pegawai)){
				// Jika User dengan email tersebut belum ada
				$add_wali ="INSERT INTO wali_murid(nama_lengkap,
						email,
						tanggal_registrasi,
						tanggal_login,
						ip,
						browser,
						status) values('$nama_lengkap',
						'$email',
						'$date $time',
						'$date $time',
						'$ip',
						'$browser',
						'Offline')";
				$connection->query($add_wali);
				$id = mysqli_insert_id($connection);
				$user_id_encrypted = convert("encrypt", strip_tags($id));

			}else{
				$user_id_encrypted = convert("encrypt", strip_tags($data_wali['wali_murid_id']));
			}

			setcookie('wali-murid', $user_id_encrypted, $expired_cookie, '/');
			header("location:../wali-murid");
        }
    }

	if (isset($_SESSION['hak-akses']) && $_SESSION['hak-akses'] == 'siswa') {
		header("location:../siswa");

	}elseif (isset($_SESSION['hak-akses']) && $_SESSION['hak-akses'] == 'pegawai') {
		header("location:../pegawai");

	}elseif (isset($_SESSION['hak-akses']) && $_SESSION['hak-akses'] == 'wali-murid') {
		header("location:../wali-murid");
	} else {
		echo "Akses ditolak, silahkan login kembali.";
	}
}else {
	$authUrl = $gclient->createAuthUrl();
	header("location: ".$authUrl);
}?>
