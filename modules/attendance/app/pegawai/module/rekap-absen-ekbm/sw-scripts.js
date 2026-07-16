

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
    var kelas = $('.kelas').val();
    var tanggal = $('.tanggal').val();
    $(".load-Histori").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-histori").load("./module/rekap-absen-ekbm/sw-proses.php?action=data-histori&kelas="+kelas+"&tanggal="+tanggal+"");
}

LoadDataHistori();

/** Pencarian */
$('.kelas, .tanggal').change(function(){
    LoadDataHistori();
});


/** Loadmore Data Absensi */
$(document).on('click','.load-more',function(){
    var id = $(this).attr("data-id");
    var kelas = $('.kelas').val();
    var tanggal = $('.tanggal').val();
    $('.show_more').hide();
    $.ajax({
        type:'POST',
        url:'./module/rekap-absen-ekbm/sw-proses.php?action=data-histori-load',
        data:{id:id,kelas:kelas,tanggal:tanggal},
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
    $('.modal-title').html('Ubah keterangan');
    $.ajax({
        type: 'POST',
        url  : './module/rekap-absen-ekbm/sw-proses.php?action=get-data-update',
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
        url  : './module/rekap-absen-ekbm/sw-proses.php?action=update',
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

$('.btn-print').click(function (e) {
    var kelas = $('.kelas').val();
    var tanggal = $('.tanggal').val();
    var url = "../print-rekap-absen-ekbm?kelas="+kelas+"&tanggal="+tanggal+"";
    window.open(url, '_blank');
});