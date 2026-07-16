
$(document).on('change','#avatar',function(){
    var file_data = $('#avatar').prop('files')[0];  
    
        var form_data = new FormData();
        form_data.append("file",file_data);
        $.ajax({
            url:'./module/setting/sw-proses.php?action=avatar',
            method:'POST',
            data:form_data,
            contentType:false,
            cache:false,
            processData:false,
        success:function(data){
            if (data == 'success') {
                swal({title: 'Behasil!', text:'Photo Profil berhasil disimpan!', icon: 'success', timer: 2500,});
                setTimeout("location.href = './setting';",2500);
            } else {
                swal({title: 'Oops!', text: data, icon: 'error',});
            }
        }
    });
});