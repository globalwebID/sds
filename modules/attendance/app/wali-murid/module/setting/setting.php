<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
    header('location:../404');
}else{
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">  
    
<!-- page content start -->
        <div class="container-fluid px-0 s-widodo.com">
            <div class="card overflow-hidden s-widodo.com">
                <div class="card-body p-0 h-150 s-widodo.com">
                    <div class="background s-widodo.com">
                        <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/image10.jpg')).'" alt="" class="s-widodo.com">
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid top-70 text-center mb-4 s-widodo.com">
            <div class="avatar avatar-140 rounded-circle mx-auto shadow s-widodo.com">
                <div class="background avatar-upload s-widodo.com">';
                    if($data_user['avatar'] == NULL OR $data_user['avatar']=='avatar.jpg'){
                        echo'<img src="../sw-content/avatar/avatar.jpg" id="output" class="imaged w100 rounded s-widodo.com" height="100">';
                        }else{
                         if(file_exists('../sw-content/avatar/'.$data_user['avatar'].'')){
                            echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/avatar/'.$data_user['avatar'].'')).'" id="output" class="imaged w100 rounded s-widodo.com" height="100">';
                         }else{
                            echo'<img src="../sw-content/avatar/avatar.jpg" id="output" class="imaged w100 rounded s-widodo.com" height="100">';
                         }
                    }
                 echo'
                </div>
                <span class="button s-widodo.com">
                    <input type="file" class="upload s-widodo.com" name="file" id="avatar" accept=".jpg, .jpeg, ,gif, .png" style="opacity:0">
                    <span class="material-icons s-widodo.com">photo_camera</span>
                </span>
            </div>
        </div>


    <div class="main-container s-widodo.com">
        <div class="container s-widodo.com">
                <div class="card s-widodo.com">
                    <div class="card-header s-widodo.com">
                        <h6 class="mb-0 s-widodo.com">Pengaturan</h6>
                    </div>
                    <div class="card-body px-0 pt-0 s-widodo.com">
                        <div class="list-group list-group-flush border-top border-color s-widodo.com">
                            <a href="profile" class="list-group-item list-group-item-action border-color s-widodo.com">
                                <div class="row s-widodo.com">
                                    <div class="col-auto s-widodo.com">
                                        <div class="avatar avatar-50 bg-default-light text-default rounded s-widodo.com">
                                            <span class="material-icons s-widodo.com">person</span>
                                        </div>
                                    </div>
                                    <div class="col align-self-center pl-0 s-widodo.com">
                                        <h6 class="mb-1 s-widodo.com">Profile</h6>
                                        <p class="text-secondary s-widodo.com">Ubah/setting profil</p>
                                    </div>
                                </div>
                            </a>

                            <a href="keamanan" class="list-group-item list-group-item-action border-color s-widodo.com">
                                <div class="row s-widodo.com">
                                    <div class="col-auto s-widodo.com">
                                        <div class="avatar avatar-50 bg-default-light text-default rounded s-widodo.com">
                                            <span class="material-icons s-widodo.com">lock_open</span>
                                        </div>
                                    </div>
                                    <div class="col align-self-center pl-0 s-widodo.com">
                                        <h6 class="mb-1 s-widodo.com">Keamaan</h6>
                                        <p class="text-secondary s-widodo.com">Ubah Email & Password</p>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="javascript:void(0);" class="list-group-item list-group-item-action border-color colorsettings s-widodo.com">
                                <div class="row s-widodo.com">
                                    <div class="col-auto s-widodo.com">
                                        <div class="avatar avatar-50 bg-default-light text-default rounded s-widodo.com">
                                            <span class="material-icons s-widodo.com">palette</span>
                                        </div>
                                    </div>
                                    <div class="col align-self-center pl-0 s-widodo.com">
                                        <h6 class="mb-1 s-widodo.com">Tampilan</h6>
                                        <p class="text-secondary s-widodo.com">Ubah tampilan warna</p>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="../logout" class="list-group-item list-group-item-action border-color s-widodo.com">
                                <div class="row s-widodo.com">
                                    <div class="col-auto s-widodo.com">
                                        <div class="avatar avatar-50 bg-danger-light text-danger rounded s-widodo.com">
                                            <span class="material-icons s-widodo.com">power_settings_new</span>
                                        </div>
                                    </div>
                                    <div class="col align-self-center pl-0 s-widodo.com">
                                        <h6 class="mb-1 s-widodo.com">Logout</h6>
                                        <p class="text-secondary s-widodo.com">Keluar dari aplikasi</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>';


}?>