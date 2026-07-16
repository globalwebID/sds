<?php
include 'inc/fungsi.php';
include 'inc/header.php';

?>
<div class="card mb-3" style="border: none;">
    <div class="card-body bg-grey" style="border-radius: 0px;">
        <h4 class="mb-3">📢 Informasi Terbaru</h4>
        <?php
        // Ambil data informasi dari database
        $resultInfo = $conn->query("
    SELECT i.*, iu.dibaca
    FROM informasi i
    LEFT JOIN informasi_user iu ON i.id = iu.informasi_id AND iu.user_id = $id_kantin
    ORDER BY i.tanggal DESC
");
        ?>
        <?php if ($resultInfo && $resultInfo->num_rows > 0): ?>
            <?php while ($info = $resultInfo->fetch_assoc()): ?>
                <?php
                $sudahDibaca = $info['dibaca'] ?? 0;
                $cardClass = $sudahDibaca ? 'bg-white' : 'bg-info bg-opacity-25 border-info';
                $badge = !$sudahDibaca ? '<span class="badge bg-info text-dark ms-2">Baru</span>' : '';
                ?>
                <div class="card shadow-sm mb-3 <?= $cardClass ?>">
                    <div class="card-body">
                        <h5 class="card-title mb-1">
                            <?= htmlspecialchars($info['judul']) ?> <?= $badge ?>
                        </h5>
                        <small class="text-muted"><?= date('d M Y H:i', strtotime($info['tanggal'])) ?></small>
                        <!-- <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($info['isi'])) ?></p> -->
                        <p class="mt-2 mb-0"><?= $info['isi'] ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">Belum ada informasi terbaru.</div>
        <?php endif; ?>

        <?php
        // Tandai semua informasi sebagai dibaca
        $conn->query("UPDATE informasi_user SET dibaca = 1 WHERE user_id = $id_kantin");
        ?>
    </div>
</div>
<!-- Footer & Menu Mobile -->
<?php include 'inc/footer.php'; ?>