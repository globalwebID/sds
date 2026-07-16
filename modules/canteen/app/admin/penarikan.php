<?php
//penarikan.php
include 'inc/fungsi.php';
$result = $conn->query("SELECT p.*, k.nama FROM penarikan p JOIN kantin k ON p.id_kantin = k.id ORDER BY p.tanggal DESC");
?>
<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>
<div class="container">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?= $_GET['success'] == 1 ? "Penarikan disetujui!" : "Penarikan ditolak!" ?>
        </div>
    <?php endif ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Kantin</th>
                    <th>Jumlah</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        <td><?= $row['tanggal'] ?></td>
                        <td><?= ucfirst($row['status']) ?></td>
                        <td>
                            <?php if ($row['status'] == 'diproses'): ?>
                                <a href="verifikasi_penarikan.php?id=<?= $row['id'] ?>&aksi=setujui" class="btn btn-success btn-sm">Setujui</a>
                                <a href="verifikasi_penarikan.php?id=<?= $row['id'] ?>&aksi=tolak" class="btn btn-danger btn-sm">Tolak</a>
                            <?php else: ?>
                                <em>-</em>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>