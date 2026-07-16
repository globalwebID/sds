<?php if(empty($connection)){
  header('location:./404/');
} else {
if(isset($_COOKIE['wali_murid'])){
echo'
<div class="footer s-widodo.com">
    <div class="appBottomMenu s-widodo.com">
        <a href="'.$base_url.'" class="item s-widodo.com">
            <div class="col s-widodo.com">
                <span class="material-icons s-widodo.com">holiday_village</span>
                <strong>Home</strong>
            </div>
        </a>
        <a href="'.$base_url.'histori-absen" class="item s-widodo.com">
            <div class="col s-widodo.com">
                <span class="material-icons s-widodo.com">fact_check</span>
                <strong>Rekap Absensi</strong>
            </div>
        </a>

        <a href="javascript:void(0);" class="item s-widodo.com">
            <div class="col s-widodo.com">
                <div class="action-button large s-widodo.com">
                    <span class="material-icons s-widodo.com">camera</span>
                </div>
            </div>
        </a>

        <a href="'.$base_url.'kartu-nama" class="item s-widodo.com">
            <div class="col s-widodo.com">
                <span class="material-icons s-widodo.com">badge</span>
                <strong>Kartu Nama</strong>
            </div>
        </a>
        
        <a href="'.$base_url.'setting" class="item s-widodo.com">
            <div class="col s-widodo.com">
                <span class="material-icons s-widodo.com">account_circle</span>
                <strong>Akun</strong>
            </div>
        </a>
    </div>
</div>';
    
}

echo'
<!-- color settings style switcher -->
    <div class="color-picker s-widodo.com">
        <div class="row s-widodo.com">
            <div class="col text-left s-widodo.com">
                <div class="selectoption s-widodo.com">
                    <input type="checkbox" id="darklayout" name="darkmode">
                    <label for="darklayout">Dark</label>
                </div>
                <div class="selectoption mb-0 s-widodo.com">
                    <input type="checkbox" id="rtllayout" name="layoutrtl">
                    <label for="rtllayout">RTL</label>
                </div>
            </div>
            <div class="col-auto s-widodo.com">
                <button class="btn btn-link text-secondary btn-round colorsettings2 s-widodo.com"><span class="material-icons">close</span></button>
            </div>
        </div>

        <hr class="mt-2 s-widodo.com">

        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor1" name="sidebarcolorselect">
            <label for="templatecolor1" class="bg-dark-blue s-widodo.com" data-title="app"></label>
        </div>

        
       
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor2" name="sidebarcolorselect">
            <label for="templatecolor2" class="bg-dark-purple s-widodo.com" data-title="dark-purple"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor4" name="sidebarcolorselect">
            <label for="templatecolor4" class="bg-dark-gray s-widodo.com" data-title="dark-gray"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor6" name="sidebarcolorselect">
            <label for="templatecolor6" class="bg-dark-brown s-widodo.com" data-title="dark-brown"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor3" name="sidebarcolorselect">
            <label for="templatecolor3" class="bg-maroon s-widodo.com" data-title="maroon"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor5" name="sidebarcolorselect">
            <label for="templatecolor5" class="bg-dark-pink s-widodo.com" data-title="dark-pink"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor8" name="sidebarcolorselect">
            <label for="templatecolor8" class="bg-red s-widodo.com" data-title="red"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor13" name="sidebarcolorselect">
            <label for="templatecolor13" class="bg-amber s-widodo.com" data-title="amber"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor7" name="sidebarcolorselect">
            <label for="templatecolor7" class="bg-dark-green s-widodo.com" data-title="dark-green"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor11" name="sidebarcolorselect">
            <label for="templatecolor11" class="bg-teal s-widodo.com" data-title="teal"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor12" name="sidebarcolorselect">
            <label for="templatecolor12" class="bg-skyblue s-widodo.com" data-title="skyblue"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor10" name="sidebarcolorselect">
            <label for="templatecolor10" class="bg-blue s-widodo.com" data-title="blue"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor9" name="sidebarcolorselect">
            <label for="templatecolor9" class="bg-purple s-widodo.com" data-title="purple"></label>
        </div>
        <div class="colorselect s-widodo.com">
            <input type="radio" id="templatecolor14" name="sidebarcolorselect">
            <label for="templatecolor14" class="bg-gray s-widodo.com" data-title="gray"></label>
        </div>
    </div>

    <footer class="text-muted text-center d-none s-widodo.com">
        <p>© 2025 - '.$year.' '.$site_name.' - Design By:
            <span class="credits s-widodo.com">
                <a class="credits_a s-widodo.com" id="mycredit" href="https://s-widodo.com"  target="_blank">S-widodo.com</a>
            </span>
        </p>
    </footer>

<script src="../sw-library/bundle.min.php?get=s-widodo.com"></script>';
if(file_exists('module/'.$mod.'/sw-scripts.js')){
echo'
<script src="module/'.$mod.'/sw-scripts.js"></script>';
}
if($mod == 'absen-in' OR $mod=='absen-out'){
require_once("module/$mod/javascript.php");
}
echo'
</body>
</html>';
}?>