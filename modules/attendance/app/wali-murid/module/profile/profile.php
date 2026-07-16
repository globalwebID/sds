<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
    header('location:../404');
}else{
    $tanggal_lahir = !empty($data_user['tanggal_lahir']) ? tanggal_ind($data_user['tanggal_lahir']) : '-';
echo'
<main class="flex-shrink-0 main has-footer">  
    <div class="main-container">
        <div class="container">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="subtitle mb-0">
                        <div class="avatar avatar-40 bg-default-light text-default rounded mr-2">
                            <span class="material-icons">person</span>
                        </div>
                        Profile
                    </h6>
                </div>
                <div class="card-body">
                <form class="form-profile" role="form" method="post" action="#" autocomplete="off">

                    <div class="form-group">
                        <label class="form-control-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" value="'.strip_tags($data_user['nama_lengkap']??'-').'" required>
                    </div>

                  
                   
                        <div class="form-group">
                            <label class="label" for="text4">NISN Siswa</label>
                            <input type="number" class="form-control nisn" min="" name="nisn" value="'.strip_tags($data_user['nisn']??'-').'" required>
                            <small class="result-output"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label">Nama Siswa</label>
                            <input type="text" class="form-control nama_siswa" name="nama_siswa" value="'.strip_tags($data_user['nama_siswa']??'-').'" readonly required>
                        </div>
                  


                    <div class="form-group">
                        <label class="form-control-label">Tempat Lahir</label>
                        <input type="text" class="form-control" name="tempat_lahir" value="'.strip_tags($data_user['tempat_lahir']).'" required>
                    </div>


                    <div class="form-group">
                        <label class="form-control-label">Jenis Kelamin</label>
                        <select class="form-control" name="jenis_kelamin" required>';
                            if($data_user['jenis_kelamin'] =='Laki-laki'){
                            echo'<option value="Laki-laki" selected>Laki-laki</option>';
                            }else{
                                echo'<option value="Laki-laki">Laki-laki</option>';
                            }
                            if($data_user['jenis_kelamin'] =='Perempuan'){
                                echo'<option value="Perempuan">Perempuan</option>';
                            }else{
                                echo'<option value="Perempuan">Perempuan</option>';
                            }
                            echo'
                        </select>
                    </div>
               


                    <div class="form-group">
                        <label class="form-control-label">Email</label>
                        <input type="email" class="form-control" name="email" value="'.strip_tags($data_user['email']).'" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">No. WhatsApp</label>
                        <input type="number" class="form-control" name="telp" value="'.strip_tags($data_user['telp']??'0').'" required>
                        <small class="text-danger">Ex : 6283160901108</small>
                    </div>

                    <div class="form-group boxed">
                        <label class="form-control-label">Alamat Rumah</label>
                        <textarea class="form-control" name="alamat" rows="3" required>'.strip_tags($data_user['alamat']??'-').'</textarea>
                    </div>

                
                <div class="card-footer">
                    <button type="submit" class="btn btn-block btn-default rounded btn-save btn-profile">Simpan</button>
                </div>
                </form>
            </div>
            
        </div>
    </div>
</main>';


}?>