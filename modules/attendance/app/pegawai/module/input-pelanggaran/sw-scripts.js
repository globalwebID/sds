
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

function LoadDatapelanggaran() {
    var bulan = $('.bulan').val();
    var tahun = $('.tahun').val();
    var kelas = $('.kelas').val();
    var siswa = $('.siswa').val();
    $(".load-pelanggaran").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');

    $(".load-pelanggaran").load("./module/input-pelanggaran/sw-proses.php?action=data-pelanggaran&kelas=" + kelas + "&siswa=" + siswa + "&bulan=" + bulan + "&tahun=" + tahun + "");
}
LoadDatapelanggaran();

/** Pencarian */
$('.search').change(function () {
    LoadDatapelanggaran();
})

/** Loadmore Data pelanggaran */
$(document).on('click', '.load-more', function () {
    var id = $(this).attr("data-id");
    var mulai = $('.mulai').val();
    var selesai = $('.selesai').val();
    $('.show_more').hide();
    $.ajax({
        type: 'POST',
        url: './module/input-pelanggaran/sw-proses.php?action=data-pelanggaran-load',
        data: { id: id, mulai: mulai, selesai: selesai },
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


/** Dropdown */
$(".kelas").change(function () {
    var kelas = $(this).val();
    $.ajax({
        type: 'POST',
        url: './module/input-pelanggaran/sw-proses.php?action=dropdown-kelas',
        data: { kelas: kelas },
        cache: false,
        success: function (data) {
            $(".siswa").html(data);
        }
    });
});


$(".kategori").change(function () {
    var id = $(this).val();
    $.ajax({
        type: 'POST',
        url: './module/input-pelanggaran/sw-proses.php?action=dropdown-kategori',
        data: { id: id },
        cache: false,
        success: function (data) {
            $(".bentuk-pelanggaran").html(data);
        }
    });
});


$(document).on('click', '.btn-add', function () {
    $('.modal-add').modal('show');
    $('.modal-title').html('Tambah pelanggaran baru');
    $(".form-add").trigger("reset");
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
    var id = $('.id').val();
    if (id == '') {
        var action = './module/input-pelanggaran/sw-proses.php?action=add';
    } else {
        var action = './module/input-pelanggaran/sw-proses.php?action=update'
    }
    $.ajax({
        type: 'POST',
        url: action,
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
                $('.modal-add').modal('hide');
                LoadDatapelanggaran();
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error' });
            }
        }
    });
    return false;
}


/** Update */
$(document).on('click', '.btn-update', function () {
    var id = $(this).attr('data-id');
    $('.modal-add').modal('show');
    $('.modal-title').html('Ubah data pelanggaran');

    $.ajax({
        type: 'POST',
        url: './module/input-pelanggaran/sw-proses.php?action=get-data-update',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            $('.id').val(response.id);
            $('.kelas').val(response.kelas);
            $('.siswa').val(response.siswa);
            $('.jenis-pelanggaran').val(response.jenis_pelanggaran);
            $('.bentuk-pelanggaran').val(response.bentuk_pelanggaran);

            $(".kelas option").each(function () {
                if ($(this).val() === response.kelas) {
                    $(this).prop('selected', true);
                }
            });

            $(".kategori option").each(function () {
                if ($(this).val() === response.jenis_pelanggaran) {
                    $(this).prop('selected', true);
                }
            });

            /** Dropdown kelas */
            $.ajax({
                type: 'POST',
                url: './module/input-pelanggaran/sw-proses.php?action=dropdown-kelas',
                data: { kelas: response.kelas, user: response.siswa },
                cache: false,
                success: function (data) {
                    $(".siswa").html(data);
                }
            });

            /** Dropdown kategori */
            $.ajax({
                type: 'POST',
                url: './module/input-pelanggaran/sw-proses.php?action=dropdown-kategori',
                data: { id: response.jenis_pelanggaran, bentuk_pelanggaran: response.bentuk_pelanggaran },
                cache: false,
                success: function (data) {
                    $(".bentuk-pelanggaran").html(data);
                }
            });
        },
        error: function (response) {
            console.log(response.responseText);
        }
    });
});


$(document).on('click', '.btn-close', function () {
    $('.modal-add').modal('hide');
    $(".form-add").trigger("reset");
});



/** Delete pelanggaran */
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
                    url: './module/input-pelanggaran/sw-proses.php?action=delete',
                    type: 'POST',
                    data: { id: id },
                    success: function (data) {
                        if (data == 'success') {
                            swal({ title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 2500, });
                            LoadDatapelanggaran();
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
