
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


/** Module*/
loadData();
function loadData(){
    var table;
    var tipe = $('.tipe').val();
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
            "scrollY": false,
            "scrollX": false,
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
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
                "url": "./sw-mod/scan-absen/sw-datatable.php",
                "type": "POST",
                "data": {
                    tipe:tipe,
                 },
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
    console.log(tipe);
}


/* -------- MODAL SCAN ABSEN */
$(document).on('click', '.btn-add-in', function(){
    $('.modal-add').modal('show');
    $('.modal-title').html('Scan Absensi Masuk');
    $(".form-add").trigger("reset");
    $('.tipe').val('masuk');
    $('.qrcode').focus();
});

$(document).on('click', '.btn-add-out', function(){
    $('.modal-add').modal('show');
    $('.modal-title').html('Scan Absensi Keluar');
    $(".form-add").trigger("reset");
    $('.tipe').val('keluar');
    $('.qrcode').focus();
});

  
$('.qrcode').focus();
$('body').click(function(){
    $('.qrcode').focus();
 });



 $(document).ready(function getLocation() {
    //result = document.getElementById("latitude");
   // 
    if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
    } else {
        swal({title: 'Oops!', text:'Maaf, browser Anda tidak mendukung geolokasi HTML5.', icon: 'error', timer: 3000,});
    }
});

// Define callback function for successful attempt
function successCallback(position) {
   var latitude =""+ position.coords.latitude + ","+position.coords.longitude + "";
    $('.latitude').val(latitude);
}

// Define callback function for failed attempt
function errorCallback(error) {
    if(error.code == 1) {
        swal({title: 'Oops!', text:'Anda telah memutuskan untuk tidak membagikan lokasi Anda, tetapi tidak apa-apa. Kami tidak akan meminta Anda lagi.', icon: 'error', timer: 3000,});
    } else if(error.code == 2) {
        swal({title: 'Oops!', text:'Jaringan tidak aktif atau layanan penentuan posisi tidak dapat dijangkau.', icon: 'error', timer: 3000,});
    } else if(error.code == 3) {
        swal({title: 'Oops!', text:'Waktu percobaan habis sebelum bisa mendapatkan data lokasi.', icon: 'error', timer: 3000,});
    } else {
        swal({title: 'Oops!', text:'Waktu percobaan habis sebelum bisa mendapatkan data lokasi.', icon: 'error', timer: 3000,});
    }
};


$(document).on('keyup', '.qrcode', function(){
    var qrcode = $('.qrcode').val();
    panjang = qrcode.length;
    setTimeout(function(){
        if(panjang > 15){
            $('.btn-submit').trigger('click');
            var shutter = new Audio();
            shutter.src = navigator.userAgent.match(/Firefox/) ? '../module/sw-assets/js/plugins/html5-qrcode/audio/beep.np3' : '../module/sw-assets/js/plugins/html5-qrcode/audio/beep.mp3';
        }
    }, 300);
 });



$(document).on('submit', '.form-absen', function(e){ 
    e.preventDefault();
    var tipe = $(".tipe").val();

    if(tipe =='masuk') {
        var url = './sw-mod/scan-absen/sw-proses.php?action=absen-in';
    }else if(tipe =='keluar'){
        var url = './sw-mod/scan-absen/sw-proses.php?action=absen-out';
    }else{
        var url = '';
        swal({title: 'Error!', text:'Url tidak ditemukan, silahakan ulangi!', icon: 'success', timer: 2500,});
    }

    $.ajax({
      url: url,
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
            var results = data.split("/");
            var alert = results[0];
            var pesan = results[1];
        if (alert == 'success') {
              swal({title: 'Berhasil!', text:pesan, icon: 'success', timer: 2500,});
              loadData();
                setTimeout(function(){
                    $('.qrcode').focus();
                    $('.qrcode').val("").focus();
                }, 500);
          } else {
              swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                setTimeout(function(){
                    $('.qrcode').focus();
                    $('.qrcode').val("").focus();
                }, 500);
          }
      },
    });
});
