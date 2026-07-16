<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
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
                        <label class="form-control-label">NIP</label>
                        <input type="text" class="form-control" name="nip" value="'.strip_tags($data_user['nip']??'').'" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">RFID</label>
                        <input type="text" class="form-control" name="nik" value="'.strip_tags($data_user['rfid']??'').'" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" value="'.strip_tags($data_user['nama_lengkap']??'-').'" required> 
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Tempat Lahir</label>
                        <input type="text" class="form-control" name="tempat_lahir" value="'.strip_tags($data_user['tempat_lahir']??'-').'" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Tanggal Lahir</label>
                        <input type="text" class="form-control datepicker" name="tanggal_lahir" value="'.$tanggal_lahir.'" required> 
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
                                echo'<option value="Perempuan" selected>Perempuan</option>';
                            }else{
                                echo'<option value="Perempuan">Perempuan</option>';
                            }
                            echo'
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Jabatan</label>
                        <select class="form-control" name="jabatan" required>
                        <option value="">Pilih:</option>';
                            if($data_user['jabatan'] =='guru'){
                            echo'<option value="guru" selected>Pengajar</option>';
                            }else{
                            echo'<option value="guru">Pengajar</option>';
                            }
                            if($data_user['jabatan'] =='staff'){
                            echo'<option value="staff" selected>Staff</option>';
                            }else{
                            echo'<option value="staff">Staff</option>';
                            }
                            echo'
                        </select>
                    </div>

                     <div class="form-group">
                        <label class="form-control-label">Walli Kelas</label>
                        <select class="form-control" name="wali_kelas" required>
                        <option value="-">Bukan Wali Kelas</option>';
                        $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                        $result_kelas = $connection->query($query_kelas);
                        while ($data_kelas = $result_kelas->fetch_assoc()) {
                            $selected = ($data_kelas['nama_kelas'] == $data_user['wali_kelas']) ? 'selected' : '';
                            echo'<option value="'.$data_kelas['nama_kelas'].'" '.$selected.'>'.$data_kelas['nama_kelas'].'</option>';
                        }
                        echo'
                        </select>
                    </div>

                   

                    <div class="form-group">
                        <label class="form-control-label">Lokasi/Tempat Sekolah</label>
                        <select class="form-control" name="lokasi" required>';
                        $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                        $result_lokasi = $connection->query($query_lokasi);
                        while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                            if($data_lokasi['lokasi_id'] == $data_user['lokasi']) {
                                echo'<option value="'.$data_lokasi['lokasi_id'].'" selected>'.$data_lokasi['lokasi_nama'].'</option>';
                            }else{
                                echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                            }
                            }
                        echo'
                        </select>
                    </div>


                    <div class="form-group">
                        <label class="form-control-label">No. WhatsApp</label>
                        <input type="number" class="form-control" name="telp" value="'.strip_tags($data_user['telp']??'0').'" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Alamat Lengkap</label>
                        <textarea class="form-control" name="alamat" rows="2" required>'.strip_tags($data_user['alamat']??'-').'</textarea>
                    </div>

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