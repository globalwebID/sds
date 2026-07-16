<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
    require_once '../../../sw-library/sw-config.php';
    require_once '../../../sw-library/sw-function.php';

    $tanggal = !empty($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : $date;
    $filter = "WHERE absen_pegawai.tanggal='$tanggal'";
 
    $aColumns = [
        'absen_pegawai.pegawai_id',
        'tanggal',
        'absen_pegawai.absen_in',
        'absen_pegawai.absen_out',
        'status_masuk',
        'status_pulang',
        'pegawai.nip',
        'pegawai.nama_lengkap',
        'pegawai.jabatan',
    ];

    $sIndexColumn = "absen_pegawai.absen_id";
    $sTable = "absen_pegawai";
    $sJoin  = "LEFT JOIN pegawai ON pegawai.pegawai_id = absen_pegawai.pegawai_id";

    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1')
    {
        $sLimit = "LIMIT ".mysqli_real_escape_string($gaSql['link'], $_POST['iDisplayStart']).", ".
            mysqli_real_escape_string($gaSql['link'], $_POST['iDisplayLength']);
    }

    $sOrder = "ORDER BY tanggal DESC";
    if (isset($_POST['iSortCol_0']))
    {
        $sOrder = "ORDER BY tanggal DESC";
        for ($i=0; $i<intval($_POST['iSortingCols']) ; $i++)
        {
            if ($_POST['bSortable_'.intval($_POST['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_POST['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_POST['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY tanggal DESC")
        {
            $sOrder = "ORDER BY tanggal DESC";
        }
    }

    $sWhere ="$filter";
    if (isset($_POST['sSearch']) && $_POST['sSearch'] != "")
    {
        $sWhere = "WHERE (";
        for ($i=0; $i<count($aColumns); $i++)
        {
            $sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($gaSql['link'], $_POST['sSearch'])."%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    for ($i=0 ; $i<count($aColumns); $i++)
    {
        if (isset($_POST['bSearchable_'.$i]) && $_POST['bSearchable_'.$i] == "true" && $_POST['sSearch_'.$i] != '')
        {
            if ($sWhere == "")
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            $sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($gaSql['link'], $_POST['sSearch_'.$i])."%' ";
        }
    }

    $sQuery = " SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
        FROM $sTable
        $sJoin
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
       // "sEcho" => intval($_POST['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)){$no++;
      extract($aRow);
        $row = array();
        for ($i=1 ; $i<count($aColumns) ; $i++){
            
            $status_masuk_val  = $aRow['status_masuk'] ?? '';
            $status_pulang_val = $aRow['status_pulang'] ?? '';

            if (strtolower($status_masuk_val) === 'tepat waktu') {
                $cls_masuk = 'success';
            } elseif (strtolower($status_masuk_val) === 'izin') {
                $cls_masuk = 'warning';
            } else {
                $cls_masuk = 'danger';
            }

            if (strtolower($status_pulang_val) === 'tepat waktu') {
                $cls_pulang = 'success';
            } elseif (strtolower($status_pulang_val) === 'izin') {
                $cls_pulang = 'warning';
            } else {
                $cls_pulang = 'danger';
            }

            $status_masuk  = '<span class="badge badge-'.$cls_masuk.'">'.htmlspecialchars($status_masuk_val).'</span>';
            $status_pulang = '<span class="badge badge-'.$cls_pulang.'">'.htmlspecialchars($status_pulang_val).'</span>';


            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = ''.strip_tags($aRow['nama_lengkap']??'-').'<br>
                    <span class="badge badge-info">'.strip_tags($aRow['nip']??'-').'</span>';
            $row[] = ''.($aRow['absen_in']??'-').'<br>'.$status_masuk.'</span>';
            $row[] = ''.($aRow['absen_out']??'-').'<br>'.$status_pulang.'</span>';  
        }
        $output['aaData'][] = $row;
   
    }

    echo json_encode($output);
  
}