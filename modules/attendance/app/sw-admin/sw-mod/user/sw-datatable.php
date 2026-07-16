<?php
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';
    require_once'../../../sw-library/phpqrcode/qrlib.php'; 
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

    function countPerangkat($userId, $connection) {
        $query = "SELECT COUNT(user_id) AS total FROM perangkat WHERE user_id = $userId";
        $result = $connection->query($query);
        return $result->fetch_assoc()['total'];
    }

    $aColumns = ['user_id', 'nisn', 'rfid', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'kelas', 'avatar','status','tanggal_login', 'active'];
    $sIndexColumn = "user_id";
    $sTable = "user";
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

    $sOrder = "ORDER BY user_id DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY user_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY user_id DESC")
        {
            $sOrder = "ORDER BY user_id DESC";
        }
    }

    $sWhere = "WHERE active='Y'";
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

        $data_kelas = NULL;
        $query_kelas ="SELECT nama_kelas FROM absensi_kelas WHERE nama_kelas='$aRow[kelas]'";
        $result_kelas = $connection->query($query_kelas);
        $data_kelas = $result_kelas->fetch_assoc();


        if(file_exists('../../../sw-content/avatar/'.($aRow['avatar']??'a.jpg').'')){
           $avatar ='
            <a class="open-popup-link" href="../sw-content/avatar/'.strip_tags($aRow['avatar']??'-').'">
                <img src="../sw-content/avatar/'.strip_tags($aRow['avatar']??'-').'" class="imaged w100 rounded-circle" height="50">
            </a>';
        }else{
            $avatar ='<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded-circle" height="50">';
        }

        if($aRow['active'] =='Y'){
            $active = '<label class="custom-toggle" style="display:inline-block">
            <input type="checkbox" class="btn-active active'.$aRow['user_id'].'" data-id="'.$aRow['user_id'].'" data-active="Y" checked>
                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
          </label>';
        }else{
            $active = '<label class="custom-toggle" style="display:inline-block">
            <input type="checkbox" class="btn-active active'.$aRow['user_id'].'"  data-id="'.$aRow['user_id'].'"  data-active="N">
            <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
          </label>';
        }

        if($aRow['status'] =='Online'){
            $status ='<small class="badge badge-dot" style="font-size:13px;"><i class="bg-success"></i>Online</small>';
        }else{
            $status ='<small class="badge badge-dot" style="font-size:13px;"><i class="bg-danger"></i>Offline</small>';
        }

        if($aRow['active'] =='Y'){
            $active ='<span class="badge badge-info">Aktif</span>';
        }else{
            $active ='<span class="badge badge-info">Tidak Aktif</span>';
        }

        $nisn = $aRow['nisn'];
        $filepath = "../../../sw-content/qrcode/$nisn.png";
        if ($nisn && !file_exists($filepath)) {
            QRCode::png($nisn, $filepath, 'QR_ECLEVEL_Q', 10, 1);
        }


        if($modifikasi =='Y'){
            
            $btn_update = '<a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-forgot"  data-id="'.strip_tags(epm_encode($aRow['user_id']??'-')).'" data-toggle="tooltip" title="Resset Password">
                <i class="fas fa-key"></i>
            </a>

            <a href="javascript:void(0)" onClick="location.href='.$onlick[0].'user&op=update&id='.htmlentities(convert('encrypt', $aRow['user_id']??'-')).''.$onlick[1].';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
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
         $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-id="'.strip_tags(epm_encode($aRow['user_id']??'')).'" title="Hapus">
            <i class="fas fa-trash"></i>
            </a>';
        }else{
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
            <i class="fas fa-trash"></i>
            </a>';
        }

        for ($i=1 ; $i<count($aColumns) ; $i++){
            $row[] = '<div class="text-center">
                <input name="id[]" value="'.$aRow['user_id'].'" type="checkbox">   
            </div>';
            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = '<div class="text-center">
                      <a class="open-popup-link" href="../sw-content/qrcode/'.strip_tags($aRow['nisn']).'.png" title="'.strip_tags($aRow['nama_lengkap']).'">
                        <img src="../sw-content/qrcode/'.strip_tags($aRow['nisn']).'.png" class="imaged w100 rounded-circle" height="50">
                      </a>
                  </div>';
            $row[] = '<b>'.strip_tags($aRow['nama_lengkap']??'-').'</b>';
            $row[] = ''.strip_tags($aRow['nisn']??'-').'<br>RFID. '.($aRow['rfid']??'-').'';
            $row[] = strip_tags($aRow['jenis_kelamin']??'-');
            $tl = trim($aRow['tanggal_lahir'] ?? '');
            $row[] = $tl && $tl !== '-' ? tanggal_ind($tl) : '-';
            $row[] = ($data_kelas['nama_kelas']??'-');
            $row[] = ''.date('d-m-Y', strtotime($aRow['tanggal_login'])).'<br>'.date('H:i:s', strtotime($aRow['tanggal_login'])).'';
            $row[] = '<div class="text-left">'.$active.'<br>'.$status.'</div>';
            $row[] = '<div class="text-center">
                        <a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-reset-qrcode" data-id="'.strip_tags(epm_encode($aRow['user_id']??'')).'" data-toggle="tooltip" title="Reset Qrcode">
                            <i class="fas fa-qrcode"></i>
                        </a>
                        '.$btn_update.''.$btn_hapus.'
                  </div>';
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}