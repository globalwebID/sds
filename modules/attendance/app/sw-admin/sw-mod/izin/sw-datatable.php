<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
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

    $aColumns = ['izin_id', 'user_id', 'tanggal', 'tanggal_selesai', 'alasan', 'keterangan', 'files','time', 'date','status'];
    $sIndexColumn = "izin_id";
    $sTable = "izin";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1'){
        $sLimit = "LIMIT ".mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayStart']).", ".
            mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayLength']);
    }

    $sOrder = "ORDER BY izin_id DESC";
    if (isset($_GET['iSortCol_0'])){
        $sOrder = "ORDER BY izin_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY izin_id DESC")
        {
            $sOrder = "ORDER BY izin_id DESC";
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
$data_siswa =NULL;
while ($aRow = mysqli_fetch_array($rResult)){$no++;
    extract($aRow);
    $row = array();
    for ($i=1 ; $i<count($aColumns) ; $i++){

        $query_siswa = "SELECT user_id,nama_lengkap,kelas FROM user WHERE user_id='$aRow[user_id]'";
        $result_siswa = $connection->query($query_siswa);
        if($result_siswa->num_rows > 0) {
            $data_siswa = $result_siswa->fetch_assoc();
            $nama_lengkap = $data_siswa['nama_lengkap'];
        }else{
            $nama_lengkap ='-';
        }

        if($aRow['files']==''){
            $files ='<img src="../sw-content/thumbnail.jpg" class="imaged w100 avatar rounded-circle" height="50">';
        }else{
            if(!file_exists('../../../sw-content/izin/'.($aRow['files']??'-.jpg').'')){
                $files ='<img src="../sw-content/thumbnail.jpg" class="imaged w100 avatar rounded-circle" height="50">';
            }else{
                $files ='<a class="open-popup-link" href="../sw-content/izin/'.strip_tags($aRow['files']).'" target="_blank">
                <img src="../sw-content/izin/'.strip_tags($aRow['files']).'" class="imaged w100 avatar rounded-circle" height="50">
                </a>';
            }
        }

        
        if($aRow['status'] == 'PENDING'){
            $status = '<span class="badge'.htmlspecialchars($aRow['izin_id']).' badge badge-info">Panding</span>';
        }elseif($aRow['status'] == 'Y'){
            $status = '<span class="badge'.htmlspecialchars($aRow['izin_id']).' badge badge-success">Diterima</span>';
        }elseif($aRow['status'] == 'N'){
            $status = '<span class="badge'.htmlspecialchars($aRow['izin_id']).' badge badge-danger">Ditolak</span>';
        }

        $btn_status ='
        <div class="dropdown">
            <a class="btn btn-sm btn-icon-only text-light" href="javascript:void();" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-ellipsis-v"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                <a class="dropdown-item btn-stts-setuju '.($aRow['status'] == 'Y' ? 'active' : '').'" href="javascript:void(0)" data-id="'.epm_encode($aRow['izin_id']).'" data-status="Y">Diterima</a>
                <a class="dropdown-item btn-stts-tolak '.($aRow['status'] == 'N' ? 'active' : '').'" href="javascript:void(0)" data-id="'.epm_encode($aRow['izin_id']).'" data-status="N">Ditolak</a>
            </div>
        </div>';

        if($modifikasi =='Y'){
            $btn_update = '<a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-dropdown-update btn-tooltip" data-toggle="tooltip"  data-placement="right" title="Edit" data-id="'.epm_encode($aRow['izin_id']).'">
            <i class="fas fa-edit"></i>
            </a>';
        }else{
            $btn_update ='<a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Edit">
            <i class="fas fa-edit"></i>
            </a>';
        }

        if($hapus =='Y'){
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right" title="Hapus" data-id="'.epm_encode($aRow['izin_id']).'">
            <i class="fas fa-trash"></i>
            </a>';
        }else{
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
            <i class="fas fa-trash"></i>
            </a>';
        }

        $row[] = '<div class="text-center">'.$no.'</div>';
        $row[] = ''.$nama_lengkap.'<br><span class="badge badge-info">'.($data_siswa['kelas']??'-').'</span>';
        $row[] = ''.tanggal_ind($aRow['tanggal']).' s.d '.tanggal_ind($aRow['tanggal_selesai']).'';
        $row[] = strip_tags($aRow['alasan']??'-');
        $row[] = strip_tags($aRow['keterangan']??'-');
        $row[] = '<div class="text-center">'.$files.'</div>';
        $row[] = tanggal_ind($aRow['date']??'-');
        $row[] = '<div class="text-center">
                    '.$status.''.$btn_status.'
                </div>';
        $row[] = '<div class="text-left">'.$btn_update.''.$btn_hapus.'</div>';
    }
    $output['aaData'][] = $row;   
}
    echo json_encode($output);
  
}