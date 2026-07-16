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


$('.timepicker').timepicker({
    showInputs: false,
    showMeridian: false,
    use24hours: true,
    format :'HH:mm'
})


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

loadData();
function loadData(){
    var table;
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
            "scrollY": false,
            "scrollX": true,
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": false,
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
                "url": "./sw-mod/kartu-invalid/sw-datatable.php",
                "type": "POST"
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });

}


setInterval(function(){
    loadData();
}, 5000);


/** Tambah */
$(".form-add").validate({
    // Specify validation rules
    rules: {
        field: {
            required: true
        },
    },

    // Specify validation error messages
    messages: {
        field: {
            required: "Silahkan masukkan data sesuai inputan",
        },
    },
    // in the "action" attribute of the form when valid
    submitHandler: submitForm_Add
    });

/* handle form submit */
function submitForm_Add() { 
    var data = $(".form-add").serialize();
    $.ajax({    
        type : 'POST',
        url  : './sw-mod/kartu-invalid/sw-proses.php?action=add-siswa',
        data : data,
        cache: false,
        async: false,
        beforeSend: function() { 
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                $(".form-add").trigger("reset");
                window.setTimeout(window.location.href = "./kartu-invalid",2500);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }
        }
    });
        return false; 
}


/** Tambah */
$(".form-add-guru").validate({
    // Specify validation rules
    rules: {
        field: {
            required: true
        },
    },

    // Specify validation error messages
    messages: {
        field: {
            required: "Silahkan masukkan data sesuai inputan",
        },
    },
    // in the "action" attribute of the form when valid
    submitHandler: submitForm_Add_Guru
    });

/* handle form submit */
function submitForm_Add_Guru() { 
    var data = $(".form-add-guru").serialize();
    $.ajax({    
        type : 'POST',
        url  : './sw-mod/kartu-invalid/sw-proses.php?action=add-guru',
        data : data,
        cache: false,
        async: false,
        beforeSend: function() { 
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                $(".form-add").trigger("reset");
                window.setTimeout(window.location.href = "./kartu-invalid",2500);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }
        }
    });
        return false; 
}

/** Hapus */
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
                 url:'./sw-mod/kartu-invalid/sw-proses.php?action=delete',
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
    