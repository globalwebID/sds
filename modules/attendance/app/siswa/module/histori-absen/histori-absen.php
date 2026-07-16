<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
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
            <div class="card s-widodo.com">
                <div class="card-body s-widodo.com">
                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                    <div class="col-md-6 s-widodo.com">
                            <div class="form-group float-label position-relative mb-1 s-widodo.com">
                            <div class="bottom-left s-widodo.com">
                                <span class="btn btn-sm btn-link text-secondary s-widodo.com"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" class="form-control mulai s-widodo.com search" placeholder="From" value="'.$tanggal->format('d-m-Y').'">
                        </div>

                    </div>

                    <div class="col-md-6 s-widodo.com">
                            <div class="form-group float-label position-relative mb-1 s-widodo.com">
                            <div class="bottom-left s-widodo.com">
                                <a href="#" class="btn btn-sm btn-link text-secondary btn-40 rounded text-mute s-widodo.com"><i class="material-icons">calendar_month</i></a>
                            </div>

                            <input type="text" class="form-control selesai s-widodo.com search" placeholder="To" value="'.tanggal_ind($date).'">
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
                        <textarea class="form-control keterangan s-widodo.com" name="keterangan" rows="3" required></textarea>
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
case 'detail':
if(!empty($_GET['id'])){
$id       = anti_injection(convert("decrypt",$_GET['id']));
$query  = "SELECT * FROM absen WHERE user_id='".htmlentities($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' AND absen_id='$id'";
$result = $connection->query($query);

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">';
        if($result->num_rows > 0){
            $data = $result->fetch_assoc();

            if($data['kehadiran'] == 'Hadir'){
                $kehadiran = '<button class="btn btn-sm btn-default rounded">'.strip_tags($data['kehadiran']??'-').'</button>';
            }else{
                $kehadiran = '<button class="btn btn-sm btn-warning rounded">'.strip_tags($data['kehadiran']??'-').'</button>';
            }

            echo'
            <div class="card mb-3">
                <div class="card-header bg-none">
                    <div class="row">
                        <div class="col">
                            <p class="text-secondary">CHECK IN</p>
                        </div>
                        <div class="col-auto align-self-center p-0">
                            '.$kehadiran.'
                        </div>
                    </div>
                </div>
                <div class="card-body position-relative">
                    <div class="media">
                        <div class="icon icon-100 mr-3 rounded">
                            <figure class="background">';
                            if(file_exists('../sw-content/absen/'.strip_tags($data['foto_in']??'-.jpg').'')){
                                echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/absen/'.$data['foto_in'].'')).'" height="40">';
                            }else{
                                echo'<img src="../sw-content/avatar/avatar.jpg" height="40">';
                            }
                            echo'
                            </figure>
                        </div>
                        <div class="media-body">
                            <h6 class="mb-1">'.tanggal_ind($data['tanggal']).'</h6>
                            <span class="username d-none">'.$data_user['nama_lengkap'].'</span>
                            <p class="text-secondary"><i class="far fa-clock"></i> '.$data['absen_in'].'<br>
                            <i class="fas fa-map-marker-alt"></i> <span class="latlotin">'.strip_tags($data['map_in']??'-').'</span><br>
                            <i class="fas fa-location-arrow"></i> '.strip_tags($data['radius']??'-').' meter</p>
                        </div>
                    </div>
                </div>';
                if($data['map_in'] !==NULL){
                echo'
                <div class="card-footer">
                    <div class="d-block rounded overflow-hidden">
                        <div id="map_in" class="h-150" style="width:100%"></div>
                    </div>
                </div>';
                }
                echo'
            </div>

           
            <div class="card mb-3">
                <div class="card-header bg-none">
                    <div class="row">
                        <div class="col">
                            <p class="text-secondary">CHECK OUT</p>
                        </div>
                        <div class="col-auto align-self-center p-0">
                            '.$kehadiran.'
                        </div>
                    </div>
                </div>
                <div class="card-body position-relative">
                    <div class="media">
                        <div class="icon icon-100 mr-3 rounded">
                            <figure class="background">';
                            if(file_exists('../sw-content/absen/'.strip_tags($data['foto_out']??'-.jpg').'')){
                                echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/absen/'.$data['foto_out'].'')).'" height="40">';
                            }else{
                                echo'<img src="../sw-content/avatar/avatar.jpg" height="40">';
                            }
                            echo'
                            </figure>
                        </div>
                        <div class="media-body">
                            <h6 class="mb-1">'.tanggal_ind($data['tanggal']).'</h6>
                            <p class="text-secondary"><i class="far fa-clock"></i> '.strip_tags($data['absen_out']??'-').'<br>
                            <i class="fas fa-map-marker-alt"></i> <span class="latlotout">'.strip_tags($data['map_out']??'-').'</span><br>
                            <i class="fas fa-location-arrow"></i> '.strip_tags($data['radius_out']??'-').' meter</p>
                        </div>
                    </div>
                </div>';
                if($data['map_out'] !==NULL){
                echo'
                <div class="card-footer">
                    <div class="d-block rounded overflow-hidden">
                        <div id="map_out" class="h-150" style="width:100%"></div>
                    </div>
                </div>';
                }
                echo'
            </div>
            
            <div class="card">
                <div class="card-body position-relative">
                    <p>Keterangan: '.strip_tags($data['keterangan']??'-').'</p>
                </div>
            </div>';
            }
            echo'
            <div class="text-center mt-3">
                <a href="'.$mod.'" class="btn btn-light rounded"><i class="fas fa-undo"></i> Kembli</a>
            </div>
        </div>  
    </div>
</main>';
}
break;
}
}?>