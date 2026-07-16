
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

loadSetting(1);
function loadSetting(id){
    $(".load-form").html('<div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <p>Loading data...</p></div>');
    $(".load-form").load("sw-mod/pengaturan/sw-form.php?id="+id+"");
}


/** Setting Web */
$(".load-form").on("submit", ".form-setting", function(e) {
        e.preventDefault();
        $.ajax({
          url:"sw-mod/pengaturan/sw-proses.php?action=setting-web",
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
                  swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                  loadSetting(1);
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
              }
          },
        });
});


$(document).on('change','.logo, .favicon, .kop, .ttd, .stempel',function(e){
    var form_data = new FormData();

    // Handle logo upload
    if ($(this).hasClass('logo')) {
        var file_data = $('.logo').prop('files')[0];  
        var image_name = file_data.name;
        var image_extension = image_name.split('.').pop().toLowerCase();
        if(jQuery.inArray(image_extension, ['gif','jpg','jpeg','png']) == -1){
            swal({title: 'Oops!', text: 'File Logo harus berformat gif, jpg, jpeg, atau png!', icon: 'error', timer: 2000});
            return;
        }
        form_data.append("logo", file_data);
    }

    // Handle favicon upload
    if ($(this).hasClass('favicon')) {
        var file_data = $('.favicon').prop('files')[0];  
        var image_name = file_data.name;
        var image_extension = image_name.split('.').pop().toLowerCase();
        if(jQuery.inArray(image_extension, ['gif','jpg','jpeg','png']) == -1){
            swal({title: 'Oops!', text: 'File Favicon harus berformat gif, jpg, jpeg, atau png!', icon: 'error', timer: 2000});
            return;
        }
        form_data.append("favicon", file_data);
    }

    // Handle kop upload
    if ($(this).hasClass('kop')) {
        var file_data = $('.kop').prop('files')[0];  
        var image_name = file_data.name;
        var image_extension = image_name.split('.').pop().toLowerCase();
        if(jQuery.inArray(image_extension, ['gif','jpg','jpeg','png']) == -1){
            swal({title: 'Oops!', text: 'File Kop harus berformat gif, jpg, jpeg, atau png!', icon: 'error', timer: 2000});
            return;
        }
        form_data.append("kop", file_data);
    }

    // Handle TTD (Tanda Tangan Digital) upload
    if ($(this).hasClass('ttd')) {
        var file_data = $('.ttd').prop('files')[0];  
        var image_name = file_data.name;
        var image_extension = image_name.split('.').pop().toLowerCase();
        if(jQuery.inArray(image_extension, ['gif','jpg','jpeg','png']) == -1){
            swal({title: 'Oops!', text: 'File TTD harus berformat gif, jpg, jpeg, atau png!', icon: 'error', timer: 2000});
            return;
        }
        form_data.append("ttd", file_data);
    }

    if ($(this).hasClass('stempel')) {
        var file_data = $('.stempel').prop('files')[0];  
        var image_name = file_data.name;
        var image_extension = image_name.split('.').pop().toLowerCase();
        if(jQuery.inArray(image_extension, ['gif','jpg','jpeg','png']) == -1){
            swal({title: 'Oops!', text: 'File Stempel harus berformat gif, jpg, jpeg, atau png!', icon: 'error', timer: 2000});
            return;
        }
        form_data.append("stempel", file_data);
    }

    // Send all files together using AJAX
    $.ajax({
        url: 'sw-mod/pengaturan/sw-proses.php?action=upload-files',
        method: 'POST',
        data: form_data,
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function(){
            loading();
        },
        success: function(data){
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'File berhasil diunggah!', icon: 'success', timer: 1500});
                loadSetting(1);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 2000});
                 loadSetting(1)
            }
        }
    });
});

$(".load-form").on("submit", ".form-profile", function(e) {
  e.preventDefault();
  $.ajax({
    url:"sw-mod/pengaturan/sw-proses.php?action=setting-profile",
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
            swal({title: 'Berhasil!', text: 'Pengaturan berhasil disimpan!', icon: 'success', timer: 2500,});
            loadSetting(5);
        } else {
            swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
        }
    },
  });
});

