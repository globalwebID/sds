<?php
$id_siswa = isset($_GET['id']) ? intval($_GET['id']) : 0;
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;

$total_topup = 0;
$total_belanja = 0;
$sisa_saldo = 0;
$rfid = '-'; // Default jika tidak ditemukan

$filter = "";
if ($bulan && $tahun) {
    $filter = "AND MONTH(tanggal) = $bulan AND YEAR(tanggal) = $tahun";
}

if ($id_siswa > 0) {
    // Ambil RFID dari pendaftaran_siswa
    $q_rfid = mysqli_query($conn, "SELECT rfid_uid FROM pendaftaran_siswa WHERE id = $id_siswa LIMIT 1");
    if ($row = mysqli_fetch_assoc($q_rfid)) {
        $rfid = $row['rfid_uid'];
    }

    // Hitung total topup
    $q_topup = mysqli_query($conn, "SELECT SUM(nominal) AS total FROM topup WHERE id_siswa = $id_siswa $filter");
    if ($row = mysqli_fetch_assoc($q_topup)) {
        $total_topup = (int)$row['total'];
    }

    // Hitung total belanja
    $q_belanja = mysqli_query($conn, "SELECT SUM(nominal) AS total FROM transaksi_kantin WHERE id_siswa = $id_siswa $filter");
    if ($row = mysqli_fetch_assoc($q_belanja)) {
        $total_belanja = (int)$row['total'];
    }

    // Hitung sisa saldo
    $sisa_saldo = $total_topup - $total_belanja;
}
?>
<div class="card-body">
    <div class="row" style="margin: -20px;">
        <div class="top-tab mt-0">
            <div class="row align-items-center justify-content-between g-2">
                <div class="col-auto">
                    <h5 class="card-title mb-0">Riwayat Transaksi (UID: <?= htmlspecialchars($rfid) ?>)</h5>
                </div>
                <div class="col-auto">
                    <form method="GET" action="" class="d-flex align-items-center gap-2 flex-wrap" onsubmit="return tambahHash();">
                        <input type="hidden" name="id" value="<?= $id_siswa ?>">

                        <select name="bulan" class="form-select" style="width: auto;">
                            <option value="">Bulan</option>
                            <?php
                            for ($b = 1; $b <= 12; $b++) {
                                $selected = isset($_GET['bulan']) && $_GET['bulan'] == $b ? 'selected' : '';
                                echo "<option value=\"$b\" $selected>" . date('F', mktime(0, 0, 0, $b, 10)) . "</option>";
                            }
                            ?>
                        </select>

                        <select name="tahun" class="form-select" style="width: auto;">
                            <option value="">Tahun</option>
                            <?php
                            $currentYear = date('Y');
                            for ($t = $currentYear; $t >= $currentYear - 5; $t--) {
                                $selected = isset($_GET['tahun']) && $_GET['tahun'] == $t ? 'selected' : '';
                                echo "<option value=\"$t\" $selected>$t</option>";
                            }
                            ?>
                        </select>

                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                        <a href="pages/partials/biodata/export_transaksi_excel.php?id=<?= $id_siswa ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Ekspor Excel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex">
    <div class="col-md-4">
        <div class="text-white bg-success shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title mb-1 text-white ">Total Topup</h6>
                <h4 class="fw-bold text-white ">Rp <?= number_format($total_topup, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-white bg-danger shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title mb-1 text-white ">Total Belanja</h6>
                <h4 class="fw-bold text-white ">Rp <?= number_format($total_belanja, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-white bg-primary shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title mb-1 text-white ">Sisa Saldo</h6>
                <h4 class="fw-bold text-white ">Rp <?= number_format($sisa_saldo, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="">
    <table class="table table-sm table-striped table-bordered table-hover shadow-sm bg-white rounded">
        <thead class="table-success">
            <tr>
                <th>Waktu</th>
                <th>Jenis Transaksi</th>
                <th>Kantin</th>
                <th>Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php include 'transaksi_log.php'; ?>
        </tbody>
    </table>
</div>

<script>
    function tambahHash() {
        const form = event.target;
        const action = form.getAttribute('action') || window.location.pathname;
        const params = new URLSearchParams(new FormData(form));
        window.location.href = `${action}?${params.toString()}#rfid`;
        return false; // mencegah form submit default
    }
</script>