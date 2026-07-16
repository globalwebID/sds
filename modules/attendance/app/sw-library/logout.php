<?PHP require_once'../sw-library/sw-config.php';
    require_once'../sw-library/sw-function.php';
    $expired_cookie = time()+60*60*24*3;
    

    if(isset($_COOKIE['siswa'])){
        if(!empty($_COOKIE['siswa'])){
            $siswa = htmlspecialchars(convert("decrypt", $_COOKIE['siswa']));
            $update_user = "UPDATE user SET status='Offline' WHERE user_id='$siswa'";
            if ($connection && $connection->query($update_user)) {
                header("location:./");
                setcookie('user', '', 0, '/');
                setcookie("user", "", time()-$expired_cookie);
                exit();
            } else {
                echo "Terjadi kesalahan saat logout.";
                exit();
            }
        } else {
            echo "Data siswa tidak ditemukan.";
            exit();
        }

    } elseif (isset($_COOKIE['pegawai'])) {
        if (!empty($_COOKIE['pegawai'])) {
            $pegawai = htmlspecialchars(convert("decrypt", $_COOKIE['pegawai']));
            $update = "UPDATE pegawai SET status='Offline' WHERE pegawai_id='$pegawai'";
            if ($connection && $connection->query($update)) {
                header("location:./");
                setcookie('pegawai', '', 0, '/');
                setcookie("pegawai", "", time()-$expired_cookie);
                exit();
            } else {
                echo "Terjadi kesalahan saat logout pegawai.";
                exit();
            }
        } else {
            echo "Data pegawai tidak ditemukan.";
            exit();
        }
    }else{
        echo'404';
    }
?>

		
