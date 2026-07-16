<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
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
                        <label class="form-control-label">NISN</label>
                        <input type="text" class="form-control" name="nisn" value="'.strip_tags($data_user['nisn']??'').'" required>
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
                    <label class="form-control-label">Kelas</label>
                        <select class="form-control" name="kelas" required>';
                            $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                            $result_kelas = $connection->query($query_kelas);
                            while ($data_kelas = $result_kelas->fetch_assoc()) {
                                $selected = ($data_kelas['nama_kelas'] == $data_user['kelas']) ? 'selected' : '';
                                echo'<option value="'.$data_kelas['nama_kelas'].'" '.$selected.'>'.$data_kelas['nama_kelas'].'</option>';
                            }
                            echo'
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Tahun Ajaran</label>
                        <select class="form-control" name="tahun_ajaran" required>';
                            $tahun_user_lama = $connection->real_escape_string((string)($data_user['tahun_ajaran'] ?? ''));
                            $query_tahun ="SELECT * FROM tahun_ajaran WHERE (is_active=1 AND status='active') OR tahun_ajaran='$tahun_user_lama' ORDER BY is_active DESC, tahun_ajaran DESC";
                            $result_tahun = $connection->query($query_tahun);
                            while ($data_tahun = $result_tahun->fetch_assoc()) {
                                $selected = ($data_tahun['tahun_ajaran'] == $data_user['tahun_ajaran']) ? 'selected' : '';
                                echo'<option value="'.$data_tahun['tahun_ajaran'].'" '.$selected.'>'.$data_tahun['tahun_ajaran'].'</option>';
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