<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:./404');
}else{

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">

        <div class="container mb-4 s-widodo.com">
            <div class="card s-widodo.com">
                <div class="card-body s-widodo.com">
                    <div class="form-group float-label position-relative mb-1 s-widodo.com">
                        <div class="bottom-right s-widodo.com">
                            <span class="btn btn-sm btn-link text-dark btn-40 rounded text-mute s-widodo.com"><i class="material-icons s-widodo.com">search</i></span>
                        </div>
                        <input type="text" class="form-control search s-widodo.com">
                        <label class="form-control-label s-widodo.com">Search</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4 s-widodo.com">
            <div class="s-widodo.com">
                <h6 class="subtitle mb-4 s-widodo.com">Informasi</h6>
            </div>

            <div class="swiper-container categories2tab1 text-center mb-4 swiper-container-horizontal s-widodo.com">
                <div class="swiper-wrapper s-widodo.com">';
                    $query_kategori="SELECT title,seotitle FROM kategori ORDER BY title ASC";
                    $result_kategori = $connection->query($query_kategori);
                    while ($data_kategori=$result_kategori->fetch_assoc()){
                    echo'
                    <div class="swiper-slide s-widodo.com">
                        <button class="btn btn-sm btn-outline-default rounded  btn-kategori s-widodo.com" data-kategori="'.strip_tags($data_kategori['seotitle']).'">'.strip_tags(ucfirst($data_kategori['title'])).'
                        </button>
                    </div>';
                    }
            echo'
                </div>
                <!-- Add Pagination -->
                <div class="swiper-pagination white-pagination text-left mb-3 s-widodo.com"></div>
                <span class="swiper-notification s-widodo.com" aria-live="assertive" aria-atomic="true"></span></div>

            <div class="load-blog row postList s-widodo.com">
                
            </div>
        </div>
    </div>
</main>';
}?>