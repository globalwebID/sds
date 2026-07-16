<?php
include 'inc/fungsi.php';
include 'inc/game_helpers.php';
game_admin_role_guard(['admin','superadmin']);

$q = mysqli_query($conn, "
    SELECT 
        b.brand,
        COALESCE(m.margin, 500) AS margin,
        COUNT(p.id) AS total,
        MIN(p.price_buy) AS min_buy,
        MAX(p.price_buy) AS max_buy
    FROM game_products p
    LEFT JOIN game_margin_brand m ON p.brand = m.brand
    JOIN (SELECT DISTINCT brand FROM game_products) b ON b.brand = p.brand
    GROUP BY b.brand
    ORDER BY b.brand ASC
");

$data = [];
while ($row = mysqli_fetch_assoc($q)) $data[] = $row;

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="container">
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4" style="background:linear-gradient(135deg,#0f766e,#16a34a); color:#fff;">
            <div class="justify-content-between align-items-lg-center gap-3">
                <div>
                    <h3 class="mb-2">Margin per Brand Game</h3>
                    <p class="mb-3 opacity-75">Ubah margin seluruh produk dalam satu brand sekaligus. Harga jual akan dihitung ulang otomatis dari harga beli + margin.</p>
                </div>
                <a href="game.php" class="btn btn-light btn-sm fw-semibold">Kembali ke Ringkasan</a>
            </div>
        </div>
    </div>
<h4 class="mb-4">⚙️ Manajemen Margin Brand</h4>

<div class="card shadow-sm border-0">
<div class="table-responsive">
<table class="table align-middle mb-0">

<thead class="table-dark">
<tr>
<th>Brand</th>
<th>Produk</th>
<th>Harga Beli</th>
<th style="width:280px;">Margin</th>
<th style="width:120px;">Aksi</th>
</tr>
</thead>

<tbody>

<?php foreach($data as $d): ?>
<tr data-brand="<?= htmlspecialchars($d['brand']) ?>">

<td class="fw-semibold"><?= htmlspecialchars($d['brand']) ?></td>

<td><?= number_format($d['total']) ?></td>

<td>
Rp <?= number_format($d['min_buy']) ?> 
- <?= number_format($d['max_buy']) ?>
</td>

<td>
<div class="d-flex gap-2 align-items-center">

<input type="range" min="0" max="5000" step="100"
value="<?= $d['margin'] ?>"
class="form-range margin-slider">

<input type="number"
class="form-control form-control-sm margin-input"
value="<?= $d['margin'] ?>"
style="width:90px">

</div>
</td>

<td>
<button class="btn btn-success btn-sm btn-save">Simpan</button>
</td>

</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
</div>

</div>

<script>
document.querySelectorAll("tr[data-brand]").forEach(row => {

    const slider = row.querySelector(".margin-slider");
    const input  = row.querySelector(".margin-input");
    const btn    = row.querySelector(".btn-save");

    // sync slider & input
    slider.addEventListener("input", () => input.value = slider.value);
    input.addEventListener("input", () => slider.value = input.value);

    btn.addEventListener("click", () => {

        const brand  = row.dataset.brand;
        const margin = input.value;

        btn.disabled = true;
        btn.innerText = "Saving...";

        fetch("ajax_update_margin.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "brand=" + encodeURIComponent(brand) + "&margin=" + margin
        })
        .then(r => r.json())
        .then(res => {
            if(res.success){
                btn.innerText = "✔";
                btn.classList.remove("btn-success");
                btn.classList.add("btn-primary");
            } else {
                alert(res.message);
                btn.innerText = "Error";
            }
        })
        .catch(() => {
            alert("Gagal koneksi");
            btn.innerText = "Error";
        })
        .finally(() => {
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = "Simpan";
                btn.classList.remove("btn-primary");
                btn.classList.add("btn-success");
            }, 1500);
        });

    });

});
</script>

<?php include 'inc/footer.php'; ?>