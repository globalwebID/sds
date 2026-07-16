

function loading(){
    $('.btn-save').prop("disabled", true);
      // add spinner to button
      $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-save').prop("disabled", false);
      $('.btn-save').html('Simpan');
    }, 2000);
}


$(".datepicker-filter").datepicker({
    format: 'dd-mm-yyyy',
    //startDate: new Date(),
    autoclose: true
});


$(".datepicker").datepicker({
    format: 'dd-mm-yyyy',
    startDate: new Date(),
    autoclose: true
});


function LoadDataHistori(){
    var mulai = $('.mulai').val();
    var selesai = $('.selesai').val();
    $(".load-Histori").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-histori").load("./module/histori-absen/sw-proses.php?action=data-histori&mulai="+mulai+"&selesai="+selesai+"");
}

LoadDataHistori();

/** Pencarian */
$('.mulai').change(function(){
    LoadDataHistori();
});

$('.selesai').change(function(){
    LoadDataHistori();
})

/** Loadmore Data Absensi */
$(document).on('click','.load-more',function(){
    var id = $(this).attr("data-id");
    var mulai = $('.mulai').val();
    var selesai = $('.selesai').val();
    $('.show_more').hide();
    $.ajax({
        type:'POST',
        url:'./module/histori-absen/sw-proses.php?action=data-histori-load',
        data:{id:id,mulai:mulai,selesai:selesai},
        beforeSend:function(){
            $(".load-more").text("Loading...");
        },
        success:function(data){
            $('.show_more_main'+id).remove();
            $('.postList').append(data);
            $(".load-more").text("Show more");
        }
    });
});


$(document).on('click', '.btn-update', function(){
    var id = $(this).attr('data-id');
    $('.modal-add').modal('show');
    $('.modal-title').html('Tambah keterangan');
    $.ajax({
        type: 'POST',
        url  : './module/histori-absen/sw-proses.php?action=get-data-update',
        data: {id:id},
        dataType:'json',
        success: function(response) {
            $('.id').val(id);
            $('.keterangan').val(response.keterangan);
        }, error: function(response){
           console.log(response.responseText);
        }
    });
});

$(document).on('click', '.btn-close', function(){
    $('.modal-add').modal('hide');
    $(".form-absen").trigger("reset");
});


/** Update Keterangan Absen */
$(".form-absen").validate({
    // Specify validation rules
    rules: {
        field: {
            required: true
        },
        keterangan: {
            required: true,
            maxlength: 250
        },
    },

    // Specify validation error messages
    messages: {
        field: {
            required: "Silahkan masukkan data sesuai inputan",
        },
        
    },
    // in the "action" attribute of the form when valid
    submitHandler: submitForm_absen
  });

/* handle form submit */
function submitForm_absen() { 
    var data = $(".form-absen").serialize();
    $.ajax({    
        type : 'POST',
        url  : './module/histori-absen/sw-proses.php?action=update',
        data : data,
        cache: false,
        async: false,
        beforeSend: function() { 
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Keterangan Absen berhasil disimpan!', icon: 'success', timer: 1500,});
                LoadDataHistori();
                $('.modal-add').modal('hide');
                $(".form-absen").trigger("reset");
                $('.id').val('');
            } else {
                swal({title: 'Oops!', text: data, icon: 'error', timer: 1500,});
            }
        }
    });
    return false; 
}


function Loadmap() { 
    var username = $('.username').html();
    var latlot = $('.latlotin').html().trim();
    if(latlot !== '-') {
        var coords = latlot.split(',').map(Number);
        var lat = coords[0];
        var lon = coords[1];
        var map = L.map('map_in', {
            center: [lat, lon],
            zoom: 16,
            zoomControl: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 16
        }).addTo(map);

        L.marker([lat, lon]).addTo(map)
            .bindPopup(username)
            .openPopup();
    }

    /** Pulang */
    var latlotout = $('.latlotout').html().trim();
    if(latlotout !== '-') {
        var coordsout = latlotout.split(',').map(Number); // 🔧 Corrected
        var latout = coordsout[0];
        var lonout = coordsout[1];
        var map = L.map('map_out', {
            center: [latout, lonout],
            zoom: 16,
            zoomControl: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 16
        }).addTo(map);

        L.marker([latout, lonout]).addTo(map)
            .bindPopup(username)
            .openPopup();
    }
}


$('.btn-print').click(function (e) {
    var mulai = $('.mulai').val();
    var selesai = $('.selesai').val();
    var url = "../pegawai-print-absensi?mulai="+mulai+"&selesai="+selesai+"";
    window.open(url, '_blank');
});