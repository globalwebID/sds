<?php header('Content-Type: application/json');
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
    $no=0;
    $data_absen = null; 
    $data = [];
    $jumlah_alpha = 0;
    $jumlah_belum_absen = 0;
    $jumlah_libur_kantor = 0;
    $jumlah_libur_nasional = 0;
    $result_libur = '';
    $status = '-';


    $lokasi     = !empty($_GET['lokasi']) ? anti_injection($_GET['lokasi']) : null;
    $pegawai    = !empty($_GET['pegawai']) ? anti_injection(epm_decode($_GET['pegawai'])) : null;
    $jabatan    = !empty($_GET['jabatan']) ? anti_injection($_GET['jabatan']) : 'guru';
    $from       = !empty($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : $date;
    $to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
   
    
    if(!empty($_GET['pegawai']) && !empty($_GET['lokasi']) && !empty($_GET['jabatan'])){
        $filter = "WHERE pegawai.lokasi_id='$lokasi' AND pegawai.pegawai_id='$pegawai' AND pegawai.jabatan='$jabatan'";

    }elseif(!empty($_GET['lokasi'])){
        $filter= "WHERE pegawai.lokasi_id='$lokasi' AND  pegawai.active='Y'";

    }elseif(!empty($_GET['pegawai']) && !empty($_GET['jabatan'])){
        $filter = "WHERE pegawai.pegawai_id='$pegawai' AND pegawai.jabatan='$jabatan'";

    }else{
        $filter = "WHERE pegawai.jabatan='$jabatan'";
    }

    //echo $filter;
    function cekLibur($connection, $tanggal) {
        $query = "SELECT keterangan FROM libur_nasional WHERE libur_tanggal ='$tanggal'";
        $result = $connection->query($query);
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            return [
                'is_libur' => true,
                'keterangan' => strip_tags($data['keterangan'] ?? ''),
                'status_libur' => 'danger'
            ];
        }

        return [
            'is_libur' => false,
            'keterangan' => '',
            'status_libur' => ''
        ];
    }

    function countAbsen($connection, $filter, $kolom, $nilai) {
        $query = "SELECT COUNT(*) as jumlah FROM absen_pegawai WHERE $filter AND $kolom = '$nilai'";
        $result = $connection->query($query);
        $data = $result->fetch_assoc();
        return (int) ($data['jumlah'] ?? 0);
    }

    // Memanggil fungsi
 
    $englishToIndonesianDays = [
        'monday'    => 'senin',
        'tuesday'   => 'selasa',
        'wednesday' => 'rabu',
        'thursday'  => 'kamis',
        'friday'    => 'jumat',
        'saturday'  => 'sabtu',
        'sunday'    => 'minggu'
    ];
    $libur_hari = [];
    $startDate = new DateTime($from);
    $endDate = new DateTime($to);


    $tempDate = clone $startDate;
    while ($tempDate <= $endDate) {
        $dateStr = $tempDate->format('Y-m-d');

        $dayNameEnglish = strtolower($tempDate->format('l'));
        $dayName = $englishToIndonesianDays[$dayNameEnglish];
        $hari_libur = date('D',strtotime($dateStr));

        $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='$jabatan'";
        $result_libur = $connection->query($query_libur);
        if ($result_libur && $result_libur->num_rows > 0) {
            while ($row_libur = $result_libur->fetch_assoc()) {
                // Simpan dalam format lowercase untuk memudahkan perbandingan
                $libur_hari[] = strtolower($row_libur['hari']);
            }
        }

        $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
        $liburInfo = cekLibur($connection, $dateStr);
        /** End Cek Libur */

        if ($holidayName=='Libur') {
            $result_libur = 'danger'; 
            $kehadiran = 'Libur';
            $jumlah_libur_kantor++; // Libur kantor
        } else {
            if ($liburInfo['is_libur']) {
                $result_libur = 'danger'; // Libur nasional, beri status_libur merah
                $kehadiran = $liburInfo['keterangan'];
                $jumlah_libur_nasional++;
            }else{
                 $result_libur='';
            }
        }
        $tempDate->modify('+1 day');
    }

    
    $query_pegawai = "SELECT pegawai_id,nama_lengkap,jabatan FROM pegawai $filter";
    $result_pegawai = $connection->query($query_pegawai);
    $pegawaiData = [];    
    /** Start Pegawai */
    while ($pegawai = $result_pegawai->fetch_assoc()) {$no++;
        $pegawaiEntry['hadir'] = $pegawaiEntry['izin'] = $pegawaiEntry['sakit'] = $pegawaiEntry['telat'] = 0;

        $pegawaiEntry = [
            'no' => $no,  // Nomor urut
            'nama_pegawai' => $pegawai['nama_lengkap'],
            'jabatan' => ucfirst($pegawai['jabatan']),
            'keterangan' => 'Hadir',  // Default keterangan
            'tanggal' => [],
            'result_libur' => '',  // hanya 1 status untuk 1 baris
            
            'hadir' => 0, // Menyimpan jumlah hadir
            'telat' => 0, // Menyimpan jumlah telat
            'izin' => 0,   // Menyimpan jumlah izin
            'sakit' => 0,   // Menyimpan jumlah sakit
            'alpha' => 0, // Menyimpan jumlah alpha*/
            'libur_kantor' => 0, // Menyimpan jumlah libur kantor
            'libur_nasional' => 0, // Menyimpan jumlah libur nasional
        ];

        // Loop untuk rentang tanggal
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            $dateStr = $tempDate->format('Y-m-d');
            
            $data_absen[] = null;
            $data_absen = null;
            $query_absen = "SELECT * FROM absen_pegawai WHERE tanggal='$dateStr' AND pegawai_id='{$pegawai['pegawai_id']}'";
            $result_absen = $connection->query($query_absen);
            
            if ($result_absen && $result_absen->num_rows > 0) {
                $data_absen = $result_absen->fetch_assoc();
                $absen_in = $data_absen['absen_in'] ?? '';
                $absen_out = $data_absen['absen_out'] ?? '';

             $status_masuk = ($data_absen['status_masuk'] === 'Tepat Waktu') 
            ? '<span class="badge badge-success">'.$data_absen['status_masuk'].'</span>' 
            : (($data_absen['status_masuk'] === 'Telat')  // Cek jika status_masuk 'Telat'
                ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
                : ($data_absen['status_masuk'] !== '' 
                    ? '<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>' 
                    : '<span class="badge badge-warning">Belum Absen</span>'));  // Default jika kosong

            // Status Pulang
            $status_pulang = $data_absen['absen_out'] === '00:00:00' 
                ? '' 
                : ($data_absen['status_pulang'] === 'Tepat Waktu' 
                    ? '<span class="badge badge-success">'.$data_absen['status_pulang'].'</span>' 
                    : '<span class="badge badge-danger">'.$data_absen['status_pulang'].'</span>');

            // Status Kehadiran
            $kehadiran = $holidayName ? 'Libur' : 
                ($data_absen['kehadiran'] !== '' 
                    ? '<span class="badge badge-info">'.strip_tags($data_absen['kehadiran']??'').'</span>' 
                    : '<span class="badge badge-warning">Tidak Hadir</span>');


                // Cek status kehadiran
                    if ($data_absen['kehadiran'] == 'Hadir') {
                        $result_libur = 'success';
                        $kehadiran = 'Hadir';
                        $pegawaiEntry['tanggal'][] = ['in' => ''.$absen_in.'<br>'.$status_masuk, 'out' => ''.$absen_out.'<br>'.$status_pulang, 'result_libur' => $result_libur];
                        $pegawaiEntry['hadir']++;

                    }

                    if ($data_absen['kehadiran'] == 'Izin') {
                        $result_libur = 'warning'; // Izin, beri status_libur kuning
                        $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']??'') . '</span>';
                        $pegawaiEntry['tanggal'][] = ['in' => $kehadiran, 'out' => $kehadiran, 'result_libur' => $result_libur];
                        $pegawaiEntry['izin']++;
                    }

                    if ($data_absen['kehadiran'] == 'Sakit') {
                        $result_libur = 'warning'; 
                        $kehadiran = '<span class="badge badge-info">' . strip_tags($data_absen['kehadiran']??'') . '</span>';
                        $pegawaiEntry['tanggal'][] = ['in' => $kehadiran, 'out' => $kehadiran, 'result_libur' => $result_libur];
                        $pegawaiEntry['sakit']++;
                    }


                    if($absen_in != NULL && $data_absen['status_masuk'] == 'Telat') {
                        $pegawaiEntry['telat']++; // Hitung jumlah telat
                    }

        } else {
            /** Jika data tidak ditemukan */
            $result_libur = 'white'; // Alpha, beri status_libur merah
            $kehadiran = 'Alpha';
            $pegawaiEntry['tanggal'][] = ['in' => 'x', 'out' => 'x', 'result_libur' => $result_libur];
        }
        
            $totalHari = $startDate->diff($endDate)->days + 1;
        // Hitung jumlah alpha
            $pegawaiEntry['alpha'] = $totalHari - (
            $pegawaiEntry['hadir'] + 
            $pegawaiEntry['izin'] + $pegawaiEntry['sakit'] + $jumlah_libur_nasional + $jumlah_libur_kantor);

            $pegawaiEntry['libur_nasional'] = $jumlah_libur_nasional;

            $tempDate->modify('+1 day');
        }

        // Hitung jumlah alpha
            
            
        /** End Looping */
        $pegawaiData[] = $pegawaiEntry;
    }
    /** End Pegawai */
  
    if (!empty($pegawaiData)) {
       echo json_encode($pegawaiData);
    } else {
       echo json_encode([]); // Kirimkan array kosong jika data kosong
    }
}