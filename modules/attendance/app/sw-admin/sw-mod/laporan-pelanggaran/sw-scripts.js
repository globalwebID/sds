
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


$("body").on("click", ".datepicker", function () {
    $(this).datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true
    });
    $(this).datepicker("show");
});


/** Dropdown */
$(".kelas").change(function () {
    var kelas = $(this).val();
    $.ajax({
        type: 'POST',
        url: './sw-mod/laporan-pelanggaran/sw-proses.php?action=dropdown',
        data: { kelas: kelas },
        cache: false,
        success: function (data) {
            $(".siswa").html(data);
        }
    });
});


/** Module  */

loadData();
function loadData() {
    var table;
    $(document).ready(function () {
        //datatables

        table = $('.datatable').DataTable({
            "scrollY": false,
            "scrollX": true,
            "processing": true,
            "serverSide": false,
            "bAutoWidth": false,
            "bSort": false,
            "bStateSave": true,
            "bDestroy": true,
            "paging": true,
            "ssSorting": [[0, 'desc']],
            "iDisplayLength": 25,
            // "order": [[1, 'desc']],

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
                "url": "./sw-mod/laporan-pelanggaran/sw-datatable.php",
                "type": "POST",
                data: function (d) {
                    //console.log("Data yang dikirimkan ke server:", d);
                    d.modifikasi = $('.modifikasi').html(),
                        d.hapus = $('.hapus').html(),
                        d.kelas = $('.kelas').val();
                    d.siswa = $('.siswa').val();
                    d.from = $('.from').val();
                    d.to = $('.to').val();
                },
            },

            fixedColumns: {
                leftColumns: 3,
            },

            "columnDefs": [{
                "targets": [0],
                "orderable": false,
            },],
        });
    });

}

$(".kelas, .siswa, .from, .to").change(function () {
    loadData();
});

/** Dropdown */
$(".kelas, .siswa, .from, .to").change(function () {
    loadData();
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
                    url: './sw-mod/laporan-pelanggaran/sw-proses.php?action=delete',
                    type: 'POST',
                    data: { id: id },
                    success: function (data) {
                        if (data == 'success') {
                            swal({ title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 2500, });
                            loadData();
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

/** Print */
$(document).on('click', '.btn-download', function () {
    var tipe = $(this).attr("data");
    var kelas = $('.kelas').val();
    var siswa = $('.siswa').val();
    var from = $('.from').val();
    var to = $('.to').val();
    var url = "./sw-mod/laporan-pelanggaran/sw-print.php?action=" + tipe + "&kelas=" + kelas +
        "&siswa=" + siswa + "&from=" + from + "&to=" + to + "";
    window.open(url, '_blank');
});

