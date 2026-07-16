<?PHP
// Admin SDS memakai sesi PHP lokal yang sama, tanpa token/URL SSO.
$sdsAdminId = (int)($_SESSION['admin_id'] ?? 0);
if ($sdsAdminId > 0) {
	$stmtSdsUser = $connection->prepare("SELECT a.id,a.username,a.email,a.password,a.full_name,a.role,x.app_role FROM admins a JOIN app_admin_access x ON x.admin_id=a.id AND x.application='absensi' AND x.active='Y' WHERE a.id = ? LIMIT 1");
	$stmtSdsUser->bind_param('i', $sdsAdminId);
	$stmtSdsUser->execute();
	$sdsUser = $stmtSdsUser->get_result()->fetch_assoc();
	$stmtSdsUser->close();
	if (!$sdsUser) {
		header('location:../../siteman/dashboard');
		exit;
	}
	$effectiveAppRole = (string)$sdsUser['role'] === 'superadmin'
		? 'superadmin'
		: (string)$sdsUser['app_role'];
	$current_user = [
		'admin_id' => (int)$sdsUser['id'],
		'username' => (string)$sdsUser['username'],
		'email' => (string)$sdsUser['email'],
		'password' => (string)$sdsUser['password'],
		'fullname' => (string)$sdsUser['full_name'],
		'avatar' => '',
		'level' => in_array($effectiveAppRole, ['superadmin', 'admin'], true) ? 1 : 2,
		'app_role' => $effectiveAppRole,
		'central_admin' => true,
		'active' => 'Y',
		'status' => 'Online',
	];
	$admin_id = (int)$sdsUser['id'];
	return;
}

if(empty($_COOKIE['ADMIN_KEY'])){
	setcookie("ADMIN_KEY", "", time()-3600);
    setcookie('ADMIN_KEY', '', 0, '/');
}else{

if(isset($_COOKIE['ADMIN_KEY']) && isset($_COOKIE['KEY'])){
	$ADMIN_KEY = htmlentities(epm_decode($_COOKIE['ADMIN_KEY']));
	$KEY 			  = htmlentities($_COOKIE['KEY']);
	$query_login= "SELECT * FROM admin WHERE admin_id='$ADMIN_KEY' AND active='Y'";
	$result_login = $connection->query($query_login);
	if($result_login->num_rows > 0){
		$current_user 	= $result_login->fetch_assoc();
		$admin_id 	= htmlentities($current_user['admin_id']);

		if($KEY === hash('sha256', $current_user['username'])){
			// Login Berhasil	
			$time_online = time();
			$update_admin = "UPDATE admin SET tanggal_login='$date $time', time='$time_online', status='Online' WHERE admin_id='$admin_id'";
			$connection->query($update_admin);
			
			/** Cek Siapa aja yg stausnya online */
			$query_online  = "SELECT tanggal_login,time FROM admin WHERE status='Online' AND  active='Y'";
			$result_online = $connection->query($query_online);
			if($result_online->num_rows > 0){
				while($data = $result_online->fetch_assoc()){
					$batas_time 	= 100;
					$timeout 		= time() - $batas_time;
					if($data['time'] > $timeout){
						// Update Online
						$update_admin = "UPDATE admin SET tanggal_login='$date $time', time='$time_online', status='Online' WHERE admin_id='$admin_id'";
						$connection->query($update_admin);
					}else{
						// Update Offline
						$update_online = "UPDATE admin SET status='Offline' WHERE status='Online' AND time < $timeout";
						$connection->query($update_online);
					}
			 }
			}
			
		}else{
			//Login tidak sesuai
			setcookie("ADMIN_KEY", "", time()-3600);
    		setcookie('ADMIN_KEY', '', 0, '/');
    		header('location:./login/');
		}
	}else{
		echo'User tidak ditemukan saat login';
		setcookie("ADMIN_KEY", "", time()-3600);
    	setcookie('ADMIN_KEY', '', 0, '/');
    	setcookie("KEY", "", time()-3600);
    	setcookie('KEY', '', 0, '/');
    	header('location:./login/');
	}

}


}
