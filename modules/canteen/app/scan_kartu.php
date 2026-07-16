<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <title>M-KANTIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #374c5d;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .container-full {
            height: 100vh;
            /* display: flex; */
            padding: 40px 40px;
        }

        .column {
            display: flex;
        }

        .column:last-child {
            border-right: none;
        }

        .card-box {
            background-color: #374c5d;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        .card-box h1 {
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .card-box p {
            color: #ccc;
            font-size: 1.1rem;
        }

        .form-control-lg {
            font-size: 2rem;
            text-align: center;
            padding: 20px;
        }

        .img-fluid {
            max-height: 500px;
        }

        .text-yellow {
            color: yellow;
        }

        .error-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 10, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .error-box {
            background-color: #1e1e2f;
            border: 2px solid #ffcc00;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            color: #fff;
            max-width: 500px;
            width: 90%;
        }

        .error-box .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .error-box .message {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffcc00;
        }

        .gedi {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100vh;
            opacity: 0;
        }
        .tinggi {
            max-height: 400px;
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            .container-full {
                padding: 30px 20px;
            }

            .card-box {
                padding: 30px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            }
            .tinggi {
                max-height: 300px;
            }

            h1,
            .card-box h1 {
                font-size: 1.8rem;
            }

            .fs-4 {
                font-size: 1.2rem !important;
            }

            .fs-3 {
                font-size: 1.3rem !important;
            }

            .fs-5 {
                font-size: 1rem !important;
            }

            .form-control-lg {
                font-size: 1.6rem;
                padding: 18px;
            }

            .img-fluid {
                max-height: 300px;
            }

            .error-box .message {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <audio id="beep" src="sound/beep-07a.mp3" preload="auto"></audio>
    <audio id="errorSound" src="sound/beep.mp3" autoplay></audio>

    <div class="container-full">
        <h1 class="fw-bold text-white mb-0 text-center pb-3">CARA MUDAH BERTRANSAKSI CASHLESS DI KANTIN SEKOLAH!</h1>
        <div class="row">
            <!-- Kolom Kiri -->
            <div class="col-md-8 mb-4">
                <p class="fs-4 text-light mb-4">
                    Nikmati kemudahan bertransaksi tanpa uang tunai menggunakan kartu Anda.
                    Cepat, Aman, dan Praktis untuk semua siswa.
                </p>
                <h2 class="text-yellow fw-bold">💳 PANDUAN PENGGUNAAN:</h2>
                <p class="fs-3 text-light lh-lg">
                    1️⃣ Siapkan kartu Anda<br>
                    2️⃣ Tempelkan pada alat pembaca<br>
                    3️⃣ Tunggu beberapa detik hingga sistem membaca kartu<br>
                    4️⃣ Pilih kantin tujuan di layar<br>
                    5️⃣ Lakukan transaksi pembayaran<br>
                    6️⃣ Tekan tombol <span class="text-yellow fw-bold">KELUAR</span> jika sudah selesai
                </p>
                <p class="text-yellow mt-4 fst-italic fs-5">
                    📢 Penting: Selalu jaga kartu Anda. Jangan dipinjamkan ke orang lain.
                </p>
            </div>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="error-popup">
                    <div class="error-box shadow-lg">
                        <div class="icon">⚠️</div>
                        <div class="message"><?= $_SESSION['error']; ?></div>
                        <button onclick="location.href='index.php'" class="btn btn-warning btn-lg mt-3 px-5">Coba Lagi</button>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <!-- Kolom Kanan -->
            <div class="col-md-4 d-flex flex-column align-items-center text-center">
                <img src="images/tap.gif" alt="Silakan Tap Kartu" class="img-fluid mb-4 tinggi">
                <form method="POST" action="rfid_login.php" id="formTap" autocomplete="off">
                    <!--<input type="text" name="uid" id="uid" class="gedi" autofocus required>-->
                    <input type="text" name="uid" id="uid" class="gedi" inputmode="none" readonly onfocus="this.removeAttribute('readonly');" autofocus required>
                </form>
                <p class="text-center fs-5">Tempelkan kartu Anda ke alat pembaca</p>
            </div>
        </div>
    </div>


    <script>
        const inputUID = document.getElementById("uid");
        const beepSound = document.getElementById("beep");

        window.addEventListener("pageshow", function() {
            inputUID.value = "";
            inputUID.focus();
        });

        inputUID.addEventListener("input", function() {
            if (this.value.length >= 10) {
                // 🔊 Mainkan suara
                beepSound.play().catch((error) => {
                    console.warn("Audio tidak bisa diputar otomatis:", error);
                });

                // Kirim form setelah sedikit delay
                setTimeout(() => {
                    document.getElementById("formTap").submit();
                }, 500);
            }
        });
    </script>
    <script>
        document.getElementById('errorSound')?.play().catch(() => {});
    </script>

</body>

</html>