<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{
    
$notifikasi = "UPDATE notifikasi SET status='Y' WHERE tipe='admin' AND tujuan='pegawai' AND pegawai_id='" . mysqli_real_escape_string($connection, $data_user['pegawai_id']) . "'";
$connection->query($notifikasi);

$tanggal_awal = date('d-m-Y');
$tanggal = DateTime::createFromFormat('d-m-Y', $tanggal_awal);
$tanggal->modify('-6 days');

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">
                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-6 s-widodo.com">
                                <div class="form-group mt-1 position-relative mb-1 s-widodo.com">
                                <div class="bottom-left s-widodo.com">
                                    <span class="btn btn-sm btn-link text-secondary s-widodo.com"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="text" class="form-control mulai s-widodo.com search" placeholder="From" value="'.$tanggal->format('d-m-Y').'">
                            </div>

                        </div>

                        <div class="col-md-6 s-widodo.com">
                                <div class="form-group mt-1 position-relative mb-1 s-widodo.com">
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
            <div class="load-izin postList s-widodo.com"></div>
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
                    
                        <div class="row input-daterange datepicker align-items-center">
                            <div class="col">
                                <div class="form-group">
                                <label class="form-control-label">Mulai</label>
                                <input type="text"  class="form-control tanggal-mulai" name="tanggal" placeholder="Start date" value="'.tanggal_ind($date).'">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                <label class="form-control-label">Sampai</label>
                                <input type="text" class="form-control tanggal-selesai" name="tanggal_selesai" placeholder="End date"  value="'.tanggal_ind($date).'">
                                </div>
                            </div>
                        </div>

                        <div class="form-group s-widodo.com">
                            <label class="form-control-label s-widodo.com">Alasan</label>
                            <select name="alasan" class="form-control alasan" required>
                                <option value="">Pilih:</option>';
                                $query = "SELECT nama FROM lain_lain WHERE tipe='izin' ORDER BY nama ASC";
                                $result = mysqli_query($connection, $query);
                                while ($row = mysqli_fetch_assoc($result)){
                                    echo'
                                    <option value="'.$row['nama'].'">'.$row['nama'].'</option>';
                                }
                            echo'
                            </select>                        
                        </div>

                        <div class="form-group s-widodo.com">
                            <label class="form-control-label s-widodo.com">Keterangan</label>
                            <textarea class="form-control keterangan s-widodo.com" name="keterangan" rows="2" required></textarea>
                        </div>

                        <div class="form-group s-widodo.com">
                          <label class="s-widodo.com">Unggah Bukti Surat</label>
                          <div class="file-upload s-widodo.com">
                              <div class="image-upload-wrap s-widodo.com">
                                <input class="file-upload-input fileInput files s-widodo.com" type="file" name="foto" onchange="readURL(this);" accept="image/*" required>
                                  <div class="drag-text s-widodo.com">
                                    <i class="lni lni-cloud-upload s-widodo.com"></i>
                                    <h3 class="s-widodo.com">Drag and drop files here</h3>
                                  </div>
                              </div>
                                <div class="file-upload-content s-widodo.com">
                                  <img class="file-upload-image s-widodo.com" src="template/img/sw-small.jpg" alt="Upload" height="150">
                                    <div class="image-title-wrap s-widodo.com">
                                      <button type="button" onclick="removeUpload()" class="btn btn-danger btn-sm s-widodo.com"><i class="fas fa-undo s-widodo.com"></i> Ubah<span class="image-title s-widodo.com"></span></button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-danger s-widodo.com">Silahkan upload foto dengan format JPG,JPEG maksimal 5M</small>
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
            <button type="submit" class="btn btn-warning btn-print s-widodo.com text-white"><span class="material-icons s-widodo.com">print</span></button>
        </div>

</main>';
}?>