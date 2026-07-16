
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


$("body").on("click", ".datepicker", function(){
    $(this).datepicker({
      format: 'dd-mm-yyyy',
      autoclose:true
    });
    $(this).datepicker("show");
});


loadData();
function loadData(){
    var modifikasi = $('.modifikasi').html();
    var hapus = $('.hapus').html();
    var table;
    $(document).ready(function() {
        //datatables
        table = $('.datatable').DataTable({
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": false,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 25,
            "order": [],
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
                "url": "./sw-mod/artikel/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                 }, 
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
}


  let pendingFile = null;
  let pendingImageDataUrl = null;
  tinymce.init({
      selector: ".swEditorText",
      plugins: "file-manager table link lists code fullscreen",
      content_style: "p { font-size: 15px;}",
      height:600,
      plugins: [
        'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor', 'pagebreak',
        'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'code', 'fullscreen', 'insertdatetime',
        'media', 'table', 'emoticons', 'wordcount', 'codesample', 'insertdatetime', 'visualchars', 'image','help',
      ],
      toolbar: [
        "undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote  removeformat | formatselect link customInsertImage image media | forecolor backcolor emoticons | codesample code | print preview  fullscreen wordcount"
      ],
      
      menu: {
        favs: { title: 'My Favorites', items: 'code visualaid | searchreplace | emoticons' }
      },
      promotion: false,
      image_advtab: true,
      relative_urls : false,
      remove_script_host : true,
      convert_urls:false,
      automatic_uploads: true,

      setup: function (editor) {
        // Tambahkan tombol custom
        editor.ui.registry.addButton('customInsertImage', {
          icon: 'browse',
          tooltip: 'Insert Image',
        
          onAction: function () {
            editor.windowManager.open({
              title: 'Insert Image',
              body: {
                type: 'panel',
                items: [
                  {
                    type: 'htmlpanel',
                    html: '<input type="file" id="custom-image-file" style="width: 100%;border: solid 1px #eeeeee;order-radius: 5px;" accept="image/*" />'
                  }
                ]
              },
              buttons: [
                { type: 'cancel', text: 'Cancel' },
                { type: 'submit', text: 'Save', primary: true }
              ],
              onSubmit: function (api) {
                const fileInput = document.getElementById('custom-image-file');
                const file = fileInput.files[0];
    
                if (!file) {
                  alert("Pilih gambar terlebih dahulu.");
                  return;
                }
    
                const formData = new FormData();
                formData.append('file', file);
    
                fetch('./sw-mod/artikel/upload.php', {
                  method: 'POST',
                  body: formData
                })
                .then(res => res.json())
                .then(data => {
                  if (data.location) {
                    editor.insertContent(`<img src="${data.location}" />`);
                    api.close();
                  } else {
                    alert(data.error || "Upload gagal.");
                  }
                })
                .catch(() => {
                  alert("Terjadi kesalahan saat upload.");
                });
              }
            });
          }
        });
      },

      images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
      
        fetch('./sw-mod/artikel/upload.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.location) {
            resolve(data.location); // URL yang dikembalikan ke TinyMCE
          } else {
            reject('Upload gagal: URL tidak ditemukan');
          }
        })
        .catch(() => {
          reject('Terjadi error saat upload');
        });
      })

  });

const originalConsoleError = console.error;
console.error = function (...args) {
  if (!args[0]?.toString().includes('[iterable]')) {
    originalConsoleError.apply(console, args);
  }
}

    
/** Upload Drag and Drop */
 function readURL(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
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
  }
  $('.image-upload-wrap').bind('dragover', function () {
      $('.image-upload-wrap').addClass('image-dropping');
  });
      $('.image-upload-wrap').bind('dragleave', function () {
      $('.image-upload-wrap').removeClass('image-dropping');
  });


/** Tambah Baru */
$('.form-add').submit(function (e) {
    e.preventDefault();
    tinyMCE.triggerSave();
    loading();
        $.ajax({
            url:"./sw-mod/artikel/sw-proses.php?action=add",
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
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                    $(".form-add").trigger("reset");
                    window.setTimeout(window.location.href = "./artikel",2500);
    
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                    loadData();
                }
  
            },
            complete: function () {
                
            },
        });
  }); 

    

/** Update Artikel */
$('.form-update').submit(function (e) {
    e.preventDefault();
    tinyMCE.triggerSave();
    loading();
        $.ajax({
            url:"./sw-mod/artikel/sw-proses.php?action=update",
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
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                    $(".form-add").trigger("reset");
                    window.setTimeout(window.location.href = "./artikel",2500);
    
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                    loadData();
                }
  
            },
            complete: function () {
                
            },
        });
  }); 


/** Hapus data Artikel*/
$(document).on('click', '.btn-delete', function(){ 
    var id = $(this).attr("data-id");
    var name = $(this).attr("data-name");
      swal({
        text: "Anda yakin ingin menghapus  data ini?",
        icon: "warning",
          buttons: {
            cancel: true,
            confirm: true,
          },
        value: "yes",
      })

      .then((value) => {
        if(value) {
            loading();
            $.ajax({  
                 url:'./sw-mod/artikel/sw-proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus.!', icon: 'success', timer: 2500,});
                        loadData();
                    } else {
                        swal({title: 'Gagal!', text: data, icon: 'error', timer:2500,});
                        
                    }
                 }  
            });  
       } else{  
        return false;
    }  
});
}); 
    

$(document).on('click', '.btn-kategori', function(){ 
    swal("Tambah kategori baru", {
        content: "input",
    })
    .then((value) => {
        if(value) {
            $.ajax({  
                url:'./sw-mod/artikel/sw-proses.php?action=add-kategori',
                type:'POST',    
                data:{title:value},  
               success:function(data){
                    var results = data.split("/");
                     var error = results[0];
                     var notif = results[1];
                    if(error == "error"){
                        swal({title: 'Gagal!', text: notif, icon: 'error', timer:1500,});
                    }else{
                        $(".kategori").html(data);
                        swal({title: 'Berhasil!', text: 'Kategori berhasil ditambah.!', icon: 'success', timer: 1500,});

                    }
                }  
           });  
        }
       
    });
});