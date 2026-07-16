<?PHP if(empty($connection)){
  	echo'Koneksi tidak ditemukan';
} else {
$siswa = NULL;
if(empty($_COOKIE['siswa'])){
	setcookie("siswa", "", time()-3600);
    setcookie('siswa', '', 0, '/');
	exit();
}
else{
    if(!empty($_COOKIE['siswa'])){$siswa = htmlspecialchars(convert("decrypt", $_COOKIE['siswa']));}
	$query_user ="SELECT * FROM user WHERE active='Y' AND user_id='$siswa'";
    $result_user = $connection->query($query_user);
	if($result_user->num_rows > 0){
		$data_user = $result_user->fetch_assoc();
		extract($data_user);

		$update_online = "UPDATE user SET tanggal_login='$date $time_sekarang', ip='$ip', status='Online' WHERE user_id='$data_user[user_id]'";
		$connection->query($update_online);

	}else{
		setcookie("siswa", "", time()-3600);
    	setcookie('siswa', '', 0, '/');
		exit();
	}
}

}