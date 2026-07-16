<?php
require "../db.php";
// GET tetap dapat dipakai untuk melihat data historis. Tanpa parameter,
// anjungan selalu mengikuti satu tahun ajaran aktif dari master SDS.
if (isset($_GET['tahun']) && preg_match('/^\d{4}\/\d{4}$/', (string)$_GET['tahun'])) {
    $tahunAjaran = (string)$_GET['tahun'];
} else {
    $tahunAjaran = (string)($tahunAjaran ?? '');
}

// Ambil status Formulir
$formAktif = $conn->query("SELECT nilai FROM formulir WHERE nama = 'form_aktif'")->fetch_assoc()['nilai'] ?? '0';

// Ambil data siswa
$sql = "SELECT 
            ps.id,
            ps.nama_lengkap AS nama,
            ps.nisn,
            ps.nipd,
            ps.latitude AS lat,
            ps.longitude AS lng,
            ps.foto,
            tk.nama_tingkat,
            k.nama_kelas
        FROM 
            siswa_kelas sk
        INNER JOIN 
            pendaftaran_siswa ps ON ps.id = sk.siswa_id
        INNER JOIN 
            kelas k ON sk.kelas_id = k.id
        INNER JOIN 
            tingkat_kelas tk ON k.tingkat_id = tk.id
        WHERE 
            ps.latitude IS NOT NULL 
            AND ps.longitude IS NOT NULL 
            AND sk.tahun_ajaran = '$tahunAjaran'";

$result = $conn->query($sql);
$siswa = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $siswa[] = $row;
    }
}
$namaKelasList = [];
foreach ($siswa as $item) {
    $namaKelasList[$item['nama_kelas']] = true; // gunakan associative array untuk otomatis unik
}
$namaKelasList = array_keys($namaKelasList);
sort($namaKelasList); // opsional: urutkan abjad

$namaTingkatList = [];
foreach ($siswa as $item) {
    $namaTingkatList[$item['nama_tingkat']] = true;
}
$namaTingkatList = array_keys($namaTingkatList);
sort($namaTingkatList); // opsional

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template">
    <meta name="author" content="Affan">
    <meta name="keywords" content="Sds, bootstrap, admin, dashboard">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png">
    <title>SDS - Sistem Data Siswa</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">

    <!-- Bootstrap & Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css" />
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">

    <style>
        .toggle-checkbox {
            width: 3rem;
            height: 1.5rem;
            appearance: none;
            background-color: #d1d5db;
            border-radius: 9999px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block;
            vertical-align: middle;
            /* Tambahan penting */
        }

        .toggle-checkbox:checked {
            background-color: #3b82f6;
        }

        .toggle-checkbox:before {
            content: '';
            position: absolute;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            background: white;
            top: 0.125rem;
            left: 0.125rem;
            transition: transform 0.3s;
        }

        .toggle-checkbox:checked:before {
            transform: translateX(1.5rem);
        }

        #mapContainer:fullscreen {
            background: white;
            padding: 1rem;
        }

        #map {
            height: 100vh;
            transition: height 0.3s ease;
        }

        #mapContainer:fullscreen #map {
            height: 100vh;
        }

        #mapContainer:-webkit-full-screen #map,
        #mapContainer:-moz-full-screen #map,
        #mapContainer:fullscreen #map {
            height: 100vh;
        }
    </style>
    <!-- Map -->
    <div id="map"></div>


    <script>
        function toRoman(num) {
            const romanMap = {
                10: 'X',
                11: 'XI',
                12: 'XII'
            };
            return romanMap[num] || num;
        }

        var map = L.map('map', {
            center: [-7.771264, 113.213682],
            zoom: 13,
            fullscreenControl: true,
            fullscreenControlOptions: {
                position: 'topright',
                target: document.getElementById('mapContainer') // <== penting!
            }
        });


        var userIcon = L.icon({
            iconUrl: 'https://static.vecteezy.com/system/resources/previews/050/757/191/large_2x/a-person-with-a-red-and-blue-figure-free-png.png',
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap Affan Contributor'
        }).addTo(map);

        var dataSiswa = <?php echo json_encode($siswa); ?>;

        let allMarkers = [];

        function tampilkanMarker(filteredData) {
            allMarkers.forEach(marker => map.removeLayer(marker));
            allMarkers = [];

            filteredData.forEach(function(siswa) {
                const fotoUrl = siswa.foto ?
                    `../uploads/${siswa.foto}` :
                    '../uploads/foto/default.png';

                var popupContent = `
                <img src="${fotoUrl}" alt="Foto ${siswa.nama}" style="width: 100%;height: 150px;border-radius:8px;object-fit: cover;"><br>
                <b>${siswa.nama}</b><br>
                NISN: ${siswa.nisn}<br>
                NIPD: ${siswa.nipd}<br>
                Tingkat: ${siswa.nama_tingkat}<br>
                Kelas: ${toRoman(parseInt(siswa.nama_tingkat))} ${siswa.nama_kelas}<br>
                <a href="student_view&id=${siswa.id}" target="_blank" class="btn btn-sm btn-primary" style="color: white;width: 100%;">Lihat Profil</a>
                <a href="https://www.google.com/maps?q=${siswa.lat},${siswa.lng}" target="_blank" class="btn btn-sm btn-success" style="color: white;width: 100%;">Kunjungi</a>
            `;

                let marker = L.marker([siswa.lat, siswa.lng], {
                        icon: userIcon
                    })
                    .addTo(map)
                    .bindPopup(popupContent);

                allMarkers.push(marker);
            });
        }

        function applyFilters() {
            const keyword = document.getElementById('searchInput').value.toLowerCase();
            const selectedKelas = document.getElementById('filterKelas').value;
            const selectedTingkat = document.getElementById('filterTingkat').value;

            const filtered = dataSiswa.filter(siswa => {
                const cocokKeyword = (
                    siswa.nama.toLowerCase().includes(keyword) ||
                    siswa.nisn.toLowerCase().includes(keyword)
                );
                const cocokKelasSelect = selectedKelas === '' || siswa.nama_kelas === selectedKelas;
                const cocokTingkatSelect = selectedTingkat === '' || siswa.nama_tingkat === selectedTingkat;
                return cocokKeyword && cocokKelasSelect && cocokTingkatSelect;
            });

            tampilkanMarker(filtered);
        }

        // Tampilkan semua saat awal
        tampilkanMarker(dataSiswa);

        // Event listener
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterKelas').addEventListener('change', applyFilters);
        document.getElementById('filterTingkat').addEventListener('change', applyFilters);
    </script>

    <script>
        const fullscreenBtn = document.getElementById('fullscreenButton');
        const fullscreenLabel = document.getElementById('fullscreenLabel');
        const fullscreenIcon = document.getElementById('fullscreenIcon');
        const mapContainer = document.getElementById('mapContainer');

        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                mapContainer.requestFullscreen().then(() => {
                    fullscreenLabel.textContent = 'Exit Fullscreen';
                    fullscreenIcon.classList.remove('fa-expand');
                    fullscreenIcon.classList.add('fa-compress');
                    document.getElementById('map').style.height = '100vh';
                    setTimeout(() => map.invalidateSize(), 300); // penting agar map tampil penuh
                });
            } else {
                document.exitFullscreen().then(() => {
                    fullscreenLabel.textContent = 'Fullscreen';
                    fullscreenIcon.classList.remove('fa-compress');
                    fullscreenIcon.classList.add('fa-expand');
                    document.getElementById('map').style.height = '400px';
                    setTimeout(() => map.invalidateSize(), 300);
                });
            }
        });
    </script>
    <script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>

</body>

</html>