
function loading(){
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
    format :'HH:mm'
});

$("body").on("click", ".datepicker", function(){
    $(this).datepicker({
      format: 'dd-mm-yyyy',
      autoclose:true
    });
    $(this).datepicker("show");
});


loadData();
function loadData(){
    var table;
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": true,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
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
                "url": "./sw-mod/laporan-pegawai-hari-ini/sw-datatable.php",
                "type": "POST",
                "data": function (d) {
                    d.modifikasi = $('.modifikasi').html();
                    d.hapus = $('.hapus').html();
                    d.lokasi = $('.lokasi').val();
                    d.tanggal = $('.tanggal').val();
                },
                
            },

            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api();
                var json = api.ajax.json();
                if (json && json.jumlah_belum_absen !== undefined) {
                    $('#belum-absen-cell').html('<span class="text-danger font-weight-bold">' + json.jumlah_belum_absen + '</span>');
                }
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });

}


/** Dropdown */
$(".posisi").change(function(){
    loadData();
});

$(".lokasi").change(function(){
    loadData();
});

$(".tanggal").change(function(){
    loadData();
});


/** Print */
$(document).on('click', '.btn-download', function(){
    var tipe = $(this).attr("data");
    var lokasi = $('.lokasi').val();
    var tanggal  = $('.tanggal').val();
    var url = "./sw-mod/laporan-pegawai-hari-ini/sw-print.php?action="+tipe+"&lokasi="+lokasi+"&tanggal="+tanggal+""; 
    window.open(url, '_blank');
});

