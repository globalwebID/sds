<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:../login');
  exit;
}
else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';

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


    $aColumns = ['kartu_id', 'nama','foto','tipe', 'active'];
    $sIndexColumn = "kartu_id";
    $sTable = "kartu_nama";
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

    $sOrder = "ORDER BY kartu_id DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY kartu_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY kartu_id DESC")
        {
            $sOrder = "ORDER BY kartu_id DESC";
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

            if(file_exists('../../../sw-content/tema/'.$aRow['foto'].'')){
                $foto = '<img src="../sw-content/tema/'.strip_tags($aRow['foto']).'" class="imaged w100 img-60 rounded" height="50">';
            }else{
                $foto = '<img src="./sw-assets/img/thumbnail.jpg" class="imaged w100 img-60 rounded" height="50">';
            }

            if($aRow['tipe'] =='P'){
                $tipe = 'Portrait';
            }else{
                $tipe = 'Landscape';
            }

            if($aRow['active'] =='Y'){
                $active = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active active'.$aRow['kartu_id'].'" data-id="'.$aRow['kartu_id'].'" data-active="Y" checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>';
            }else{
                 $active = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active active'.$aRow['kartu_id'].'"  data-id="'.$aRow['kartu_id'].'"  data-active="N">
                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>';
            }

            if($modifikasi =='Y'){
                $btn_update = '<a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" data-toggle="tooltip"  data-placement="right" title="Edit" data-id="'.epm_encode($aRow['kartu_id']).'">
                <i class="fas fa-edit"></i>
            </a>';
            }else{
                $btn_update ='<a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
            }
    
            if($hapus =='Y'){
                $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right" title="Hapus" data-id="'.epm_encode($aRow['kartu_id']).'">
                <i class="fas fa-trash"></i>
                </a>';
            }else{
                $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
                <i class="fas fa-trash"></i>
                </a>';
            }

            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = '<div class="text-center">
                        <a class="open-popup-link" href="../sw-content/tema/'.strip_tags($aRow['foto']).'" title="'.strip_tags($aRow['nama']).'">
                            '.$foto.'
                        </a>
                    </div>';
            $row[] = strip_tags($aRow['nama']??'-');
            $row[] = strip_tags($tipe??'-');
            $row[] = '<div class="text-center">'.$active.'</div>';
            $row[] = '<div class="text-center">'.$btn_update.''.$btn_hapus.'</div>';
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}