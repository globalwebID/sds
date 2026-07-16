<?php
include 'inc/fungsi.php';
$role = $_SESSION['role'] ?? null;
// Cek apakah role bukan admin/superadmin/operator
if (!in_array($role, ['admin', 'superadmin', 'operator'])) {
    header('Location: login.php?error=Akses ditolak');
    exit;
}

// Statistik dasar
$siswa_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran_siswa");
$total_siswa = mysqli_fetch_assoc($siswa_result)['total'] ?? 0;

$saldo_result = mysqli_query($conn, "SELECT SUM(saldo) AS total FROM pendaftaran_siswa");
$saldo_siswa = mysqli_fetch_assoc($saldo_result)['total'] ?? 0;

$kantin_result = mysqli_query($conn, "SELECT SUM(saldo) AS total FROM kantin");
$saldo_kantin = mysqli_fetch_assoc($kantin_result)['total'] ?? 0;

$total_saldo = $saldo_siswa + $saldo_kantin;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = trim($_POST['uid']);
    $nominal = isset($_POST['nominal']) ? (int) $_POST['nominal'] : 0;

    if ($uid === '' || $nominal <= 0) {
        $error = 'Masukkan UID dan nominal yang valid.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama_lengkap, saldo, nohp_siswa, nohp_ortu FROM pendaftaran_siswa WHERE rfid_uid = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $siswa = $result->fetch_assoc();

        if ($siswa) {
            $saldo_akhir = $siswa['saldo'] + $nominal;

            // Update saldo
            $update = $conn->prepare("UPDATE pendaftaran_siswa SET saldo = ? WHERE id = ?");
            $update->bind_param("ii", $saldo_akhir, $siswa['id']);
            $update->execute();

            // Simpan ke tabel topup
            $insert = $conn->prepare("INSERT INTO topup (id_siswa, tanggal, nominal, saldo_akhir) VALUES (?, NOW(), ?, ?)");
            $insert->bind_param("iii", $siswa['id'], $nominal, $saldo_akhir);
            $insert->execute();

            // Kirim notifikasi WhatsApp
            $message = "✅ *Bukti Transaksi Top-up M-Kantin*\n\n" .
                "Nama : {$siswa['nama_lengkap']}\n" .
                "Jumlah Top-up : Rp " . number_format($nominal, 0, ',', '.') . "\n" .
                "*Saldo Akhir : Rp " . number_format($saldo_akhir, 0, ',', '.') . "*\n\n" .
                "Terima kasih telah menggunakan layanan kami 🙏\n\n".
                "_Pesan dikirim dari Aplikasi *M-Kantin* SMKN 1 PROBOLINGGO_";

            // Kirim ke siswa
            if (!empty($siswa['nohp_siswa'])) {
                kirim_wa($siswa['nohp_siswa'], $message);
            }

            // Kirim ke orang tua
            if (!empty($siswa['nohp_ortu'])) {
                kirim_wa($siswa['nohp_ortu'], $message);
            }
            // End Kirim notifikasi WhatsApp

            $success = "Top-up berhasil untuk <strong>{$siswa['nama_lengkap']}</strong>. Saldo baru: <strong>Rp " . number_format($saldo_akhir, 0, ',', '.') . "</strong>";
            // Cetak struk pakai JavaScript
            echo "
            <script>
                setTimeout(() => {
                    document.getElementById('struk-nama').innerText = " . json_encode($siswa['nama_lengkap']) . ";
                    document.getElementById('struk-uid').innerText = " . json_encode($uid) . ";
                    document.getElementById('struk-tanggal').innerText = new Date().toLocaleString('id-ID');
                    document.getElementById('struk-nominal').innerText = '" . number_format($nominal, 0, ',', '.') . "';
                    document.getElementById('struk-saldo').innerText = '" . number_format($saldo_akhir, 0, ',', '.') . "';
                    
                    const printContents = document.getElementById('struk-cetak').innerHTML;
                    const win = window.open('', '', 'width=400,height=600');
                    win.document.write('<html><head><title>Struk Top-up</title></head><body>' + printContents + '</body></html>');
                    win.document.close();
                    win.print();
                }, 500);
            </script>";

            $_SESSION['last_struk'] = [
                'nama' => $siswa['nama_lengkap'],
                'uid' => $uid,
                'nominal' => $nominal,
                'saldo_akhir' => $saldo_akhir,
                'tanggal' => date('d-m-Y H:i:s'),
            ];
        }
    }
}

