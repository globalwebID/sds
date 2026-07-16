    <style>
        body {
            font-family: Arial, sans-serif;
            /* background-color: #f4f6f8; */
            background-color: #e0e0e0;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #00a651;
            color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }

        .main-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 285px);
            /* 100vh dikurangi tinggi header */
            padding: 20px;
        }

        .container {
            width: 100%;
            /* padding: 20px 85px; */
            /* background-color: white; */
            border-radius: 8px;
            /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); */
            /* max-width: 700px;
            margin: 40px auto; */
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .options {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            /* gap: 20px; */
            margin-bottom: 50px;
            place-content: space-between;
        }

        .option {
            text-align: center;
        }

        .option-title {
            font-weight: bold;
            margin: 10px 0;
        }

        .option img {
            width: 100px;
            height: 100px;
            cursor: pointer;
            border-radius: 50%;
            transition: transform 0.2s;
        }

        .option img:hover {
            transform: scale(1.1);
        }

        .btn {
            margin-top: 10px;
            padding: 14px 20px;
            border: none;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-green {
            background-color: #28a745;
        }

        .btn-blue {
            background-color: #17a2b8;
        }

        .btn-orange {
            background-color: #fd7e14;
        }

        .btn-orange:hover {
            background-color: #fd8a29;
        }

        .btn-grad {
            background: linear-gradient(to right, #864d8d, #ff5d38);
            ;
        }

        .btn-grad:hover {
            background: linear-gradient(to right, #ff5d38, #864d8d);
            color: white;
        }

        .btn-red {
            background-color: #dc3545;
        }

        .btn-darkblue {
            background-color: #007bff;
        }

        .rekap-link {
            text-align: center;
            margin-top: 20px;
        }

        .rekap-link a {
            text-decoration: none;
            color: #007bff;
        }

        .rekap-link a:hover {
            text-decoration: underline;
        }

        input[type="radio"] {
            display: none;
        }

        .selected {
            outline: 3px solid #00a651;
            border-radius: 50%;
        }

        #thankyou-popup {
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .popup-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: popupFade 0.4s ease-out;
        }

        .popup-content h2 {
            color: #00a651;
            margin-bottom: 10px;
        }

        @keyframes popupFade {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
    <div class="modal-inner">
        <div class="colscroll">
            <div class="modal-padding">
                <div class="main-wrapper">
                    <div class="container">
                        <!-- <h2>Form Kepuasan Pelanggan</h2> -->
                        <form onsubmit="event.preventDefault(); submitForm();">
                            <!-- <label for="nama">Nama:</label> -->
                            <input type="hidden" id="nama" name="nama" placeholder="(Boleh dikosongkan)">

                            <!-- <label>Penilaian Layanan:</label> -->
                            <input type="hidden" name="penilaian" id="penilaian" required>

                            <div class="options">
                                <div class="option" onclick="setRating(5, this)">
                                    <div class="option-title">SANGAT PUAS</div>
                                    <img src="survey/icon/sangat-puas.png" alt="Sangat Puas">
                                    <!-- <div><button type="button" class="btn btn-green" onclick="submitForm()">✉ SIMPAN PENDAPAT</button></div> -->
                                </div>

                                <div class="option" onclick="setRating(4, this)">
                                    <div class="option-title">PUAS</div>
                                    <img src="survey/icon/puas.png" alt="Puas">
                                    <!-- <div><button type="button" class="btn btn-blue" onclick="submitForm()">✉ SIMPAN PENDAPAT</button></div> -->
                                </div>

                                <div class="option" onclick="setRating(3, this)">
                                    <div class="option-title">CUKUP</div>
                                    <img src="survey/icon/cukup.png" alt="Cukup">
                                    <!-- <div><button type="button" class="btn btn-orange" onclick="submitForm()">✉ SIMPAN PENDAPAT</button></div> -->
                                </div>

                                <div class="option" onclick="setRating(2, this)">
                                    <div class="option-title">KURANG PUAS</div>
                                    <img src="survey/icon/kurang.png" alt="Kurang Puas">
                                    <!-- <div><button type="button" class="btn btn-red" onclick="submitForm()">✉ SIMPAN PENDAPAT</button></div> -->
                                </div>

                                <div class="option" onclick="setRating(1, this)">
                                    <div class="option-title">TIDAK PUAS</div>
                                    <img src="survey/icon/tidak-puas.png" alt="Tidak Puas">
                                    <!-- <div><button type="button" class="btn btn-darkblue" onclick="submitForm()">✉ SIMPAN PENDAPAT</button></div> -->
                                </div>
                            </div>

                            <!-- <label for="saran">Kritik / Saran:</label> -->
                            <textarea id="saran" name="saran" rows="4" style="display: none;"></textarea>

                            <button type="submit" style="display: none;">Kirim</button>
                        </form>

                        <div class="rekap-link">
                            <button type="button" class="btn btn-grad" onclick="submitForm()">✉ SIMPAN PENDAPAT</button>
                            <!-- <a href="rekap.php">Lihat Rekap</a> -->
                        </div>
                    </div>
                </div>
                <!-- Popup -->
                <div id="thankyou-popup">
                    <div class="popup-content">
                        <h2>🎉 Terima Kasih!</h2>
                        <p>Masukan Anda telah kami terima.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        let selectedOption = null;

        function setRating(value, element) {
            document.getElementById('penilaian').value = value;

            if (selectedOption) {
                selectedOption.querySelector('img').classList.remove('selected');
            }

            selectedOption = element;
            selectedOption.querySelector('img').classList.add('selected');
        }

        function submitForm() {
            const form = document.querySelector('form');
            const penilaian = document.getElementById('penilaian').value;

            if (!penilaian) {
                alert("Silakan pilih penilaian terlebih dahulu.");
                return;
            }

            const formData = new FormData(form);

            fetch('survey/simpan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Tampilkan popup
                    document.getElementById('thankyou-popup').style.display = 'flex';
                    // Redirect setelah 3 detik
                    setTimeout(() => {
                        window.location.href = 'index';
                    }, 3000);
                })
                .catch(error => {
                    alert('Terjadi kesalahan. Coba lagi.');
                    console.error(error);
                });
        }
    </script>