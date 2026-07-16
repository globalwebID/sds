
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
    var pegawai = $('.result-pegawai').html();
    $.ajax({
        type: 'POST',
        url: './sw-mod/laporan-pegawai-perhari/sw-proses.php?action=dropdown',
        data: { lokasi: lokasi, pegawai: pegawai },
        cache: false,
        success: function (data) {
            $(".pegawai").html(data);
        }
    });
}

$(".lokasi-dropdown").change(function () {
    dropdownLokasi();
});


$(document).on('click', '.btn-cari', function () {
    filter();
});

function filter() {
    var lokasi = $('.lokasi').val();
    var pegawai = $('.pegawai').val();
    var jabatan = $('.jabatan').val();
    var from = $('.from').val();
    var to = $('.to').val();
    window.location.replace("./laporan-pegawai-perhari&lokasi=" + lokasi + "&pegawai=" + pegawai + "&jabatan=" + jabatan + "&from=" + from + "&to=" + to + "");
}

$(document).ready(function () {
    function loadData() {
        var lokasi = $('.lokasi').val();
        var pegawai = $('.pegawai').val();
        var jabatan = $('.jabatan').val();
        var from = $('.from').val();
        var to = $('.to').val();

        $('.datatable').DataTable({
            "ajax": {
                "url": "./sw-mod/laporan-pegawai-perhari/sw-datatable.php?lokasi=" + lokasi + "&pegawai=" + pegawai + "&jabatan=" + jabatan + "&from=" + from + "&to=" + to,
                "dataSrc": function (json) {
                    // Pastikan response JSON memiliki data yang benar
                    if (json && Array.isArray(json) && json.length > 0) {
                        return json; // Jika data valid, kembalikan data
                    } else {
                        console.error("Data dari server tidak valid atau kosong:", json);
                        return []; // Kembalikan array kosong jika data tidak valid
                    }
                }
            },

            processing: true,
            serverSide: false,
            destroy: true,

            fixedColumns: {
                leftColumns: 2,
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

            "columns": [
                { "data": "no" },
                { "data": "nama_pegawai" },
                { "data": "jabatan" },
                // Kolom tanggal IN/OUT
            ],
            "createdRow": function (row, data) {
                let hadir = data.hadir || 0;  // Menggunakan nilai default 0 jika tidak ada
                let telat = data.telat || 0;  // Menggunakan nilai default 0 jika tidak ada
                let izin = data.izin || 0;    // Menggunakan nilai default 0 jika tidak ada
                let sakit = data.sakit || 0;
                let cuti = data.cuti || 0;    // Menggunakan nilai default 0 jika tidak ada
                let alpha = data.alpha || 0;  // Menggunakan nilai default 0 jika tidak ada

                // Pastikan data.tanggal ada dan merupakan array
                if (data.tanggal && Array.isArray(data.tanggal)) {
                    let htmlInOut = '';
                    data.tanggal.forEach(function (tanggal) {
                        let inOutClass = tanggal.result_libur || 'bg-white text-dark';
                        if (tanggal.result_libur === 'danger') {
                            inOutClass = 'bg-danger text-white';
                        } else if (tanggal.result_libur === 'success') {
                            inOutClass = 'bg-success text-white';
                        } else if (tanggal.result_libur === 'warning') {
                            inOutClass = 'bg-warning text-dark';
                        } else {
                            inOutClass = 'bg-white text-dark';
                        }

                        // Menambahkan <td> IN dan OUT secara terpisah
                        htmlInOut += `<td class="text-center ${inOutClass}">${tanggal.in || 'N/A'}</td>`;
                        htmlInOut += `<td class="text-center ${inOutClass}">${tanggal.out || 'N/A'}</td>`;
                    });
                    // Menambahkan kolom IN/OUT setelah kolom jabatan (kolom ke-3)
                    $('td', row).eq(2).after(htmlInOut);
                } else {
                    console.error("Data tanggal tidak valid atau tidak ada", data);
                }
                // Tambahan: beri warna seluruh baris jika seluruh hari libur
                if (data.result_libur === 'danger') {
                    $(row).addClass('table-danger'); // Bootstrap class untuk highlight baris
                }

                let status = `<td class="text-center bg-secondary">${hadir}</td>
                <td class="text-center bg-secondary">${telat}</td>
                <td class="text-center bg-secondary">${izin}</td>
                <td class="text-center bg-secondary">${sakit}</td>
                <td class="text-center bg-secondary">${alpha}</td>`;
                $('td', row).eq(data.tanggal.length * 2 + 2).after(status);  // Menambahkan status setelah semua tanggal
            },
        });
    }

    setTimeout(function () {
        loadData();
    }, 500);
});


/** Print */
$(document).on('click', '.btn-download', function () {
    var tipe = $(this).attr("data");
    var lokasi = $('.lokasi').val();
    var pegawai = $('.pegawai').val();
    var jabatan = $('.jabatan').val();
    var from = $('.from').val();
    var to = $('.to').val();
    var url = "./sw-mod/laporan-pegawai-perhari/sw-print.php?action=" + tipe + "&lokasi=" + lokasi + "&pegawai=" + pegawai + "&jabatan=" + jabatan + "&from=" + from + "&to=" + to + "";
    window.open(url, '_blank');
});


