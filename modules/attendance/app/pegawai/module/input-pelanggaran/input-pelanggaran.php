<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{ 
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">
                
                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control kelas search mb-1 mt-1" required>
                                <option value="">Semua Kelas</option>';
                                $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                                $result_kelas = $connection->query($query_kelas);
                                while ($data_kelas = $result_kelas->fetch_assoc()) {
                                    echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                                }
                                echo'
                            </select>
                        </div>

                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control siswa search" name="siswa" required>
                                <option value="">Semua Siswa</option>
                            </select>
                        </div>

                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control mb-1 mt-1 bulan search" required>';
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

                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control mb-1 mt-1 tahun search" required>';
                                $tahun_skr = date('Y');
                                for($tahun=2024; $tahun<=$tahun_skr; $tahun++){
                                    if($tahun==$tahun_skr ) {
                                    echo'<option value="'.$tahun.'" selected>'.$tahun.'</option>';
                                    }else { 
                                    echo'<option value="'.$tahun.'">'.$tahun.'</option>'; 
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
            <div class="load-pelanggaran postList s-widodo.com"></div>
        </div>
    </div>

    
    <!-- Modal Add  -->
    <div class="modal fade modalbox modal-add s-widodo.com" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-md modal-dialog-centered s-widodo.com" role="document">
            <div class="modal-content s-widodo.com">
            <form class="form-add s-widodo.com" role="form" method="post" action="javascript:;" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" class="d-none id s-widodo.com" name="id" value="" readonly required>
                <div class="modal-header s-widodo.com">
                    <h5 class="modal-title s-widodo.com"></h5>
                    <button type="button" class="close s-widodo.com" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body s-widodo.com">
                    <div class="form-group s-widodo.com">
                        <label class="form-control-label s-widodo.com">Kelas</label>
                        <select class="form-control kelas" name="kelas" required>
                            <option value="">Pilih Kelas</option>';
                            $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                            $result_kelas = $connection->query($query_kelas);
                            while ($data_kelas = $result_kelas->fetch_assoc()) {
                                echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                            }
                            echo'
                        </select>
                    </div>

                    <div class="form-group s-widodo.com">
                        <label class="form-control-label s-widodo.com">Siswa</label>
                        <select class="form-control siswa" name="siswa" required>
                            <option value="">Pilih Siswa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jenis Pelanggaran</label>
                        <select class="form-control kategori" name="kategori" required>
                            <option value="">Pilih</option>';
                            $query_kategori = "SELECT * FROM kategori_pelanggaran ORDER BY nama_kategori ASC";
                            $result_kategori = $connection->query($query_kategori);
                            while ($data_kategori = $result_kategori->fetch_assoc()){
                                echo'<option value="'.$data_kategori['kategori_pelanggaran_id'].'">'.strip_tags($data_kategori['nama_kategori']).'</option>';
                            }
                        echo'
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Bentuk Pelanggaran</label>
                        <select class="form-control bentuk-pelanggaran" name="bentuk_pelanggaran" required>
                            <option value="">Pilih</option>
                        </select>
                    </div>

                    
                </div>
                <div class="modal-footer s-widodo.com">
                    <button type="submit" class="btn btn-primary btn-save s-widodo.com">Simpan</button>
                    <button type="button" class="btn btn-secondary btn-close s-widodo.com">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="btn-floating s-widodo.com">
        <button type="submit" class="btn btn-add btn-primary s-widodo.com"><span class="material-icons s-widodo.com">add_circle</span></button>
    </div>

</main>';
}?>