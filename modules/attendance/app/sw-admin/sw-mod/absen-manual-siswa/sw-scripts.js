
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


$(document).ready(function(){
    var debounceTimeout;
    var app = {
        check: function(){
            clearTimeout(debounceTimeout);  // reset timer setiap keyup
            debounceTimeout = setTimeout(function(){
                var nisnVal = $(".nisn").val();
                $.ajax({
                    url: "./sw-mod/absen-manual-siswa/sw-proses.php?action=cek-nisn",
                    method: "POST",
                    data: {nisnVal: nisnVal},
                    dataType: "json",
                    success: function(response){
                        if(response.status === "success"){
                            $(".siswa").val(response.data.nama_lengkap);
                            $(".result-kelas").val(response.data.kelas);
                            $(".result-output").html('<span class="text-primary">Siswa ditemukan</span>').fadeIn("slow");
                        } else {
                            $(".siswa, .result-kelas").val('');
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

loadData();
function loadData(){
    var table;
    var modifikasi = $('.modifikasi').html();
    var hapus   = $('.hapus').html();
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
            "scrollY": false,
            "scrollX": false,
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": true,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 10,
           // "order": [[1, 'desc']],
            
            "aLengthMenu": [
                [10, 30, 50, -1],
                [10, 30, 50, "All"]
            ],
            language: {
              paginate: {
                previous: "<i class='fas fa-angle-left'>",
                next: "<i class='fas fa-angle-right'>"
              }
            },
            "ajax": {
                "url": "./sw-mod/absen-manual-siswa/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                 },
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
}


$(document).ready(function getLocation() {
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


$(document).on('click', '.btn-reset', function(){
    $(".form-add").trigger("reset");
    $('.id').val('');
});

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
        url  : './sw-mod/absen-manual-siswa/sw-proses.php?action=add',
        data : data,
        cache: false,
        async: false,
        beforeSend: function() { 
            loading();
        },
        success: function (data) {
            const [status, message] = data.split("/");
            if (status == 'success') {
                swal({title: 'Berhasil!', text: message, icon: 'success', timer: 2500,});
                $(".form-add").trigger("reset");
                $('.id').val('');
                loadData();
            } else {
                swal({title: 'Oops!', text: message, icon: 'error', timer: 2500,});
                loadData();
            }
        }
    });
    return false; 
}


$(document).on('click', '.btn-absen', function(){ 
    var tipe = $(this).attr("data-tipe");
    if(tipe=='webcame'){
        //webcame();
         html5QrcodeScanner.render(onScanSuccess);
    }else{
         html5QrcodeScanner.clear();
    }
});

//function webcame(){
    let isScanned = !1;
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Code matched = ${decodedText}`, decodedResult);
        if (!isScanned) {
            isScanned = !0;
            var latitude = $(".latitude").val();
            var kehadiran = $(".kehadiran-webcame").val();
            var shutter = new Audio("../template/vendor/html5-qrcode/audio/beep.mp3");
            shutter.play().catch(function (error) {
                console.error("Gagal memutar audio:", error);
            });

            $.ajax({
                type: "POST",
                url: "./sw-mod/absen-manual-siswa/sw-proses.php?action=absen-webcame",
                data: { qrcode: decodedText,kehadiran:kehadiran, latitude: latitude },
                success: function (data) {
                    const [status, message] = data.split("/");
                    if (status == "success") {
                        swal({ title: "Berhasil!", text: message, icon: "success", timer: 2500 });
                        loadData();
                    } else {
                        swal({ title: "Oops!", text: message, icon: "error", timer: 2500 });
                        loadData();
                    }
                },
            });
            setTimeout(() => {
                isScanned = !1;
            }, 1000);
        }
    }
    const config = {
        fps: 20,
        qrbox: function (viewportWidth, viewportHeight) {
            const minEdge = Math.min(viewportWidth, viewportHeight);
            const size = Math.floor(minEdge * 0.7);
            return { width: size, height: size };
        },
        rememberLastUsedCamera: !0,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
    };
    const html5QrcodeScanner = new Html5QrcodeScanner("reader", config, !1);
   

//}