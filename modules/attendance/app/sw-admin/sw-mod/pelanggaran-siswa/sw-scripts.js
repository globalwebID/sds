
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
        url: './sw-mod/pelanggaran-siswa/sw-proses.php?action=dropdown',
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
    $(document).ready(function () {
        var table;
        table = $('.datatable').DataTable({
            processing: true,
            serverSide: false,
            destroy: true,
            "aLengthMenu": [
                [30, 50, -1],
                [30, 50, "All"]
            ],

            language: {
                paginate: {
                    previous: "<i class='fas fa-angle-left'>",
                    next: "<i class='fas fa-angle-right'>"
                }
            },

            ajax: {
                url: './sw-mod/pelanggaran-siswa/sw-datatable.php',
                type: 'POST',
                data: function (d) {
                    console.log("Data yang dikirimkan ke server:", d);
                    d.modifikasi = $('.modifikasi').html(),
                        d.hapus = $('.hapus').html(),
                        d.kelas = $('.kelas').val();
                    d.siswa = $('.siswa').val();
                    d.from = $('.from').val();
                    d.to = $('.to').val();
                },
            },

            "columns": [
                { "data": "no" },
                { "data": "siswa" },
                { "data": "kelas" },
                { "data": "pelanggaran" },
                { "data": "bobot" },
                { "data": "guru" },
                { "data": "tanggal" },
                { "data": "aksi" },
            ],

            fixedColumns: {
                leftColumns: 3,
            },

            "columnDefs": [
                {
                    "targets": [0], // Tentukan kolom yang ingin di-render HTML
                    "render": function (data, type, row) {
                        return data; // Kembalikan data untuk merender HTML
                    },
                    "createdCell": function (td, cellData, rowData, row, col) {
                        // Menangani atau mengubah data jika perlu
                    }
                }
            ],

            footerCallback: function (row, data, start, end, display) {
                const json = this.api().ajax.json();
                if (json) {
                    $('.total-cell').html(`<strong>${json.jumlah_bobot ?? 0}</strong>`);
                }
            }
        });

        // Reload data jika filter berubah
        $('.kelas, .siswa, .from, .to').on('change', function () {
            table.ajax.reload();
        });
    });

}

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
                    url: './sw-mod/pelanggaran-siswa/sw-proses.php?action=delete',
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
    var url = "./sw-mod/pelanggaran-siswa/sw-print.php?action=" + tipe + "&kelas=" + kelas +
        "&siswa=" + siswa + "&from=" + from + "&to=" + to + "";
    window.open(url, '_blank');
});

