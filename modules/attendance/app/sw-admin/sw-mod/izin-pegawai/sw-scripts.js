
function loading() {
    $('.btn-save').prop("disabled", true);
    // add spinner to button
    $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
    );
    window.setTimeout(function () {
        $('.btn-save').prop("disabled", false);
        $('.btn-save').html('<i class="far fa-save"></i> Simpan'
        );
    }, 2000);
}


$('.timepicker').timepicker({
    showInputs: false,
    showMeridian: false,
    use24hours: true,
    format: 'HH:mm'
});

$("body").on("click", ".datepicker", function () {
    $(this).datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true
    });
    $(this).datepicker("show");
});


/** Upload Drag and Drop */
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('.image-upload-wrap').hide();
            $('.file-upload-image').attr('src', e.target.result);
            $('.file-upload-content').show();
            //$('.image-title').html(input.files[0].name);
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        removeUpload();
    }
}

function removeUpload() {
    $('.file-upload-input').replaceWith($('.file-upload-input').clone());
    $('.file-upload-content').hide();
    $('.image-upload-wrap').show();
    $('.fileInput').val('');
}
$('.image-upload-wrap').bind('dragover', function () {
    $('.image-upload-wrap').addClass('image-dropping');
});
$('.image-upload-wrap').bind('dragleave', function () {
    $('.image-upload-wrap').removeClass('image-dropping');
});


/** Module  */
loadData();
function loadData() {
    var table;
    var modifikasi = $('.modifikasi').html();
    var hapus = $('.hapus').html();
    $(document).ready(function () {
        table = $('.datatable').DataTable({
            //"scrollY": false,
            "scrollX": false,
            "processing": true,
            "serverSide": false,
            "bAutoWidth": true,
            "bSort": false,
            "bStateSave": true,
            "bDestroy": true,
            "paging": true,
            "ssSorting": [[0, 'desc']],
            "iDisplayLength": 25,
            // "order": [[1, 'desc']],
            "fnDrawCallback": function () {
                $('.open-popup-link').magnificPopup({
                    type: 'image',
                    removalDelay: 300,
                    mainClass: 'mfp-fade',
                    gallery: {
                        enabled: true
                    },
                    zoom: {
                        enabled: true,
                        duration: 300,
                        easing: 'ease-in-out',
                        opener: function (openerElement) {
                            return openerElement.is('img') ? openerElement : openerElement.find('img');
                        }
                    }
                });
            },

            fixedColumns: {
                leftColumns: 2,
            },

            "aLengthMenu": [
                [25, 30, 50, -1],
                [25, 30, 50, "All"]
            ],
            language: {
                paginate: {
                    previous: "<i class='fas fa-angle-left'>",
                    next: "<i class='fas fa-angle-right'>"
                }
            },
            "ajax": {
                "url": "./sw-mod/izin-pegawai/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi: modifikasi,
                    hapus: hapus,
                },
            },

            "columnDefs": [{
                "targets": [0],
                "orderable": false,
            },],

            "createdRow": function (row, data, dataIndex) {
                let cleanId = stripHtml(data[0]).trim(); // Bersihkan HTML jika ada
                $(row).addClass('row-' + cleanId);
            }
        });
    });
}

function stripHtml(html) {
    let doc = new DOMParser().parseFromString(html, 'text/html');
    return doc.body.textContent || "";
}

/* -------- MODAL ADD */
$(document).on('click', '.btn-add', function () {
    $('.modal-add').modal('show');
    $('.modal-title').html('Tambah Data Izin');
    $(".form-add").trigger("reset");
    $('.id').val('');

    $('.file-upload-image').attr('src', './sw-assets/img/upload.png');
    $('.image-upload-wrap').show();
    $('.file-upload-content').hide();
});

/** Tambah  */
$('.form-add').submit(function (e) {
    loading();
    e.preventDefault();
    var id = $('.id').val();
    if (id == '') {
        var action = './sw-mod/izin-pegawai/sw-proses.php?action=add';
    } else {
        var action = './sw-mod/izin-pegawai/sw-proses.php?action=update';
    }
    $.ajax({
        url: action,
        type: "POST",
        data: new FormData(this),
        processData: false,
        contentType: false,
        cache: false,
        async: false,
        beforeSend: function () {
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({ title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500, });
                loadData();
                $(".form-add").trigger("reset");
                $('.modal-add').modal('hide');
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error', });
            }

        },
        complete: function () {
            $(".loading").hide();
        },
    });
});


/**  Update */
$(document).on('click', '.btn-update', function () {
    var id = $(this).attr('data-id');
    $('.modal-add').modal('show');
    $('.modal-title').html('Ubah data Izin');
    $('.tanggal').prop('readonly', true);
    $('.tanggal-selesai').prop('readonly', true);
    $.ajax({
        type: 'POST',
        url: './sw-mod/izin-pegawai/sw-proses.php?action=get-data-update',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            $('.id').val(response.izin_id);
            $('.pegawai').val(response.pegawai_id);
            $('.tanggal').val(response.tanggal);
            $('.tanggal-selesai').val(response.tanggal_selesai);
            $('.alasan').val(response.alasan);
            $('.keterangan').val(response.keterangan);
            if (!response.files == '') {
                $('.file-upload-image').attr('src', '../sw-content/izin/' + response.files + '');
                $('.image-upload-wrap').hide();
                $('.file-upload-content').show();
            }

        }, error: function (response) {
            console.log(response.responseText);
        }
    });
});



/* ------------- Set Status  --------------*/
$(document).on('click', '.btn-stts-setuju', function () {
    var id = $(this).attr("data-id");
    var status = $(this).attr("data-status");
    $.ajax({
        type: "POST",
        url: "./sw-mod/izin-pegawai/sw-proses.php?action=setujui",
        data: { id: id, status: status },
        success: function (data) {
            if (data == 'success') {
                swal({ title: 'Berhasil!', text: 'Data berhasil simpan!', icon: 'success', timer: 2500, });
                loadData();
            } else {
                swal({ title: 'Gagal!', text: data, icon: 'error', timer: 2500, });
            }
        }
    });
});

$(document).on('click', '.btn-stts-tolak', function () {
    var id = $(this).attr("data-id");
    var status = $(this).attr("data-status");
    $.ajax({
        type: "POST",
        url: "./sw-mod/izin-pegawai/sw-proses.php?action=ditolak",
        data: { id: id, status: status },
        success: function (data) {
            if (data == 'success') {
                swal({ title: 'Berhasil!', text: 'Data berhasil simpan!', icon: 'success', timer: 2500, });
                loadData();
            } else {
                swal({ title: 'Gagal!', text: data, icon: 'error', timer: 2500, });

            }
        }
    });
});

/** Hapus data */
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
                    url: './sw-mod/izin-pegawai/sw-proses.php?action=delete',
                    type: 'POST',
                    data: { id: id },
                    success: function (data) {
                        if (data == 'success') {
                            swal({ title: 'Berhasil!', text: 'Data berhasil dihapus.!', icon: 'success', timer: 2500, });
                            loadData();
                        } else {
                            swal({ title: 'Gagal!', text: data, icon: 'error', });
                        }
                    }
                });
            } else {
                return false;
            }
        });
}); 