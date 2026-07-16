
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
})

loadData();
function loadData(){
    var table;
    var modifikasi = $('.modifikasi').html();
    var hapus   = $('.hapus').html();
    var pegawai = $('.pegawai').val();
    var kelas   = $('.kelas').val();
    var mata_pelajaran= $('.mata_pelajaran').val();
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
            "scrollY": false,
            "scrollX": false,
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": true,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 15,
           // "order": [[1, 'desc']],
            
            "aLengthMenu": [
                [25, 30, 50, -1],
                [15, 30, 50, "All"]
            ],
            language: {
              paginate: {
                previous: "<i class='fas fa-angle-left'>",
                next: "<i class='fas fa-angle-right'>"
              }
            },
            "ajax": {
                "url": "./sw-mod/jadwal-notifikasi/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                    pegawai:pegawai,
                    kelas:kelas,
                    mata_pelajaran:mata_pelajaran
                 },
            },
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
}

$(".pegawai, .kelas, .mata_pelajaran").change(function(){
 loadData();
});