<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{

$sql_count = "
    SELECT 
        (SELECT COUNT(*) FROM user WHERE active = 'Y') AS jumlah_siswa,       
        (SELECT COUNT(*) FROM pegawai WHERE active = 'Y') AS jumlah_pegawai, 
        (SELECT COUNT(*) FROM wali_murid) AS jumlah_wali_murid,              
        (SELECT COUNT(*) FROM absensi_kelas WHERE parent_id != 0) AS jumlah_kelas,     
        (SELECT COUNT(*) FROM jadwal_mengajar) AS jumlah_jadwal,
        (SELECT COUNT(*) FROM izin WHERE status='PENDING') AS jumlah_izin_siswa,
        (SELECT COUNT(*) FROM izin_pegawai WHERE status='PENDING') AS jumlah_izin_pegawai;";
$result_count = $connection->query($sql_count);
$row_count = $result_count->fetch_assoc();

$jumlah_siswa = $row_count['jumlah_siswa'];
$jumlah_pegawai = $row_count['jumlah_pegawai'];
$jumlah_wali_murid = $row_count['jumlah_wali_murid'];
$jumlah_kelas = $row_count['jumlah_kelas'];
$jumlah_jadwal = $row_count['jumlah_jadwal'];
$jumlah_izin_siswa = $row_count['jumlah_izin_siswa'];
$jumlah_izin_pegawai = $row_count['jumlah_izin_pegawai'];

echo'
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-12 col-12">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
              <li class="breadcrumb-item active">Dashboards</li>
            </ol>
          </nav>
        </div>
      </div>

      <!-- Card stats -->
      <div class="row">
        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Siswa</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_siswa.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-gradient-red text-white rounded-circle shadow">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Siswa</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">

                  <h5 class="card-title text-uppercase text-muted mb-0">Pegawai</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_pegawai.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-gradient-orange text-white rounded-circle shadow">
                    <i class="fas fa-user"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Pegawai</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Wali Murid</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_wali_murid.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-gradient-green text-white rounded-circle shadow">
                    <i class="fas fa-users"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Wali Murid</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Kelas</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_wali_murid.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-gradient-blue text-white rounded-circle shadow">
                    <i class="fas fa-star"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Kelas</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Jadwal Mengajar</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_jadwal.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                    <i class="fas fa-book-open"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Jadwal Mengajar</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Izin Siswa</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_izin_siswa.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                    <i class="fas fa-paste"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Izin '.tanggal_ind($date).'</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Izin Pegawai</h5>
                  <span class="h2 font-weight-bold mb-0">'.$jumlah_izin_pegawai.'</span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                    <i class="fas fa-paste"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Jumlah Izin '.tanggal_ind($date).'</span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
        <a href="pengaturan">
          <div class="card card-stats mb-2">
            <!-- Card body -->
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="card-title text-uppercase text-muted mb-0">Pengaturan</h5>
                  <span class="h2 font-weight-bold mb-0"></span>
                </div>
                <div class="col-auto">
                  <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                    <i class="fas fa-tools"></i>
                  </div>
                </div>
              </div>
              <p class="mt-3 mb-0 text-sm">
                <span class="text-nowrap">Pengaturan web</span>
              </p>
            </div>
          </div>
          </a>
        </div>
      </div>
    </div>
</div>
</div>  

    <!-- Page content -->
    <div class="container-fluid mt--4">
      <div class="row">
        <div class="col-xl-6">
          <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Grafik Absensi & Izin 7 Hari Terakhir</h3>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" class="chart-canvas"></canvas>
            </div>
          </div>
      </div>

      <div class="col-xl-6">
          <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Absensi '.tgl_indo($date).'</h3>
            </div>
           
            <div class="card-body">
               <canvas id="kelasChart" class="chart-canvas"></canvas>
            </div>
          </div>
      </div>

    </div>';

  $data = [
    'labels' => [],
    'total_absen' => [],
    'total_absen_pegawai' => [],
    'total_izin' => [],
    'total_izin_pegawai' => [],
    'total_pelanggaran' => []
];

// Loop untuk mengambil data selama 7 hari
for ($i = 6; $i >= 0; $i--) {
    // Menghitung tanggal berdasarkan hari sebelumnya
    $tanggal = date('Y-m-d', strtotime("-$i days"));

    // Query untuk mengambil jumlah data untuk masing-masing tabel
    $sql = "
        SELECT 
            (SELECT COUNT(*) FROM absen WHERE tanggal = '$tanggal') AS total_absen,
            (SELECT COUNT(*) FROM absen_pegawai WHERE tanggal = '$tanggal') AS total_absen_pegawai,
            (SELECT COUNT(*) FROM izin WHERE tanggal = '$tanggal') AS total_izin,
            (SELECT COUNT(*) FROM izin_pegawai WHERE tanggal = '$tanggal') AS total_izin_pegawai,
            (SELECT COUNT(*) FROM pelanggaran WHERE tanggal = '$tanggal') AS total_pelanggaran;
    ";

    // Menjalankan query
    $result = $connection->query($sql);

    if ($result) {
        // Mengambil hasil dari query
        $row = $result->fetch_assoc();
        
        // Memeriksa apakah data ditemukan
        if ($row) {
            $total_absen = (int)$row['total_absen'];
            $total_absen_pegawai = (int)$row['total_absen_pegawai'];
            $total_izin = (int)$row['total_izin'];
            $total_izin_pegawai = (int)$row['total_izin_pegawai'];
            $total_pelanggaran = (int)$row['total_pelanggaran'];

            // Menambahkan data ke array $data
            $data['labels'][] = tanggal_ind($tanggal);
            $data['total_absen'][] = $total_absen;
            $data['total_absen_pegawai'][] = $total_absen_pegawai;
            $data['total_izin'][] = $total_izin;
            $data['total_izin_pegawai'][] = $total_izin_pegawai;
            $data['total_pelanggaran'][] = $total_pelanggaran;
        }
    } else {
        // Menangani error jika query gagal
        echo "Error executing query: " . $connection->error;
    }

  }
  
  $query_kelas = "SELECT nama_kelas FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
  $result_kelas = $connection->query($query_kelas);
  $labels_kelas = [];
  $jumlah_siswa = [];
  while ($data_kelas = $result_kelas->fetch_assoc()) {
      $labels_kelas[] = $data_kelas['nama_kelas'];
    
      $query_absen_kelas = "SELECT COUNT(*) AS jumlah_absen_kelas FROM absen
      LEFT JOIN user ON user.user_id = absen.user_id WHERE user.kelas= '$data_kelas[nama_kelas]' AND absen.tanggal = '$date'";
      $result_absen_kelas = $connection->query($query_absen_kelas);
      $row_absen_siswa = $result_absen_kelas->fetch_assoc();
      $jumlah_absen_kelas[] = $row_absen_siswa['jumlah_absen_kelas'];
  }

?>
<script src="sw-assets/vendor/chart.js/dist/Chart.min.js"></script>
<script type="text/javascript">
const data = <?php echo json_encode($data); ?>;
const ctx = document.getElementById('attendanceChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: data.labels, // Tanggal
        datasets: [{
                label: 'Total Absen Siswa',
                data: data.total_absen,
                lineTension: 0.1,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true
            },
            {
                label: 'Total Absen Pegawai',
                data: data.total_absen_pegawai,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true
            },
            {
                label: 'Total Izin',
                data: data.total_izin,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true
            },
            {
                label: 'Total Izin Pegawai',
                data: data.total_izin_pegawai,
                borderColor: 'rgba(153, 102, 255, 1)',
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                fill: true
            },
            {
                label: 'Total Pelanggaran',
                data: data.total_pelanggaran,
                borderColor: 'rgba(255, 0, 0, 1)',
                backgroundColor: 'rgba(255, 102, 102, 0.44)',
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Tanggal'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Jumlah'
                }
            }
        }
    }
});
</script>

