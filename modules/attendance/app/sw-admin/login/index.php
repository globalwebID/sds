<?PHP require_once'../../sw-library/sw-config.php';
$loginLogoUrl = !empty($sds_site_logo_url) ? $sds_site_logo_url : '../../sw-content/'.$site_logo;
$loginFaviconUrl = !empty($sds_site_favicon_url) ? $sds_site_favicon_url : '../../sw-content/'.$site_favicon;
echo'
<!DOCTYPE html>
<html>

<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login Dashboard</title>
    <meta name="robots" content="noindex">
    <meta name="description" content="'.$site_name.'">
    <meta name="author" content="s-widodo.com">
    <meta http-equiv="Copyright" content="'.$site_name.'">
    <meta name="copyright" content="s-widodo.com">
    <meta name="description" content="s-widodo.com 083160901108">
    <!-- Favicon -->
    <link rel="icon" href="'.htmlspecialchars($loginFaviconUrl, ENT_QUOTES, 'UTF-8').'" type="image/png">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <!-- Icons -->
    <link rel="stylesheet" href="../sw-assets/vendor/nucleo/css/nucleo.css" type="text/css">
    <link rel="stylesheet" href="../sw-assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css">
    <link rel="stylesheet" href="../sw-assets/css/argon.min.css?v=1.1.0" type="text/css">
</head>

<body>
    <!-- Navbar -->';
switch(@$_GET['op']){ 
    default:
    echo'
    <!-- Main content -->
    <div class="main-content">
        <!-- Page content -->
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="text-center mt-5">
                        <img src="'.htmlspecialchars($loginLogoUrl, ENT_QUOTES, 'UTF-8').'" class="navbar-brand-img" height="40" alt="Logo Sekolah">
                    </div>
                    <div class="card bg-secondary border-0 mb-0 mt-4">
                        <div class="card-header bg-transparent">
                            <div class="text-muted text-center mt-2 mb-2">Login ke Dashboard</div>
                        </div>

                        <div class="card-body px-lg-4 py-lg-4">
                            <div class="text-center text-muted mb-4">
                                <small>Masukkan Username dan password</small>
                            </div>

                            <form class="login" role="form" method="post" action="#" autocomplete="off">
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="username" value="" placeholder="Username/Email" required>
                                    </div>
                                    
                                </div>
                                <div class="form-group">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                        </div>
                                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                                    </div>
                            

                                </div>
                               
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary my-4">Masuk Ke Dashboard</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <a href="?op=forgot">Lupa password?</a>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>';

    break;
    case'forgot':
        echo'
        <!-- Main content -->
        <div class="main-content">
            <!-- Page content -->
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5 col-md-7">
                        <div class="text-center mt-5">
                            <img src="'.htmlspecialchars($loginLogoUrl, ENT_QUOTES, 'UTF-8').'" class="navbar-brand-img" height="40" alt="Logo Sekolah">
                        </div>
                        <div class="card bg-secondary border-0 mb-0 mt-4">
                            <div class="card-header bg-transparent">
                                <div class="text-muted text-center mt-2 mb-2">Resset Password</div>
                                
                            </div>
    
                            <div class="card-body px-lg-4 py-lg-4">
                                <div class="text-center text-muted mb-4">
                                    <small>Masukkan Email untuk resset Password baru</small>
                                </div>
    
                                <form class="form-forgot" role="form" method="post" action="#" autocomplete="off">
                                    <div class="form-group mb-3">
                                        <div class="input-group input-group-merge input-group-alternative">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                            </div>
                                            <input type="email" class="form-control" name="email" value="" placeholder="Email" required>
                                        </div>
                                        
                                    </div>
                        
                                   
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary my-4">Reset  Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-6">
                                <a href="./">Login</a>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>';
break;
    }
echo'
<span class="credits" style="display:none">
    <a class="credits_a" id="mycredit" href="https://s-widodo.com"  target="_blank">S-widodo.com</a>
</span>
  <script src="../sw-assets/bundle.min.php?get=s-widodo.com"></script>
  <script src="./sw-script.js"></script>
</body>
</html>';?>

