<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
include('../../../sw-library/sw-function.php');
require_once'../../login/user.php';
require_once'../../../sw-library/phpqrcode/qrlib.php'; 

switch (@$_GET['action']){
/* ---------- ADD  ---------- */
case 'add':
    $error = array();

    if (empty($_POST['lokasi_nama'])) {
      $error[] = 'Nama tidak boleh kosong';
    } else {
      $lokasi_nama = anti_injection($_POST['lokasi_nama']);
    }

    if (empty($_POST['lokasi_alamat'])) {
        $error[] = 'Alamat Lengkap tidak boleh kosong';
      } else {
        $lokasi_alamat = anti_injection($_POST['lokasi_alamat']);
    }

    if (empty($_POST['lokasi_latitude'])) {
      $error[] = 'Latitude tidak boleh kosong';
    } else {
      $lokasi_latitude = anti_injection($_POST['lokasi_latitude']);
    }

    if (empty($_POST['lokasi_longitude'])) {
        $error[] = 'Longitude tidak boleh kosong';
      } else {
        $lokasi_longitude  = anti_injection($_POST['lokasi_longitude']);
    }

    if (empty($_POST['lokasi_radius'])) {
      $error[] = 'Radius tidak boleh kosong';
    } else {
      $lokasi_radius = anti_injection($_POST['lokasi_radius']);
    }

    if (empty($_POST['lokasi_status'])) {
      $lokasi_status = 'N';
    } else {
      $lokasi_status = 'Y';
    }

    
  if (empty($error)) {

  $query="SELECT lokasi_nama from lokasi where lokasi_nama='$lokasi_nama'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){

        /* --  Membuat Random Karakter ---- */
        $random_karakter = md5($lokasi_nama);
        $shuffle  = substr(str_shuffle($random_karakter),0,4);
        $qrcode   = ''.strtoupper($shuffle).''.time().'';
        /* --  End Random Karakter ---- */

          $codeContents = $qrcode;
          $tempdir = '../../../sw-content/lokasi/';
          $namafile = ''.seo_title($codeContents).'.jpg';

        $add ="INSERT INTO lokasi(lokasi_nama,
                    lokasi_alamat,
                    lokasi_latitude,
                    lokasi_longitude,
                    lokasi_radius,
                    lokasi_qrcode,
                    lokasi_tanggal,
                    lokasi_jam_mulai,
                    lokasi_jam_selesai,
                    lokasi_status) values('$lokasi_nama',
                    '$lokasi_alamat',
                    '$lokasi_latitude',
                    '$lokasi_longitude',
                    '$lokasi_radius',
                    '$qrcode',
                    '$date',
                    '$time',
                    '$time',
                    '$lokasi_status')";
        if($connection->query($add) === false) { 
              die($connection->error.__LINE__); 
              echo'Data tidak berhasil disimpan!';
          } else{
              echo'success';
              if(file_exists('../../../sw-content/lokasi/'.$namafile.'')){}else{
                $quality = 'QR_ECLEVEL_Q'; //ada 4 pilihan, L (Low), M(Medium), Q(Good), H(High)
                $ukuran = 8; //batasan 1 paling kecil, 10 paling besar
                $padding = 1;
                QRCode::png($codeContents,$tempdir.$namafile,$quality,$ukuran,$padding);
              }
          }
        }
      else{
        echo'Sepertinya lokasi "'.$lokasi_nama.'" sudah ada!';
      }}
      else{           
          foreach ($error as $key => $values) {            
            echo"$values\n";
          }
      }


/* -------------- Update ----------*/
break;
case 'update':
$error = array();
    if (empty($_POST['id'])) {
      $error[] = 'ID tidak ditemukan';
    } else {
      $id = anti_injection(epm_decode($_POST['id']));
    }

    if (empty($_POST['lokasi_nama'])) {
      $error[] = 'Nama tidak boleh kosong';
    } else {
      $lokasi_nama = anti_injection($_POST['lokasi_nama']);
    }

    if (empty($_POST['lokasi_alamat'])) {
        $error[] = 'Alamat Lengkap tidak boleh kosong';
      } else {
        $lokasi_alamat = anti_injection($_POST['lokasi_alamat']);
    }

    if (empty($_POST['lokasi_latitude'])) {
      $error[] = 'Latitude tidak boleh kosong';
    } else {
      $lokasi_latitude = anti_injection($_POST['lokasi_latitude']);
    }

    if (empty($_POST['lokasi_longitude'])) {
        $error[] = 'Longitude tidak boleh kosong';
      } else {
        $lokasi_longitude  = anti_injection($_POST['lokasi_longitude']);
    }

    if (empty($_POST['lokasi_radius'])) {
      $error[] = 'Radius tidak boleh kosong';
    } else {
      $lokasi_radius = anti_injection($_POST['lokasi_radius']);
    }

    if (empty($_POST['lokasi_status'])) {
      $lokasi_status = 'N';
    } else {
      $lokasi_status = 'Y';
    }
    
  if (empty($error)) {
    $update="UPDATE lokasi SET lokasi_nama='$lokasi_nama',
            lokasi_alamat='$lokasi_alamat',
            lokasi_latitude='$lokasi_latitude',
            lokasi_longitude='$lokasi_longitude',
            lokasi_radius='$lokasi_radius',
            lokasi_status='$lokasi_status' WHERE lokasi_id='$id'"; 
    if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }}
  else{
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }


/** --------- Set Active Lokasi ------- */

break;
case 'active':
  $id = htmlentities($_POST['id']);
  $active = htmlentities($_POST['active']);
  $update="UPDATE lokasi SET lokasi_status='$active' WHERE lokasi_id='$id'";
  if($connection->query($update) === false) { 
    echo 'error';
    die($connection->error.__LINE__); 
  }
  else{
    echo'success';
  }
  
/* --------------- Delete ------------*/
    break;
    case 'delete':
        $id       = anti_injection(epm_decode($_POST['id']));
        $query_lokasi ="SELECT lokasi.lokasi_qrcode, user.lokasi_id FROM lokasi
        JOIN user ON lokasi.lokasi_id =user.lokasi_id WHERE lokasi.lokasi_id='$id'";
        $result_lokasi = $connection->query($query_lokasi);
        if(!$result_lokasi->num_rows > 0){
          $row = $result_lokasi->fetch_assoc();
            /** Delete Qrcode */
            $qrcode_delete =''.seo_title($row['lokasi_qrcode']).'.jpg';
            $tmpfile = "../../../sw-content/qrcode/".$qrcode_delete;
              if(file_exists("../../../sw-content/lokasi/$qrcode_delete")){
                 unlink ($tmpfile); 
              }
            /* Script Delete Data ------------*/
            $deleted  = "DELETE FROM lokasi WHERE lokasi_id='$id'";
            if($connection->query($deleted) === true) {
                echo'success';
              } else { 
                //tidak berhasil
                echo'Data tidak berhasil dihapus.!';
                die($connection->error.__LINE__);
              }

        }else{
          echo 'Data lokasi ini aktif atau digunakan!';
        }
   

break;
}}