<script type="text/javascript">
<?php 
    echo 'var labels_kelas = ' . json_encode($labels_kelas) . ';';
    echo 'var jumlah_absen_kelas = ' . json_encode($jumlah_siswa) . ';';?>

function generateColorsFixed(total) {
    let colors = [];
    for (let i = 0; i < total; i++) {
        let hue = i * (360 / total);
        colors.push(`hsla(${hue}, 70%, 60%, 0.6)`);
    }
    return colors;
}

const data_kelas = {
    labels: <?php echo json_encode($labels_kelas); ?>, // Nama kelas
    datasets: [{
        label: 'Jumlah Absen per Kelas',
        data: <?php echo json_encode($jumlah_absen_kelas); ?>, // Jumlah siswa per kelas
        backgroundColor: generateColorsFixed(jumlah_absen_kelas.length),
        borderColor: generateColorsFixed(jumlah_absen_kelas.length),
        hoverOffset: 4
    }]
};

// Inisialisasi Chart.js
const ctx_kelas = document.getElementById('kelasChart').getContext('2d');
const kelasChart = new Chart(ctx_kelas, {
    type: 'bar', // Jenis grafik pie
    data: data_kelas,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.label + ': ' + tooltipItem.raw + ' siswa';
                    }
                }
            }
        }
    }
});
</script>
<?php
}?>