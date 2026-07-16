<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';


switch (@$_GET['action']){
case 'add':
$error = array();

  $fields = [
    'tipe'   => 'Tipe',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
    }
  }
$item = $_POST['item'];
if (empty($error)) {
  $query_jam = "SELECT * FROM jam_sekolah WHERE tipe='$tipe'";
  $result_jam = $connection->query($query_jam);
  if(!$result_jam->num_rows > 0){

    for($i = 0; $i < sizeof($item); $i++){
        $hari         = htmlentities($item[$i]);
        $jam_masuk    = htmlentities($_POST["jam_masuk"][$i]);
        $jam_telat    = htmlentities($_POST["jam_telat"][$i]??'00:00:00');
        $jam_pulang   = htmlentities($_POST["jam_pulang"][$i]??'00:00:00');
        $mulai_absen  = htmlentities($_POST["mulai_absen"][$i]??'00:00:00');
        $mulai_pulang = htmlentities($_POST["mulai_pulang"][$i]??'00:00:00');
        $active       = htmlentities($_POST["active"][$i]);

        $add ="INSERT INTO jam_sekolah (
              hari,
              jam_masuk,
              jam_telat,
              jam_pulang,
              tipe,
              active) values(
              '$hari',
              '$jam_masuk',
              '$jam_telat',
              '$jam_pulang',
              '$tipe',
              '$active')"; 
        $connection->query($add); 
    }

  }else{
    for($i = 0; $i < sizeof($item); $i++){
      $hari         = htmlentities($item[$i]);
      $jam_masuk    = strip_tags($_POST["jam_masuk"][$i]);
      $jam_telat    = strip_tags($_POST["jam_telat"][$i]);
      $jam_pulang   = strip_tags($_POST["jam_pulang"][$i]);
      $active       = strip_tags($_POST["active"][$i]);

      $update="UPDATE jam_sekolah SET jam_masuk='$jam_masuk',
            jam_telat='$jam_telat',
            jam_pulang='$jam_pulang',
            active='$active' WHERE hari='$hari' AND tipe='$tipe'";
      $connection->query($update);
    }
  }

  echo'success';
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}

break;
}}