?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>
<div class="container">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <script>
            setTimeout(() => window.location.href = window.location.href.split('?')[0], 2000);
        </script>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-receipt text-info card-icon me-3"></i>
                    <div>
                        <h5>Transaksi Hari ini</h5>
                        <p class="mb-0 fw-bold" id="transaksi-hari-ini">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-currency-exchange text-danger card-icon me-3"></i>
                    <div>
                        <h5>Transaksi Hari ini</h5>
                        <p class="mb-0 fw-bold" id="nominal-hari-ini">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class=" col-md-3">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-receipt text-info card-icon me-3"></i>
                    <div>
                        <h5>Total Transaksi</h5>
                        <p class="mb-0 fw-bold" id="total-transaksi">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-currency-exchange text-danger card-icon me-3"></i>
                    <div>
                        <h5>Total Transaksi</h5>
                        <p class="mb-0 fw-bold" id="total-nominal">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <a href="siswa.php" style="text-decoration: none; color: inherit;">
            <div class="card shadow-sm p-3 bg-info">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 card-icon me-3"></i>
                    <div>
                        <h5>Saldo Siswa</h5>
                        <p class="mb-0 fw-bold" id="saldo-siswa">Memuat...</p>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="penarikan.php" style="text-decoration: none; color: inherit;">
                <div id="card-saldo-kantin" class="card shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-wallet2 card-icon me-3"></i>
                        <div>
                            <h5>Saldo Kantin</h5>
                            <p class="mb-0 fw-bold" id="saldo-kantin">Memuat...</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="topup.php" style="text-decoration: none;">
            <div class="card shadow-sm text-success p-3" style="transition: 0.3s; cursor: pointer;">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet-fill text-success card-icon me-3"></i>
                    <div>
                        <h5>Total Top-up</h5>
                        <p class="mb-0 fw-bold" id="total-topup">Memuat...</p>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <!--<div class="col-md-3">-->
        <!--    <div class="card shadow-sm text-success p-3" data-bs-toggle="modal" data-bs-target="#topupModal" style="transition: 0.3s; cursor: pointer;">-->
        <!--        <div class="d-flex align-items-center">-->
        <!--            <i class="bi bi-wallet-fill text-success card-icon me-3"></i>-->
        <!--            <div>-->
        <!--                <h5>Total Top-up</h5>-->
        <!--                <p class="mb-0 fw-bold" id="total-topup">Memuat...</p>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </div>-->
        <!--</div>-->

        <div class="col-md-3">
            <div class="card shadow-sm bg-success p-3 text-white">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 text-white card-icon me-3"></i>
                    <div>
                        <h5>Saldo Saat ini</h5>
                        <p class="mb-0 fw-bold" id="saldo-sekarang">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <h4 class="mb-3">📄 Riwayat Transaksi Terakhir</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover shadow-sm bg-white rounded">
            <thead class="table-success">
                <tr>
                    <th>Waktu</th>
                    <th>Nama Siswa</th>
                    <th>Jenis</th>
                    <th>Kantin</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody id="transaksi-body">
                <!-- Data akan dimuat lewat AJAX -->
            </tbody>
        </table>
    </div>
</div>
<audio id="notif-audio" src="sounds/beep.mp3" preload="auto" loop></audio>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let lastData = '';

    function loadTransaksi() {
        $.get('tables/ajax_transaksi_terakhir.php', function(data) {
            const newHTML = data.trim();

            if (lastData !== newHTML) {
                $('#transaksi-body').html(newHTML);

                const $firstRow = $('#transaksi-body tr').first();

                // Tambahkan class highlight
                $firstRow.addClass('highlight-green');

                // Hapus setelah 3 detik agar bisa dipakai lagi saat update selanjutnya
                setTimeout(() => {
                    $firstRow.removeClass('highlight-green');
                }, 3000);

                lastData = newHTML;
            }
        });
    }

    // Load awal
    loadTransaksi();

    // Perbarui setiap 5 detik
    setInterval(loadTransaksi, 5000);
</script>

