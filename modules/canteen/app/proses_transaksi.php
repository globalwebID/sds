<?php
require_once '../config/runtime.php';
sds_session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['id_siswa'])) {
    header("Location: scan_kartu.php");
    exit;
}
include '../config/db.php';

if (empty($_SESSION['mkantin_csrf'])) {
    $_SESSION['mkantin_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['kantin_request_key'])) {
    $_SESSION['kantin_request_key'] = bin2hex(random_bytes(24));
}

// Ambil data siswa dari session
$id_siswa = (int) $_SESSION['id_siswa'];
$result = mysqli_query($conn, "SELECT * FROM pendaftaran_siswa WHERE id='$id_siswa'");
$siswa = mysqli_fetch_assoc($result);

$error = '';
$success = isset($_GET['success']) ? true : false;

// taruh function di atas biar aman
if (!function_exists('kirim_wa')) {
    function kirim_wa($nomor, $message)
    {
        if (substr($nomor, 0, 1) === "0") {
            $nomor = "62" . substr($nomor, 1);
        }

        $api_URL = (string)sds_config('services.whatsapp.url', '');
        $apiKey = (string)sds_config('services.whatsapp.api_key', '');
        $sender = (string)sds_config('services.whatsapp.sender', '');
        if ($api_URL === '' || $apiKey === '' || $sender === '' || $nomor === '') return;
        $data = [
            'api_key' => $apiKey,
            'sender'  => $sender,
            'number'  => $nomor,
            'message' => $message
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
    }
}

// Proses saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaksi'])) {

    if (!hash_equals((string)$_SESSION['mkantin_csrf'], (string)($_POST['csrf'] ?? ''))
        || !hash_equals((string)$_SESSION['kantin_request_key'], (string)($_POST['request_key'] ?? ''))) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } else {

    $nominal   = (int) str_replace('.', '', $_POST['nominal']);
    $id_kantin = (int) ($_POST['id_kantin'] ?? 0);

    if ($nominal <= 0) {
        $error = "Nominal tidak boleh kosong atau nol!";
    } elseif ($id_kantin <= 0) {
        $error = "Kantin tidak valid!";
    } else {

        mysqli_begin_transaction($conn);

        try {
            // 1) POTONG SALDO (REALTIME + ATOMIC) hanya jika: tidak diblokir & saldo cukup
            $stmt = mysqli_prepare($conn, "
                UPDATE pendaftaran_siswa
                SET saldo = saldo - ?
                WHERE id = ? AND blokir = 0 AND saldo >= ?
            ");
            mysqli_stmt_bind_param($stmt, "iii", $nominal, $id_siswa, $nominal);
            mysqli_stmt_execute($stmt);

            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected <= 0) {
                // gagal: cek kondisi terbaru
                $cek = mysqli_query($conn, "SELECT saldo, blokir FROM pendaftaran_siswa WHERE id='$id_siswa' LIMIT 1");
                $now = mysqli_fetch_assoc($cek);

                mysqli_rollback($conn);

                if ((int)($now['blokir'] ?? 0) === 1) {
                    $error = "KARTU KAMU SEDANG DIBLOKIR. HUBUNGI ADMIN!";
                } else {
                    $error = "SALDO KAMU TIDAK CUKUP, AYO TOP-UP DULU!";
                }

            } else {
                // 2) Catat transaksi
                $requestKey = (string)$_SESSION['kantin_request_key'];
                $ins = $conn->prepare('INSERT INTO transaksi_kantin (id_siswa,tanggal,nominal,id_kantin,request_key) VALUES (?,NOW(),?,?,?)');
                $ins->bind_param('iiis', $id_siswa, $nominal, $id_kantin, $requestKey);
                $ins->execute();
                $ins->close();

                // 3) Tambah saldo kantin
                $upK = $conn->prepare('UPDATE kantin SET saldo=saldo+? WHERE id=?');
                $upK->bind_param('ii', $nominal, $id_kantin);
                $upK->execute();
                if ($upK->affected_rows !== 1) throw new RuntimeException('Kantin tidak ditemukan');
                $upK->close();

                // 4) Ambil data terbaru untuk WA
                $r2 = mysqli_query($conn, "SELECT saldo, nohp_siswa, nohp_ortu, nama_lengkap FROM pendaftaran_siswa WHERE id='$id_siswa' LIMIT 1");
                $siswa_baru = mysqli_fetch_assoc($r2);

                $qk = mysqli_query($conn, "SELECT nama FROM kantin WHERE id = $id_kantin LIMIT 1");
                $kantinRow = mysqli_fetch_assoc($qk);

                mysqli_commit($conn);
                unset($_SESSION['kantin_request_key']);

                $new_saldo   = (int)($siswa_baru['saldo'] ?? 0);
                $nomor_siswa = $siswa_baru['nohp_siswa'] ?? '';
                $nomor_ortu  = $siswa_baru['nohp_ortu'] ?? '';
                $nama        = $siswa_baru['nama_lengkap'] ?? '';
                $kantin      = $kantinRow['nama'] ?? '-';

                $message =
                    "✅ *Bukti Transaksi Pembayaran M-Kantin*\n\n" .
                    "Nama: $nama\n" .
                    "Kantin: $kantin\n" .
                    "Nominal: Rp " . number_format($nominal, 0, ',', '.') . "\n" .
                    "Sisa Saldo: Rp " . number_format($new_saldo, 0, ',', '.') . "\n\n" .
                    "Terima kasih telah menggunakan layanan kami 🙏\n" .
                    "_Pesan dikirim dari Aplikasi *M-Kantin* {$pengaturan['nama_sekolah']}_";

                // Kirim WA (opsional: cek nomor tidak kosong)
                if (!empty($nomor_siswa)) kirim_wa($nomor_siswa, $message);
                if (!empty($nomor_ortu))  kirim_wa($nomor_ortu, $message);

                header("Location: proses_transaksi.php?success=1");
                exit;
            }

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan saat memproses transaksi!";
            // kalau mau debug:
            // $error .= " (" . $e->getMessage() . ")";
        }
    }
    }
}


