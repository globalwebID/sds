<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';
    $onlick = "','";
    $onlick = explode(",",$onlick);

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

    $aColumns = ['wali_murid_id', 'nama_lengkap', 'nisn', 'nama_siswa', 'jenis_kelamin', 'avatar','tanggal_registrasi','tanggal_login', 'status'];
    $sIndexColumn = "wali_murid_id";
    $sTable = "wali_murid";
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

    $sOrder = "ORDER BY wali_murid_id DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY wali_murid_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY wali_murid_id DESC")
        {
            $sOrder = "ORDER BY wali_murid_id DESC";
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

        if(file_exists('../../../sw-content/avatar/'.($aRow['avatar']??'a.jpg').'')){
           $avatar ='
            <a class="open-popup-link" href="../sw-content/avatar/'.strip_tags($aRow['avatar']??'-').'">
                <img src="../sw-content/avatar/'.strip_tags($aRow['avatar']??'-').'" class="imaged w100 rounded-circle" height="50">
            </a>';
        }else{
            $avatar ='<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded-circle" height="50">';
        }

        if($aRow['status'] =='Online'){
            $status ='<small class="badge badge-dot" style="font-size:13px;"><i class="bg-success"></i>Online</small>';
        }else{
            $status ='<small class="badge badge-dot" style="font-size:13px;"><i class="bg-danger"></i>Offline</small>';
        }

        if($modifikasi =='Y'){
            
            $btn_update = '<a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-forgot"  data-id="'.strip_tags(epm_encode($aRow['wali_murid_id']??'-')).'" data-toggle="tooltip" title="Resset Password">
                <i class="fas fa-key"></i>
            </a>

            <a href="javascript:void(0)" onClick="location.href='.$onlick[0].'wali-murid&op=update&id='.htmlentities(convert('encrypt', $aRow['wali_murid_id']??'-')).''.$onlick[1].';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
        }else{
            $btn_update ='
             <a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Resset Password">
                <i class="fas fa-key"></i>
            </a>
        
            <a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
        }

        if($hapus =='Y'){
         $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-id="'.strip_tags(epm_encode($aRow['wali_murid_id']??'')).'" title="Hapus">
            <i class="fas fa-trash"></i>
            </a>';
        }else{
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
            <i class="fas fa-trash"></i>
            </a>';
        }

    
        for ($i=1 ; $i<count($aColumns) ; $i++){
            
            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = '<div class="text-center">'.$avatar.'</div>';
            $row[] = '<b>'.strip_tags($aRow['nama_lengkap']??'-').'</b>';
            $row[] = '<b>'.strip_tags($aRow['nama_siswa']??'-').'</b><br>NISN.'.strip_tags($aRow['nisn']??'-').'';
            $row[] = strip_tags($aRow['jenis_kelamin']??'-');
            $row[] = date('d-m-Y', strtotime($aRow['tanggal_registrasi']));
            $row[] = date('d-m-Y', strtotime($aRow['tanggal_login']));
            $row[] = '<div class="text-center">'.$status.'</div>';
            $row[] = '<div class="text-center">
                       '.$btn_update.''.$btn_hapus.'
                  </div>';
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}