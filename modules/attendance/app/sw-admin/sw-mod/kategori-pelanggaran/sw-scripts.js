
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


loadData();
function loadData() {
    var table;
    var modifikasi = $('.modifikasi').html();
    var hapus = $('.hapus').html();
    $(document).ready(function () {
        //datatables
        table = $('.datatable').DataTable({
            "scrollY": false,
            "scrollX": false,
            "processing": true,
            "serverSide": false,
            "bAutoWidth": true,
            "bSort": true,
            "bStateSave": true,
            "bDestroy": true,
            "paging": true,
            "ssSorting": [[0, 'desc']],
            "iDisplayLength": 10,
            // "order": [[1, 'desc']],

            "aLengthMenu": [
                [10, 30, 50, -1],
                [10, 30, 50, "All"]
            ],
            language: {
                paginate: {
                    previous: "<i class='fas fa-angle-left'>",
                    next: "<i class='fas fa-angle-right'>"
                }
            },
            "ajax": {
                "url": "./sw-mod/kategori-pelanggaran/sw-datatable.php",
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
        });
    });
}


$(document).on('click', '.btn-reset', function () {
    $(".form-add").trigger("reset");
    $('.id').val('');
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
    var data = $(".form-add").serialize();
    $.ajax({
        type: 'POST',
        url: './sw-mod/kategori-pelanggaran/sw-proses.php?action=add',
        data: data,
        cache: false,
        async: false,
        beforeSend: function () {
            loading();
        },
        success: function (data) {
            if (data == 'success') {
                swal({ title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500, });
                $(".form-add").trigger("reset");
                $('.id').val('');
                //$('.modal-add').modal('hide');
                loadData();
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error', timer: 2500, });
                loadData();
            }
        }
    });
    return false;
}

$(document).on('click', '.btn-update', function () {
    var id = $(this).attr('data-id');
    $('.modal-add').modal('show');
    $('.modal-title').html('Ubah Kelas');
    $.ajax({
        type: 'POST',
        url: './sw-mod/kategori-pelanggaran/sw-proses.php?action=get-data-update',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            $('.id').val(response.id);
            $('.nama_kategori').val(response.nama_kategori);
        }, error: function (response) {
            console.log(response.responseText);
        }
    });
});


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
                    url: './sw-mod/kategori-pelanggaran/sw-proses.php?action=delete',
                    type: 'POST',
                    data: { id: id },
                    success: function (data) {
                        if (data == 'success') {
                            swal({ title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 2500, });
                            loadData();
                            $('.id').val('');
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