
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
    var table;
    var modifikasi = $('.modifikasi').html();
    var hapus = $('.hapus').html();
    $(document).ready(function() {
        //datatables
        table = $('.datatable-user').DataTable({
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
            "processing": false, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": false,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 25,
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
                "url": "./sw-mod/pegawai/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                 }, 
            },

            fixedColumns: {
                leftColumns: 5,
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
}

    $("body").on("click", ".datepicker", function(){
        $(this).datepicker({
          format: 'dd-mm-yyyy',
          autoclose:true
        });
        $(this).datepicker("show");
    });


    /** Tambah User/Pegawai */
    $('.password').keypress(function( e ) {
        if(e.which === 32) 
        return false;
    });

    $(".toggle-password").click(function() {
        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
          input.attr("type", "text");
        } else {
          input.attr("type", "password");
        }
    });


$('.form-table').submit(function (e) {
    loading();
    e.preventDefault();
    $.ajax({
        url:"./sw-mod/pegawai/sw-proses.php?action=status",
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
                swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                loadData();
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }

        },
        complete: function () {
            $(".loading").hide();
        },
    });
});



    /** Add */
    $('.form-add').submit(function (e) {
            loading();
            e.preventDefault();
            $.ajax({
                url:"./sw-mod/pegawai/sw-proses.php?action=add",
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
                        setTimeout(function() {
                            window.location.href = "./pegawai";
                        }, 3000);
                    } else {
                        swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                    }
    
                },
                complete: function () {
                    $(".loading").hide();
                },
            });
      });


    $('.form-update').submit(function (e) {
        loading();
        e.preventDefault();
        $.ajax({
            url:"./sw-mod/pegawai/sw-proses.php?action=update",
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
                    setTimeout(function() {
                        window.location.href = "./pegawai";
                    }, 3000);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }

            },
            complete: function () {
                $(".loading").hide();
            },
        });
  });

    /** ------- Forgot ---------- */
    $(document).on('click', '.btn-forgot', function(){ 
        var id = $(this).attr("data-id");
        var name = $(this).attr("data-name");
          swal({
            title: "Resset Password!",
            text: "Anda yakin ingin meresset password?\r\nPassword akan diganti ke default : 123456",
            icon: "info",
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
                     url:'./sw-mod/pegawai/sw-proses.php?action=forgot',
                     type:'POST',    
                     data:{id:id},  
                    success:function(data){ 
                        if (data == 'success') {
                            swal({title: 'Berhasil!', text: 'Password berhasil diresset.!', icon: 'success', timer: 2500,});
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


$(document).on('click', '.btn-reset-qrcode', function(){ 
    var id = $(this).attr("data-id");
      swal({
        text: "Anda akan mereset QR Code. Apakah Anda yakin ingin melanjutkan?",
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
                 url:'./sw-mod/pegawai/sw-proses.php?action=reset-qrcode',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                       swal({title: 'Berhasil!', text: 'QRCODE berhasil diubah!', icon: 'success', timer: 3500,});
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



$(document).on('click', '.btn-import', function(){
    $('.modal-import').modal('show');
    $(".form-import").trigger("reset");
});

$('.form-import').submit(function (e) {
    e.preventDefault();
    loading();
        $.ajax({
            url:"./sw-mod/pegawai/sw-proses.php?action=import",
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
                    $(".form-import").trigger("reset");
                    $('.modal-import').modal('hide');
                    loadData();
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
  
            },
        });
  }); 


/** Hapus data User/pegawai */
$(document).on('click', '.btn-delete', function(){ 
    var id = $(this).attr("data-id");
    var name = $(this).attr("data-name");
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
                 url:'./sw-mod/pegawai/sw-proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 2500,});
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


$(document).on('click', '.btn-qrcode', function(){
    $('.modal-qrcode').modal('show');
});

$(document).on('click', '.btn-export', function(){
    var jabatan  = $('.result-jabatan').val();
    var url     = "./sw-mod/pegawai/print.php?jabatan=" + jabatan+"";
    window.open(url, '_blank');
});



