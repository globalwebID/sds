
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



$(document).on('click', '.btn-absen', function(){
    var jadwal_id   = $(this).attr("data-jadwal");
    var id          = $(this).attr("data-id");
    var keterangan  = $(this).attr("data-keterangan");
    $('.card-active' + id).removeClass('alert-info alert-danger alert-warning');
    $.ajax({
        type: "POST",
        url: "./module/e-kbm/sw-proses.php?action=add-absen",
        data:{jadwal_id:jadwal_id,siswa:id,keterangan:keterangan},
        success: function (data) {
          if (data == 'success') {
            if(keterangan =='H'){
              $('.card-active'+id+'').addClass('alert-info');
            }else if(keterangan =='A'){
              $('.card-active'+id+'').addClass('alert-danger');
            }else if(keterangan =='I'){
              $('.card-active'+id+'').addClass('alert-warning');
            }else if(keterangan =='S'){
              $('.card-active'+id+'').addClass('alert-warning');
            }
            console.log('id: '+id+', Keterangan: '+keterangan+'');
          }else{
            swal({title: 'Oops!', text: data, icon: 'error',});
          }
        }
    });
});