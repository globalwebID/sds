
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

function LoadDataSanksi() {
    var bulan = $('.bulan').val();
    var siswa = $('.siswa').val();
    $(".load-sanksi").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-sanksi").load("./module/sanksi-pelanggaran/sw-proses.php?action=data-sanksi&siswa=" + siswa + "&bulan=" + bulan + "");
}
LoadDataSanksi();

/** Pencarian */
$('.search').change(function () {
    LoadDataSanksi();
})

/** Loadmore Data pelanggaran */
$(document).on('click', '.load-more', function () {
    var id = $(this).attr("data-id");
    var bulan = $('.bulan').val();
    var siswa = $('.siswa').val();
    $('.show_more').hide();
    $.ajax({
        type: 'POST',
        url: './module/sanksi-pelanggaran/sw-proses.php?action=data-pelanggaran-load',
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


/** Dropdown Siswa */
$(".siswa").change(function () {
    var siswa = $(this).val();
    $.ajax({
        type: 'POST',
        url: './module/sanksi-pelanggaran/sw-proses.php?action=dropdown-siswa',
        data: { siswa: siswa },
        dataType: 'json',
        success: function (response) {
            $('.wali_murid').val(response.wali_murid);
            $('.ditujukan').val(response.nama_lengkap);

            let html = "";

            if (response.pelanggaran.length > 0) {
                response.pelanggaran.forEach(function (p) {
                    html += `<button type="button" class="btn btn-sm btn-outline-secondary rounded mb-1 pelanggaran-btn" style="padding: 2px 6px;font-size: 12px;">${p.bentuk_pelanggaran}</button>`;
                });
            } else {
                html = `Tidak ada pelanggaran`;
            }
            $('.data-pelanggaran-body').html(html);
        },
        error: function (response) {
            console.log(response.responseText);
        }
    });
});


$(document).on("click", ".pelanggaran-btn", function () {
    $(this).toggleClass("active");
    let textarea = $(".keterangan");
    let selected = [];

    // Ambil SEMUA pelanggaran yang aktif
    $(".pelanggaran-btn.active").each(function () {
        selected.push("- " + $(this).text());
    });

    // Gabungkan ke textarea
    textarea.val(selected.join("\n"));
});


$(".form-add").validate({
    rules: {
        field: {
            required: true
        },
    },
    messages: {
        field: {
            required: "Silahkan masukkan data sesuai inputan",
        },

    },
    submitHandler: submitForm_Add
});

/* handle form submit */
function submitForm_Add() {
    $.ajax({
        type: 'POST',
        url: './module/sanksi-pelanggaran/sw-proses.php?action=add',
        data: new FormData($(".form-add")[0]),
        processData: false,
        contentType: false,
        cache: false,
        beforeSend: function () {
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({ title: 'Berhasil!', text: 'Data Pelanggaran berhasil disimpan!', icon: 'success', timer: 2500, });
                $(".form-add").trigger("reset");
                setTimeout(function () {
                    window.location.href = 'sanksi-pelanggaran';
                }, 2500);
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error' });
            }
        }
    });
    return false;
}



$(".form-update").validate({
    rules: {
        field: {
            required: true
        },
    },
    messages: {
        field: {
            required: "Silahkan masukkan data sesuai inputan",
        },

    },
    submitHandler: submitForm_Update
});

/* handle form submit */
function submitForm_Update() {
    $.ajax({
        type: 'POST',
        url: './module/sanksi-pelanggaran/sw-proses.php?action=update',
        data: new FormData($(".form-update")[0]),
        processData: false,
        contentType: false,
        cache: false,
        beforeSend: function () {
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({ title: 'Berhasil!', text: 'Data Pelanggaran berhasil disimpan!', icon: 'success', timer: 2500, });
                $(".form-update").trigger("reset");
                setTimeout(function () {
                    window.location.href = 'sanksi-pelanggaran';
                }, 2500);
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error' });
            }
        }
    });
    return false;
}


$(document).on('click', '.btn-delete', function () {
    var id = $(this).attr("data-id");
    swal({
        text: "Anda yakin ingin menghapus data ini?",
        icon: "warning",
        buttons: {
            cancel: true,
            confirm: true,
        },
        value: "yes",
    })

        .then((value) => {
            if (value) {
                loading();
                $.ajax({
                    url: './module/sanksi-pelanggaran/sw-proses.php?action=delete',
                    type: 'POST',
                    data: { id: id },
                    success: function (data) {
                        if (data == 'success') {
                            swal({ title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 2500, });
                            LoadDataSanksi();
                        } else {
                            swal({ title: 'Gagal!', text: data, icon: 'error', timer: 2500, });

                        }
                    }
                });
            } else {
                return false;
            }
        });
});


$(document).on('click', '.more-expand-btn', function () {
    $('.more-expand').toggle();
});

/** Print Data*/
$('.btn-print').click(function (e) {
    var mulai = $('.mulai').val();
    var selesai = $('.selesai').val();
    var url = "../pegawai-print-pelanggaran?mulai=" + mulai + "&selesai=" + selesai + "";
    window.open(url, '_blank');
});