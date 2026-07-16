
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

$("body").on("click", ".datepicker", function(){
    $(this).datepicker({
        format: 'dd-mm-yyyy',
        autoclose:true
    });
    $(this).datepicker("show");
});

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
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": false,
            "bSort": true,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 25,
            "order": [],
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
                "url": "./sw-mod/wali-murid/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                 }, 
            },
 
            fixedColumns: {
                leftColumns:3,
            },
        
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
}


$(document).ready(function(){
    var debounceTimeout;
    var app = {
        check: function(){
            clearTimeout(debounceTimeout);  // reset timer setiap keyup
            debounceTimeout = setTimeout(function(){
                var nisnVal = $(".nisn").val();
                $.ajax({
                    url: "./sw-mod/wali-murid/sw-proses.php?action=cek-nisn",
                    method: "POST",
                    data: {nisnVal: nisnVal},
                    dataType: "json",
                    success: function(response){
                        if(response.status === "success"){
                            $(".nama_siswa").val(response.data.nama_lengkap);
                            $(".result-output").html('Siswa: <span class="text-primary">'+response.data.nama_lengkap+'</span>').fadeIn("slow");
                        } else {
                            $(".nama_siswa").val('');
                             $(".result-output").html('<span class="text-danger">'+response.message+'</span>').fadeIn("slow");
                        }
                    },
                    error: function(){
                        $(".nama_siswa").val('');
                        $(".result-output").html('<span class="text-danger">Terjadi kesalahan server</span>').fadeIn("slow");
                    }
                });
            }, 500);
        }
    }

    $(".nisn").keyup(app.check);
});

    /** Add */
    $('.form-add').submit(function (e) {
            loading();
            e.preventDefault();
            $.ajax({
                url:"./sw-mod/wali-murid/sw-proses.php?action=add",
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
                        swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 4000,});
                        $(".form-add").trigger("reset");
                        setTimeout(function() {
                            window.location.href = "./wali-murid";
                        }, 3000)
                    } else {
                        swal({title: 'Oops!', text: data, icon: 'error', timer: 4500,});
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
            url:"./sw-mod/wali-murid/sw-proses.php?action=update",
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
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 4500,});
                    $(".form-add").trigger("reset");
                    setTimeout(function() {
                        window.location.href = "./wali-murid";
                    }, 3000);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 4500,});
                }

            },
            complete: function () {
                $(".loading").hide();
            },
        });
  });

$(document).on('click', '.btn-forgot', function(){ 
    var id = $(this).attr("data-id");
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
                    url:'./sw-mod/wali-murid/sw-proses.php?action=forgot',
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


$(document).on('click', '.btn-import', function(){
    $('.modal-import').modal('show');
    $(".form-import").trigger("reset");
});

$('.form-import').submit(function (e) {
    e.preventDefault();
    loading();
    $.ajax({
        url:"./sw-mod/wali-murid/sw-proses.php?action=import",
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
                $(".form-import").trigger("reset");
                $('.modal-import').modal('hide');
                loadData();
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }
        },
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
                 url:'./sw-mod/wali-murid/sw-proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus.!', icon: 'success', timer: 3500,});
                        loadData();
                    } else {
                        swal({title: 'Gagal!', text: data, icon: 'error', timer:3500,});
                        
                    }
                 }  
            });  
       } else{  
        return false;
    }  
});
}); 
