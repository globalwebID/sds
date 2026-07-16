<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
    require_once'../../../sw-library/sw-config.php';
    include('../../../sw-library/sw-function.php');
    
    if(empty($_POST['tipe'] =='masuk')){
        $order = "absen_in";
    }else if(empty($_POST['tipe'] =='pulang')){
        $order = "absen_out";
    }else{
        $order = "absen_guru_id";
    }
    $aColumns = ['absen_guru_id', 'guru_id','absen_in','absen_out','status_masuk','status_pulang'];
    $sIndexColumn = "absen_guru_id";
    $sTable = "absen_guru";
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

    $sOrder = "ORDER BY $order DESC";
    if (isset($_GET['iSortCol_0']))
    {
        $sOrder = "ORDER BY $order DESC";
        for ($i=0; $i<intval($_GET['iSortingCols']) ; $i++)
        {
            if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY $order DESC")
        {
            $sOrder = "ORDER BY $order DESC";
        }
    }

    $sWhere = "WHERE tanggal='$date'";
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

        $query_guru ="SELECT nip,nama_lengkap,jabatan FROM guru WHERE guru_id='$aRow[guru_id]'";
        $result_guru = $connection->query($query_guru);
        if($result_guru->num_rows > 0 ){
            $data_guru = $result_guru->fetch_assoc();
            $nama_lengkap = strip_tags($data_guru['nama_lengkap']);
            $nip = strip_tags($data_guru['nip']);
        }else{
            $nip ='-';
            $nama_lengkap ='-';
        }

        if($aRow['status_masuk']=='Tepat Waktu'){
            $status_masuk ='<span class="badge badge-success">'.$aRow['status_masuk'].'</span>';
        }else{
            $status_masuk ='<span class="badge badge-danger">'.$aRow['status_masuk'].'</span>';
        }

        if($aRow['absen_out']=='00:00:00'){
            $status_pulang = '';
        }else{
            if($aRow['status_pulang']=='Tepat Waktu'){
                $status_pulang ='<span class="badge badge-success">'.$aRow['status_pulang'].'</span>';
            }else{
                $status_pulang ='<span class="badge badge-danger">'.$aRow['status_pulang'].'</span>';
            }
        }

       

        for ($i=1 ; $i<count($aColumns) ; $i++){

            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = strip_tags($nama_lengkap);
            $row[] = strip_tags($nip);
            $row[] = '<div class="text-center">'.$aRow['absen_in'].' '.$status_masuk.'</div>';
            $row[] = '<div class="text-center">'.$aRow['absen_out'].' '.$status_pulang.'</div>';
        }
        $output['aaData'][] = $row;
   
    }
    echo json_encode($output);
  
}