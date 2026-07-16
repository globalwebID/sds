
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



/** Dropdown */
dropdownLokasi();
function dropdownLokasi() {
    var lokasi = $('.lokasi').val();
    $.ajax({
        type: 'POST',
        url: './sw-mod/laporan-pegawai/sw-proses.php?action=dropdown',
        data: { lokasi: lokasi },
        cache: false,
        success: function (data) {
            $(".pegawai").html(data);
        }
    });
}

$(".lokasi-dropdown").change(function () {
    dropdownLokasi();
});

//loadData();
setTimeout(function () {
    loadData();
}, 1000);
function loadData() {
    $(document).ready(function () {
        var table;
        table = $('.datatable').DataTable({
            processing: true,
            serverSide: false,
            destroy: true,
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
                leftColumns: 3,
            },

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
                url: './sw-mod/laporan-pegawai/sw-datatable.php',
                type: 'POST',
                data: function (d) {
                    console.log("Data yang dikirimkan ke server:", d);
                    d.modifikasi = $('.modifikasi').html(),
                        d.hapus = $('.hapus').html(),
                        d.pegawai = $('.pegawai').val();
                    d.from = $('.from').val();
                    d.to = $('.to').val();
                },
            },

            "columns": [
                { "data": "no" },
                { "data": "date" },
                { "data": "pegawai" },
                { "data": "jam_kerja" },
                { "data": "toleransi" },
                { "data": "foto_masuk" },
                { "data": "absen_masuk" },
                { "data": "foto_pulang" },
                { "data": "absen_pulang" },
                { "data": "durasi" },
                { "data": "radius" },
                { "data": "status" },
                { "data": "titik_lokasi" },
                { "data": "keterangan" },
                { "data": "aksi" },
            ],

            "rowCallback": function (row, data, index) {
                // Kamu bisa menambahkan styling khusus di sini, misalnya
                if (data.background === 'danger') {
                    $(row).css('background-color', '#f8d7da'); // Beri warna merah untuk hari libur
                } else if (data.background === 'warning') {
                    $(row).css('background-color', '#fff6c9fa');
                } else if (data.background === 'bg-white') {
                    $(row).css('background-color', '#fff'); // Beri warna putih untuk hari kerja

                }
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
                    $('.hadir-cell').html(`<strong>${json.jumlah_hadir ?? 0}</strong>`);
                    $('.telat-cell').html(`<strong>${json.jumlah_telat ?? 0}</strong>`);
                    $('.izin-cell').html(`<strong>${json.jumlah_izin ?? 0}</strong>`);
                    $('.sakit-cell').html(`<strong>${json.jumlah_sakit ?? 0}</strong>`);
                    $('.total-jam-cell').html(`<strong>${json.total_jam ?? 0}</strong>`);
                    $('.belum-absen-cell').html(`<strong>${json.jumlah_belum_absen ?? 0}</strong>`);
                }
            }
        });

        // Reload data jika filter berubah
        $('.pegawai, .from, .to').on('change', function () {
            table.ajax.reload();
        });
    });

}

$('.pegawai, .from, .to').on('change', function () {
    loadData();
});



/**  Update */
$(document).on('click', '.btn-update', function () {
    var id = $(this).attr('data-id');
    $('.modal-add').modal('show');
    $('.modal-title').html('Ubah Data Absensi');
    $.ajax({
        type: 'POST',
        url: './sw-mod/laporan-pegawai/sw-proses.php?action=get-data-update',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            $('.id').val(response.absen_id);
            $('.absen-in').val(response.absen_in);
            $('.absen-out').val(response.absen_out);
            $('.status-masuk').val(response.status_masuk);
            $('.status-pulang').val(response.status_pulang);
            $('.keterangan').val(response.keterangan);
        }, error: function (response) {
            console.log(response.responseText);
        }
    });
});


/**  Save */
$('.form-add').submit(function (e) {
    loading();
    e.preventDefault();
    $.ajax({
        url: './sw-mod/laporan-pegawai/sw-proses.php?action=update',
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
                swal({ title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500, });
                loadData();
                $(".form-add").trigger("reset");
                $('.modal-add').modal('hide');
                $('id').val('');
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error', });
            }
        },
        complete: function () {
            $(".loading").hide();
        },
    });
});

/** Print */
$(document).on('click', '.btn-download', function () {
    var tipe = $(this).attr("data");
    var pegawai = $('.pegawai').val();
    var from = $('.from').val();
    var to = $('.to').val();
    var url = "./sw-mod/laporan-pegawai/sw-print.php?action=" + tipe + "&pegawai=" + pegawai + "&from=" + from + "&to=" + to + "";
    window.open(url, '_blank');
});
