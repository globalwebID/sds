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
                    <!-- <img src="../../images/kantin/<?= $kantin['gambar'] ?>" class="kantin-img rounded"> -->
                    <div>
                        <h5 class="text-grey fw-bold" style="text-transform: uppercase;">Saldo <?= htmlspecialchars($kantin['nama']) ?></h5>
                        <p class="mb-0 fw-bold fs-1 text-yellow" id="total-pendapatan">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bagian Atas -->
<section class="container pt-4" style="margin-top:-4rem;">
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card shadow-sm p-3 text-white text-center bg-success" data-bs-toggle="modal" data-bs-target="#tarikSaldoModal" style="cursor: pointer;">
                <h3 class="mb-0 fs-4 pt-2 pb-2 fw-bold">Tarik Saldo</h3>
            </div>
        </div>
        <div class="col-6">
            <a href="../logout.php" class="text-decoration-none">
                <div class="card shadow-sm p-3 text-white text-center bg-danger" style="cursor: pointer;">
                    <h3 class="mb-0 fs-4 pt-2 pb-2 fw-bold">Keluar</h3>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Riwayat Penarikan -->
<div class="container mb-5">
    <h4 class="fw-bold mb-3">Riwayat Penarikan</h4>
    <div class="row row-cols-1 g-3">
        <?php
        $id_kantin = $_SESSION['id_kantin'];
        $query = mysqli_query($conn, "SELECT * FROM penarikan WHERE id_kantin = $id_kantin ORDER BY tanggal DESC");

        if (mysqli_num_rows($query) === 0) {
            echo "<p class='text-muted'>Belum ada riwayat penarikan.</p>";
        } else {
            while ($row = mysqli_fetch_assoc($query)) {
                $tanggal = date('d M Y, H:i', strtotime($row['tanggal']));
                $jumlah = number_format($row['jumlah'], 0, ',', '.');
                $status = ucfirst($row['status']);

                switch ($row['status']) {
                    case 'diproses':
                        $badgeClass = 'warning';
                        break;
                    case 'berhasil':
                        $badgeClass = 'success';
                        break;
                    case 'ditolak':
                        $badgeClass = 'danger';
                        break;
                    default:
                        $badgeClass = 'secondary';
                        break;
                }

                echo "
                <div class='col'>
                    <div class='card border-start border-4 border-{$badgeClass} shadow-sm'>
                        <div class='card-body'>
                            <div class='d-flex justify-content-between align-items-center mb-2'>
                                <span class='text-muted small'>{$tanggal}</span>
                                <span class='badge bg-{$badgeClass}'>{$status}</span>
                            </div>
                            <h5 class='mb-0 fw-bold text-success'>Rp {$jumlah}</h5>
                        </div>
                    </div>
                </div>";
            }
        }
        ?>
    </div>
</div>



<?php include 'inc/footer.php'; ?>

<!-- Modal Tarik Saldo -->
<div class="modal fade" id="tarikSaldoModal" tabindex="-1" aria-labelledby="tarikSaldoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formTarikSaldo" class="modal-content" style="font-size: 1.15rem;">
            <div class="modal-header bg-success text-white">
                <h4 class="modal-title" id="tarikSaldoModalLabel" style="font-weight: bold;">Formulir Tarik Saldo</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="font-weight: bold;">Saldo Tersedia:</label>
                    <div id="saldoTersedia" class="fs-4 fw-bold text-primary">Rp 0</div>
                </div>

                <div class="mb-3">
                    <label for="jumlahTarik" class="form-label" style="font-weight: bold;">Jumlah Penarikan</label>
                    <input type="text" class="form-control form-control-lg" name="jumlah" id="jumlahTarik" required placeholder="Contoh: 10000">
                    <div class="form-text text-danger fw-semibold">Minimal penarikan Rp 1.000</div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success btn-lg">Kirim Permintaan</button>
            </div>
        </form>
    </div>
</div>


<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const saldo = <?= (int)($kantin['saldo'] ?? 0) ?>;
        const inputJumlah = document.getElementById('jumlahTarik');
        const saldoTersedia = document.getElementById('saldoTersedia');
        const form = document.getElementById('formTarikSaldo');

        if (saldoTersedia) {
            saldoTersedia.textContent = 'Rp ' + saldo.toLocaleString('id-ID');
        }

        function formatRupiah(angka) {
            return angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Format input saat diketik
        inputJumlah.addEventListener('input', function() {
            let angka = this.value.replace(/\D/g, '');
            this.value = formatRupiah(angka);
        });

        // Submit form
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            let raw = inputJumlah.value.replace(/\./g, '');
            const jumlah = parseInt(raw || 0);

            if (jumlah < 1000) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Minimal penarikan Rp 1.000'
                });
                return;
            }

            if (jumlah > saldo) {
                Swal.fire({
                    icon: 'error',
                    title: 'Saldo Tidak Cukup',
                    text: 'Jumlah penarikan tidak boleh melebihi saldo.'
                });
                return;
            }

            Swal.fire({
                title: 'Mengirim...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('tarik_saldo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `jumlah=${jumlah}`
                })
                .then(res => res.json())
                .then(res => {
                    Swal.close();
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Permintaan Dikirim',
                            text: 'Menunggu persetujuan admin.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        inputJumlah.value = '';
                        bootstrap.Modal.getInstance(document.getElementById('tarikSaldoModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.message || 'Gagal mengirim permintaan.'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: 'Tidak dapat menghubungi server.'
                    });
                    console.error(err);
                });
        });
    });
</script>

<script>
    function loadRiwayat() {
        const riwayatBody = document.getElementById('riwayatBody');
        riwayatBody.innerHTML = '<p>Memuat data...</p>';

        fetch('fetch_riwayat_penarikan.php')
            .then(res => res.text())
            .then(html => {
                riwayatBody.innerHTML = html;
            })
            .catch(err => {
                riwayatBody.innerHTML = '<p class="text-danger">Gagal memuat riwayat.</p>';
                console.error(err);
            });
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ambil saldo dari server secara real-time
        fetch('fetch_total_pendapatan.php')
            .then(response => response.json())
            .then(data => {
                const el = document.getElementById('total-pendapatan');
                if (data.total !== undefined) {
                    el.textContent = 'Rp ' + parseInt(data.total).toLocaleString('id-ID');
                } else {
                    el.textContent = 'Rp 0';
                }
            })
            .catch(error => {
                console.error('Gagal memuat total saldo:', error);
                document.getElementById('total-pendapatan').textContent = 'Gagal memuat';
            });
    });
</script>