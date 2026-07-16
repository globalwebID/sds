<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4 shadow-sm" style="position: fixed;width: 100%;z-index: 99;top:0;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">E-Money Kantin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (in_array($role, ['admin', 'superadmin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'dashboard.php' ? ' active' : '' ?> text-white" href="dashboard.php">Dashboard</a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['superadmin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'manajemen_user.php' ? ' active' : '' ?> text-white" href="manajemen_user.php">Pengguna</a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'superadmin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'siswa.php' ? ' active' : '' ?> text-white" href="siswa.php">Siswa</a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'superadmin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'kantin.php' ? ' active' : '' ?> text-white" href="kantin.php">Kantin</a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'superadmin', 'operator'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'topup.php' ? ' active' : '' ?> text-white" href="topup.php">Topup</a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'superadmin'])): ?>
                    <?php $gamePages = ['game.php', 'game_margin.php', 'game_profit.php', 'game_sync.php', 'game_transactions.php']; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= in_array($currentPage, $gamePages, true) ? ' active' : '' ?> text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Game</a>
                        <ul class="dropdown-menu shadow-sm border-0">
                            <li><a class="dropdown-item<?= $currentPage === 'game.php' ? ' active' : '' ?>" href="game.php">Ringkasan Game</a></li>
                            <li><a class="dropdown-item<?= $currentPage === 'game_margin.php' ? ' active' : '' ?>" href="game_margin.php">Margin per Brand</a></li>
                            <li><a class="dropdown-item<?= $currentPage === 'game_profit.php' ? ' active' : '' ?>" href="game_profit.php">Profit Game</a></li>
                            <li><a class="dropdown-item<?= $currentPage === 'game_sync.php' ? ' active' : '' ?>" href="game_sync.php">Sinkron Produk</a></li>
                            <li><a class="dropdown-item<?= $currentPage === 'game_transactions.php' ? ' active' : '' ?>" href="game_transactions.php">Detail Transaksi Game</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'superadmin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'informasi.php' ? ' active' : '' ?> text-white" href="informasi.php">Informasi</a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'superadmin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'laporan_transaksi.php' ? ' active' : '' ?> text-white" href="laporan_transaksi.php">Laporan</a>
                    </li>
                <?php endif; ?>
                
            </ul>


            <div class="d-flex align-items-center">
                <span class="navbar-text text-white me-3">
                    <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)
                </span>
                <a href="logout.php" class="btn btn-warning">KELUAR</a>
            </div>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 70px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Selamat datang, <strong><?= htmlspecialchars($username) ?></strong></h2>
            <span class="badge bg-secondary">Role: <?= htmlspecialchars($role) ?></span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock-history text-warning card-icon me-3 fs-4"></i>
                    <div>
                        <h6 class="mb-0">Aktivitas Terakhir</h6>
                        <small class="fw-bold"><?= $last_waktu ?></small>
                    </div>
                </div>
            </div>

            <?php if (!empty($pengaturan['logo'])): ?>
                <img src="../../uploads/logo/<?= $pengaturan['logo'] ?>" class="rounded-circle border" style="width: 60px; height: 60px;" alt="Logo">
            <?php endif; ?>
        </div>
    </div>
</div>