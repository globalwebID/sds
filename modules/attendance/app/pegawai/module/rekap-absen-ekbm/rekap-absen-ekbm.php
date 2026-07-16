<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:./404');
}else{

$tanggal_awal = date('d-m-Y');
$tanggal = DateTime::createFromFormat('d-m-Y', $tanggal_awal);
$tanggal->modify('-6 days');

switch(@$_GET['op']){ 
default:
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">

                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-6 s-widodo.com">
                            <div class="form-group position-relative mb-1 mt-1 s-widodo.com">
                            <select class="form-control kelas" required>
                                <option value="">Pilih Kelas</option>';
                                $query_kelas = "SELECT nama_kelas FROM absensi_kelas ORDER BY nama_kelas ASC";
                                $result_kelas = $connection->query($query_kelas);
                                if($result_kelas->num_rows > 0) {
                                    while($data_kelas = $result_kelas->fetch_assoc()){
                                        echo'<option value="'.strip_tags($data_kelas['nama_kelas']??'-').'">'.strip_tags($data_kelas['nama_kelas']??'-').'</option>';
                                    }
                                }else{
                                    echo'<option value="">Data tidak ditemukan</option>';
                                }
                                echo'
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 s-widodo.com">
                            <div class="form-group position-relative mb-1 mt-1 s-widodo.com">
                            <div class="bottom-left s-widodo.com">
                                <span class="btn btn-sm btn-link text-secondary s-widodo.com"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" class="form-control tanggal s-widodo.com search" placeholder="Tanggal" value="'.tanggal_ind($date).'">
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    
        <div class="container mb-4 s-widodo.com">
            <div class="load-histori postList s-widodo.com">
            </div>
        </div>
    </div>

    <div class="modal fade modal-add s-widodo.com" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-sm modal-dialog-centered s-widodo.com" role="document">
            <div class="modal-content s-widodo.com">
            <form class="form-absen s-widodo.com" role="form" method="post" action="#" autocomplete="off">
                
                <div class="modal-header s-widodo.com">
                    <h5 class="modal-title s-widodo.com"></h5>
                    <button type="button" class="close s-widodo.com" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body s-widodo.com">
                    <input type="hidden" class="form-control id d-none s-widodo.com" name="id" readonly>
                    <div class="form-group s-widodo.com">
                        <label class="form-control-label s-widodo.com">Keterangan</label>
                        <select class="form-control keterangan" name="keterangan" required>
                            <option value="H">Hadir</option>
                            <option value="A">Alpha</option>
                            <option value="I">Ijin</option>
                            <option value="S">Sakit</option>
                            <option value="N">Belum Absen</option>
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
        <button type="submit" class="btn btn-warning text-white btn-print s-widodo.com"><span class="material-icons">print</span></button>
    </div>
</main>';
break;
}
}?>