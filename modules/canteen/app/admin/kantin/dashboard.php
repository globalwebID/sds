<?php
include 'inc/fungsi.php';

$query = mysqli_query($conn, "SELECT * FROM kantin WHERE id = $id_kantin");
$kantin = mysqli_fetch_assoc($query);

// Ambil info transaksi kantin ini
$result = mysqli_query($conn, "
    SELECT t.tanggal, s.nama_lengkap AS nama_siswa, t.nominal
    FROM transaksi_kantin t
    JOIN pendaftaran_siswa s ON t.id_siswa = s.id
    WHERE t.id_kantin = $id_kantin 
      AND DATE(t.tanggal) = CURDATE()
    ORDER BY t.tanggal DESC
");

if (!$result) {
    die("Query transaksi gagal: " . mysqli_error($conn));
}

$jumlah_hari_ini_result = mysqli_query($conn, "
    SELECT COUNT(*) as jumlah 
    FROM transaksi_kantin 
    WHERE id_kantin = $id_kantin 
      AND DATE(tanggal) = CURDATE()
");

$jumlah_hari_ini = mysqli_fetch_assoc($jumlah_hari_ini_result)['jumlah'] ?? 0;

$total_result = mysqli_query($conn, "
    SELECT SUM(nominal) AS total_transaksi 
    FROM transaksi_kantin 
    WHERE id_kantin = $id_kantin 
      AND DATE(tanggal) = CURDATE()
");

if (!$total_result) {
    die("Query total gagal: " . mysqli_error($conn));
}

$total_transaksi = mysqli_fetch_assoc($total_result)['total_transaksi'] ?? 0;

include 'inc/header.php';
?>


<div class="styles_search-box__aevfx">
    <div class="flex items-center">

        <div class="col-12">
            <div class="p-3">
                <div class="d-flex align-items-center">
                    <img src="../../images/kantin/<?= $kantin['gambar'] ?>" class="kantin-img rounded">
                    <div>
                        <h5 class="text-grey fw-bold " style="text-transform: uppercase;"><?= htmlspecialchars($kantin['nama']) ?></h5>
                        <small class="mb-0 text-grey">Lokasi: <?= htmlspecialchars($kantin['lokasi']) ?></small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- <nav class="navbar navbar-dark bg-dark">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <span class="navbar-brand">Kantin - <?= htmlspecialchars($username) ?></span>
                <div class="d-flex align-items-center">
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                    <a href="laporan.php" class="btn btn-outline-light btn-sm me-2">Laporan</a>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </nav> -->


<div class="container py-4 m" style="margin-top:-4rem;">
    <div class="row g-3 mb-4">
        <!-- <div class="col-md-6">
                    <div class="card shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <img src="../../images/kantin/<?= $kantin['gambar'] ?>" class="kantin-img rounded">
                            <div>
                                <h5><?= htmlspecialchars($kantin['nama']) ?></h5>
                                <p class="mb-0">Lokasi: <?= htmlspecialchars($kantin['lokasi']) ?></p>
                            </div>
                        </div>
                    </div>
                </div> -->
        <div class="col-6">
            <div class="card bg-warning shadow-sm" style="padding: 10px;">
                <div class="d-flex align-items-center">
                    <i class="bi bi-currency-exchange text-darkblue card-icon me-2"></i>
                    <div>
                        <!-- <h6>Transaksi Hari ini</h6> -->
                        <p class="mb-0 fs-5 fw-bold" id="nominal-hari-ini">Rp <?= number_format($total_transaksi, 0, ',', '.') ?></p>
                        <p class="mb-0 fw-bold" id="transaksi-hari-ini"><?= $jumlah_hari_ini ?> transaksi</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <?php $status_toko = $kantin['status_toko'] ?? 'tutup'; ?>
            <div id="statusCard"
                class="card shadow-sm p-3 text-white text-center <?= $status_toko == 'buka' ? 'bg-success' : 'bg-danger' ?>"
                style="cursor: pointer;">
                <h3 id="statusText" class="mb-0 fs-4 pt-2 pb-2 fw-bold"><?= $status_toko == 'buka' ? 'Tutup Kantin' : 'Buka Kantin' ?></h3>
            </div>
        </div>
    </div>


    <!-- Daftar Transaksi Hari Ini -->
    <div class="card shadow rounded-3">
        <div class="card-header bg-darkblue text-white">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-clock-history me-2"></i>Transaksi Hari Ini (<?= $hariIni ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0 table-transaksi" id="transaksi-hari-ini">
                <thead>
                    <tr>
                        <th style="width: 6rem;"><i class="bi bi-clock"></i> Jam</th>
                        <th><i class="bi bi-person"></i> Nama Siswa</th>
                        <th style="width: 8rem; text-align: right;"><i class="bi bi-cash"></i> Nominal</th>
                    </tr>
                </thead>
                <tbody id="transaksi-body">
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">⏳ Memuat data transaksi...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row g-3 mb-4">

    </div>
    <div class="col-12">
        <div class="card shadow-sm p-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-receipt text-info card-icon me-3"></i>
                <div>
                    <h5 class="fw-bold">Saldo Kantin</h5>
                    <p class="mb-0 fw-bold" id="total-pendapatan">Memuat...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="border-radius: 0;padding-bottom:65px">
    <div class="card-body">
        <h5>Grafik Pendapatan 7 Hari Terakhir</h5>
        <canvas id="grafikPendapatan" height="100"></canvas>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Modal Popup -->
<div class="modal fade" id="popupTransaksi" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal shadow-lg border-0 rounded-4">
            <div class="modal-header bg-gradient text-white rounded-top-4">
                <h5 class="modal-title fs-1 fw-semibold" id="popupLabel">🔔 Transaksi Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3 bg-darkblue">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-calendar3 fs-1 text-warning"></i>
                    <div>
                        <div class="fw-bold small text-grey">Tanggal</div>
                        <div id="popupTanggal" class="fs-3 text-yellow fw-semibold"></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-person-circle fs-1 text-warning"></i>
                    <div>
                        <div class="fw-bold small text-grey">Nama Siswa</div>
                        <div id="popupNama" class="fs-3 text-yellow fw-semibold"></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <i class="bi bi-cash-coin fs-1 text-warning"></i>
                    <div>
                        <div class="fw-bold small text-grey">Nominal</div>
                        <div id="popupNominal" class="fs-2 text-yellow fw-bold"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4 bg-darkblue">
                <button type="button" class="btn btn-yellow w-100 py-3 fs-4 fw-bold" id="btnDilayani">
                    ORDER SELESAI
                </button>
            </div>
        </div>
    </div>
</div>
</div>


<!-- Audio -->
<audio id="notifSound" src="audio/notif.mp3" preload="auto"></audio>

<!-- Script -->
<script>
    const popup = document.getElementById('popupTransaksi');
    const sound = document.getElementById('notifSound');

    popup.addEventListener('shown.bs.modal', function() {
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch(e => {
                console.warn('Gagal memutar suara:', e);
            });
        }
    });
</script>
<script>
    let transaksiQueue = [];
    let isPopupActive = false;
    let lastIds = new Set();

    function formatTanggalIndonesia(tanggal) {
        const date = new Date(tanggal);
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const hari = date.getDate();
        const bulanStr = bulan[date.getMonth()];
        const tahun = date.getFullYear();
        const jam = String(date.getHours()).padStart(2, '0');
        const menit = String(date.getMinutes()).padStart(2, '0');
        // const detik = String(date.getSeconds()).padStart(2, '0');
        // return `${hari} ${bulanStr} ${tahun} ${jam}:${menit} WIB`; // Lengkap
        return `${jam}:${menit}`;
    }

    function tampilkanPopup(transaksi) {
        document.getElementById('popupTanggal').textContent = formatTanggalIndonesia(transaksi.tanggal);
        document.getElementById('popupNama').textContent = transaksi.nama_siswa;
        document.getElementById('popupNominal').textContent = 'Rp ' + parseInt(transaksi.nominal).toLocaleString('id-ID');

        const modal = new bootstrap.Modal(document.getElementById('popupTransaksi'));
        modal.show();

        document.getElementById('btnDilayani').onclick = () => {
            fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + transaksi.id
                })
                .then(() => {
                    const row = document.querySelector(`tr[data-id="${transaksi.id}"]`);
                    if (row) row.classList.add('table-success');

                    // Tutup modal secara resmi
                    const modalInstance = bootstrap.Modal.getInstance(popup);
                    modalInstance.hide();

                    // Setelah animasi selesai, bersihkan backdrop & class modal
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();

                        document.body.classList.remove('modal-open');
                        document.body.style = '';
                    }, 300); // Sesuai durasi animasi modal Bootstrap

                    isPopupActive = false;
                    transaksiQueue.shift();
                    if (transaksiQueue.length > 0) tampilkanPopup(transaksiQueue[0]);
                });
        };

        document.getElementById('popupTransaksi').addEventListener('hidden.bs.modal', () => {
            isPopupActive = false;
        }, {
            once: true
        });

        isPopupActive = true;
    }

    function fetchTransaksi() {
        fetch('fetch_transaksi.php')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('transaksi-body');
                tbody.innerHTML = '';

                const newLastIds = new Set();

                data.forEach(row => {
                    newLastIds.add(row.id);

                    const tr = document.createElement('tr');
                    tr.setAttribute('data-id', row.id);

                    if (row.status_dilayani == 1) {
                        tr.classList.add('table-success');
                    }

                    tr.innerHTML = `
                    <td><strong>${formatTanggalIndonesia(row.tanggal)}</strong></td>
                    <td>${row.nama_siswa}</td>
                    <td class="text-end">Rp ${parseInt(row.nominal).toLocaleString('id-ID')}</td>
                `;

                    tbody.appendChild(tr);

                    if (!lastIds.has(row.id) && row.status_dilayani == 0) {
                        transaksiQueue.push(row);
                    }
                });

                lastIds = newLastIds;

                if (!isPopupActive && transaksiQueue.length > 0) {
                    tampilkanPopup(transaksiQueue[0]);
                }
            })
            .catch(err => {
                const tbody = document.getElementById('transaksi-body');
                tbody.innerHTML = `<tr><td colspan="3" class="text-danger text-center">❌ Gagal mengambil data.</td></tr>`;
                console.error("Gagal mengambil data transaksi:", err);
            });
    }

    // Jalankan saat awal & setiap 5 detik
    fetchTransaksi();
    setInterval(fetchTransaksi, 5000);
