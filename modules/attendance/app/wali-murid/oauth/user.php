<?PHP if(empty($connection)){
  	echo'Koneksi tidak ditemukan';
} else {
$wali_murid = NULL;
if(empty($_COOKIE['wali_murid'])){
	setcookie("wali_murid", "", time()-3600);
    setcookie('wali_murid', '', 0, '/');
	exit();
}
else{
    if(!empty($_COOKIE['wali_murid'])){$wali_murid = htmlspecialchars(convert("decrypt", $_COOKIE['wali_murid']));}
	$query_user ="SELECT * FROM wali_murid WHERE wali_murid_id='$wali_murid'";
    $result_user = $connection->query($query_user);
	if($result_user->num_rows > 0){
		$data_user = $result_user->fetch_assoc();
		extract($data_user);

		$update_online = "UPDATE wali_murid SET tanggal_login='$date $time_sekarang', ip='$ip', status='Online' WHERE wali_murid_id='$data_user[wali_murid_id]'";
		$connection->query($update_online);

	}else{
		setcookie("wali_murid", "", time()-3600);
    	setcookie('wali_murid', '', 0, '/');
		exit();
	}
}

}