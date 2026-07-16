
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
});



  /** Tambah Jam */
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
        url  : './sw-mod/jam-sekolah/sw-proses.php?action=add',
        data : data,
        cache: false,
        async: false,
        beforeSend: function() { 
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                setTimeout(function () {
                    location.reload();
                }, 2500);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }
        }
    });
 return false; 
}
