<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{ 
$op = isset($_GET['op']) ? $_GET['op'] : ''; 
$op = convert("decrypt", $op);
$perihal_options = [
    "Peringatan Pertama",
    "Peringatan Kedua",
    "Peringatan Ketiga",
    "Skorsing",
    "Dikeluarkan"
];

switch($op){ 
default:
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">
                
                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control siswa search mb-1 mt-1" required>
                                <option value="">Semua Siswa</option>';
                                $query_siswa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$data_user[wali_kelas]' ORDER BY nama_lengkap ASC";
                                $result_siswa = $connection->query($query_siswa);
                                if($result_siswa->num_rows > 0) {
                                    while($data_siswa = $result_siswa->fetch_assoc()){
                                        echo'<option value="'.convert("encrypt",$data_siswa['user_id']).'">'.strip_tags($data_siswa['nama_lengkap']??'-').'</option>';
                                    }
                                }else{
                                    echo'<option value="">Data tidak ditemukan</option>';
                                }
                                echo'
                            </select>
                        </div>

                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control bulan search mb-1 mt-1" required>';
                                $bulan_nama =array(1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
                                for($bulan=1; $bulan<=12; $bulan++){
                                    if($bulan<=$month ) {
                                    echo'<option value="'.$bulan.'" selected>'.$bulan_nama[$bulan].'</option>';
                                    }else { 
                                    echo'<option value="'.$bulan.'">'.$bulan_nama[$bulan].'</option>'; 
                                    }
                                }
                            echo'
                            </select>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>

         <div class="container mb-4 s-widodo.com">
            <div class="row mb-2">
                <div class="col">
                    <h7 class="subtitle s-widodo.com">Top 4 Pelanggaran Siswa</h7>
                </div>
                
                <div class="col-auto">
                    <a href="javascript:;" class="float-right small more-expand-btn">View All</a>
                </div>
            </div>
            <div class="load-sanksi postList s-widodo.com"></div>
        </div>
    </div>

    <div class="btn-floating s-widodo.com">
       <a href="./'.$mod.'&op='.convert("encrypt", "add").'" class="btn btn-primary s-widodo.com" style="line-height:50px"><span class="material-icons s-widodo.com">add_circle</span></a>
    </div>

</main>';


break;
case'add':
echo'
<main class="flex-shrink-0 main has-footer">  
    <div class="main-container">
        <div class="container">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <h6 class="subtitle s-widodo.com">Tambah Surat Sanksi</h6>
                        </div>
                        
                        <div class="col-auto">
                            <a href="'.$mod.'" class="btn btn-sm btn-outline-secondary rounded float-right small more-expand-btn">Kembali</a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                <form class="form-add" role="form" method="post" action="javascript:;" autocomplete="off">
                    <div class="form-group">
                        <label class="form-control-label">Siswa</label>
                        <select class="form-control siswa" name="siswa" required>
                            <option value="">Pilih Siswa</option>';
                            $query_siswa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$data_user[wali_kelas]' ORDER BY nama_lengkap ASC";
                            $result_siswa = $connection->query($query_siswa);
                            if($result_siswa->num_rows > 0) {
                                while($data_siswa = $result_siswa->fetch_assoc()){
                                    echo'<option value="'.convert("encrypt",$data_siswa['user_id']).'">'.strip_tags($data_siswa['nama_lengkap']??'-').'</option>';
                                }
                            }else{
                                echo'<option value="">Data tidak ditemukan</option>';
                            }
                            echo'
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-control-label">Ditujukan Kepada</label>
                        <input type="hidden" class="form-control wali_murid d-none" name="wali_murid">
                        <input type="text" class="form-control ditujukan" name="ditujukan" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Perihal</label>
                        <select class="form-control" name="perihal" required>
                            <option value="">Pilih Perihal</option>';
                           foreach($perihal_options as $option):
                            echo '<option value="'.$option.'">'.$option.'</option>';
                            endforeach;
                            echo'
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="data-pelanggaran-body"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-control-label">Keterangan/Daftar Pelanggaran</label>
                        <textarea class="form-control keterangan" name="keterangan" rows="5" required></textarea>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-block btn-default rounded btn-save btn-profile">Simpan</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</main>';
break;



break;
case'update':
if(empty($_GET['id']) OR !is_numeric(convert("decrypt", $_GET['id']??'0'))){
    header('location:'.$mod);
}else{
$id = convert("decrypt", $_GET['id']??'0');

echo'
<main class="flex-shrink-0 main has-footer">  
    <div class="main-container">
        <div class="container">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <h6 class="subtitle s-widodo.com">Ubah Surat Sanksi</h6>
                        </div>
                        
                        <div class="col-auto">
                            <a href="'.$mod.'" class="btn btn-sm btn-outline-secondary rounded float-right small more-expand-btn">Kembali</a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">';
                if(isset($id) AND is_numeric($id)){
                $query_sanksi = "SELECT * FROM sanksi_pelanggaran WHERE id='$id' AND pegawai_id='{$data_user['pegawai_id']}' LIMIT 1";
                $result_sanksi = $connection->query($query_sanksi);
                if($result_sanksi->num_rows > 0) {
                    $data_sanksi = $result_sanksi->fetch_assoc();
 
                echo'
                <form class="form-update" role="form" method="post" action="javascript:;" autocomplete="off">
                    <input type="hidden" name="id" value="'.convert("encrypt",$data_sanksi['id']).'">
                    <div class="form-group">
                        <label class="form-control-label">Siswa</label>
                        <select class="form-control siswa" name="siswa" required>
                            <option value="">Pilih Siswa</option>';
                            $query_siswa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$data_user[wali_kelas]' ORDER BY nama_lengkap ASC";
                            $result_siswa = $connection->query($query_siswa);
                            if($result_siswa->num_rows > 0) {
                                while($data_siswa = $result_siswa->fetch_assoc()){
                                    $selected = ($data_siswa['user_id'] == $data_sanksi['user_id']) ? 'selected' : '';
                                    echo'<option value="'.convert("encrypt",$data_siswa['user_id']).'" '.$selected.'>'.strip_tags($data_siswa['nama_lengkap']??'-').'</option>';
                                }
                            }else{
                                echo'<option value="">Data tidak ditemukan</option>';
                            }
                            echo'
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-control-label">Ditujukan Kepada</label>
                        <input type="hidden" class="form-control wali_murid d-none" value="'.$data_sanksi['wali_murid'].'" name="wali_murid">
                        <input type="text" class="form-control ditujukan" name="ditujukan" value="'.$data_sanksi['ditujukan'].'" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Perihal</label>
                        <select class="form-control" name="perihal" required>
                        <option value="">Pilih Perihal</option>';
                        foreach($perihal_options as $option):
                            echo '<option value="'.$option.'" '.(($data_sanksi['perihal'] == $option) ? 'selected' : '').'>
                                    '.$option.'
                                </option>';
                        endforeach;
                        echo'
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="data-pelanggaran-body"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-control-label">Keterangan/Daftar Pelanggaran</label>
                        <textarea class="form-control keterangan" name="keterangan" rows="5" required>'.$data_sanksi['keterangan'].'</textarea>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-block btn-default rounded btn-save btn-profile">Simpan</button>
                    </div>
                </form>';
                }else{
                    echo'<div class="alert alert-danger mt-3">Data tidak ditemukan</div>';
                    exit();
                }
                }
            echo'
            </div>
            
        </div>
    </div>
</main>';
}
break;
}
}?>