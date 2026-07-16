<?php 
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

    $filterParts = [];
    $hari  = isset($_POST['hari']) ? strip_tags($_POST['hari']) : '-';
    $filterParts[] = "hari='$hari'";


    if (!empty($_POST['mata_pelajaran'])) {
        $mata_pelajaran = htmlentities($_POST['mata_pelajaran']);
        $filterParts[] = "mata_pelajaran='$mata_pelajaran'";
    }

    if (!empty($_POST['pegawai'])) {
        $pegawai = htmlentities($_POST['pegawai']);
        $filterParts[] = "pegawai='$pegawai'";
    }

    if (!empty($_POST['kelas'])) {
        $kelas= htmlentities($_POST['kelas']);
        $filterParts[] = "kelas='$kelas'";
    }

    $filter = 'WHERE ' . implode(' AND ', $filterParts);

    $aColumns = ['jadwal_id', 'hari', 'pegawai', 'mata_pelajaran', 'tingkat', 'kelas', 'dari_jam', 'sampai_jam'];

    $sIndexColumn = "jadwal_id";
    $sTable = "jadwal_mengajar";
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

    $sOrder = "ORDER BY jadwal_id DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY jadwal_id DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY jadwal_id DESC")
        {
            $sOrder = "ORDER BY jadwal_id DESC";
        }
    }

    $sWhere = "$filter";
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

            $query = "SELECT 
                mata_pelajaran.nama_mapel, 
                pegawai.nama_lengkap
            FROM jadwal_mengajar
            LEFT JOIN mata_pelajaran ON jadwal_mengajar.mata_pelajaran = mata_pelajaran.id
            LEFT JOIN pegawai ON jadwal_mengajar.pegawai = pegawai.pegawai_id
            WHERE jadwal_mengajar.jadwal_id = '$aRow[jadwal_id]'";
            $result = $connection->query($query);
            $data = $result->fetch_assoc();
            $data_matkul = $data['nama_mapel'];
            $data_guru = $data['nama_lengkap'];

            if($modifikasi =='Y'){
                $btn_update = '<a href="javascript:void(0)" data-id="'.htmlentities(convert('encrypt', $aRow['jadwal_id']??'-')).'" class="table-action table-action-primary btn-update" data-toggle="tooltip" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>';
            }else{
                $btn_update ='<a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip"  data-placement="right" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>';
            }

            if($hapus =='Y'){
                $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-id="'.htmlentities(convert('encrypt', $aRow['jadwal_id']??'-')).'" title="Hapus">
                    <i class="fas fa-trash"></i>
                </a>';
            }else{
                $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
                <i class="fas fa-trash"></i>
                </a>';
            }

            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = ''.($aRow['hari']??'-').'<br>'.($aRow['dari_jam']??'-').' - '.($aRow['sampai_jam']??'-').'';
            $row[] = '<span class="badge badge-dark text-white">'.$aRow['tingkat'].'</span>
                      <span class="badge bg-primary text-white">'.$aRow['kelas'].'</span>';
            $row[] = ''.($data_matkul??'-').'<br>
                    <span class="badge badge-warning" style="text-transform:inherit">'.($data_guru??'-').'</span>';
            $row[] = '<div class="text-center">
                       '.$btn_update.''.$btn_hapus.'
                  </div>';
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}