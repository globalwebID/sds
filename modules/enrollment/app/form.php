<?php
session_start();
require dirname(__DIR__, 3) . '/db.php'; // koneksi database

// Ambil NISN dari parameter URL
$nisn = isset($_GET['nisn']) ? trim($_GET['nisn']) : '';

if ($nisn === '') {
    $_SESSION['error'] = 'NISN tidak valid!';
    header('Location: students');
    exit;
}

// Ambil data siswa berdasarkan NISN
$stmt = $conn->prepare("
    SELECT p.*, k.nama_kelas, tk.nama_tingkat 
    FROM pendaftaran_siswa p
    LEFT JOIN kelas k ON p.kelas_id = k.id
    LEFT JOIN tingkat_kelas tk ON k.tingkat_id = tk.id
    WHERE p.nisn = ?
");
$stmt->bind_param('s', $nisn);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['error'] = 'Data siswa tidak ditemukan!';
    header('Location: students');
    exit;
}
$pengaturan = [];

$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");

if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    // Default jika belum ada data
    $pengaturan = [
        'nama_sekolah' => '',
        'logo' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Siswa</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Fullscreen Plugin -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css" />
    <script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
    <!-- Tambahkan di <head> -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />




    <style>
        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
            --card-bg: #f9f9f9;
            --button-bg: #007bff;
            --button-hover: #0056b3;
            --border-color: #ccc;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #121212;
                --text-color: #ffffff;
                --card-bg: #1e1e1e;
                --button-bg: #0d6efd;
                --button-hover: #0a58ca;
                --border-color: #444;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 1rem;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        nav {
            background-color: var(--button-bg);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }

        .card p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .label {
            font-weight: bold;
        }

        form label {
            font-weight: bold;
            margin-top: 1rem;
            display: block;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            font-size: 1rem;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .success {
            display: inline-block;
            text-align: center;
            margin-top: 10px;
            padding: 0.9rem;
            background-color: green;
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            width: 100%;
            font-size: 1.1rem;
        }


        button {
            width: 100%;
            padding: 0.9rem;
            background-color: var(--button-bg);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            cursor: pointer;
        }

        button:hover {
            background-color: var(--button-hover);
        }

        #map {
            width: 100%;
            height: 40vh;
            margin-top: 1rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .leaflet-control-fullscreen-button {
            background-color: white !important;
            z-index: 1000;
        }
    </style>


</head>

<body>
    <div style="display: flex; align-items: center; gap: 10px; text-align: left;margin-bottom: 20px;">
        <?php if (!empty($pengaturan['logo'])): ?>
            <img src="uploads/logo/<?= htmlspecialchars($pengaturan['logo']) ?>" alt="Logo Sekolah" width="40" style="height: auto;">
        <?php endif; ?>
        <h2 style="margin: 0; text-align: left;">Lokasi Tinggal Peserta Didik</h2>
    </div>


    <div class="card">
        <p><span class="label">Nama Lengkap:</span> <?= htmlspecialchars($student['nama_lengkap']) ?></p>
        <p><span class="label">NISN:</span> <?= htmlspecialchars($student['nisn']) ?></p>
        <!-- <p><span class="label">No. HP Siswa:</span> <?= htmlspecialchars($student['nohp_siswa']) ?></p> -->
    </div>

    <form action="proses_map.php" method="POST" enctype="multipart/form-data" autocomplete="off">
        <!-- <label>Koordinat Rumah *</label> -->
        <input type="text" name="koordinat" id="koordinat_rumah" placeholder="Izinkan lokasi otomatis atau Klik pada peta" value="<?= $student['latitude'] && $student['longitude'] ? htmlspecialchars($student['latitude']) . ', ' . htmlspecialchars($student['longitude']) : '' ?>">
        <input type="hidden" name="latitude" value="<?= htmlspecialchars($student['latitude']) ?>">
        <input type="hidden" name="longitude" value="<?= htmlspecialchars($student['longitude']) ?>">
        <input type="hidden" name="nisn" value="<?= htmlspecialchars($student['nisn']) ?>">

        <div id="map"></div>
        <br>
        <button type="submit">Simpan Lokasi</button><br>
        <a href="siteman/student_pdf.php?id=<?= urlencode($student['id']) ?>" class="success">Unduh Dataku</a>


    </form>


    <!-- Tambahkan sebelum </body> -->
    <script src="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>
    <script>
        // Koordinat awal dari data siswa atau fallback default
        var initialLat = <?= $student['latitude'] ? $student['latitude'] : '-7.781571' ?>;
        var initialLng = <?= $student['longitude'] ? $student['longitude'] : '113.212075' ?>;

        var map = L.map('map', {
            fullscreenControl: true
        }).setView([initialLat, initialLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; Affan contributors'
        }).addTo(map);

        var marker = L.marker([initialLat, initialLng]).addTo(map);

        // Klik pada peta untuk update lokasi
        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);
            document.getElementById('koordinat_rumah').value = lat + ', ' + lng;
            document.querySelector('input[name="latitude"]').value = lat;
            document.querySelector('input[name="longitude"]').value = lng;
            marker.setLatLng(e.latlng);
        });

        // Tambahkan tombol lokasi ala Google Maps
        L.control.locate({
            position: 'topleft',
            strings: {
                title: "Temukan lokasi saya"
            },
            drawCircle: true,
            keepCurrentZoomLevel: true,
            initialZoomLevel: 16,
            locateOptions: {
                enableHighAccuracy: true
            }
        }).addTo(map);

        // Saat lokasi ditemukan oleh tombol tersebut
        map.on('locationfound', function(e) {
            var lat = e.latitude.toFixed(6);
            var lng = e.longitude.toFixed(6);
            var latlng = [lat, lng];
            map.setView(latlng, 16);
            marker.setLatLng(latlng);
            document.getElementById('koordinat_rumah').value = lat + ', ' + lng;
            document.querySelector('input[name="latitude"]').value = lat;
            document.querySelector('input[name="longitude"]').value = lng;
        });

        // Fallback: Deteksi otomatis saat load halaman
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude.toFixed(6);
                var lng = position.coords.longitude.toFixed(6);
                var currentLatLng = [lat, lng];
                map.setView(currentLatLng, 16);
                marker.setLatLng(currentLatLng);
                document.getElementById('koordinat_rumah').value = lat + ', ' + lng;
                document.querySelector('input[name="latitude"]').value = lat;
                document.querySelector('input[name="longitude"]').value = lng;
            }, function(error) {
                let pesan = '';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        pesan = "Akses lokasi ditolak. Silakan aktifkan layanan lokasi di perangkat Anda.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        pesan = "Informasi lokasi tidak tersedia.";
                        break;
                    case error.TIMEOUT:
                        pesan = "Permintaan lokasi kehabisan waktu.";
                        break;
                    default:
                        pesan = "Terjadi kesalahan saat mengambil lokasi.";
                }
                alert(pesan);
            });
        } else {
            alert("Peramban Anda tidak mendukung fitur lokasi.");
        }
    </script>
</body>

</html>
