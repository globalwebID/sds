<?php
include 'inc/fungsi.php';

$id_kantin = $_GET['id'] ?? null;
$activeTab = $_GET['tab'] ?? 'transaksi';

if (!$id_kantin) {
    header("Location: kantin.php");
    exit;
}

// Filter waktu
$filter = $_GET['filter'] ?? null;
$filterCondition = '';

if ($filter === 'hari') {
    $filterCondition = "AND DATE(tanggal) = CURDATE()";
} elseif ($filter === 'minggu') {
    $filterCondition = "AND YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'bulan') {
    $filterCondition = "AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
}

// Ambil nama kantin
$kantin = mysqli_query($conn, "SELECT nama FROM kantin WHERE id = $id_kantin");
$nama_kantin = mysqli_fetch_assoc($kantin)['nama'] ?? '';

// Data transaksi kantin
$transaksiQuery = "SELECT tk.*, s.nama_lengkap as nama_siswa 
          FROM transaksi_kantin tk 
          JOIN pendaftaran_siswa s ON tk.id_siswa = s.id 
          WHERE tk.id_kantin = $id_kantin $filterCondition
          ORDER BY tk.tanggal DESC";
$transaksiResult = mysqli_query($conn, $transaksiQuery);

// Total nominal transaksi
$totalQuery = "SELECT SUM(nominal) as total 
               FROM transaksi_kantin 
               WHERE id_kantin = $id_kantin " . $filterCondition;
$totalResult = mysqli_query($conn, $totalQuery);
$total = mysqli_fetch_assoc($totalResult)['total'] ?? 0;

// Data penarikan
$penarikanQuery = "SELECT * FROM penarikan WHERE id_kantin = $id_kantin ORDER BY tanggal DESC";
$penarikanResult = mysqli_query($conn, $penarikanQuery);

$totalPenarikanQuery = "SELECT SUM(jumlah) as total FROM penarikan WHERE id_kantin = $id_kantin";
$totalPenarikanResult = mysqli_query($conn, $totalPenarikanQuery);
$totalPenarikan = mysqli_fetch_assoc($totalPenarikanResult)['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi & Penarikan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            /* Gradasi biru muda ke putih */
            background-attachment: fixed;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .nav-tabs .nav-link {
            font-weight: 500;
            color: #0d6efd;
            background-color: #f8f9fa;
            border: 1px solid transparent;
            border-radius: .5rem .5rem 0 0;
            transition: all 0.3s ease-in-out;
        }

        .nav-tabs .nav-link:hover {
            background-color: #e2e6ea;
            color: #0a58ca;
        }

        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd #0d6efd #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .tab-content {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 1.5rem;
            border-radius: 0 0 .5rem .5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
    </style>

</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-4">
                <h4>Riwayat Transaksi<br><?= htmlspecialchars($nama_kantin) ?></h4>
                <a href="kantin.php" class="btn btn-secondary mb-3">Kembali</a>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Transaksi</h5>
                        <p class="card-text fs-4">Rp <?= number_format($total, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Penarikan</h5>
                        <p class="card-text fs-4">Rp <?= number_format($totalPenarikan, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'transaksi' ? 'active' : '' ?>" href="?id=<?= $id_kantin ?>&tab=transaksi">
                    🛒 Riwayat Pembelian
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'penarikan' ? 'active' : '' ?>" href="?id=<?= $id_kantin ?>&tab=penarikan">
                    💸 Riwayat Penarikan
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <?php if ($activeTab === 'transaksi'): ?>
                <!-- Filter -->
                <div class="mb-3">
                    <div class="btn-group">
                        <a href="?id=<?= $id_kantin ?>&tab=transaksi&filter=hari" class="btn btn-outline-primary btn-sm">Harian</a>
                        <a href="?id=<?= $id_kantin ?>&tab=transaksi&filter=minggu" class="btn btn-outline-primary btn-sm">Mingguan</a>
                        <a href="?id=<?= $id_kantin ?>&tab=transaksi&filter=bulan" class="btn btn-outline-primary btn-sm">Bulanan</a>
                    </div>
                </div>

                <!-- Tabel Transaksi -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Tanggal/Waktu</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($transaksiResult) > 0): $no = 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($transaksiResult)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal'])) ?></td>
                                        <td>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada transaksi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th>Rp <?= number_format($total, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php elseif ($activeTab === 'penarikan'): ?>
                <!-- Tabel Riwayat Penarikan -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($penarikanResult) > 0): $no = 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($penarikanResult)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal'])) ?></td>
                                        <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($row['status']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada data penarikan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total</th>
                                <th colspan="2">Rp <?= number_format($totalPenarikan, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>