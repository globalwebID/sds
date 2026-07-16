

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
    var siswa = $('.siswa').val();
    var tanggal = $('.tanggal').val();
    $(".load-Histori").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-histori").load("./module/rekap-absen-siswa/sw-proses.php?action=data-histori&siswa="+siswa+"&tanggal="+tanggal+"");
}

LoadDataHistori();

/** Pencarian */
$('.siswa, .tanggal').change(function(){
    LoadDataHistori();
});


/** Loadmore Data Absensi */
$(document).on('click','.load-more',function(){
    var id = $(this).attr("data-id");
    var siswa = $('.siswa').val();
    var tanggal = $('.tanggal').val();
    $('.show_more').hide();
    $.ajax({
        type:'POST',
        url:'./module/rekap-absen-siswa/sw-proses.php?action=data-histori-load',
        data:{id:id,siswa:siswa,tanggal:tanggal},
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
        url  : './module/rekap-absen-siswa/sw-proses.php?action=get-data-update',
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
    var siswa = $('.siswa').val();
    var tanggal = $('.tanggal').val();
    var url = "../print-rekap-absen-siswa?siswa="+siswa+"&tanggal="+tanggal+"";
    window.open(url, '_blank');
});