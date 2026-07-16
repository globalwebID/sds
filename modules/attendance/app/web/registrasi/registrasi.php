<?php if(empty($connection)){
    header('location:../404');
}else{
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">

    <header class="header">
        <div class="row">
            <div class="ml-auto col-auto align-self-center">
                <a href="./" class="text-white">
                    Login
                </a>
            </div>
        </div>
    </header>
        
   
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-12 col-sm-8 col-md-6 col-lg-8 col-xl-8">
                    <div class="text-center">
                        <img src="./sw-content/'.($row_site['site_logo']??'logo.jpg').'" class="img-responsive" height="40">
                    </div>

                     <h6 class="mb-5 mt-3 text-center text-white">Masuk ke akun Anda</h6>

                    <div class="card mb-4">
                        <div class="card-header" style="padding:10px 0px!important;overflow:hidden">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link nav-link1 text-black" href="javascript:void(0);" onclick="loadReg(1);" role="tab" aria-controls="tabhome125" aria-selected="true">
                                        Siswa
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-link2 text-black" href="javascript:void(0);" onclick="loadReg(2);" role="tab" aria-controls="tabhome225" aria-selected="false">
                                        Pegawai/Pengajar
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-link3 text-black" href="javascript:void(0);" onclick="loadReg(3);" role="tab" aria-controls="tabhome325" aria-selected="false">
                                        Wali Murid
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="load-form">
                               
                            </div>
                        </div>
                        
                    </div>

                </div>
            </div>
        </div>
    
</main>';
}?>