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


loadData();
function loadData(){
  var modifikasi = $('.modifikasi').html();
  var hapus = $('.hapus').html();
    var table;
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
          "fnDrawCallback": function () {
            $('.open-popup-link').magnificPopup({
            type: 'image',
            removalDelay: 300,
            mainClass: 'mfp-fade',
                gallery: {
                    enabled: true
                },
                zoom: {
                    enabled: true,
                    duration: 300,
                    easing: 'ease-in-out',
                    opener: function (openerElement) {
                        return openerElement.is('img') ? openerElement : openerElement.find('img');
                    }
                }
            });
        },
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": false,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 25,
           // "order": [[1, 'desc']],
            
            "aLengthMenu": [
                [25, 30, 50, -1],
                [25, 30, 50, "All"]
            ],
            language: {
              paginate: {
                previous: "<i class='fas fa-angle-left'>",
                next: "<i class='fas fa-angle-right'>"
              }
            },
            "ajax": {
                "url": "./sw-mod/id-card/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                 }, 
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });

}

/** Upload Drag and Drop */
 function readURL(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        $('.image-upload-wrap').hide();
        $('.file-upload-image').attr('src', e.target.result);
        $('.file-upload-content').show();
        //$('.image-title').html(input.files[0].name);
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      removeUpload();
    }
  }

  function removeUpload() {
    $('.file-upload-input').replaceWith($('.file-upload-input').clone());
    $('.file-upload-content').hide();
    $('.image-upload-wrap').show();
  }
  $('.image-upload-wrap').bind('dragover', function () {
      $('.image-upload-wrap').addClass('image-dropping');
  });
      $('.image-upload-wrap').bind('dragleave', function () {
      $('.image-upload-wrap').removeClass('image-dropping');
  });



/* -------- MODAL ADD */
$(document).on('click', '.btn-add', function(){
    $('.modal-add').modal('show');
    $('.modal-title').html('Tambah Kartu Nama baru');
    $(".form-add").trigger("reset");
    $('.id').val('');
    removeUpload();
});


/** Tambah data */
$('.form-add').submit(function (e) {
  e.preventDefault();
  loading();
      $.ajax({
          url:"./sw-mod/id-card/sw-proses.php?action=add",
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
                  swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                  $(".form-add").trigger("reset");
                  $('.modal-add').modal('hide');
                  loadData();
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                  loadData();
              }
          },
      });
}); 


$(document).on('click', '.btn-update', function(){
    var id = $(this).attr('data-id');
    $('.modal-add').modal('show');
    $('.modal-title').html('Ubah Kartu nama');
    $.ajax({
        type: 'POST',
        url  : './sw-mod/id-card/sw-proses.php?action=get-data-update',
        data: {id:id},
        dataType:'json',
        success: function(response) {
            $('.id').val(response.id);
            $('.nama').val(response.nama);
            $('.file-upload-image').attr('src','../sw-content/tema/'+response.foto+'');
            $('.image-upload-wrap').hide();
            $('.file-upload-content').show();
        }, error: function(response){
            console.log(response.responseText);
        }
    });
});


/* ------------- Set Active--------------*/
$(document).on('click', '.btn-active', function(){
  var id = $(this).attr("data-id");
  var active = $(".active"+id).attr("data-active");
  if(active == "Y"){
      var dataactive = "N";
  }else{
      var dataactive = "Y";
  }
    var dataString = 'id='+ id + '&active='+ dataactive;
  $.ajax({
      type: "POST",
      url: "./sw-mod/id-card/sw-proses.php?action=active",
      data: dataString,
      success: function (data) {
          if(active == "Y"){
              $(".active"+id).attr("data-active","N");
          }else{
              $(".active"+id).attr("data-active","Y");
          }

        if (data == 'success') {
              console.log('Successfully set active');
          }else{
              console.log(data);
          }
      }
  });
});

/** Hapus data Slider  */
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
                 url:'./sw-mod/id-card/sw-proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus.!', icon: 'success', timer: 2500,});
                        loadData();
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
    