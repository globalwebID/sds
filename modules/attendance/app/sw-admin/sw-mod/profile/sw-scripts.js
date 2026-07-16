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

loadUser(1);
function loadUser(id){
  $(".load-data").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading data...</p></div>');
  $(".load-data").load("sw-mod/profile/sw-proses.php?action=load-data&id="+id+"");
}

    /** Tambah User/Pegawai */
    $('.password').keypress(function( e ) {
        if(e.which === 32) 
        return false;
    });

    $(document).on('click', '.toggle-password', function(){ 
        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });


$(document.body).on('click', '.submitBtn', function(){
    $(".form-update").validate({
        // Specify validation rules
        rules: {
            field: {
                required: true
            },
            email: {
                required: true,
                email: true
            },

            telp: {
                required: true,
                number: true
            },
            alamat: {
                required: true,
                minlength: 10,
                maxlength: 150
            }
        },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
          email: {
            required: "Silahkan masukkan alamat email anda",
            email: "Email seharusnya dalam format: swidodo.com@gmail.com"
          },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Update
      });
    });
    /* handle form submit */
    function submitForm_Update() { 
        var data = $(".form-update").serialize();
        $.ajax({    
            type : 'POST',
            url  : './sw-mod/profile/sw-proses.php?action=update',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                    loadUser(1);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }



    /* ------ Update Password ------- */
    $(document.body).on('click', '.submitForgot', function(){
    $(".form-password").validate({
        // Specify validation rules
        rules: {
            field: {
                required: true
            },
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 6,
                maxlength: 15
            },
        },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
          email: {
            required: "Silahkan masukkan alamat email anda",
            email: "Email seharusnya dalam format: swidodo.com@gmail.com"
          },
          password: {
            required: "Please provide a password",
            minlength: "Password anda paling sedikit berisi 6 karakter"
          },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Password
      });
    });

    /* handle form submit */
    function submitForm_Password() { 
        var data = $(".form-password").serialize();
        $.ajax({    
            type : 'POST',
            url  : './sw-mod/profile/sw-proses.php?action=forgot',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Password berhasil disimpan.!', icon: 'success', timer: 2500,});
                    loadUser(2);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }




/** Update Avatar */
$(document).on('change','.foto',function(){
    var file_data = $('.foto').prop('files')[0];  
    var image_name = file_data.name;
    var image_extension = image_name.split('.').pop().toLowerCase();
  
      if(jQuery.inArray(image_extension,['gif','jpg','jpeg','png']) == -1){
          swal({title: 'Oops!', text: 'File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!', icon: 'error', timer: 2500,});
      }
  
    var form_data = new FormData();
    form_data.append("avatar",file_data);
    $.ajax({
      url:'./sw-mod/profile/sw-proses.php?action=avatar',
      method:'POST',
      data:form_data,
      contentType:false,
      cache:false,
      processData:false,
      success:function(data){
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Avatar berhasil disimpan.!', icon: 'success', timer: 1500,});
               setTimeout(function(){location.reload(); }, 1500);
               $('.foto').val('');
            } else {
                swal({title: 'Oops!', text: data, icon: 'error'});
                $('.foto').val('');
            }
      }
    });
  });