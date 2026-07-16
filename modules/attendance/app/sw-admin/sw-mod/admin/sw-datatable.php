<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';
    require_once'../../login/user.php';

    $isCentralAbsensiManager = !empty($current_user['central_admin'])
      && in_array(($current_user['app_role'] ?? ''), ['superadmin', 'admin'], true);
    if (!$isCentralAbsensiManager) {
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak']);
        exit;
    }

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


    $aColumns = ['admin_id', 'fullname', 'email', 'phone','registrasi_date', 'tanggal_login', 'status','avatar','level','active'];
    $sIndexColumn = "admin_id";
    $sTable = "admin";
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

    $sOrder = "ORDER BY admin_id DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY admin_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY admin_id DESC")
        {
            $sOrder = "ORDER BY admin_id DESC";
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
        $onlick = "','";
        $onlick = explode(",",$onlick);

        $level = '-';
        $query_level ="SELECT level_nama FROM level WHERE level_id='$aRow[level]'";
        $result_level = $connection->query($query_level);
        if($result_level->num_rows > 0){
            $data_level = $result_level->fetch_assoc();
            $level = strip_tags($data_level['level_nama']??'-');
            if ((int)$aRow['level'] === 2) $level = 'Staf Absensi';
        }

        if($aRow['avatar'] == NULL OR $aRow['avatar']=='avatar.jpg'){
            $avatar ='<img src="./sw-assets/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
            }else{
            if(file_exists('../../../sw-content/avatar/'.$aRow['avatar'].'')){
                $avatar = '<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../../../sw-content/avatar/'.$aRow['avatar'].'')).'" class="imaged w100 rounded" height="50">';
            }else{
                $avatar ='<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
            }
        }

        if($aRow['active'] =='Y'){
            if($current_user['admin_id'] == $aRow['admin_id']){
                $active = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active" data-active="Y" disabled checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>';
            }else{
                $active = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active active'.$aRow['admin_id'].'" data-id="'.$aRow['admin_id'].'" data-active="Y" checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>';
            }

        }else{
             $active = '<label class="custom-toggle" style="display:inline-block">
            <input type="checkbox" class="btn-active active'.$aRow['admin_id'].'"  data-id="'.$aRow['admin_id'].'"  data-active="N">
            <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
          </label>';
        }

        if($aRow['status'] =='Online'){
            $status ='<small class="badge badge-dot text-info" style="font-size:13px;"><i class="bg-success"></i>Online</small>';
        }else{
            $status ='<small class="badge badge-dot" style="font-size:13px;"><i class="bg-danger"></i>Offline</small>';
        }

        $button ='<a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-forgot" data-name="'.strip_tags($aRow['fullname']).'" data-id="'.strip_tags(epm_encode($aRow['admin_id'])).'" data-toggle="tooltip" title="Resset Password">
                <i class="fas fa-key"></i>
            </a>

        <a href="javascript:void(0)" onClick="location.href='.$onlick[0].'admin&op=update&id='.epm_encode($aRow['admin_id']).''.$onlick[1].';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
            <i class="fas fa-edit"></i>
        </a>';
        if($current_user['admin_id'] == $aRow['admin_id']){
            $button_hapus ='
            <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" title="Hapus">
                <i class="fas fa-trash"></i>
            </a>';
        }else{
            $button_hapus ='
            <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-name="'.strip_tags($aRow['fullname']).'" data-id="'.strip_tags(epm_encode($aRow['admin_id'])).'" title="Hapus">
                <i class="fas fa-trash"></i>
                    </a>';
        }

        for ($i=1 ; $i<count($aColumns) ; $i++){
            if($current_user['admin_id'] == $aRow['admin_id']){
            $row[] = '<div class="text-center text-info">'.$no.'</div>';
            $row[] = '<div class="text-center text-info">'.$avatar.'</div>';
            $row[] = '<span class="text-info">'.strip_tags($aRow['fullname']).'<br>'.$status.'</span>';
            $row[] = '<span class="text-info">'.strip_tags($aRow['email']).'</span>';
            $row[] = '<span class="text-info">'.strip_tags($aRow['phone']).'</span>';
            $row[] = '<span class="text-info">'.$level.'</span>';
            $row[] = '<span class="text-info">'.strip_tags(tanggal_ind($aRow['registrasi_date'])).'</span>';
            $row[] = '<span class="text-info">'.strip_tags($aRow['tanggal_login']).'</span>';
            $row[] = '<div class="text-center">'.$active.'</div>';
            $row[] = '<div class="text-center">
                       '.$button.'
                       '.$button_hapus.'
                     </div>';
        }else{
            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = '<div class="text-center">'.$avatar.'</div>';
            $row[] = ''.strip_tags($aRow['fullname']).'<br>'.$status.'';
            $row[] = strip_tags($aRow['email']);
            $row[] = strip_tags($aRow['phone']);
            $row[] = $level;
            $row[] = strip_tags(tanggal_ind($aRow['registrasi_date']));
            $row[] = strip_tags($aRow['tanggal_login']);
            $row[] = '<div class="text-center">'.$active.'</div>';
            $row[] = '<div class="text-center">
                       '.$button.'
                       '.$button_hapus.'
                     </div>';
        
        }
            
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}
