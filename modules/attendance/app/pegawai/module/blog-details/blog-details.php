<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:./404');
}else{
    if (isset($_GET['details'])){
        $details = mysqli_real_escape_string($connection,$_GET['details']);
        $blog = str_replace('-',' ',$details);
        $query_artikel ="SELECT * FROM artikel WHERE active='Y' AND artikel_id='$details'"; 
        $result_artikel = $connection->query($query_artikel);
    }

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">';
        if($result_artikel->num_rows > 0){
            $data_artikel = $result_artikel->fetch_assoc();
            $kategori = str_replace('-',' ',$data_artikel['kategori']);
            $kategori = ucfirst($kategori);

            $statistik = $data_artikel['statistik']+1;
            $update = "UPDATE artikel SET statistik='$statistik' WHERE artikel_id='$data_artikel[artikel_id]'";
            $connection->query($update);

            echo'
            <article class="blog-details s-widodo.com">
                <h6>'.strip_tags($data_artikel['judul']).'</h6>
                <ul class="meta s-widodo.com">
                    <li><i class="far fa-user"></i><a href="#">'.strip_tags($data_artikel['penerbit']).'</a></li>
                    <li><i class="far fa-calendar-alt"></i><a href="#">'.tgl_indo($data_artikel['date']).'</a></li>
                    <li><i class="fa fa-tags"></i><a href="#">'.$kategori.'</a></li>
                    <li><i class="fa fa-eye"></i><a href="#">'.$data_artikel['statistik'].' Pembaca</a></li>
                </ul>

                <div class="deskripsi s-widodo.com">
                    '.$data_artikel['deskripsi'].'
                </div>
                <div class="text-center mt-4">
                    <a href="./blog" class="btn btn-light rounded">Kembali</a>
                </div>
            </article>';
        }else{
            echo'
            <div class="col-12 col-md-6 col-lg-4 align-self-center text-center my-3 mx-auto s-widodo.com">
                <div class="icon icon-120 bg-danger-light text-danger rounded-circle mb-3 s-widodo.com">
                    <i class="material-icons display-4">error_outline</i>
                </div>
                <h2 class="display-2 s-widodo.com">404</h2>
                <h5 class="text-secondary mb-4 text-uppercase s-widodo.com">Page not found </h5>
                <p class="text-secondary s-widodo.com">Halaman yang Anda cari tidak tersedia, silakan periksa ulang URL atau coba lagi nanti.</p>
                <br>
                <a href="'.$base_url.'" class="btn btn-default rounded s-widodo.com">Go back to Home</a>
            </div>';
        }
        echo'   
        </div>
    </div>
</main>';
}?>