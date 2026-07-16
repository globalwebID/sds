<?PHP if(empty($connection)){
  	echo'Koneksi tidak ditemukan';
} else {
$pegawai = NULL;
if(empty($_COOKIE['pegawai'])){
	setcookie("pegawai", "", time()-3600);
    setcookie('pegawai', '', 0, '/');
	exit();
}
else{
    if(!empty($_COOKIE['pegawai'])){$pegawai = htmlspecialchars(convert("decrypt", $_COOKIE['pegawai']));}
	$query_user ="SELECT * FROM pegawai WHERE pegawai_id='$pegawai'";
    $result_user = $connection->query($query_user);
	if($result_user->num_rows > 0){
		$data_user = $result_user->fetch_assoc();
		extract($data_user);
		
		$update_online = "UPDATE pegawai SET tanggal_login='$date $time_sekarang', ip='$ip', status='Online' WHERE pegawai_id='$data_user[pegawai_id]'";
		$connection->query($update_online);
		
	}else{
		setcookie("pegawai", "", time()-3600);
    	setcookie('pegawai', '', 0, '/');
		exit();
	}
}

}