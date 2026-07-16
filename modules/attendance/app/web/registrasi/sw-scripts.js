
$('.password').keypress(function( e ) {
    if(e.which === 32) 
    return false;
});

function loading(){
    $('.btn-reg').prop("disabled", true);
      // add spinner to button
      $('.btn-reg').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-reg').prop("disabled", false);
      $('.btn-reg').html('Mendaftar');
    }, 2000);
}

$(document).on('change', '#showPassword', function () {
    const passwordField = $('#password');
    if ($(this).is(':checked')) {
        passwordField.attr('type', 'text');  // Menampilkan password
    } else {
        passwordField.attr('type', 'password');  // Menyembunyikan password
    }
});

loadReg(1);
function loadReg(id){
    $(".load-form").html('<div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <p>Loading data...</p></div>');
    $(".load-form").load("./web/registrasi/sw-proses.php?action=form&id="+id+"");
    $(".nav-link").css("background", "");
    $(".nav-link"+id+"").css("background", "#CCCCCC");
}

   
$(".load-form").on("submit", ".form-siswa", function(e) {
        e.preventDefault();
        $.ajax({
          url:"./web/registrasi/sw-proses.php?action=add-siswa",
          type: "POST",
          data: new FormData(this),
          processData: false,
          contentType: false,
          cache: false,
          async: false,
          beforeSend: function() { 
              loading();
          },
          success: function (data) {
              if (data == 'success') {
                  swal({title: 'Berhasil!', text: 'Pendaftaran Akun berhasil disimpan!', icon: 'success', timer: 2500,});
                  loadSetting(1);
                  $(".form-siswa").trigger("reset");
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
              }
          },
        });
});


$(".load-form").on("submit", ".form-pegawai", function(e) {
        e.preventDefault();
        $.ajax({
          url:"./web/registrasi/sw-proses.php?action=add-pegawai",
          type: "POST",
          data: new FormData(this),
          processData: false,
          contentType: false,
          cache: false,
          async: false,
          beforeSend: function() { 
              loading();
          },
          success: function (data) {
              if (data == 'success') {
                  swal({title: 'Berhasil!', text: 'Pendaftaran Akun berhasil disimpan!', icon: 'success', timer: 2500,});
                  loadSetting(2);
                  $(".form-pegawai").trigger("reset");
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
              }
          },
        });
});


$(".load-form").on("submit", ".form-walimurid", function(e) {
        e.preventDefault();
        $.ajax({
          url:"./web/registrasi/sw-proses.php?action=add-walimurid",
          type: "POST",
          data: new FormData(this),
          processData: false,
          contentType: false,
          cache: false,
          async: false,
          beforeSend: function() { 
              loading();
          },
          success: function (data) {
              if (data == 'success') {
                  swal({title: 'Berhasil!', text: 'Pendaftaran Akun berhasil disimpan!', icon: 'success', timer: 2500,});
                  loadSetting(3);
                  $(".form-walimurid").trigger("reset");
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
              }
          },
        });
});



