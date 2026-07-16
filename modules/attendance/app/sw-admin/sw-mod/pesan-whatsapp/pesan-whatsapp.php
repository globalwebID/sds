<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  $query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='17' AND level_id='$current_user[level]'";
  $result_role = $connection->query($query_role);
  if($result_role->num_rows > 0){
    $data_role = $result_role->fetch_assoc();

  switch(@$_GET['op']){ 
    default:
echo'

<!-- Header -->
<div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white d-inline-block mb-0">Template</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Template Pesan WhatsApp</li>
                </ol>
              </nav>
            </div>
            
          </div>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--6">
      <!-- Table -->
      <div class="row">
        <div class="col">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mt-2 mb-0 text-left float-left">Template Pesan WhatsApp</h3>
            </div>';

            if($data_role['lihat']=='Y'){
              $query_pesan = "SELECT * FROM whatsapp_pesan WHERE whatsapp_pesan_id=1";
              $result_pesan = $connection->query($query_pesan);
              if($result_pesan->num_rows > 0){
              $data_pesan = $result_pesan->fetch_assoc();
              echo'
              <div class="card-body">
              <form class="form-add" role="form" method="post" action="javascript:void(0)" autocomplete="off">
                <fieldset class="row">
                  <div class="col-lg-8">

                    <div class="form-group">
                        <label>Salam Pembukaan</label>
                        <input type="text" class="form-control" name="pembukaan" value="'.strip_tags($data_pesan['pembukaan']).'" required>
                    </div>

                    <div class="form-group">
                        <label>Pesan Masuk</label>
                        <textarea class="form-control" rows="3" name="pesan_masuk" required>'.strip_tags($data_pesan['pesan_masuk']).'</textarea>
                    </div>
                    <hr>
                    <div class="">
                      <div class="form-group">
                          <label>Salam Penutupan</label>
                          <input type="text" class="form-control" name="penutupan" value="'.strip_tags($data_pesan['penutupan']).'" required>
                      </div>

                      <div class="form-group">
                          <label>Pesan Pulang</label>
                          <textarea class="form-control" rows="3" name="pesan_pulang" required>'.strip_tags($data_pesan['pesan_pulang']).'</textarea>
                      </div>

              
                    <hr>
                    <div class="form-group">
                      <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                      <button class="btn btn-secondary" type="reset"><i class="fas fa-undo"></i> Reset</button>
                    </div>


                    </div>
                      <!-- End col-lg-6 -->
                  </fieldset>
                </form>';
              }
              echo'
              </div>';
            }else{
              hak_akses();
            }
            echo'
            </div>
          </div>
        </div>
      </div>';


      if($data_role['modifikasi']=='Y'){
      echo'

      <!-- Modal ADD -->
      <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <form class="form-add" role="form" action="#">
                    <input type="hidden" class="form-control id d-none" name="id" readonly>
                    <div class="modal-header">
                        <h5 class="modal-title">Modal Title <span class="modal-title-name text-info"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Pesan WhatsApp</label>
                            <input type="text" class="form-control Pesan WhatsApp-nama" name="nama_Pesan WhatsApp" required>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn  btn-primary btn-save">Simpan</button>
                        <button type="button" class="btn  btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>';
      }
  break;
  }
  }else{
    theme_404();
  }
}?>

  