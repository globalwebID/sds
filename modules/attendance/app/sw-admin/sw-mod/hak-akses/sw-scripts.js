'use strict';
function loading(){
    $('.btn-save').prop("disabled", true);
      // add spinner to button
      $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-save').prop("disabled", false);
      $('.btn-save').html('<i class="far fa-save"></i> Simpan'
      );
    }, 2000);
}

function loadData(id){
  $(".load-data").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading data...</p></div>');
  $(".load-data").load("sw-mod/hak-akses/sw-proses.php?action=data&id="+id+"");
  $(".level").val(id);
}
loadData(1);


$(document).on('click', '.btn-tab', function(){
  var name = $(this).attr("data-name");
  $('.title-header').html(name);
});



/** Tambah data */
$(document).on('click', '.btn-add', function(){
  $('.modal-add').modal('show');
  $('.modal-title').html('Tambah hak akses');
});


/** Add Hak akses */
$('.form-add').submit(function (e) {
  var id = $('.level').val();
  e.preventDefault();
  loading();
      $.ajax({
          url:"./sw-mod/hak-akses/sw-proses.php?action=add",
          type: "POST",
          data: new FormData(this),
          processData: false,
          contentType: false,
          cache: false,
          async: false,
          beforeSend: function () { 
            loading();
          },
          success: function (data) {
              if (data == 'success') {
                  swal({title: 'Berhasil!', text: 'Hak akses berhasil disimpan.!', icon: 'success', timer: 1500,});
                  loadData(id);
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 1500,});
              }

          },
          complete: function () {
              
          },
      });
}); 


/* ------------- Set Active Lihat--------------*/
$(document).on('click', '.lihat', function(){
var id = $(this).attr("data-id");
var active = $(this).attr("data-active");

 var dataString = 'id='+ id + '&active='+ active;
$.ajax({
    type: "POST",
    url: "./sw-mod/hak-akses/sw-proses.php?action=lihat",
    data: dataString,
    success: function (data) {
      if (data == 'success') {
            console.log('Successfully set active');
        }else{
           console.log(data);
        }
    }
});
});


/* ------------- Set Active modifikasi --------------*/
$(document).on('click', '.modifikasi', function(){
var id = $(this).attr("data-id");
var active = $(this).attr("data-active");

 var dataString = 'id='+ id + '&active='+ active;
$.ajax({
    type: "POST",
    url: "./sw-mod/hak-akses/sw-proses.php?action=modifikasi",
    data: dataString,
    success: function (data) {
      if (data == 'success') {
            console.log(data);
        }else{
           console.log(data);
        }
    }
});
});

/* ------------- Set Active Hapus --------------*/
$(document).on('click', '.hapus', function(){
var id = $(this).attr("data-id");
var active = $(this).attr("data-active");

 var dataString = 'id='+ id + '&active='+ active;
$.ajax({
    type: "POST",
    url: "./sw-mod/hak-akses/sw-proses.php?action=hapus",
    data: dataString,
    success: function (data) {
      if (data == 'success') {
            console.log('Successfully set active');
        }else{
           console.log(data);
        }
    }
});
});
    
    


$(document).on('click', '.btn-delete', function(){ 
    var id = $(this).attr("data-id");
      swal({
        text: "Anda yakin ingin menghapus data ini?",
        icon: "warning",
          buttons: {
            cancel: true,
            confirm: true,
          },
        value: "yes",
      })

      .then((value) => {
        if(value) {
            loading();
            $.ajax({  
                 url:'./sw-mod/hak-akses/sw-proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 2500,});
                        loadDataKelas();
                        $('.id').val('');
                    } else {
                        swal({title: 'Gagal!', text: data, icon: 'error', timer:2500,});
                        
                    }
                 }  
            });  
       } else{  
        return false;
        }  
    });
}); 