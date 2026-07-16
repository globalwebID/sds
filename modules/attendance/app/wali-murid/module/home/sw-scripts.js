
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

    
    /** Login */
    $(".form-login").validate({
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
          password: {
            required: "Please provide a password",
            minlength: "Password anda paling sedikit berisi 6 karakter"
          },
          email: {
            required: "Silahkan masukkan alamat email anda",
            email: "Email seharusnya dalam format: @gmail.com"
          },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Login
      });

    /* handle form submit */
    function submitForm_Login() { 
        var data = $(".form-login").serialize();
        $.ajax({    
            type : 'POST',
            url  : './module/home/sw-proses.php?action=login',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Login berhasil.!', icon: 'success', timer: 1500,});
                    $(".form-login").trigger("reset");
                    setTimeout(function(){location.reload(); }, 1200);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }


    /* ------ Update Admin ------- */
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

    /* handle form submit */
    function submitForm_Update() { 
        var data = $(".form-update").serialize();
        $.ajax({    
            type : 'POST',
            url  : './sw-mod/admin/sw-proses.php?action=update',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                    $(".form-login").trigger("reset");
                    setTimeout(function(){history.back();}, 3000);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }

    /** ------- Forgot ---------- */
    $(document).on('click', '.btn-forgot', function(){ 
        var id = $(this).attr("data-id");
        var name = $(this).attr("data-name");
          swal({
            title: "Resset Password!",
            text: "Anda yakin ingin meresset password "+name+".?\r\nPassword baru: 123456",
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
                     url:'./sw-mod/admin/sw-proses.php?action=forgot',
                     type:'POST',    
                     data:{id:id},  
                    success:function(data){ 
                        if (data == 'success') {
                            swal({title: 'Berhasil!', text: 'Password berhasil direset.!', icon: 'success', timer: 2500,});
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



$(document).on('click', '.btn-shift', function(){
    $('.modal-shift').modal('show');
    $('.modal-title').html('Pilih Shift Kerja');
    $.ajax({    
        type : 'POST',
        url  : './module/home/sw-proses.php?action=daftar-shift',
        success: function (data) {
            $('.daftar-shift').html(data);
        }
    });
});

$('.daftar-shift').on('change', function () {
    var shift = $(this).val(); 
    $.ajax({    
        type : 'POST',
        url  : './module/home/sw-proses.php?action=pilih-shift',
        data:{jam_kerja_id:shift}, 
        beforeSend: function() { 
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                setTimeout(function(){
                    location.reload();
                }, 2500);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }
        }
    });
    return false;
});


$(document).on('click', '.btn-lokasi', function(){
    $('.modal-lokasi').modal('show');
    $('.modal-title').html('Lokasi Absen');
    $.ajax({    
        type : 'POST',
        url  : './module/home/sw-proses.php?action=daftar-lokasi',
        success: function (data) {
            $('.daftar-lokasi').html(data);
        }
    });
});


$('.daftar-lokasi').on('change', function () {
    var lokasi = $(this).val(); 
    $.ajax({    
        type : 'POST',
        url  : './module/home/sw-proses.php?action=pilih-lokasi',
        data:{lokasi:lokasi}, 
        success: function (data) {
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                setTimeout(function(){
                    location.reload();
                }, 2500);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
            }
        }
    });
    return false;
});

