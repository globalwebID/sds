<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">  
    <div class="main-container s-widodo.com">
        <div class="container s-widodo.com">
            <div class="card mb-4 s-widodo.com pb-4">
                <div class="card-header s-widodo.com">
                    <h6 class="subtitle mb-0 s-widodo.com">
                        <div class="avatar avatar-40 bg-default-light text-default rounded mr-2 s-widodo.com"><span class="material-icons vm s-widodo.com">lock</span></div>
                        Ubah Kata Sandi
                    </h6>
                </div>
                <form class="form-password s-widodo.com" role="form" method="post" action="#" autocomplete="off">
                    <div class="card-body s-widodo.com">
                    
                        <div class="form-group s-widodo.com">
                            <label class="form-control-label s-widodo.com">Email</label>
                            <input type="email" class="form-control s-widodo.com" name="email" value="'.strip_tags($data_user['email']??'-').'" required>
                        </div>

                        <div class="form-group s-widodo.com">
                            <label class="form-control-label s-widodo.com">Password lama</label>
                            <div class="input-group s-widodo.com">
                            <input type="password" class="form-control password s-widodo.com" id="password-field" name="password_lama">
                                <div class="input-group-append s-widodo.com">
                                <span class="input-group-text s-widodo.com"><span toggle="#password-field" class="far fa-eye toggle-password s-widodo.com"></span></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group s-widodo.com">
                            <label class="form-control-label s-widodo.com">Password baru</label>
                            <div class="input-group s-widodo.com">
                                <input type="password" class="form-control password password-verifikasi s-widodo.com" id="password-baru" name="password_baru">
                                <div class="input-group-append s-widodo.com">
                                    <span class="input-group-text s-widodo.com"><span toggle="#password-baru" class="far fa-eye toggle-passwordb s-widodo.com"></span></span>
                                </div>
                            </div>
                            <span class="error"></span>
                        </div>

                    </div>
                    <div class="card-footer s-widodo.com">
                        <button type="submit" class="btn btn-block btn-default rounded btn-save btn-profile s-widodo.com">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>';


}?>