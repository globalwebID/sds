<?php if(empty($connection)){
	header('location:./');
} else {
$mod = "home";
$mod = htmlentities(@$_GET['mod']);
// Get number
function get_numbers() {
  for ($i = 1; $i <= 500; $i++) {yield $i;}
}
$result = get_numbers();
function convertkb($size){
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

require_once'./sw-mod/chat/chat.php';
echo'
    <!-- Footer -->
      <footer class="footer pt-0">
        <div class="row align-items-center justify-content-lg-between">
          <div class="col-lg-12">
            <div class="copyright text-center text-lg-left text-muted">
            <!-- Dilarang menghapus credits, hargai developmentnya -->
              &copy; '.$date.' <a href="./" class="font-weight-bold ml-1" target="_blank">'.$site_name.'</a>
              | <span class="credits">
                  <a class="credits_a" id="mycredit" href="https://s-widodo.com"  target="_blank">ICT SMKN 1 Probolinggo</a>
              </span>
            </div>
          </div>
  
        </div>
      </footer>
    </div>
</div>


<script src="sw-assets/bundle.min.php?get=s-widodo.com"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>';
if($mod=='lokasi'){
  echo'
  <script src="./sw-assets/vendor/leatfet/leaflet.js"></script>
  <script src="./sw-assets/vendor/leatfet/L.Control.Locate.js"></script>';
}
if($mod=='artikel'){
echo'
<script src="sw-assets/vendor/tinymce/tinymce.min.js"></script>';
}
if(file_exists('sw-mod/'.$mod.'/sw-scripts.js')){
echo'
<script src="sw-mod/'.$mod.'/sw-scripts.js"></script>';
}else{
  echo'
<script src="sw-mod/home/sw-scripts.js"></script>';
}
echo'
</body>
</html>';}