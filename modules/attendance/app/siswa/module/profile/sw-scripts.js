
function loading(){
    $('.btn-save').prop("disabled", true);
      // add spinner to button
      $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-save').prop("disabled", false);
      $('.btn-save').html('Simpan'
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

    /** Update Profile */
    $(".form-profile").validate({
        // Specify validation rules
        rules: {
            field: {
                required: true
            },
            telp: {
                required: true,
                number: true
            },
            alamat: {
                required: true,
                minlength: 10,
                maxlength: 150
            },
        },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
            
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Profile
      });

    /* handle form submit */
    function submitForm_Profile() { 
        var data = $(".form-profile").serialize();
        $.ajax({    
            type : 'POST',
            url  : './module/profile/sw-proses.php?action=update',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Profil berhasil disimpan!', icon: 'success', timer: 2500,});
                    //$(".form-profile").trigger("reset");
                    //setTimeout(function () {history.back();}, 1500);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
        return false; 
    }


    