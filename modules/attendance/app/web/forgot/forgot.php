<?php 
if ($mod ==''){
    header('location:../404');
    echo'kosong';
}else{
include_once './home-page/sw-header.php';
 echo'
 <!-- App Capsule -->
    <div id="appCapsule">
        <div class="section text-center">
            <img src="'.$base_url.'sw-content/'.$site_logo.'" height="40" class="mb-3">
            <h4>Resset Password</h4>
            <p>Masukkan Email untuk resset password Anda</p>
        </div>
        <div class="section mb-5 p-2">
            <div class="container">
                <div class="row justify-content-md-center">
                    <div class="col-md-5">

                        <form class="form-forgot" role="form" method="post" action="javascript:;" autocomplete="off">
                            <div class="card">
                                <div class="card-body pb-1">
                                    <div class="form-group basic">
                                        <div class="input-wrapper">
                                            <label class="label">E-mail</label>
                                            <input type="email" class="form-control" name="email" placeholder="E-mail" required>
                                            <i class="clear-input"><ion-icon name="close-circle"></ion-icon></i>
                                        </div>
                                    </div>

                                    <div class="form-group basic">
                                        <div class="input-wrapper">
                                            <label class="label"></label>
                                            <select name="tipe" class="form-control tipe" required>
                                                <option value="">Pilih level</option>
                                                <option value="siswa">Siswa</option>
                                                <option value="wali">Wali Murid</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-2">
                                        <button type="submit" class="btn btn-primary btn-block btn-login">Resset Password</button>
                                    </div>
                                </form>

                                </div>
                            </div>

                            <div class="form-links mt-2">
                                <div>
                                    <a href="registrasi">Mendaftar</a>
                                </div>
                                <div><a href="forgot" class="text-muted">Lupa Password?</a></div>
                            </div>
                        
                </div>
            </div>
            </div>
        </div>

    </div>
    <!-- * App Capsule -->';
  include_once './home-page/sw-footer.php';
} ?>