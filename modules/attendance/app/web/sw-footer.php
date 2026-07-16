<?php if(empty($connection)){
  header('location:./404');
} else {
$mod = "home";
if(!empty($_GET['mod'])){$mod = mysqli_escape_string($connection,@$_GET['mod']);}else {$mod ='home';}
echo'
<footer class="text-muted text-center d-none s-widodo.com">
<p>© 2025 - '.$year.' '.$site_name.' - Design By:
    <span class="credits s-widodo.com">
        <a class="credits_a s-widodo.com" id="mycredit" href="https://s-widodo.com"  target="_blank">S-widodo.com</a>
    </span>
</p>
</footer>
    
<script src="./sw-library/bundle.min.php?get=s-widodo.com"></script>';
if(file_exists('web/'.$mod.'/sw-scripts.js')){
echo'
<script src="web/'.$mod.'/sw-scripts.js"></script>';
}
echo'
</body>

</html>';
}?>