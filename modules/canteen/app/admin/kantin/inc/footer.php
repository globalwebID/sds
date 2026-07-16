</div>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="mobile-footer m-auto">
    <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-receipt-cutoff"></i>
        Dashboard
    </a>
    <a href="laporan.php" class="<?= $currentPage === 'laporan.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-data"></i>
        Laporan
    </a>
    <a href="informasi.php" id="info-link" class="<?= $currentPage === 'informasi.php' ? 'active' : '' ?> position-relative">
        <i class="bi bi-bell-fill" id="icon-bell"></i> <span id="text-info">Informasi</span>
        <span id="notif-dot" class="position-absolute translate-middle p-1 bg-danger border border-light rounded-circle" style="top: 12px; right: 50px; display: none;opacity:0">
            <span class="visually-hidden">Ada informasi baru</span>
        </span>
    </a>
    <a href="akun.php" class="<?= $currentPage === 'akun.php' ? 'active' : '' ?>">
        <i class="bi bi-person-circle"></i>
        Akun
    </a>
</div>

<script>
    function cekInformasiBaru() {
        fetch('cek_informasi_baru.php')
            .then(response => response.json())
            .then(data => {
                const icon = document.getElementById('icon-bell');
                const dot = document.getElementById('notif-dot');
                const text = document.getElementById('text-info');

                if (data.baru) {
                    icon.classList.add('blink');
                    dot.style.display = 'inline-block';
                    text.innerHTML = '<span class="blink">Informasi</span>';
                } else {
                    icon.classList.remove('blink');
                    dot.style.display = 'none';
                    text.innerHTML = 'Informasi';
                }
            });
    }

    // Cek setiap 5 detik
    setInterval(cekInformasiBaru, 5000);
    document.addEventListener("DOMContentLoaded", cekInformasiBaru);
</script>

</body>

</html>