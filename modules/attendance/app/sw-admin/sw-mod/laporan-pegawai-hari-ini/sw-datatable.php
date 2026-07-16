<?php 
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
    require_once '../../../sw-library/sw-config.php';
    require_once '../../../sw-library/sw-function.php';

    $tanggal = !empty($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : $date;
    $lokasi  = !empty($_POST['lokasi']) ? convert('decrypt',$_POST['lokasi']) : null;

    $filter = "WHERE absen_pegawai.tanggal='$tanggal'";
    if ($lokasi) $filter .= " AND pegawai.lokasi='$lokasi'";
    $filterUser= NULL;
    if ($lokasi) $filterUser .= " AND pegawai.lokasi='$lokasi'";

    $aColumns = [
        'absen_pegawai.*',
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

        $queryBelumAbsen = "SELECT COUNT(*) AS jumlah_belum_absen FROM pegawai
        LEFT JOIN absen_pegawai ON absen_pegawai.pegawai_id = pegawai.pegawai_id AND absen_pegawai.tanggal = '$tanggal'
        WHERE (1=1) $filterUser AND absen_pegawai.pegawai_id IS NULL";

        $resultBelumAbsen = mysqli_query($connection, $queryBelumAbsen);
        $rowBelumAbsen = mysqli_fetch_assoc($resultBelumAbsen);
        $jumlahBelumAbsen = $rowBelumAbsen['jumlah_belum_absen'];


        $foto = strip_tags($aRow['foto_in']??'avatar.jpg');
        if (!empty($foto) AND file_exists('../../../sw-content/absen/'.$foto.'')) {
            $foto_masuk ='<a href="../sw-content/absen/'.$foto.'" class="open-popup-link">
                            <img src="../sw-content/absen/'.$foto.'" class="avatar rounded-circle mr-3" height="50">
                        </a>';
        } else {
            $foto_masuk = '<a href="#" class="avatar rounded-circle mr-3">
                <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">
            </a>';
        }

            $foto_out = strip_tags($aRow['foto_out']??'avatar.jpg');
            if (!empty($foto_out) AND file_exists('../../../sw-content/absen/'.$foto_out.'')) {
                $foto_pulang ='<a href="../sw-content/absen/'.$foto_out.'" class="open-popup-link">
                                <img src="../sw-content/absen/'.$foto_out.'" class="avatar rounded-circle mr-3" height="50">
                            </a>';
            } else {
                $foto_pulang = '<a href="#" class="avatar rounded-circle mr-3">
                    <img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">
                </a>';
            }

            $status_masuk  = '<span class="badge badge-'.($aRow['status_masuk'] == 'Tepat Waktu' ? 'success' : 'danger').'">'.$aRow['status_masuk'].'</span>';
            $status_pulang = '<span class="badge badge-'.($aRow['status_pulang'] == 'Tepat Waktu' ? 'success' : 'danger').'">'.$aRow['status_pulang'].'</span>';

            $map_in = ($aRow['map_in'] && $aRow['map_in'] != '-') 
          ? '<a href="https://www.google.com/maps/place/'.$aRow['map_in'].'" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-map-marker-alt"></i> IN</a>' 
          : '-';

            $map_out = ($aRow['map_out'] && $aRow['map_out'] != '-') 
           ? '<a href="https://www.google.com/maps/place/'.$aRow['map_out'].'" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-map-marker-alt"></i> OUT</a>' 
           : '';

            $row[] = '<div class="text-center">'.$no.'</div>';
            $row[] = strip_tags($aRow['nama_lengkap']??'-');
            $row[] = ucfirst($aRow['jabatan']??'-');
            $row[] = '<div class="media align-items-center">
                         '.$foto_masuk.'
                        <div class="media-body">
                          <span class="name mb-0 text-sm">'.$aRow['absen_in'].'<br>'.$status_masuk.'</span>
                        </div>
                      </div>';

            $row[] = '<div class="media align-items-center">
                         '.$foto_pulang.'
                        <div class="media-body">
                          <span class="name mb-0 text-sm">'.$aRow['absen_out'].'<br>'.$status_pulang.'</span>
                        </div>
                      </div>';
                      
                      
            $row[] = ($aRow['radius'] ?? '-');
            $row[] = ''.$map_in.''.$map_out;
        }
        $output['aaData'][] = $row;
   
    }
   
    $output['jumlah_belum_absen'] = $jumlahBelumAbsen??'0';
    echo json_encode($output);
  
}