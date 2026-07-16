<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';
    $no = 0;
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
    $from   = isset($_POST['from']) ? date('Y-m-d', strtotime(strip_tags($_POST['from']))) : date('Y-m-d', strtotime($date));
    $to     = isset($_POST['to']) ? date('Y-m-d', strtotime(strip_tags($_POST['to']))) : date('Y-m-d', strtotime($date));
    $filterParts[] = "sanksi_pelanggaran.tanggal  BETWEEN '$from' AND '$to'";

    if (!empty($_POST['siswa'])) {
        $siswa = htmlentities(epm_decode($_POST['siswa']??'-'));
        $filterParts[] = "sanksi_pelanggaran.user_id='$siswa'";
    }
    
    if (!empty($_POST['kelas'])) {
        $kelas = htmlentities($_POST['kelas']);
        $filterParts[] = "user.kelas='$kelas'";
    }
    $filter = 'WHERE ' . implode(' AND ', $filterParts);

    $query ="SELECT sanksi_pelanggaran.*,user.user_id,user.nama_lengkap,user.kelas FROM sanksi_pelanggaran
    LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id $filter";
    $result = $connection->query($query);
    if($result->num_rows > 0){
    while($aRow = $result->fetch_assoc()){$no++;

        /** Data Guru */
        $query_guru ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$aRow[pegawai_id]'";
        $result_guru = $connection->query($query_guru);
        $data_guru = $result_guru->fetch_assoc();

        if($hapus =='Y'){
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right" title="Hapus" data-id="'.convert('encrypt', $aRow['pelanggaran_id']??'-').'">
            <i class="fas fa-trash"></i>
            </a>';
        }else{
            $btn_hapus ='<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" data-placement="right" title="Hapus">
            <i class="fas fa-trash"></i>
            </a>';
        }
   
            $data[] = [
                'no' => $no,
                'siswa' => strip_tags($aRow['nama_lengkap']??'-'),
                'kelas' => strip_tags($aRow['nama_kelas']??'-'),
                'perihal' => ''.strip_tags($aRow['kode_surat']??'-').'<br>'.strip_tags($aRow['perihal']??'-'),
                'guru' => strip_tags($data_guru['nama_lengkap']??'-'),
                'tanggal' => tanggal_ind($aRow['tanggal']),
                'aksi' => '<div class="text-center"><a href="../print-sanksi?id='.convert("encrypt",$aRow['id']).'" class="table-action table-action-info" target="_blank">
                    <i class="fas fa-print"></i></i>
                  </a>
                  '.$btn_hapus.'</div>',
            ];
        }
    }else{
        $data[] = [
            'no' => '-',
            'siswa' => '-',
            'kelas' => '-',
            'perihal' => '-',
            'guru' => '-',
            'tanggal' => '-',
            'aksi' => '-',
        ];
    }

    $response = [
        'data' => $data,
    ];

    
    // Kirim response JSON untuk DataTables
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data,
    ]);
  
}