/** Setting Absensi */
$(".load-form").on("submit", ".form-setting-absensi", function(e) {
  e.preventDefault();
  $.ajax({
    url:"sw-mod/pengaturan/sw-proses.php?action=setting-absensi",
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
            swal({title: 'Berhasil!', text: 'Pengaturan Absen berhasil disimpan.!', icon: 'success', timer: 2500,});
            loadSetting(2);
        } else {
            swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
        }
    },
  });
});



/** Setting Server */
$(".load-form").on("submit", ".form-setting-server", function(e) {
  e.preventDefault();
  $.ajax({
    url:"sw-mod/pengaturan/sw-proses.php?action=setting-server",
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
            swal({title: 'Berhasil!', text: 'Pengaturan Server berhasil disimpan.!', icon: 'success', timer: 2500,});
            loadSetting(3);
        } else {
            swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
        }
    },
  });
});

/** Setting APi WhatsApp */
$(".load-form").on("submit", ".form-setting-whatsapp", function(e) {
  e.preventDefault();
  $.ajax({
    url:"sw-mod/pengaturan/sw-proses.php?action=setting-whatsapp",
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
            swal({title: 'Berhasil!', text: 'Pengaturan Api WHatsApp berhasil disimpan.!', icon: 'success', timer: 2500,});
            loadSetting(4);
        } else {
            swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
        }
    },
  });
});



$(document).on('click', '.btn-backup', function(){
    $('.modal-add').modal('show');
    $('.modal-title').html('Backup Database');
    $('.password').val('');
});


$(document).on('click', '.btn-backup-download', function(){
  var password = $('.password').val();
  $.ajax({
        url: 'sw-mod/pengaturan/sw-proses.php?action=backup-database',
        method: 'POST',
        data :{password:password},
        dataType: 'json',
        success: function (res) {
          if (res.status === 'success') {
            $('.status').html('Backup berhasil. Mengunduh file...');
            $('.password').val('');
            $('.modal-add').modal('hide');
            // Buat link download otomatis
            const link = document.createElement('a');
            link.href = 'sw-mod/pengaturan/sw-proses.php?action=download&file=' + encodeURIComponent(res.filename);
            link.download = res.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          } else {
            $('.status').html('<span style="color:red;">' + res.message + '</span>');
          }
        },
        error: function () {
          $('.status').html('<span style="color:red;">Terjadi kesalahan AJAX.</span>');
        }
  });
});

/** Backup Folder */
$(".load-form").on("submit", ".backupForm", function(e) {

    e.preventDefault();
    let selected = [];
    $('input[name="folders[]"]:checked').each(function() {
      selected.push($(this).val());
    });

    if(selected.length === 0) {
      $('.status-backup').html('<p style="color:red;">Pilih minimal satu folder untuk backup.</p>');
      return;
    }

    $('.status-backup').html('<p>Memproses backup, mohon tunggu...</p>');

    $.ajax({
      url: 'sw-mod/pengaturan/sw-proses.php?action=backup-folder',
      method: 'POST',
      data: { folders: selected },
      xhrFields: {
        responseType: 'blob' // agar menerima file binary zip
      },
      success: function(data, status, xhr) {
        let disposition = xhr.getResponseHeader('Content-Disposition');
        let filename = "backup.zip";
        if (disposition && disposition.indexOf('attachment') !== -1) {
          let matches = /filename="?([^"]+)"?/.exec(disposition);
          if (matches != null && matches[1]) filename = matches[1];
        }
        // Buat link download file
        let blob = new Blob([data], { type: 'application/zip' });
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        $('.status-backup').html('<p style="color:green;">Backup selesai dan file telah diunduh.</p>');
      },
      error: function(xhr) {
        let reader = new FileReader();
        reader.onload = function() {
          let text = reader.result;
          $('.status-backup').html('<p style="color:red;">Error: ' + text + '</p>');
        };
        reader.readAsText(xhr.response);
      }
    });
  });