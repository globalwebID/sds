<?php 
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
} else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';
require_once'../../../sw-library/fpdf/fpdf.php';


$query_tema ="SELECT foto FROM kartu_nama WHERE active='Y'";
$result_tema = $connection->query($query_tema);
$data_tema = $result_tema->fetch_assoc();


if(file_exists('../../../sw-content/tema/'.$data_tema['foto'].'')){
    $background = '../../../sw-content/tema/'.$data_tema['foto'].'';
}else{
    $background = '';
}


if(isset($_GET['kelas']) && !empty($_GET['kelas'])){
$kelas = strip_tags($_GET['kelas']);
$query_user ="SELECT * FROM user WHERE kelas='$kelas' ORDER BY nama_lengkap ASC";
$result_user = $connection->query($query_user);

if($result_user->num_rows > 0){
echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>ID Card</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif}

        .content-box {
            text-align: center;
            float: left;
            width: 550px;
            margin:5%;
        }

      
        .thema-id-card{
            position: relative;
            display: inline-block;
            background: #ffffff;
            background-size:100%!important;
            width :200px;
            height:380px;
            border-radius:10px;
            border:solid 1px #cccccc;
            padding: 20px 30px;
            float:left;
        }


        .thema-id-card .avatar-idcard{
            display: inline-block;
            
            margin-top:80px!important;
        }

        .thema-id-card .avatar-idcard img{
            width: 120px;
            height:130px;
            object-fit: cover;
            border:solid 2px #ffffff;
        }

        .thema-id-card .description{
            margin-top:5px;
        }

        .thema-id-card .description p.bold{
            font-size:16px!important;
            font-weight: 500;
        }

        .thema-id-card .description p {
            font-size: 13px;
            line-height:17px;
        }

        .thema-id-card .description .label p{
            font-size: 14px;
            line-height:20px;
            padding: 0px;
            margin: auto;
            text-transform: uppercase;
            color:#ffffff;
        }

        .thema-id-card .description ul {
            list-style: none;
            padding: 0;
            display: inline-block;
            flex-direction: column;
            margin-top:15px;
            text-align: left;
        }

        .thema-id-card .description ul li {
            display: inline-block;
            align-items: center;
            font-size: 13px;
        }

        .thema-id-card .description ul li span {
            width: 50px;
            margin-right: 10px;
            line-height: 22px;
        }


        .thema-id-card .qrcode{
            display: inline-block;
            margin-top: 80px!important;
        }

        .thema-id-card .qrcode img{
            height:120px;
            width: 120px;
            border:solid 2px #fff;
        }

    </style>
    <script>
        window.onafterprint = window.close;
        //window.print();
      </script>
</head>
<body>';
    while ($data_user = $result_user->fetch_assoc()) {
        // Tentukan avatar path
        if (file_exists('../../../sw-content/avatar/'.strip_tags($data_user['avatar']??'-').'')) {
            $avatarPath = '../../../sw-content/avatar/'.strip_tags($data_user['avatar']??'-').'';
        } else {
            $avatarPath = '../../../sw-content/avatar/avatar.jpg';
        }

        echo'
        <div class="content-box">
            <div class="thema-id-card s-widodo.com" style="background: url(../../../sw-content/tema/'.$data_tema['foto'].')">
                <div class="avatar-idcard s-widodo.com">';
                    if(file_exists('../sw-content/avatar/'.$avatarFile)){
                        echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../../../sw-content/avatar/'.$data_user['avatar'].'')).'" class="s-widodo.com">';
                    }else{
                        echo'<img src="../../../sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                    }
                echo'
                </div>

                <div class="description s-widodo.com">
                    <div class="label">
                        <p class="s-widodo.com">'.strip_tags($data_user['nama_lengkap']??'-').'</p>
                        <p class="s-widodo.com">Kelas '.strip_tags($data_user['kelas']??'-').'</p>
                    </div>
                </div>
                <div class="description s-widodo.com">
                    <ul>
                        <li><span>NISN</span>: '.$data_user['nisn'].'</li>
                        <li><span>Email</span>: '.$data_user['email'].'</li>
                    </ul>
                    </div>
            </div>

            <div class="thema-id-card s-widodo.com" style="background: url(../../../sw-content/tema/'.$data_tema['foto'].')">
    
                <div class="qrcode s-widodo.com">';
                    if(file_exists('../../../sw-content/qrcode/'.($data_user['nisn']??'avatar').'.png')){
                        echo'<img src="data:image/png;base64,'.base64_encode(file_get_contents('../../../sw-content/qrcode/'.($data_user['nisn']??'avatar.jpg').'.png')).'"  class="s-widodo.com">';
                    }else{
                        echo'<img src="../../../sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                    }
                echo'
                </div>

                <div class="description s-widodo.com">
                    <div class="label">
                        <p class="s-widodo.com">'.strip_tags($data_user['nama_lengkap']??'-').'</p>
                        <p class="s-widodo.com">Kelas '.strip_tags($data_user['kelas']??'-').'</p>
                    </div>
                </div>
                <div class="description s-widodo.com">
                    <ul>
                        <li><span>NISN</span>: '.$data_user['nisn'].'</li>
                        <li><span>Email</span>: '.$data_user['email'].'</li>
                    </ul>
                    </div>

                    <div class="description s-widodo.com">
                    <p>'.$row_site['nama_sekolah'].'<br>Telp. '.$row_site['site_phone'].'</p>
                    </div>
            </div>
        
        </div>';

    }
    
echo'
</body>
</html>';

}else{
    echo'Data yang Anda cari tidak ditemukan';
}

}}?>