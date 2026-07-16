<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
    require_once'../../../sw-library/sw-config.php';
    require_once'../../../sw-library/sw-function.php';
    $total_bobot = 0; 
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
    $filterParts[] = "tanggal  BETWEEN '$from' AND '$to'";

    if (!empty($_POST['siswa'])) {
        $siswa = htmlentities(epm_decode($_POST['siswa']??'-'));
        $filterParts[] = "user_id='$siswa'";
    }
    
    if (!empty($_POST['kelas'])) {
        $kelas = htmlentities($_POST['kelas']);
        $filterParts[] = "kelas='$kelas'";
    }
    $filter = 'WHERE ' . implode(' AND ', $filterParts);

    $query_pelanggaran ="SELECT * FROM pelanggaran $filter";
    $result_pelanggaran = $connection->query($query_pelanggaran);
    if($result_pelanggaran->num_rows > 0){
    while($aRow = $result_pelanggaran->fetch_assoc()){$no++;

        $jumlah_bobot = $aRow['bobot'];
        $total_bobot += $jumlah_bobot;

        $query_siswa ="SELECT nama_lengkap,kelas FROM user WHERE user_id='$aRow[user_id]'";
        $result_siswa = $connection->query($query_siswa);
        $data_siswa = $result_siswa->fetch_assoc();


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
                'siswa' => strip_tags($data_siswa['nama_lengkap']??'-'),
                'kelas' => strip_tags($data_siswa['kelas']??'-'),
                'pelanggaran' => strip_tags($aRow['bentuk_pelanggaran']??'-'),
                'bobot' => '<span class="badge badge-danger">'.strip_tags($aRow['bobot']??'-').'</span>',
                'guru' => strip_tags($data_guru['nama_lengkap']??'-'),
                'tanggal' => tanggal_ind($aRow['tanggal']),
                'aksi' => '<div class="text-center">'.$btn_hapus.'</div>',
            ];
        }
    }else{
        $data[] = [
            'no' => $no,
            'siswa' => '-',
            'kelas' => '-',
            'pelanggaran' => '-',
            'bobot' => '-',
            'guru' => '-',
            'tanggal' => '-',
            'aksi' => '-',
        ];
    }

    $response = [
        'data' => $data,
        'jumlah_bobot' => $total_bobot,
    ];

    
    // Kirim response JSON untuk DataTables
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data,
        "jumlah_bobot"         => $total_bobot,
    ]);
  
}