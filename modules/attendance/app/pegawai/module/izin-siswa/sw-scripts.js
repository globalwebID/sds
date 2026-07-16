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

function LoadDataizin(){
    var siswa = $('.siswa').val();
    var tanggal = $('.tanggal').val();
    $(".load-izin").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-izin").load("./module/izin-siswa/sw-proses.php?action=data-izin&iwa="+siswa+"&tanggal="+tanggal+"");
}
LoadDataizin();

/** Pencarian */
$('.search, .siswa').change(function(){
    LoadDataizin();
})

/** Loadmore Data izin */
$(document).on('click','.load-more',function(){
    var id = $(this).attr("data-id");
    var siswa = $('.siswa').val();
    var tanggal = $('.tanggal').val();
    $('.show_more').hide();
    $.ajax({
        type:'POST',
        url:'./module/izin-siswa/sw-proses.php?action=data-izin-load',
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



 /* ------------- Set Status  --------------*/
 $(document).on('click', '.btn-status', function(){
    var id = $(this).attr("data-id");
    var status = $(this).attr("data-status");
    if(status=='Y'){
        var url ="./module/izin-siswa/sw-proses.php?action=setujui";
    }else{
        var url ="./module/izin-siswa/sw-proses.php?action=tolak";
    }
    $.ajax({
        type: "POST",
        url: url,
        data:{id:id,status:status},
        success:function(data){ 
            if (data == 'success') {
                swal({title: 'Berhasil!', text: 'Data berhasil simpan!', icon: 'success', timer: 2500,});
                LoadDataizin();
            } else {
                swal({title: 'Gagal!', text: data, icon: 'error', timer:2500,});
            }
        }
    });
});


/** Print Data Izin */
$('.btn-print').click(function (e) {
    var siswa = $('.siswa').val();
    var tanggal = $('.tanggal').val();
    var url     = "../print-izin-siswa?siswa="+siswa+"&taggal="+tanggal+"";
    window.open(url, '_blank');
});