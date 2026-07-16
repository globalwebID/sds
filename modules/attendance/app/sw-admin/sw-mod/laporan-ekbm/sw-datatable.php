<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
    require_once '../../../sw-library/sw-config.php';
    require_once '../../../sw-library/sw-function.php';

    $tanggal = !empty($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : $date;
    $kelas  = !empty($_POST['kelas']) ? anti_injection($_POST['kelas']) : null;
    $pelajaran  = !empty($_POST['pelajaran']) ? anti_injection($_POST['pelajaran']) : null;

    $filter = "WHERE absen_ekbm.tanggal='$tanggal'";
    if ($kelas) $filter .= " AND user.kelas='$kelas'";
    if ($pelajaran) $filter .= " AND absen_ekbm.pelajaran='$pelajaran'";

    $aColumns = [
        'absen_ekbm.*',
        'user.nisn',
        'user.nama_lengkap',
        'pegawai.nama_lengkap AS nama_pegawai'
    ];

    $sIndexColumn = "absen_ekbm.absen_id";
    $sTable = "absen_ekbm";
    $sJoin  = "LEFT JOIN user ON user.user_id = absen_ekbm.user_id
                LEFT JOIN pegawai ON pegawai.pegawai_id = absen_ekbm.pegawai";

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

    $sOrder = "ORDER BY user.nama_lengkap ASC";
    if (isset($_POST['iSortCol_0']))
    {
        $sOrder = "ORDER BY user.nama_lengkap ASC";
        for ($i=0; $i<intval($_POST['iSortingCols']) ; $i++)
        {
            if ($_POST['bSortable_'.intval($_POST['iSortCol_'.$i])] == "true")
            {
                $sOrder .= $aColumns[ intval($_POST['iSortCol_'.$i])]."
                    ".mysqli_real_escape_string($gaSql['link'], $_POST['sSortDir_'.$i]) .", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY user.nama_lengkap ASC")
        {
            $sOrder = "ORDER BY user.nama_lengkap ASC";
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
    $data_matkul =NULL;
    while ($aRow = mysqli_fetch_array($rResult)){$no++;
      extract($aRow);
        $row = array();
        for ($i=1 ; $i<count($aColumns) ; $i++){

            $query_matkul = "SELECT id, nama_mapel FROM mata_pelajaran WHERE id='$aRow[pelajaran]'";
            $result_matkul = $connection->query($query_matkul);
            $data_matkul = $result_matkul->fetch_assoc();

            $kehadiran =  $aRow['keterangan'];
            $keterangan_map = [
                'H' => 'Hadir',
                'A' => 'Tidak Hadir',
                'I' => 'Ijin',
                'S' => 'Sakit',
                'N' => 'Belum Absen'
            ];
             
            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = strip_tags($aRow['nisn']??'-');
            $row[] = strip_tags($aRow['nama_lengkap']??'-');
            $row[] = '<span class="badge bg-primary text-white">'.$aRow['kelas'].'</span>';
            $row[] = ''.$data_matkul['nama_mapel'].'<br>
                    <span class="badge badge-warning" style="text-transform:inherit">'.($aRow['nama_pegawai']??'-').'</span>';
            $row[] = $keterangan_map[$kehadiran] ?? 'Status Tidak Diketahui';
        }
        $output['aaData'][] = $row;
   
    }

    echo json_encode($output);
  
}