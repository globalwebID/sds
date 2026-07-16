<?PHP require_once'../../sw-library/sw-config.php';
	include_once '../../sw-library/sw-function.php';
	require_once'../login/user.php';
    $expired_cookie = time()+60*60*24*7;

	$update_user = "UPDATE admin SET tanggal_login='$date $time', status='Offline' WHERE admin_id='$current_user[admin_id]'";
    $connection->query($update_user);
	setcookie("ADMIN_KEY", "", time()-3600);
	setcookie('ADMIN_KEY', '', 0, '/');
	setcookie("KEY", "", time()-3600);
	setcookie('KEY', '', 0, '/');
	header('location:./login/');
	//session_destroy();
exit();
?>

		
