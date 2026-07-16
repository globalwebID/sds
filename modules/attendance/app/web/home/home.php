<?php if(empty($connection)){
    header('location:../404');
}else{
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">

    <header class="header">
        <div class="row">
            <div class="ml-auto col-auto align-self-center">
                <a href="registrasi" class="text-white">
                    Daftar baru
                </a>
            </div>
        </div>
    </header>
        
    <form class="form-login" role="form" method="post" action="javascript:;" autocomplete="off">
        <div class="container h-100 text-white">
            <div class="row justify-content-center h-100">
                <div class="col-11 col-sm-7 col-md-6 col-lg-5 col-xl-5">
                    <div class="text-center">
                        <img src="./sw-content/'.($row_site['site_logo']??'logo.jpg').'" class="img-responsive" height="40">
                    </div>
                    
                    <h6 class="mb-5 mt-3 text-center">Masuk ke akun Anda</h6>

                    <div class="form-group">
                        <label class="form-control-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control password">
                    </div>

                    <div class="form-group">    
                        <label class="form-control-label">Level Login</label>
                        <select name="tipe" class="form-control tipe">
                            <option value="">Pilih level login</option>
                            <option value="siswa">Siswa</option>
                            <option value="pegawai">Pegawai/Guru</option>
                            <option value="wali-murid">Wali Murid</option>
                        </select>
                        
                    </div>

                    <div class="row mt-2">
                        <div class="col-6 col-md-6">
                            <div class="form-group">
                                <input type="checkbox" id="showPassword">
                                <label for="showPassword" class="checkbox-label">Tampilkan Password</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-6">
                            <p class="text-right"><a href="forgot" class="text-white">Forgot Password?</a></p>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">    
                        <button type="submit" class="btn btn-default rounded btn-block btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>';
                        if($row_site['google_client_active']=='Y'){
                        echo'
                        <button type="button" class="btn btn-danger rounded btn-block btn-login-google"><i class="fab fa-google"></i> Login dengan Google</button>';
                        }
                    echo'
                    </div>

                </div>
            </div>
            </div>
        </div>
        </form>
</main>';
}?>