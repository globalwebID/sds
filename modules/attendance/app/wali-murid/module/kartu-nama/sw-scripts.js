

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

/** Print Data */
$('.btn-print').click(function (e) {
    var url = "../wali-murid-print-idcard";
    window.open(url, '_blank');
});