

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

$('.btn-print').click(function (e) {
    var mulai = $('.mulai').val();
    var selesai = $('.selesai').val();
    var url = "../wali-murid-print-absensi?mulai="+mulai+"&selesai="+selesai+"";
    window.open(url, '_blank');
});