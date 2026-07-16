<?php
include 'inc/db.php';

$uid = $_POST['uid'] ?? '';
$siswa = null;
$error = '';
$success = false;

// Ambil data siswa berdasarkan UID
if ($uid) {
    $siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE rfid_uid='$uid'"));
}

// Proses form transaksi
if (isset($_POST['transaksi']) && $siswa) {
    $nominal = (int) str_replace('.', '', $_POST['nominal']);
    $id_kantin = (int) $_POST['id_kantin'];

    if ($siswa['saldo'] < $nominal) {
        $error = "Saldo tidak cukup!";
    } else {
        $id = $siswa['id'];
        $new_saldo = $siswa['saldo'] - $nominal;

        mysqli_query($conn, "UPDATE siswa SET saldo='$new_saldo' WHERE id='$id'");
        mysqli_query($conn, "INSERT INTO transaksi_kantin (id_siswa, tanggal, nominal, id_kantin) VALUES ('$id', NOW(), '$nominal', '$id_kantin')");

        $success = true;
        $siswa['saldo'] = $new_saldo;
    }
}

$kantin_result = mysqli_query($conn, "SELECT id, nama, gambar FROM kantin");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            background-color: #f1f3f5;
        }

        .kiosk-container {
            padding: 0;
        }

        .kiosk-card {
            width: 100%;
            padding: 2rem;
            padding-bottom: 200px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .kantin-img {
            width: 85px;
            height: 85px;
            object-fit: cover;
        }

        .btn-check:checked+.kantin-card {
            border: 2px solid #0d6efd;
            background-color: #e8f0fe;
        }

        .kantin-card {
            cursor: pointer;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            height: 111px;
        }

        .nominal-card {
            cursor: pointer;
            width: 170px;
            font-size: 1.5rem;
            padding: 1rem;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            transition: 0.2s;
        }

        .nominal-card.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .btn-large {
            font-size: 1.5rem;
            padding: 1rem;
        }

        .fixed-bottom-buttons {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background-color: #fff;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        strong {
            font-size: 1.5rem;
        }

        .center-nominal-wrapper {
            justify-content: center;
            text-align: center;
        }

        .custom-nominal-input {
            width: 50%;
            border: 2px solid #198754;
            margin: 0 auto;
            /* untuk center input dalam div */
        }
    </style>
</head>

<body>
    <div class="kiosk-container">
        <div class="kiosk-card">
            <?php if (!$siswa): ?>
                <div class="alert alert-danger fs-4 text-center">Kartu tidak terdaftar!</div>
                <a href="index.php" class="btn btn-secondary w-100 btn-large mt-3">Kembali</a>
            <?php else: ?>
                <div class="card text-white bg-success shadow-sm mb-4 w-25">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-credit-card-fill fs-1 me-3"></i>
                        <div>
                            <h4 class="mb-1">Halo, <?= htmlspecialchars($siswa['nama']) ?></h4>
                            <p class="fs-5 mb-0">Saldo: <strong>Rp <?= number_format($siswa['saldo'], 0, ',', '.') ?></strong></p>
                        </div>
                    </div>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger fs-5"><?= $error ?></div>
                <?php elseif ($success): ?>
                    <script>
                        window.addEventListener('DOMContentLoaded', () => {
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                            setTimeout(() => window.location.href = 'index.php', 2000);
                        });
                    </script>
                    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content text-center p-4">
                                <div class="modal-body">
                                    <h2 class="mb-3 text-success">🎉 Transaksi Berhasil!</h2>
                                    <p class="fs-4">Sisa saldo Anda:</p>
                                    <h1 class="text-primary mb-4">Rp <?= number_format($siswa['saldo'], 0, ',', '.') ?></h1>
                                </div>
                            </div>
                        </div>
                    </div>
                    <audio autoplay>
                        <source src="sounds/success.mp3" type="audio/mpeg">
                    </audio>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="uid" value="<?= htmlspecialchars($uid) ?>">

                    <div class="mb-4">
                        <!-- <div class="row align-items-center mb-3">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label for="nominalInput" class="form-label fs-4 mb-0"><strong>Masukkan Nominal Pembayaran:</strong></label>
                            </div>
                            <div class="col-md-7">
                                <input type="text" name="nominal" id="nominalInput" class="form-control form-control-lg text-end fs-3 fw-bold" autofocus>
                            </div>
                        </div> -->
                        <div class="row align-items-center mb-3 center-nominal-wrapper">
                            <label for="nominalInput" class="form-label fs-4 mb-3 text-center w-100">
                                <strong>MASUKKAN NOMINAL PEMBAYARAN</strong>
                            </label>
                            <input type="text" name="nominal" id="nominalInput"
                                class="text-center form-control form-control-lg text-end fs-3 fw-bold custom-nominal-input"
                                autofocus>
                        </div>

                        <div class="d-flex flex-wrap gap-3">
                            <?php
                            $preset_nominals = [2000, 5000, 10000, 15000, 20000, 25000, 30000];
                            foreach ($preset_nominals as $value):
                            ?>
                                <div class="nominal-card" data-value="<?= $value ?>">
                                    Rp <?= number_format($value, 0, ',', '.') ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-4 mt-4">
                        <label class="form-label fs-3"><strong>PILIH KANTIN:</strong></label>
                        <div class="row">
                            <?php while ($k = mysqli_fetch_assoc($kantin_result)):
                                $gambar = !empty($k['gambar']) ? 'images/kantin/' . $k['gambar'] : 'images/kantin/default.png';
                            ?>
                                <div class="col-md-3 mb-3">
                                    <input type="radio" name="id_kantin" value="<?= $k['id'] ?>" class="btn-check" id="kantin-<?= $k['id'] ?>">
                                    <label class="kantin-card" for="kantin-<?= $k['id'] ?>">
                                        <img src="<?= $gambar ?>" class="kantin-img rounded">
                                        <strong><?= htmlspecialchars($k['nama']) ?></strong>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="fixed-bottom bg-white border-top">
                        <div class="d-flex gap-3 p-3">
                            <button type="submit" name="transaksi" class="btn btn-primary flex-fill btn-lg fs-3 p-3"><strong>Bayar</strong></button>
                            <a href="index.php" class="btn btn-secondary flex-fill btn-lg fs-3 p-3"><strong>Batal</strong></a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-center p-4">
                <div class="modal-body">
                    <h2 class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Oops!</h2>
                    <p class="fs-4" id="errorModalMessage">Isi data dengan lengkap sebelum melanjutkan.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form");
            const nominalInput = document.getElementById("nominalInput");
            const nominalCards = document.querySelectorAll(".nominal-card");
            const errorModalEl = document.getElementById("errorModal");
            const errorModal = new bootstrap.Modal(errorModalEl);
            const errorMessage = document.getElementById("errorModalMessage");

            function formatRupiah(angka) {
                return angka.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            // Format saat input
            nominalInput.addEventListener("input", function() {
                const raw = this.value.replace(/\./g, "");
                this.value = formatRupiah(raw);
            });

            // Preset nominal cards
            nominalCards.forEach((card) => {
                card.addEventListener("click", function() {
                    const value = this.dataset.value;
                    nominalInput.value = formatRupiah(value);

                    nominalCards.forEach((c) => c.classList.remove("active"));
                    this.classList.add("active");
                });
            });

            // Handle form submit
            form.addEventListener("submit", function(e) {
                const nominalValue = nominalInput.value.trim().replace(/\./g, "");
                const kantinChecked = document.querySelector('input[name="id_kantin"]:checked');

                nominalInput.value = nominalValue; // Set ulang nilai bersih untuk server

                if (!nominalValue || !kantinChecked) {
                    e.preventDefault();

                    errorMessage.textContent = !nominalValue ?
                        "Nominal harus diisi!" :
                        "Silakan pilih kantin terlebih dahulu!";

                    console.log("Modal error akan ditampilkan");

                    errorModal.show();
                }
            });
        });
    </script>

</body>

</html>