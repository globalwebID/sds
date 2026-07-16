<?php
include 'inc/fungsi.php';
$query = mysqli_query($conn, "SELECT * FROM kantin WHERE id = $id_kantin");
$kantin = mysqli_fetch_assoc($query);

include 'inc/header.php';
?>
<div class="styles_search-box__aevfx">
    <div class="flex items-center">
        <div class="col-12">
            <div class="p-3">
                <div class="d-flex align-items-center">
                    <img src="../../images/kantin/<?= $kantin['gambar'] ?>" class="kantin-img rounded">
                    <div>
                        <h5 class="text-grey fw-bold" style="text-transform: uppercase;">Laporan Pemasukan</h5>
                        <h5 class="text-grey fw-bold" style="text-transform: uppercase;"><?= htmlspecialchars($kantin['nama']) ?> 📊</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="container py-4 m" style="margin-top:-4rem;">
    <div class="">
        <form id="filterForm" class="row g-3 mb-4">
            <div class="col-6">
                <select name="periode" id="periode" class="form-select fs-3 fw-bold" style="height: 79px;">
                    <option value="harian" selected>Harian</option>
                    <option value="mingguan">Mingguan</option>
                    <option value="bulanan">Bulanan</option>
                </select>
            </div>
            <div class="col-6">
                <button type="submit" class="btn btn-primary fs-3 fw-bold w-100" style="padding: 19px 0px;">Tampilkan</button>
            </div>
        </form>

    </div>

    <div id="laporanArea">
        <p>Silakan pilih periode dan klik Tampilkan.</p>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');

        // Handler submit form
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const periode = document.getElementById('periode').value;
            const laporanArea = document.getElementById('laporanArea');
            laporanArea.innerHTML = '<p class="text-muted">Memuat data...</p>';

            // Panggil backend untuk ambil laporan berdasarkan periode
            fetch(`fetch_laporan.php?periode=${periode}`)
                .then(res => res.text())
                .then(html => {
                    laporanArea.innerHTML = html;
                })
                .catch(err => {
                    laporanArea.innerHTML = '<p class="text-danger">Gagal memuat data.</p>';
                    console.error(err);
                });
        });

        // 👇 Auto-trigger submit dengan periode "harian"
        form.dispatchEvent(new Event('submit'));
    });
</script>


<?php include 'inc/footer.php'; ?>