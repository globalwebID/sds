
function loading(){
    $('.btn-save').prop("disabled", true);
      // add spinner to button
      $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-save').prop("disabled", false);
      $('.btn-save').html('Simpan');
    }, 2000);
}


function LoadBlog(){
    var search = $('.search').val();
    $(".load-blog").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-blog").load("./module/blog/sw-proses.php?action=data-blog&search="+search+"");
}
LoadBlog();

/** Pencarian */
$('.search').change(function(){
    LoadBlog();
})

/** Kategori */
$('.btn-kategori').click(function(){
    var search = $('.search').val();
    var kategori = $(this).attr("data-kategori");
    $(".load-blog").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading..</p></div>');
    $(".load-blog").load("./module/blog/sw-proses.php?action=data-blog&search="+search+"&kategori="+kategori);
})


/** Loadmore Data Artkel */
$(document).on('click','.load-more',function(){
    var id = $(this).attr("data-id");
    var search = $('.search').val();
    $('.show_more').hide();
    $('.show-blog').addClass('float-left');
    $.ajax({
        type:'POST',
        url:'./module/blog/sw-proses.php?action=data-blog-load',
        data:{id:id,search:search},
        beforeSend:function(){
            $(".load-more").text("Loading...");
        },
        success:function(data){
            $('.show_more_main'+id).remove();
            $('.postList').append(data);
            $(".load-more").text("Show more");
        }
    });
});
