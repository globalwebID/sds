<?php
include 'inc/fungsi.php';

// Cek role
if (!in_array($role, ['admin', 'superadmin', 'operator'])) {
    header('Location: login.php?error=Akses ditolak');
    exit;
}

$success = '';
$error   = '';

/**
 * RULE TOPUP VALID:
 * - Manual/sekolah: merchant_order_id & duitku_reference NULL => valid
 * - Duitku: merchant_order_id atau duitku_reference NOT NULL => valid hanya kalau status = PAID
 */
$filterTopupValid = "
(
  (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL)
  OR tp.status = 'PAID'
)
";

/**
 * Ambil ID petugas dari tabel users berdasarkan username session.
 * Session yang ada: $_SESSION['username'] (lihat inc/fungsi.php)
 */
$petugas_username = $_SESSION['username'] ?? '';
$petugas_id = null;

if ($petugas_username !== '') {
    $qPetugas = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $qPetugas->bind_param("s", $petugas_username);
    $qPetugas->execute();
    $rPetugas = $qPetugas->get_result()->fetch_assoc();
    if ($rPetugas && isset($rPetugas['id'])) {
        $petugas_id = (int)$rPetugas['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid     = trim($_POST['uid'] ?? '');
    $nominal = isset($_POST['nominal']) ? (int)$_POST['nominal'] : 0;

    if ($uid === '' || $nominal <= 0) {
        $error = 'Masukkan UID dan nominal yang valid.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama_lengkap, saldo, nohp_siswa, nohp_ortu FROM pendaftaran_siswa WHERE rfid_uid = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $siswa  = $result->fetch_assoc();

        if (!$siswa) {
            $error = 'Siswa dengan UID tersebut tidak ditemukan.';
        } else {
            $saldo_awal  = (int)$siswa['saldo'];
            $saldo_akhir = $saldo_awal + $nominal;

            $conn->begin_transaction();
            try {
                // Update saldo siswa
                $update = $conn->prepare("UPDATE pendaftaran_siswa SET saldo = ? WHERE id = ?");
                $update->bind_param("ii", $saldo_akhir, $siswa['id']);
                $update->execute();

                /**
                 * INSERT TOPUP MANUAL/SEKOLAH:
                 * - petugas_id disimpan agar bisa dilihat di laporan
                 * - status = PAID agar tidak bercampur dengan PENDING Duitku
                 */
                $insert = $conn->prepare("
                    INSERT INTO topup (id_siswa, petugas_id, tanggal, nominal, saldo_akhir, status, paid_at)
                    VALUES (?, ?, NOW(), ?, ?, 'PAID', NOW())
                ");

                // Handle NULL petugas_id
                if ($petugas_id === null || $petugas_id <= 0) {
                    $null = null;
                    $insert->bind_param("iiii", $siswa['id'], $null, $nominal, $saldo_akhir);
                } else {
                    $insert->bind_param("iiii", $siswa['id'], $petugas_id, $nominal, $saldo_akhir);
                }

                $insert->execute();

                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'Gagal topup. ' . $e->getMessage();
            }

            if (!$error) {
                // Kirim notifikasi WhatsApp
                $message = "✅ *Bukti Transaksi Top-up M-Kantin*\n\n" .
                    "Nama : {$siswa['nama_lengkap']}\n" .
                    "Jumlah Top-up : Rp " . number_format($nominal, 0, ',', '.') . "\n" .
                    "Saldo Akhir : Rp " . number_format($saldo_akhir, 0, ',', '.') . "\n\n" .
                    "Terima kasih telah menggunakan layanan kami 🙏\n\n" .
                    "_Pesan dikirim dari Aplikasi *M-Kantin* SMKN 1 PROBOLINGGO_";

                if (!empty($siswa['nohp_siswa'])) kirim_wa($siswa['nohp_siswa'], $message);
                if (!empty($siswa['nohp_ortu']))  kirim_wa($siswa['nohp_ortu'],  $message);

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
                    'nama'       => $siswa['nama_lengkap'],
                    'uid'        => $uid,
                    'nominal'    => $nominal,
                    'saldo_akhir'=> $saldo_akhir,
                    'tanggal'    => date('d-m-Y H:i:s'),
                ];
            }
        }
    }
}

/**
 * Riwayat topup valid + petugas
 */
$riwayat_result = mysqli_query($conn, "
    SELECT 
        tp.tanggal,
        s.nama_lengkap,
        tp.nominal,
        tp.saldo_akhir,
        COALESCE(u.username, '-') AS petugas_username,
        CASE
            WHEN (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL) THEN 'SEKOLAH'
            ELSE 'MERCHANT'
        END AS sumber
    FROM topup tp
    JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
    LEFT JOIN users u ON tp.petugas_id = u.id
    WHERE $filterTopupValid
    ORDER BY tp.tanggal DESC
    LIMIT 10
");

/**
 * Total topup valid saja (manual + Duitku PAID)
 */
$saldo_result = mysqli_query($conn, "
    SELECT SUM(tp.nominal) AS total
    FROM topup tp
    WHERE $filterTopupValid
");
$total_topup = mysqli_fetch_assoc($saldo_result)['total'] ?? 0;

include 'inc/header.php';
include 'inc/navbar.php';
?>
<div class="container">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <script>
            setTimeout(() => window.location.href = window.location.href.split('?')[0], 2000);
        </script>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- STRUK CETAK (disembunyikan) -->
    <div id="struk-cetak" style="display: none; font-family: arial; padding: 20px;">
        <h4>Struk Top-up</h4>
        <span id="struk-tanggal"></span>
        <hr>
        <p><strong>Nama:</strong> <span id="struk-nama"></span></p>
        <p><strong>UID:</strong> <span id="struk-uid"></span></p>
        <p><strong>Tanggal:</strong> <span id="struk-tanggal"></span></p>
        <p><strong>Top-up:</strong> Rp <span id="struk-nominal"></span></p>
        <p><strong>Saldo Baru:</strong> Rp <span id="struk-saldo"></span></p>
        <hr>
        <p>Terima kasih 🙏</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 text-primary card-icon me-3"></i>
                    <div>
                        <span style="display:flex"><h5>Total Top-up</h5>*<small class="text-muted">Manual + Merchant</small></span>
                        <p class="mb-0 fw-bold">Rp <?= number_format((int)$total_topup, 0, ',', '.') ?></p>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm p-3 cursor-pointer" data-bs-toggle="modal" data-bs-target="#topupModal" style="transition: 0.3s; cursor: pointer;">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet-fill card-icon me-3 fs-2 text-white"></i>
                    <div>
                        <h5>Top-up Saldo</h5>
                        <p class="mb-0 fw-bold">Klik untuk tambah saldo</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?php if (isset($_SESSION['last_struk'])): ?>
                <div class="card shadow-sm p-3 cursor-pointer" onclick="cetakStrukUlang()" style="transition: 0.3s; cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-printer-fill text-success card-icon me-3 fs-2"></i>
                        <div>
                            <h5>Cetak Ulang Struk</h5>
                            <p class="mb-0 fw-bold">Klik untuk mencetak ulang</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h4 class="mb-3">Riwayat Top-up Terakhir (Valid)</h4>
            <div class="table-responsive">
                <table class="table table-striped table-bordered shadow-sm bg-white rounded">
                    <thead class="table-success">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Sumber</th>
                            <th>Saldo Awal</th>
                            <th>Top-up</th>
                            <th>Saldo Akhir</th>
                            <th>Petugas Topup</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($riwayat_result && mysqli_num_rows($riwayat_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($riwayat_result)): ?>
                                <?php
                                    $saldo_awal = (int)$row['saldo_akhir'] - (int)$row['nominal'];
                                    $sumber = strtoupper((string)$row['sumber']);
                                    $badge = ($sumber === 'MERCHANT')
                                        ? '<span class="badge bg-info text-dark">Merchant</span>'
                                        : '<span class="badge bg-dark">Sekolah</span>';
                                ?>
                                <tr>
                                    <td><?= date('d M Y H:i', strtotime($row['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                    <td><?= $badge ?></td>
                                    <td>Rp <?= number_format($saldo_awal, 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format((int)$row['nominal'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format((int)$row['saldo_akhir'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars((string)($row['petugas_username'] ?? '-')) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Belum ada data top-up</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <small class="text-muted">* Topup Merchant yang status <b>PENDING/FAILED/EXPIRED</b> tidak ditampilkan.</small>
            </div>
        </div>
    </div>
</div>

<!-- Modal Topup -->
<div class="modal fade" id="topupModal" tabindex="-1" aria-labelledby="topupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" class="p-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="topupModalLabel">Top-up Saldo Siswa (Manual/Sekolah)</h5>
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
                    <button type="button" class="btn btn-success w-100 p-3" id="btn-topup-submit">
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
        document.getElementById('uid-input-modal').value = '';
        document.getElementById('nominal-display-modal').value = '';
        document.getElementById('nominal-hidden-modal').value = '';
        document.getElementById('info-siswa-modal').innerHTML = '';
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

    topupModal.addEventListener('shown.bs.modal', function() {
        uidInput.focus();
        uidInput.select();
    });

    function formatRibuan(nilai) {
        return nilai.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    nominalDisplay.addEventListener("input", function() {
        let raw = this.value.replace(/\D/g, "");
        nominalDisplay.value = formatRibuan(raw);
        nominalHidden.value = raw;

        shortcutButtons.forEach(btn => btn.classList.remove('btn-primary', 'active'));
        shortcutButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
    });

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

    uidInput.addEventListener('input', function() {
        const uid = uidInput.value.trim();
        if (uid.length >= 5) {
            fetch('tables/ajax_get_siswa.php?uid=' + encodeURIComponent(uid))
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

<script>
document.getElementById('btn-topup-submit').addEventListener('click', function() {
    const nominal = document.getElementById('nominal-hidden-modal').value;
    const uid = document.getElementById('uid-input-modal').value.trim();

    if (!uid) {
        alert("UID tidak boleh kosong!");
        return;
    }

    if (!nominal || parseInt(nominal) <= 0) {
        alert("Nominal top-up belum diisi atau tidak valid.");
        return;
    }

    this.disabled = true;
    this.closest('form').submit();
});
</script>

<script>
function cetakStrukUlang() {
<?php if (isset($_SESSION['last_struk'])):
    $data = $_SESSION['last_struk'];
    $struk = [
        'nama'    => $data['nama'],
        'uid'     => $data['uid'],
        'nominal' => number_format($data['nominal'], 0, ',', '.'),
        'saldo'   => number_format($data['saldo_akhir'], 0, ',', '.'),
        'tanggal' => $data['tanggal']
    ];
?>
    const struk = <?= json_encode($struk, JSON_UNESCAPED_UNICODE) ?>;

    const html = `
<!DOCTYPE html>
<html>
<head>
    <title>Struk</title>
    <style>
        @media print {
            @page { size: 80mm auto; margin: 5mm; }
            body { margin: 0; font-family: monospace; font-size: 12px; }
        }
        body { font-family: monospace; padding: 10px; width: 100%; }
        .struk { text-align: center; }
        .struk hr { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
        .struk p { margin: 2px 0; }
        table { width: 95%; }
        td:first-child { text-align: left; }
        td:last-child { text-align: right; width: 60%; }
    </style>
</head>
<body onload="window.print(); window.close();">
    <div class="struk">
        <h3>Struk Top-up</h3>
        <p>${struk.tanggal}</p>
        <hr>
        <table>
            <tr><td>Nama</td><td>${struk.nama}</td></tr>
            <tr><td>UID</td><td>${struk.uid}</td></tr>
            <tr><td>Top-up</td><td>Rp ${struk.nominal}</td></tr>
            <tr><td>Saldo</td><td>Rp ${struk.saldo}</td></tr>
        </table>
        <hr>
        <table>
            <tr><td>Bayar</td><td>Rp ${struk.nominal}</td></tr>
        </table>
        <hr>
        <p>Terima kasih 🙏</p>
    </div>
</body>
</html>`;

    const printWindow = window.open('data:text/html;charset=utf-8,' + encodeURIComponent(html), '_blank', 'width=400,height=600');
    printWindow.document.write(html);
    printWindow.document.close();
<?php endif; ?>
}
</script>

<?php include 'inc/footer.php'; ?>