$kantin_result = mysqli_query($conn, "SELECT * FROM kantin");
$kantin_data = mysqli_fetch_all($kantin_result, MYSQLI_ASSOC);
// Urutkan: yang buka dulu, lalu yang tutup
usort($kantin_data, function ($a, $b) {
    return $a['status_toko'] === 'tutup' && $b['status_toko'] === 'buka' ? 1 : -1;
});

// Query gabungkan data transaksi dan nama kantin
$query = "
    SELECT tk.tanggal, tk.nominal, k.nama AS nama_kantin
    FROM transaksi_kantin tk
    LEFT JOIN kantin k ON tk.id_kantin = k.id
    WHERE tk.id_siswa = '$id_siswa'
    ORDER BY tk.tanggal DESC
";

$result = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style_pt.css">
    <style>
        .badge-tutup {
            background-color: rgba(220, 53, 69, 0.9);
            /* Bootstrap danger */
            color: white;
            padding: 4px 8px;
            border-radius: 8px 0 0 8px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 10;
        }

        .nav-pills .nav-link {
            transition: all 0.3s ease;
            border-radius: 50px;
            background-color: #1a1a40;
            color: #ffffff;
            text-align: left;
            padding-left: 2rem;
        }

        .nav-link:hover {
            background-color: #333366;
            color: #fff;
        }

        .nav-pills .nav-link.active,
        .nav-pills .show>.nav-link {
            background-color: #ffd700;
            color: #000;
            font-weight: bold;
            transform: translateX(40px);
            /* Menjorok keluar */
            box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .nav-pills .nav-link {
            border-radius: 50px;
        }

        .nav-item-w {
            width: 23rem;
        }

        .cutom-margin {
            margin-left: -100px;
        }

        .fancybox__content>.f-button.is-close-btn {
            opacity: 0;
        }
        
        @keyframes blink {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.blink {
    animation: blink 1s infinite;
}

        @media (min-width: 768px) and (max-width: 1024px) {

            .container,
            .container-md,
            .container-sm {
                max-width: 920px;
            }

            .text-t {
                font-size: 20px;
            }

            .scroll-text {
                font-size: 1.5rem;
            }

            .bi-person-badge {
                font-size: 60px !important;
            }

            .btn-red {
                font-size: 1.2rem !important;
                padding: 10px 20px !important;
            }

            .modal-content h2,
            .modal-content h1 {
                font-size: 1.5rem !important;
            }

            .modal-content p {
                font-size: 1.2rem !important;
            }

            .preset-btn {
                font-size: 1.2rem !important;
            }

            .form-control-lg {
                font-size: 2rem;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="row w-100 m-0">
        <!-- Sidebar Menu (Kiri - Fixed) -->
        <div class="col-md-3 position-relative">
            <div class="position-fixed top-0 start-0 h-100 px-4 py-4" style="width: 25%;">
                <ul class="nav flex-column nav-pills h-100 d-flex justify-content-between" id="tabMenu" role="tablist">
                    <div>
                        <p class="text-white fs-5 fw-bold m-0"><?= htmlspecialchars($siswa['nama_lengkap']) ?></p>
                        <!-- Info Siswa -->
                        <li class="nav-item mb-4" role="presentation">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-badge text-white" style="font-size: 70px;margin-left: -12px;"></i>
                                <div>
                                    <h4 class="m-0 text-white">Saldo :</h4>
                                    <p class="fs-5 mb-0 text-white">
                                        <strong style="font-size: 35px;">Rp <?= number_format($siswa['saldo'], 0, ',', '.') ?></strong>
                                    </p>
                                </div>
                            </div>
                        </li>

                        <!-- Menu Navigasi -->
                        <li class="nav-item-w mb-2 cutom-margin" role="presentation">
                            <button class="btn btn-lg px-4 py-3 fs-5 w-100 nav-link text-end active"
                                id="tab-kantin-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#tab-kantin"
                                type="button"
                                role="tab">
                                <strong>DAFTAR KANTIN</strong>
                            </button>
                        </li>
                        <li class="nav-item-w mb-2 cutom-margin" role="presentation">
                            <button class="btn btn-lg px-4 py-3 fs-5 w-100 nav-link text-end"
                                id="tab-riwayat-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#tab-riwayat"
                                type="button"
                                role="tab">
                                <strong>RIWAYAT TRANSAKSI</strong>
                            </button>
                        </li>
                        <li class="nav-item-w mb-2 cutom-margin" role="presentation">
                            <button class="btn btn-lg px-4 py-3 fs-5 w-100 nav-link text-end"
                                id="tab-kirim-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#tab-kirim"
                                type="button"
                                role="tab">
                                <strong>KIRIM SALDO</strong>
                            </button>
                        </li>
                        <li class="nav-item-w mb-2 cutom-margin" role="presentation">
                            <button class="btn btn-lg px-4 py-3 fs-5 w-100 nav-link text-end"
                                id="tab-info-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#tab-info"
                                type="button"
                                role="tab">
                                <strong>INFORMASI</strong>
                            </button>
                        </li>
                    </div>

                    <!-- Tombol Keluar (Posisi Bawah Tetap) -->
                    <li class="nav-item mt-auto" role="presentation">
                        <a href="logout.php" class="btn btn-red btn-lg py-3 fs-5 text-white" style="width: 255px;">
                            <strong>KELUAR</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Konten Tab (Kanan) -->
        <div class="col-md-9 offset-md-3">
            <div class="tab-content py-4" id="tabContent">
                <!-- Semua tab-pane konten -->
                <div class="tab-pane fade show active" id="tab-kantin" role="tabpanel">
                    <div class="row" id="daftarKantin">
                        <!-- Diisi via JS/PHP -->
                    </div>
                </div>
                <!-- Tab: Riwayat Transaksi -->
                <div class="tab-pane fade" id="tab-riwayat" role="tabpanel">
                    <?php
                    $id_siswa = $_SESSION['id_siswa'];
                    $riwayat = [];

                    // Transaksi Kantin
                    $trans_kantin = $conn->query("SELECT 'kantin' AS jenis, k.nama AS nama_kantin, t.tanggal, t.nominal 
        FROM transaksi_kantin t 
        LEFT JOIN kantin k ON t.id_kantin = k.id 
        WHERE t.id_siswa = '$id_siswa'");

                    if ($trans_kantin) {
                        while ($row = $trans_kantin->fetch_assoc()) {
                            $riwayat[] = [
                                'jenis' => 'kantin',
                                'judul' => $row['nama_kantin'] ?? 'Kantin',
                                'deskripsi' => 'Pembelian di kantin',
                                'tanggal' => $row['tanggal'],
                                'nominal' => $row['nominal'],
                                'warna' => 'text-danger',
                                'prefix' => '-',
                                'bg' => 'bg-light'
                            ];
                        }
                    }

                    // Transfer Keluar
                    $trans_keluar = $conn->query("SELECT 'transfer-keluar' AS jenis, ps.nama_lengkap AS lawan, l.tanggal, l.jumlah 
        FROM log_transfer l 
        JOIN pendaftaran_siswa ps ON ps.id = l.id_penerima 
        WHERE l.id_pengirim = '$id_siswa'");

                    if ($trans_keluar) {
                        while ($row = $trans_keluar->fetch_assoc()) {
                            $riwayat[] = [
                                'jenis' => 'transfer-keluar',
                                'judul' => $row['lawan'],
                                'deskripsi' => 'Transfer ke ' . $row['lawan'],
                                'tanggal' => $row['tanggal'],
                                'nominal' => $row['jumlah'],
                                'warna' => 'text-dark',
                                'prefix' => '-',
                                'bg' => 'bg-danger-subtle'
                            ];
                        }
                    }

                    // Transfer Masuk
                    $trans_masuk = $conn->query("SELECT 'transfer-masuk' AS jenis, ps.nama_lengkap AS lawan, l.tanggal, l.jumlah 
        FROM log_transfer l 
        JOIN pendaftaran_siswa ps ON ps.id = l.id_pengirim 
        WHERE l.id_penerima = '$id_siswa'");

                    if ($trans_masuk) {
                        while ($row = $trans_masuk->fetch_assoc()) {
                            $riwayat[] = [
                                'jenis' => 'transfer-masuk',
                                'judul' => $row['lawan'],
                                'deskripsi' => 'Saldo diterima dari ' . $row['lawan'],
                                'tanggal' => $row['tanggal'],
                                'nominal' => $row['jumlah'],
                                'warna' => 'text-success',
                                'prefix' => '+',
                                'bg' => 'bg-success-subtle'
                            ];
                        }
                    }

                    // Urutkan berdasarkan tanggal terbaru
                    usort($riwayat, fn($a, $b) => strtotime($b['tanggal']) - strtotime($a['tanggal']));
                    ?>

                    <?php if (count($riwayat) > 0): ?>
                        <?php foreach ($riwayat as $row): ?>
                            <?php
                            $tanggal = date("d F Y, H:i", strtotime($row['tanggal']));
                            $nominal = number_format($row['nominal'], 0, ',', '.');
                            $bg = $row['bg'] ?? 'bg-grey';
                            ?>
                            <div class="mb-3 p-3 rounded <?= $bg ?>">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="fs-4 fw-bold mb-1"><?= htmlspecialchars($row['judul']) ?></h5>
                                        <small class="text-muted fs-5 fw-bold"><?= $tanggal ?> &bull; <?= htmlspecialchars($row['deskripsi']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold fs-4 <?= $row['warna'] ?>" style="font-size: 1.2rem;"><?= $row['prefix'] ?>Rp <?= $nominal ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="fs-5 fw-bold text-white">Belum ada transaksi yang tercatat.</p>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-kirim" role="tabpanel">
                    <div class="d-flex justify-content-center align-items-center" style="height: 85vh;">
                        <div class="card text-center border-0 bg-transparent" style="max-width: 500px; color: #eee;">
                            <h4 class="fw-bold mb-4">Kirim Saldo ke Teman</h4>

                            <!-- SECTION: SCAN RFID -->
                            <div id="section-scan">
                                <img src="images/tap.png" alt="Tap Kartu" class="mb-4 blink" width="250">
                                <label for="rfid_teman" class="form-label fs-4 fw-semibold d-block">Tempelkan Kartu Teman Anda</label>
                                <!--<input type="text" id="rfid_teman"-->
                                <!--    class="form-control form-control-lg text-center position-absolute top-0 start-50 translate-middle-x opacity-0"-->
                                <!--    tabindex="-1" required>-->

                                <!-- <input type="text" id="rfid_teman" class="form-control form-control-lg text-center position-absolute top-0 opacity-0" autofocus required> -->
                                <input type="text" id="rfid_teman"
    class="form-control form-control-lg text-center position-absolute top-0 start-50 translate-middle-x opacity-0"
    tabindex="-1"
    inputmode="none"
    readonly
    onfocus="this.removeAttribute('readonly');"
    required>

                            </div>

                            <!-- Nama Penerima di Luar Modal -->
                            <div id="nama_penerima_modal" class="mt-3 fw-bold fs-4 text-yellow text-center"></div>

                            <!-- FORM KIRIM -->
                            <form method="POST" id="formKirimSaldo">
                                <input type="hidden" name="rfid_teman" id="rfid_hidden">

                                <!-- MODAL -->
                                <div class="modal fade" id="modalKirimSaldo" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h3 class="modal-title text-yellow"><strong>KIRIM SALDO KE TEMAN</strong></h3>
                                                <button type="button" class="btn-close bg-yellow" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body text-center">
                                                <div id="nama_penerima" class="fs-3 fw-bold text-primary mb-3 bg-light p-2 rounded"></div>

                                                <input type="text" name="nominal" id="modalNominalInput" inputmode="numeric"
                                                    class="form-control form-control-lg text-center fs-2 fw-bold mb-3"
                                                    placeholder="Masukkan jumlah kirim" autofocus>

                                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                                    <?php foreach ([2000, 5000, 10000, 15000, 20000, 25000, 30000, 35000] as $value): ?>
                                                        <button type="button" class="btn btn-outline-yellow fs-4 preset-btn fw-bold"
                                                            data-val="<?= $value ?>">Rp <?= number_format($value, 0, ',', '.') ?></button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-warning btn-lg w-100 shadow">
                                                    <strong>KIRIM SEKARANG</strong>
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-info" role="tabpanel">
                    <div class="card shadow-sm mb-3" style="border: none;">
                        <div class="card-body bg-grey" style="border-radius: 5px;">
                            <h4 class="mb-3">📢 Informasi Terbaru</h4>
                            <?php
                            // Ambil data informasi dari database
                            $resultInfo = $conn->query("
    SELECT i.*, iu.dibaca
    FROM informasi i
    LEFT JOIN informasi_user iu ON i.id = iu.informasi_id AND iu.user_id = $id_siswa
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
                                            <!--<p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($info['isi'])) ?></p>-->
                                            <p class="mt-2 mb-0"><?= $info['isi'] ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Belum ada informasi terbaru.</div>
                            <?php endif; ?>

                            <?php
                            // Tandai semua informasi sebagai dibaca
                            $conn->query("UPDATE informasi_user SET dibaca = 1 WHERE user_id = $id_siswa");
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Tambah tab lain jika perlu -->
            </div>
        </div>
    </div>


    <div class="container py-4">
        <?php if (!$siswa): ?>
            <div class="alert alert-danger text-center fs-4">Kartu tidak terdaftar!</div>
            <a href="index.php" class="btn btn-secondary btn-lg w-100">Kembali</a>
        <?php else: ?>

            <?php if ($error): ?>
                <script>
                    window.addEventListener('DOMContentLoaded', () => {
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        errorModal.show();
                    });
                </script>
                <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content text-center p-4" style="background-color: darkred;">
                            <div class="modal-body text-white">
                                <h2 class="mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Transaksi Gagal</h2>
                                <p class="fs-4"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <audio autoplay>
                    <source src="sounds/error.mp3" type="audio/mpeg">
                </audio>
            <?php elseif (isset($_GET['success'])): ?>
                <script>
                    window.addEventListener('DOMContentLoaded', () => {
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                    });
                </script>
                <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content text-center p-4" style="background-color: darkblue;">
                            <div class="modal-body">
                                <h2 class="mb-3 text-yellow">🎉 Transaksi Berhasil!</h2>
                                <p class="fs-4 text-white">Sisa saldo Anda:</p>
                                <h1 class="text-yellow mb-4">Rp <?= number_format($siswa['saldo'], 0, ',', '.') ?></h1>
                            </div>
                        </div>
                    </div>
                </div>
                <audio autoplay>
                    <source src="sounds/success.mp3" type="audio/mpeg">
                </audio>
            <?php endif; ?>

            <div class="row" id="daftarKantin"></div>

            <form method="POST" id="kantinForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['mkantin_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="request_key" value="<?= htmlspecialchars($_SESSION['kantin_request_key'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="uid" value="<?= htmlspecialchars($uid) ?>">
                <input type="hidden" name="id_kantin" id="formKantinId">
                <input type="hidden" name="transaksi" value="1">
                <div class="modal fade" id="modalNominal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title text-yellow"><strong>MASUKKAN NOMINAL PEMBAYARAN</strong></h3>
                                <button type="button" class="btn-close bg-yellow" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">

                                <!-- PREVIEW KANTIN DIPILIH -->
                                <div id="selectedKantinPreview" class="d-flex align-items-center justify-content-center mb-4">
                                    <!-- Akan diisi lewat JavaScript -->
                                </div>

                                <input type="text" name="nominal" id="NominalInput" class="form-control form-control-lg text-center fs-1 fw-bold mb-3" placeholder="Masukkan jumlah">
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <?php foreach ([2000, 5000, 10000, 15000, 20000, 25000, 30000, 35000] as $value): ?>
                                        <button type="button" class="btn btn-outline-yellow fs-3 preset-btn fw-bold" data-val="<?= $value ?>">Rp <?= number_format($value, 0, ',', '.') ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="submitBtn" name="transaksi" class="btn btn-warning btn-lg w-100"><strong>BAYAR</strong></button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <audio id="beep-sound">
        <source src="sound/beep-07a.mp3" type="audio/mpeg">
    </audio>
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-center p-4 bg-danger">
                <div class="modal-body">
                    <h2 class="text-white mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Oops!</h2>
                    <p class="fs-4 text-yellow" id="errorModalMessage"><?= htmlspecialchars($error ?: 'Isi data dengan lengkap sebelum melanjutkan.') ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php if ($error): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalNominal = new bootstrap.Modal(document.getElementById('modalNominal'));
        const successModalEl = document.getElementById('successModal');
        const errorModalEl = document.getElementById("errorModal");

        const kantinButtons = document.querySelectorAll('.kantin-btn');
        const presetButtons = document.querySelectorAll('.preset-btn');
        const kantinPreview = document.getElementById('selectedKantinPreview');
        const nominalInput = document.getElementById("NominalInput");
        const kantinIdInput = document.getElementById("formKantinId");
        const errorMessage = document.getElementById("errorModalMessage");
        const form = document.getElementById("kantinForm");
        const submitBtn = document.getElementById("submitBtn");

        const errorModal = new bootstrap.Modal(errorModalEl);

        // Format angka jadi rupiah
        const formatRupiah = (angka) => {
            return angka.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        };

        // Tampilkan modal nominal saat kantin dipilih
        kantinButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                kantinIdInput.value = btn.dataset.id;
                nominalInput.value = "";
                kantinPreview.innerHTML = `
                <div class="d-flex align-items-center bg-yellow p-2 rounded w-100">
                    ${btn.innerHTML}
                </div>
            `;
                modalNominal.show();
            });
        });

        // Format input nominal saat diketik
        nominalInput.addEventListener('input', function() {
            this.value = formatRupiah(this.value);
        });

        // Preset nominal button
        presetButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                presetButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                nominalInput.value = formatRupiah(btn.dataset.val);
            });
        });

        // Reset preset button saat modal ditutup
        document.getElementById('modalNominal').addEventListener('hidden.bs.modal', () => {
            presetButtons.forEach(btn => btn.classList.remove('active'));
        });

        // Fokus input nominal saat modal dibuka
        document.getElementById('modalNominal').addEventListener('shown.bs.modal', () => {
            nominalInput.focus();
        });

        // Validasi & spinner saat submit
        form.addEventListener("submit", function(e) {
            const nominalValue = nominalInput.value.trim().replace(/\./g, "");
            const kantinId = kantinIdInput.value;
            const currentSaldo = <?= (int)$siswa['saldo'] ?>;

            if (currentSaldo <= 0) {
                e.preventDefault();
                errorMessage.textContent = "SALDO KAMU HABIS, AYO TOP-UP DULU!";
                errorModal.show();
                return;
            }

            if (!nominalValue || !kantinId) {
                e.preventDefault();
                errorMessage.textContent = !nominalValue ?
                    "NOMINAL HARUS DI ISI DAHULU!" :
                    "Silakan pilih kantin terlebih dahulu!";
                errorModal.show();
                return;
            }

            // Spinner di tombol BAYAR
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
            }
        });

        // Modal sukses otomatis tampil jika ada
        if (successModalEl) {
            window.addEventListener('DOMContentLoaded', () => {
                const successModal = new bootstrap.Modal(successModalEl);
                successModal.show();
            });
        }

        // Jika error dari PHP, kembalikan tombol seperti semula
        <?php if (!empty($error)): ?>
            window.addEventListener('DOMContentLoaded', () => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<strong>BAYAR</strong>';
                }
            });
        <?php endif; ?>
    </script>

    <script>
        if (window.performance && window.performance.navigation.type === 2) {
            // Halaman diakses via back/forward
            window.location.reload();
        }
    </script>
    <script>
        let tokoBuka = false;

        document.getElementById("statusCard").addEventListener("click", function() {
            tokoBuka = !tokoBuka;

            const card = document.getElementById("statusCard");
            const text = document.getElementById("statusText");
            const kantinButtons = document.querySelectorAll(".kantin-btn");
            const images = document.querySelectorAll(".kantin-img");

            if (tokoBuka) {
                card.classList.remove("bg-danger");
                card.classList.add("bg-success");
                text.textContent = "Toko Dibuka";

                kantinButtons.forEach(btn => btn.classList.remove("disabled-btn"));
                images.forEach(img => {
                    img.classList.remove("img-bw");
                    img.classList.add("img-color");
                });
            } else {
                card.classList.remove("bg-success");
                card.classList.add("bg-danger");
                text.textContent = "Toko Ditutup";

                kantinButtons.forEach(btn => btn.classList.add("disabled-btn"));
                images.forEach(img => {
                    img.classList.remove("img-color");
                    img.classList.add("img-bw");
                });
            }
        });
    </script>
    <script>
        function updateKantinStatus() {
            fetch('get_status_kantin.php')
                .then(res => res.json())
                .then(data => {
                    const daftarKantin = document.getElementById("daftarKantin");
                    daftarKantin.innerHTML = "";

                    // Urutkan: buka dulu baru tutup
                    data.sort((a, b) => (a.status_toko === 'tutup' && b.status_toko === 'buka') ? 1 : -1);

                    data.forEach(k => {
                        const isTutup = k.status_toko === 'tutup';
                        const disabledAttr = isTutup ? 'disabled style="background-color: #eee; color: #999; cursor: not-allowed;"' : '';
                        const buttonClass = isTutup ? 'disabled-btn' : '';
                        const imgClass = isTutup ? 'img-bw' : 'img-color';

                        const btnHTML = `
                        <div class="col-md-4 mb-3">
                            <button type="button" class="btn btn-yellow w-100 kantin-btn p-3 ${buttonClass}" ${disabledAttr} data-id="${k.id}" data-nama="${k.nama}">
                                <div class="position-relative">
                                    ${isTutup ? '<span class="badge-tutup position-absolute top-0 end-0">TUTUP</span>' : ''}
                                    <label class="kantin-card d-flex align-items-center">
                                        <img src="${k.gambar ? 'images/kantin/' + k.gambar : 'images/kantin/default.png'}" class="me-2 kantin-img ${imgClass}">
                                        <strong class="text-t">${k.nama}</strong>
                                    </label>
                                </div>
                            </button>
                        </div>
                    `;
                        daftarKantin.insertAdjacentHTML('beforeend', btnHTML);
                    });

                    // Bind ulang event ke tombol-tombol baru
                    document.querySelectorAll('.kantin-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            kantinIdInput.value = btn.dataset.id;
                            nominalInput.value = "";
                            kantinPreview.innerHTML = `
                            <div class="d-flex align-items-center bg-yellow p-2 rounded w-100">
                                ${btn.innerHTML}
                            </div>
                        `;
                            modalNominal.show();
                        });
                    });
                })
                .catch(err => console.error('Gagal memuat status kantin:', err));
        }

        // Jalankan pertama kali saat halaman dimuat
        updateKantinStatus();

        // Jalankan setiap 5 detik
        setInterval(updateKantinStatus, 3000);
    </script>
    <script>
        const beepSound = document.getElementById('beep-sound');

        // Fungsi untuk memainkan suara beep
        const playBeep = () => {
            if (beepSound) {
                beepSound.currentTime = 0;
                beepSound.play().catch(e => {
                    // Autoplay policy bisa blokir sebelum interaksi
                    console.warn("Autoplay prevented:", e);
                });
            }
        };

        // Tambahkan event listener ke semua tombol dan elemen klik lainnya
        document.addEventListener('DOMContentLoaded', () => {
            document.body.addEventListener('click', (e) => {
                // Hanya mainkan beep jika klik pada tombol, link, atau elemen dengan role interaktif
                if (
                    e.target.closest('button') ||
                    e.target.closest('a') ||
                    e.target.closest('.kantin-btn') ||
                    e.target.closest('.preset-btn') ||
                    e.target.closest('[role="button"]')
                ) {
                    playBeep();
                }
            });
        });
    </script>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            // Kirim pesan ke parent untuk menyembunyikan tombol
            window.parent.postMessage({
                hideCloseButton: true
            }, "*");
        });
    </script>


    <!-- Kirim saldo -->
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- SCRIPT KIRIM SALDO -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const rfidInput = document.getElementById("rfid_teman");
            const namaPenerima = document.getElementById("nama_penerima");
            const namaPenerimaModal = document.getElementById("nama_penerima_modal");
            const formKirim = document.getElementById("formKirimSaldo");
            const rfidHidden = document.getElementById("rfid_hidden");
            const modalEl = document.getElementById("modalKirimSaldo");
            const modalNominalInput = document.getElementById("modalNominalInput");
            const scanSection = document.getElementById("section-scan");

            // Format angka dengan titik ribuan
            function formatRupiah(angka) {
                return angka.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // Format saat input nominal
            if (modalNominalInput) {
                modalNominalInput.addEventListener("input", function() {
                    const cursorPos = this.selectionStart;
                    const originalLength = this.value.length;

                    this.value = formatRupiah(this.value);

                    const newLength = this.value.length;
                    this.selectionEnd = cursorPos + (newLength - originalLength);
                });
            }

            // Fokus otomatis ke RFID input
            if (modalEl) {
                modalEl.addEventListener("shown.bs.modal", function() {
                    setTimeout(() => rfidInput?.focus(), 100);
                });
            }

            const tabKirimTrigger = document.querySelector('[data-bs-target="#tab-kirim"]');
            if (tabKirimTrigger) {
                tabKirimTrigger.addEventListener("shown.bs.tab", function() {
                    setTimeout(() => rfidInput?.focus(), 100);
                });
            }

            // Reset saat pindah tab
            document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(tab => {
                tab.addEventListener("shown.bs.tab", function(e) {
                    if (e.target.getAttribute("data-bs-target") !== "#tab-kirim") {
                        formKirim.reset();
                        formKirim.style.display = "none";
                        scanSection.style.display = "block";
                        namaPenerima.innerHTML = "";
                        namaPenerimaModal.innerHTML = "";
                        rfidInput.value = "";
                        if (modalNominalInput) modalNominalInput.value = "";
                    }
                });
            });

            // Deteksi RFID
            let typingTimer;
            const TYPING_DELAY = 400; // waktu tunggu input selesai (ms)

            rfidInput.addEventListener("input", function() {
                clearTimeout(typingTimer);

                const uid = this.value.trim();
                if (uid.length === 0) return;

                typingTimer = setTimeout(() => {
                    // Mulai proses setelah input selesai
                    fetch("get_nama_siswa.php?uid=" + encodeURIComponent(uid))
                        .then(response => response.json())
                        .then(data => {
                            if (data.found) {
                                namaPenerima.innerText = "👤 " + data.nama;
                                namaPenerimaModal.innerText = "👤 " + data.nama;
                                rfidHidden.value = uid;
                                formKirim.style.display = "block";
                                scanSection.style.display = "none";

                                // Tampilkan modal
                                const modal = new bootstrap.Modal(modalEl);
                                modal.show();
                            } else {
                                namaPenerimaModal.innerText = "❌ UID tidak dikenali. Silakan scan ulang.";
                                namaPenerima.innerText = "";
                                formKirim.style.display = "none";

                                // Kosongkan input dan tunggu untuk scan ulang
                                rfidInput.value = "";
                                setTimeout(() => rfidInput.focus(), 100);
                            }
                        })
                        .catch(() => {
                            namaPenerimaModal.innerText = "⚠️ Terjadi kesalahan saat menghubungi server.";
                            formKirim.style.display = "none";

                            rfidInput.value = "";
                            setTimeout(() => rfidInput.focus(), 500);
                        });
                }, TYPING_DELAY);
            });


            // Tombol preset jumlah
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-val');
                    if (modalNominalInput) modalNominalInput.value = formatRupiah(val);
                });
            });

            // Submit form
            formKirim.addEventListener("submit", function(e) {
                e.preventDefault();

                const formData = new FormData(formKirim);
                formData.set('csrf', <?= json_encode($_SESSION['mkantin_csrf']) ?>);
                if (modalNominalInput) {
                    formData.set('nominal', modalNominalInput.value.replace(/\./g, '')); // bersihkan titik
                }

                Swal.fire({
                    title: "Mengirim...",
                    text: "Harap tunggu sebentar.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("proses_kirim_saldo.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        Swal.close();
                        if (res.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Saldo Terkirim!",
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            formKirim.reset();
                            rfidInput.value = "";
                            namaPenerima.innerText = "";
                            namaPenerimaModal.innerText = "";
                            formKirim.style.display = "none";
                            scanSection.style.display = "block";
                            if (modalNominalInput) modalNominalInput.value = "";

                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Gagal Mengirim",
                                text: res.message || "Terjadi kesalahan saat mengirim saldo."
                            });
                        }
                    })
                    .catch(err => {
                        Swal.close();
                        Swal.fire({
                            icon: "error",
                            title: "Kesalahan Server",
                            text: "Gagal menghubungi server. Silakan coba lagi."
                        });
                        console.error(err);
                    });
            });

            // ⬇️ TAMBAHAN: Reset dan kembali ke scan saat modal ditutup
            if (modalEl) {
                modalEl.addEventListener("hidden.bs.modal", function() {
                    formKirim.reset();
                    namaPenerima.innerHTML = "";
                    namaPenerimaModal.innerHTML = "";
                    scanSection.style.display = "block";
                    formKirim.style.display = "none";
                    rfidInput.value = "";
                    if (modalNominalInput) modalNominalInput.value = "";

                    setTimeout(() => rfidInput.focus(), 100);
                });
            }
        });
    </script>



</body>

</html>
