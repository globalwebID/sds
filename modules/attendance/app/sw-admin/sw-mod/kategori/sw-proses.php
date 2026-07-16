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
/* ---------- ADD  ---------- */
case 'add':
    $error = array();
    $id = anti_injection($_POST['id']);
    
    if (empty($_POST['title'])) {
      $error[] = 'Judul tidak boleh kosong';
    } else {
      $title    = anti_injection($_POST['title']);
      $seotitle = seo_title($title);
    }


  if (empty($error)) {

  $query="SELECT kategori_id from kategori where kategori_id='$id'";
  $result= $connection->query($query) or die($connection->error.__LINE__);
  if(!$result ->num_rows >0){
        /* ---- Tambah data ------*/
        $query_kategori="SELECT title from kategori WHERE title='$title'";
        $result_kategori = $connection->query($query_kategori);
        if(!$result_kategori->num_rows > 0){

          $add ="INSERT INTO kategori(title,seotitle) values('$title','$seotitle')";
            if($connection->query($add) === false) { 
                die($connection->error.__LINE__); 
                echo'Data tidak berhasil disimpan!';
            } else{
                echo'success';
            }
        } else{
          echo 'Kategori '.$title.' sudah ada!';
        }
      }else{
        /* --  Update data -- */
        $update="UPDATE kategori SET title='$title', seotitle='$seotitle' WHERE kategori_id='$id'"; 
        if($connection->query($update) === false) { 
            die($connection->error.__LINE__); 
            echo'Data tidak berhasil disimpan!';
        } else{
            echo'success';
        }
    }
  }
  else{           
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
  }

  
/* --------------- Delete ------------*/
break;
case 'delete':
    $id       = anti_injection(epm_decode($_POST['id']));
    $deleted = "DELETE FROM kategori WHERE kategori_id='$id'";
    if($connection->query($deleted) === true) {
      echo'success';
    } else { 
      //tidak berhasil
      echo'Data tidak berhasil dihapus.!';
      die($connection->error.__LINE__);
    }

break;
}}