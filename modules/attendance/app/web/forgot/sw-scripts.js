'use strict';

    /** Tambah User/Pegawai */
    $('.password').keypress(function( e ) {
        if(e.which === 32) 
        return false;
    });

    

    /** Login */

    $(".form-forgot").validate({
        // Specify validation rules
        rules: {
            field: {
                required: true
            },
            email: {
                required: true,
                email: true
            },

        },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
        
          email: {
            required: "Silahkan masukkan alamat email anda",
            email: "Email seharusnya dalam format: @gmail.com"
          },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Forgot
      });

    /* handle form submit */
    function submitForm_Forgot() { 
        var data = $(".form-forgot").serialize();
        $.ajax({    
            type : 'POST',
            url  : './home-page/forgot/sw-proses.php?action=forgot',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Password baru Anda berhasil kami resset, silahkan cek email!', icon: 'success', timer: 1500,});
                    $(".form-forgot").trigger("reset");
                    //setTimeout(function(){location.reload(); }, 1200);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }



    