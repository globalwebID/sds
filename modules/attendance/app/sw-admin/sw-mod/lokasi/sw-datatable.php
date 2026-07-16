<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';
    require_once'../../../sw-library/phpqrcode/qrlib.php'; 

    if(!empty($_POST['modifikasi'])){
        $modifikasi = convert('decrypt', $_POST['modifikasi']);
    }else{
        $modifikasi ='N';
    }
    
    if(!empty($_POST['modifikasi'])){
        $hapus = convert('decrypt', $_POST['hapus']);
    }else{
        $hapus ='N';
    }


    $aColumns = ['lokasi_id', 'lokasi_nama', 'lokasi_latitude', 'lokasi_longitude','lokasi_radius', 'lokasi_qrcode', 'lokasi_status'];
    $sIndexColumn = "lokasi_id";
    $sTable = "lokasi";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1')
    {
        $sLimit = "LIMIT ".mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayStart']).", ".
            mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayLength']);
    }

    $sOrder = "ORDER BY lokasi_id DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY lokasi_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY lokasi_id DESC")
        {
            $sOrder = "ORDER BY lokasi_id DESC";
        }
    }

    $sWhere = "";
    if (isset($_GET['sSearch']) && $_GET['sSearch'] != "")
    {
        $sWhere = "WHERE (";
        for ($i=0; $i<count($aColumns); $i++)
        {
            $sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($gaSql['link'], $_GET['sSearch'])."%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    for ($i=0 ; $i<count($aColumns); $i++)
    {
        if (isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '')
        {
            if ($sWhere == "")
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            $sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($gaSql['link'], $_GET['sSearch_'.$i])."%' ";
        }
    }

    $sQuery = " SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
        FROM $sTable
        $sWhere
        $sOrder
        $sLimit ";
    $rResult = mysqli_query($gaSql['link'], $sQuery);

    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    $sQuery = "SELECT COUNT(".$sIndexColumn.") FROM   $sTable";
    $rResultTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultTotal = mysqli_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];

    $output = array( 
       // "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)){$no++;
      extract($aRow);
        $row = array();
        for ($i=1 ; $i<count($aColumns) ; $i++){
            $onlick = "','";
            $onlick = explode(",",$onlick);

            if(file_exists('../../../sw-content/lokasi/'.seo_title($aRow['lokasi_qrcode']).'.jpg')){
                //echo 'QR code ada';
            }else{
                /* --  End Random Karakter ---- */
                $codeContents = $aRow['lokasi_qrcode'];
                $tempdir = '../../../sw-content/lokasi/';
                $namafile = ''.seo_title($codeContents).'.jpg';
                $quality = 'QR_ECLEVEL_Q'; //ada 4 pilihan, L (Low), M(Medium), Q(Good), H(High)
                $ukuran = 8; //batasan 1 paling kecil, 10 paling besar
                $padding = 1;
                QRCode::png($codeContents,$tempdir.$namafile,$quality,$ukuran,$padding);
            }
            
            if($aRow['lokasi_status'] =='Y'){
                $status = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active active'.$aRow['lokasi_id'].'" data-id="'.$aRow['lokasi_id'].'" data-active="Y" checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>';
            }else{
                 $status = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active active'.$aRow['lokasi_id'].'"  data-id="'.$aRow['lokasi_id'].'"  data-active="N">
                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>';
            }

            if($modifikasi =='Y'){
                $btn_update = '<a href="javascript:void(0)" onClick="location.href='.$onlick[0].'lokasi&op=update&id='.convert('encrypt',$aRow['lokasi_id']).''.$onlick[1].';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
            }else{
                $btn_update ='<a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
            }
    
            if($hapus =='Y'){
             $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-name="'.strip_tags($aRow['lokasi_nama']).'" data-id="'.strip_tags(epm_encode($aRow['lokasi_id'])).'" title="Hapus">
             <i class="fas fa-trash"></i>
                </a>';
            }else{
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
             <i class="fas fa-trash"></i>
            </a>';
            }

            
            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = '<div class="text-center">'.$aRow['lokasi_id'].'</div>';
            $row[] = '<div class="text-center">
                        <a class="open-popup-link" href="../sw-content/lokasi/'.strip_tags(seo_title($aRow['lokasi_qrcode'])).'.jpg" title="'.strip_tags($aRow['lokasi_nama']??'-').'">
                        <img src="../sw-content/lokasi/'.strip_tags(seo_title($aRow['lokasi_qrcode'])).'.jpg" class="imaged w100 rounded" height="50">
                        </a>
                    </div>';
            $row[] = strip_tags($aRow['lokasi_nama']??'-');
            $row[] = strip_tags($aRow['lokasi_radius']??'-');
            $row[] = '<div class="text-center">'.$status.'</div>';
            $row[] = '<div class="text-center">'.$btn_update.''.$btn_hapus.'</div>';
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}