<script>
    let lastPenarikanState = false; // Menyimpan status sebelumnya

    function updateDashboard() {
        fetch('api/get_dashboard_data.php')
            .then(res => res.json())
            .then(data => {
                console.log(data);
                const formatRp = num => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

                document.getElementById('transaksi-hari-ini').innerText = data.total_transaksi_hari_ini + ' transaksi';
                document.getElementById('nominal-hari-ini').innerText = formatRp(data.total_nominal_transaksi_hari_ini);
                document.getElementById('total-transaksi').innerText = data.total_transaksi + ' transaksi';
                document.getElementById('total-nominal').innerText = formatRp(data.total_nominal_transaksi);
                document.getElementById('saldo-siswa').innerText = formatRp(data.total_saldo);
                document.getElementById('saldo-kantin').innerText = formatRp(data.total_nominal_kantin);
                document.getElementById('total-topup').innerText = formatRp(data.total_nominal_topup);
                document.getElementById('saldo-sekarang').innerText = formatRp(data.total_saldo_sekarang);

                // Efek kedip dan suara jika ada permintaan tarik saldo
                const cardKantin = document.getElementById('card-saldo-kantin');
                const audio = document.getElementById('notif-audio');

                if (data.ada_penarikan) {
                    cardKantin.classList.add('card-blink');
                    cardKantin.classList.remove('bg-warning');

                    if (!lastPenarikanState) {
                        audio.play().catch(e => console.warn("Autoplay ditolak: ", e));
                    }

                    lastPenarikanState = true;
                } else {
                    cardKantin.classList.remove('card-blink');
                    cardKantin.classList.add('bg-warning');
                    lastPenarikanState = false;
                }

                // Update log transaksi
                const tbody = document.getElementById('log-transaksi-body');
                tbody.innerHTML = '';
                data.log.forEach(log => {
                    const row = `<tr>
                        <td>${log.waktu}</td>
                        <td>${log.nama_siswa}</td>
                        <td>${log.jenis}</td>
                        <td>${log.nama_kantin}</td>
                        <td>${formatRp(log.nominal)}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            })
            .catch(err => console.error('Gagal ambil data:', err));
    }

    updateDashboard();
    setInterval(updateDashboard, 3000);
</script>

<!-- Modal Topup -->
<div class="modal fade" id="topupModal" tabindex="-1" aria-labelledby="topupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <!-- Tambahkan class ini -->
        <div class="modal-content">
            <form method="POST" class="p-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="topupModalLabel">Top-up Saldo Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">UID Kartu</label>
                        <input type="text" class="form-control" name="uid" id="uid-input-modal" required>
                        <div id="info-siswa-modal" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal Top-up (Rp)</label>
                        <input type="text" class="form-control" name="nominal_display" id="nominal-display-modal" required>
                        <input type="hidden" name="nominal" id="nominal-hidden-modal">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <?php $preset_nominals = [10000, 20000, 50000, 100000]; ?>
                            <?php foreach ($preset_nominals as $value): ?>
                                <button type="button" class="btn btn-outline-primary shortcut-btn" data-nominal="<?= $value ?>">
                                    Rp <?= number_format($value, 0, ',', '.') ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100 p-3" onclick="this.disabled=true; this.form.submit();">
                        Top-up
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('topupModal');

        modal.addEventListener('hidden.bs.modal', function() {
            // Reset semua input
            document.getElementById('uid-input-modal').value = '';
            document.getElementById('nominal-display-modal').value = '';
            document.getElementById('nominal-hidden-modal').value = '';
            document.getElementById('info-siswa-modal').innerHTML = '';

            // Reset highlight tombol nominal (jika kamu tambahkan fitur active misalnya)
            document.querySelectorAll('.shortcut-btn').forEach(btn => btn.classList.remove('active'));
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const topupModal = document.getElementById('topupModal');
        const uidInput = document.getElementById('uid-input-modal');
        const nominalDisplay = document.getElementById('nominal-display-modal');
        const nominalHidden = document.getElementById('nominal-hidden-modal');
        const infoDiv = document.getElementById('info-siswa-modal');
        const shortcutButtons = document.querySelectorAll('.shortcut-btn');

        // Autofocus saat modal dibuka
        topupModal.addEventListener('shown.bs.modal', function() {
            uidInput.focus();
            uidInput.select();
        });

        // Format angka ke format ribuan
        function formatRibuan(nilai) {
            return nilai.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function unformatRibuan(nilai) {
            return nilai.replace(/\./g, "");
        }

        // Input nominal manual
        nominalDisplay.addEventListener("input", function() {
            let raw = this.value.replace(/\D/g, "");
            let formatted = formatRibuan(raw);
            nominalDisplay.value = formatted;
            nominalHidden.value = raw;

            shortcutButtons.forEach(btn => btn.classList.remove('btn-primary', 'active'));
            shortcutButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
        });

        // Tombol shortcut nominal
        shortcutButtons.forEach(button => {
            button.addEventListener('click', function() {
                const nominal = this.dataset.nominal;

                nominalDisplay.value = formatRibuan(nominal);
                nominalHidden.value = nominal;

                shortcutButtons.forEach(btn => btn.classList.remove('btn-primary', 'active'));
                shortcutButtons.forEach(btn => btn.classList.add('btn-outline-primary'));

                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary', 'active');
            });
        });

        // Ambil info siswa saat input UID
        uidInput.addEventListener('input', function() {
            const uid = uidInput.value.trim();
            if (uid.length >= 5) {
                fetch('tables/ajax_get_siswa.php?uid=' + uid)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            infoDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        } else {
                            infoDiv.innerHTML = `<div class="alert alert-info">
                            <strong>Nama:</strong> ${data.nama}<br>
                            <strong>Saldo:</strong> Rp ${parseInt(data.saldo).toLocaleString('id-ID')}
                        </div>`;
                        }
                    })
                    .catch(() => {
                        infoDiv.innerHTML = `<div class="alert alert-warning">Gagal mengambil data.</div>`;
                    });
            } else {
                infoDiv.innerHTML = '';
            }
        });
    });
</script>

<?php include 'inc/footer.php'; ?>