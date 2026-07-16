
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
           
            fixedColumns: {
                leftColumns: 3,
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
                "url": "./sw-mod/laporan-ekbm/sw-datatable.php",
                "type": "POST",
                "data": function (d) {
                    d.modifikasi = $('.modifikasi').html();
                    d.hapus = $('.hapus').html();
                    d.kelas = $('.kelas').val();
                    d.pelajaran = $('.pelajaran').val();
                    d.tanggal = $('.tanggal').val();
                },
                
            },
            
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });

}


/** Dropdown */
$(".kelas, .tanggal, .pelajaran").change(function(){
    loadData();
});


/** Print */
$(document).on('click', '.btn-download', function(){
    var tipe = $(this).attr("data");
    var kelas = $('.kelas').val();
    var pelajaran = $('.pelajaran').val();
    var tanggal  = $('.tanggal').val();
    var url = "./sw-mod/laporan-ekbm/sw-print.php?action="+tipe+"&kelas="+kelas+"&pelajaran="+pelajaran+"&tanggal="+tanggal+""; 
    window.open(url, '_blank');
});