</script>

<script>
    function fetchSaldo() {
        fetch('fetch_saldo.php')
            .then(res => res.json())
            .then(data => {
                const el = document.getElementById('total-pendapatan');
                el.textContent = 'Rp ' + data.saldo.toLocaleString('id-ID');
            })
            .catch(err => {
                console.error("Gagal memuat saldo:", err);
            });
    }

    // Jalankan awal & setiap 5 detik
    fetchSaldo();
    setInterval(fetchSaldo, 5000);
</script>
<script>
    let chartPendapatan;

    function renderChart(data) {
        const ctx = document.getElementById('grafikPendapatan').getContext('2d');

        const labels = data.map(item => item.tanggal);
        const totals = data.map(item => item.total);

        if (chartPendapatan) {
            chartPendapatan.data.labels = labels;
            chartPendapatan.data.datasets[0].data = totals;
            chartPendapatan.update();
            return;
        }

        chartPendapatan = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: totals,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    function fetchPendapatanChart() {
        fetch('fetch_pendapatan_chart.php')
            .then(res => res.json())
            .then(data => renderChart(data))
            .catch(err => console.error("Gagal memuat chart:", err));
    }

    fetchPendapatanChart();
    setInterval(fetchPendapatanChart, 10000); // Update setiap 10 detik
</script>
<script>
    document.getElementById('statusCard').addEventListener('click', function() {
        const card = this;
        const statusText = document.getElementById('statusText').textContent;
        const statusBaru = statusText.includes('Tutup') ? 'tutup' : 'buka';

        fetch('update_status_toko.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'status=' + statusBaru
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const isBuka = data.status === 'buka';
                    card.classList.toggle('bg-success', isBuka);
                    card.classList.toggle('bg-danger', !isBuka);
                    card.classList.toggle('fw-bold', true); // jika selalu ingin ditambahkan
                    document.getElementById('statusText').textContent = isBuka ? 'Tutup Kantin' : 'Buka Kantin';
                } else {
                    alert('Gagal memperbarui status');
                }
            })
            .catch(err => {
                alert('Gagal terkoneksi ke server');
                console.error(err);
            });
    });
</script>

<!-- Footer & Menu Mobile -->
<?php include 'inc/footer.php'; ?>