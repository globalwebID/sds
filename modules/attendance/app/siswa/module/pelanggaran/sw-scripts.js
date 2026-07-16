function loading() {
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

function LoadDataPelanggaran() {
    var bulan = $('.bulan').val();
    var siswa = $('.siswa').val();
    $(".load-pelanggaran").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-pelanggaran").load("./module/pelanggaran/sw-proses.php?action=data-pelanggaran&siswa=" + siswa + "&bulan=" + bulan + "");
}
LoadDataPelanggaran();

/** Pencarian */
$('.search').change(function () {
    LoadDataPelanggaran();
})

/** Loadmore Data */
$(document).on('click', '.load-more', function () {
    var id = $(this).attr("data-id");
    var bulan = $('.bulan').val();
    var siswa = $('.siswa').val();
    $('.show_more').hide();
    $.ajax({
        type: 'POST',
        url: './module/pelanggaran/sw-proses.php?action=data-pelanggaran-load',
        data: { id: id, siswa: siswa, bulan: bulan },
        beforeSend: function () {
            $(".load-more").text("Loading...");
        },
        success: function (data) {
            $('.show_more_main' + id).remove();
            $('.postList').append(data);
            $(".load-more").text("Show more");
        }
    });
});

$(document).on('click', '.more-expand-btn', function () {
    $('.more-expand').toggle();
});

/** Print Data */
$('.btn-print').click(function (e) {
    var bulan = $('.bulan').val();
    var siswa = $('.siswa').val();
    var url = "../print-pelanggaran?siswa=" + siswa + "&bulan=" + bulan + "";
    window.open(url, '_blank');
});