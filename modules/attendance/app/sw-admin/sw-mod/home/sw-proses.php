<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
include('../../../sw-library/sw-function.php');
require_once'../../login/user.php';

switch (@$_GET['action']){
case 'table':

$query_statistik = "SELECT statistik.jumlah,statistik.date,user.nama_lengkap FROM statistik
LEFT JOIN user ON statistik.user_id = user.user_id WHERE statistik.date='$date' ORDER BY statistik.date DESC LIMIT 15";
$result_statistik = $connection->query($query_statistik);
if($result_statistik -> num_rows > 0){
       
echo'
<table class="table align-items-center table-flush">
  <thead class="thead-light">
    <tr>
      <th scope="col">Siswa</th>
      <th scope="col">Tanggal</th>
      <th scope="col" class="text-center">Jumlah</th>
    </tr>
  </thead>
  <tbody>';
  while($data = $result_statistik->fetch_assoc()){
  echo'
    <tr>
      <td>'.strip_tags($data['nama_lengkap']).'</td>
      <td>'.tanggal_ind($data['date']).'</td>
      <td class="text-center">'.$data['jumlah'].'</td>
    </tr>';
  }
  echo' 
  </tbody>
</table>';
}else{
  echo'<div class="text-center pt-5 pb-5">Saat ini belum ada data statistik siswa cek nisn</div>';
}
   

break;
}

}