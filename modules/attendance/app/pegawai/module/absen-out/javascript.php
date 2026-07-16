<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  header('location:./404');
} else {
$data_lokasi = getLokasiData($connection, $data_user['lokasi']);
if (!$data_lokasi) {?>
    <script type="text/javascript">
        swal({
            text: "Lokasi tidak temukan, silahkan lengkapi Profil Anda!",
            icon: "warning",
            buttons: {
                cancel: true,
                confirm: true,
            },
            value: "yes",
        })

        .then((value) => {
            if(value) {
                setTimeout("location.href = './profile';");
            }
        });
    </script> 
<?php exit(); }

$tipe_absen = $row_site['tipe_absen_pegawai'];
$radius_required = ($data_lokasi['status'] == 'Y');
switch ($tipe_absen) {
    case 'qrcode':
        if ($radius_required) {
            /** QRCode + Radius */?>
            <script type="text/javascript">
            // Fungsi untuk menampilkan notifikasi
            function notify(message, type = 'error') {
                swal({ title: type === 'success' ? 'Success!' : 'Oops!', text: message, icon: type, timer: 2500 });
                console.warn(message);
                if (type === 'error') setTimeout(() => location.href = './', 3000);
            }

            // Fungsi untuk menghitung jarak s-widodo.com
            function getDistance(lat1, lon1, lat2, lon2) {
                const R = 6371e3;
                const toRad = deg => deg * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLon = toRad(lon2 - lon1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

           
            async function verifyLocationWithOSM(lat, lon, maxDistanceMeters) {
                const proxyUrl = 'https://cors-anywhere.herokuapp.com/';  // Proxy URL
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`;
                try {
                    const res = await fetch(proxyUrl + url, {
                        method: 'GET',
                        headers: {
                            'User-Agent': 's-widodo.com (swidodo.com@email.com)',  // Ganti dengan informasi yang sesuai
                        }
                    });

                    const data = await res.json();
                     const address = data.address;
                    const validArea = address.city || address.town || address.village;
                    if (!validArea) {
                        return false;
                    }
                    console.log("Valid Area from OSM: ", validArea);
                    return true;

                } catch (error) {
                    console.error('Error fetching OSM data:', error);
                    return false;  // Jika ada error, kembalikan false
                }
            }


            function detectSpeed(lat1, lon1, lat2, lon2, time1, time2) {
                const speed = (getDistance(lat1, lon1, lat2, lon2) / ((time2 - time1) / 3600000)).toFixed(2);
                if (speed > 100) notify(`⚠️ Fake GPS terdeteksi! Kecepatan tidak wajar (${speed} km/jam)`);
            }

            async function processLocationAndQRCode(validLat, validLon, maxDistanceMeters) {
                try {
                    const pos = await new Promise((resolve, reject) => {
                        if ("geolocation" in navigator) {
                            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000 });
                        } else {
                            reject('Geolocation tidak didukung di browser ini.');
                        }
                    });

                    const userLat = pos.coords.latitude;
                    const userLon = pos.coords.longitude;
                    const distance = getDistance(userLat, userLon, validLat, validLon);
                    const roundedDistance = Math.round(distance);
                    const isValid = distance <= maxDistanceMeters;
                    const latitude = `${userLat.toFixed(6)}, ${userLon.toFixed(6)}`;
                    const isLocationValid = await verifyLocationWithOSM(userLat, userLon, maxDistanceMeters);

                    if (isValid) {
                        notify(`✅ Anda berada dalam radius yang diperbolehkan. (${roundedDistance} m)`, 'success');
                    } else {
                        notify(`❌ Anda berada di luar radius yang diperbolehkan. (${roundedDistance} m)`);
                    }

                    if (window.lastLocation) {
                        detectSpeed(window.lastLocation.lat, window.lastLocation.lon, userLat, userLon, window.lastLocation.time, Date.now());
                    }
                    window.lastLocation = { lat: userLat, lon: userLon, time: Date.now() };

                    if (isValid && isLocationValid) {
                        startQRCodeScanner(latitude, roundedDistance);
                    }
                } catch (err) {
                    notify(`❌ Gagal mendeteksi lokasi. ${err}`, 'error');
                    console.error(err);
                }
            }

            // Fungsi untuk memulai pemindaian QR Code
            function startQRCodeScanner(latitude, radius) {
                let isScanned = false;
                function onScanSuccess(decodedText, decodedResult) {
                    if (!isScanned) {
                        isScanned = true;
                        new Audio('./template/vendor/html5-qrcode/audio/beep.mp3').play().catch(console.error);
                        $.ajax({
                            type: "POST",
                            url: "./module/absen-out/sw-proses.php?action=absen-qrcode",
                            data: { qrcode: decodedText, latitude: latitude, radius: radius, radius_aktif: 'Y' },
                            success: function (data) {
                                const [status, message] = data.split("/");
                                if (status === 'success') {
                                    swal({ title: 'Berhasil!', text: message, icon: 'success', timer: 3000 });
                                    setTimeout(function() {
                                        window.location.href = "./";
                                    }, 3000);
                                } else {
                                    swal({ title: 'Oops!', text: data, icon: 'error', timer: 3000 });
                                }
                            }
                        });

                        setTimeout(() => { isScanned = false; }, 5000);
                    }
                }

                const config = {
                    fps: 20,
                    qrbox: (viewportWidth, viewportHeight) => ({ width: Math.floor(Math.min(viewportWidth, viewportHeight) * 0.7), height: Math.floor(Math.min(viewportWidth, viewportHeight) * 0.7) }),
                    rememberLastUsedCamera: true,
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                };

                const html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
                html5QrcodeScanner.render(onScanSuccess);
            }

            // Mulai proses lokasi dan QR code saat halaman dimuat
            const validLat = <?php echo isset($data_lokasi['latitude']) ? floatval($data_lokasi['latitude']) : '0'; ?>;
            const validLon = <?php echo isset($data_lokasi['longitude']) ? floatval($data_lokasi['longitude']) : '0'; ?>;
            const maxDistanceMeters = <?php echo isset($data_lokasi['radius']) ? intval($data_lokasi['radius']) : 0; ?>;
            processLocationAndQRCode(validLat, validLon, maxDistanceMeters);
        </script>

        <?php } else {
            /** QRCode tanpa Radius */?>
            <script type="text/javascript">
            function notify(message, type = 'error') {
                swal({
                    title: type === 'success' ? 'Success!' : 'Oops!',
                    text: message,
                    icon: type,
                    timer: 2500
                });
                console.warn(message);
                if (type === 'error') {
                    setTimeout(() => location.href = './', 3000);
                }
            }

            async function verifyLocationWithOSM(lat, lon) {
                const proxyUrl = 'https://cors-anywhere.herokuapp.com/';  // Proxy URL
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`;
                try {
                    const res = await fetch(proxyUrl + url, {
                        method: 'GET',
                        headers: {
                            'User-Agent': 's-widodo.com (swidodo.com@email.com)',  // Ganti dengan informasi yang sesuai
                        }
                    });

                    const data = await res.json();
                     const address = data.address;
                    const validArea = address.city || address.town || address.village;
                    if (!validArea) {
                        return false;
                    }
                    console.log("Valid Area from OSM: ", validArea);
                    return true;

                } catch (error) {
                    console.error('Error fetching OSM data:', error);
                    return false;  // Jika ada error, kembalikan false
                }
            }

            async function processLocationAndQRCode() {
                try {
                    const pos = await new Promise((resolve, reject) => {
                        if ("geolocation" in navigator) {
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: true,
                                timeout: 10000
                            });
                        } else {
                            reject('Geolocation tidak didukung di browser ini.');
                        }
                    });

                    const userLat = pos.coords.latitude;
                    const userLon = pos.coords.longitude;
                    const latitude = `${userLat.toFixed(6)}, ${userLon.toFixed(6)}`;

                    const isLocationValid = await verifyLocationWithOSM(userLat, userLon);
                 
                    notify(`✅ Lokasi Anda berhasil terdeteksi!`, 'success');
                    startQRCodeScanner(latitude);
                } catch (err) {
                    notify(`❌ Gagal mendeteksi lokasi. ${err.message || err}`, 'error');
                    console.error(err);
                }
            }

            // Fungsi untuk memulai pemindaian QR Code
            function startQRCodeScanner(latitude) {
                let isScanned = false;

                function onScanSuccess(decodedText, decodedResult) {
                    if (!isScanned) {
                        isScanned = true;
                        new Audio('./template/vendor/html5-qrcode/audio/beep.mp3').play().catch(console.error);

                        $.ajax({
                            type: "POST",
                            url: "./module/absen-out/sw-proses.php?action=absen-qrcode",
                            data: {
                                qrcode: decodedText,
                                latitude: latitude,
                                radius: 0,
                                radius_aktif: 'N'
                            },
                            success: function (data) {
                                const [status, message] = data.split("/");
                                if (status === 'success') {
                                    swal({ title: 'Berhasil!', text: message, icon: 'success', timer: 3000 });
                                    setTimeout(function() {
                                        window.location.href = "./";
                                    }, 3000);
                                } else {
                                    swal({ title: 'Oops!', text: data, icon: 'error', timer: 3000 });
                                }
                            }
                        });

                        setTimeout(() => { isScanned = false; }, 5000);
                    }
                }

                const config = {
                    fps: 20,
                    qrbox: (viewportWidth, viewportHeight) => ({
                        width: Math.floor(Math.min(viewportWidth, viewportHeight) * 0.7),
                        height: Math.floor(Math.min(viewportWidth, viewportHeight) * 0.7)
                    }),
                    rememberLastUsedCamera: true,
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                };

                const html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
                html5QrcodeScanner.render(onScanSuccess);
            }

            // Mulai proses lokasi dan QR code saat halaman dimuat
            processLocationAndQRCode();
            </script>
       <?php }

    /* ---------------------------------------------
     -----------------------------------------------
     ----------------------------------------------*/
    break;
    case 'selfie':
        if ($radius_required) {
            /** Selfie + Radius */?>
            <script type="text/javascript">
            // Fungsi untuk menampilkan notifikasi
            function notify(message, type = 'error') {
                swal({
                    title: type === 'success' ? 'Success!' : 'Oops!',
                    text: message,
                    icon: type,
                    timer: 2500
                });
                console.warn(message);
                if (type === 'error') setTimeout(() => location.href = './', 3000);
            }

            // Fungsi Haversine untuk menghitung jarak
            function getDistance(lat1, lon1, lat2, lon2) {
                const R = 6371e3; // Radius bumi dalam meter
                const toRad = deg => deg * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLon = toRad(lon2 - lon1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            // Verifikasi lokasi OSM
           async function verifyLocationWithOSM(lat, lon, maxDistanceMeters) {
                const proxyUrl = 'https://cors-anywhere.herokuapp.com/';  // Proxy URL
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`;
                try {
                    const res = await fetch(proxyUrl + url, {
                        method: 'GET',
                        headers: {
                            'User-Agent': 's-widodo.com (swidodo.com@email.com)',  // Ganti dengan informasi yang sesuai
                        }
                    });

                    const data = await res.json();
                    const address = data.address;
                    if (!address.city && !address.town && !address.village) {
                        return false; // Lokasi tidak valid
                    }
                    return {
                        road: address.road || "",
                        hamlet: address.hamlet || "",
                        village: address.village || "",
                        suburb: address.suburb || "",
                        city: address.city || "",
                        country: address.country || ""
                    };

                } catch (error) {
                    console.error('Error fetching OSM data:', error);
                    return false;  // Jika ada error, kembalikan false
                }
            }

            // Deteksi kecepatan palsu
            function detectSpeed(lat1, lon1, lat2, lon2, time1, time2) {
                const speed = (getDistance(lat1, lon1, lat2, lon2) / ((time2 - time1) / 3600000)).toFixed(2);
                if (speed > 100) {
                    notify(`⚠️ Fake GPS terdeteksi! Kecepatan tidak wajar (${speed} km/jam)`);
                }
            }

            // Proses utama
            async function processLocationAndSelfie(validLat, validLon, maxDistanceMeters) {
                try {
                    const pos = await new Promise((resolve, reject) => {
                        if ("geolocation" in navigator) {
                            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000 });
                        } else {
                            reject('Geolocation tidak didukung di browser ini.');
                        }
                    });

                    const userLat = pos.coords.latitude;
                    const userLon = pos.coords.longitude;
                    const distance = getDistance(userLat, userLon, validLat, validLon);
                    const roundedDistance = Math.round(distance);
                    const isValid = distance <= maxDistanceMeters;
                    const latitude = `${userLat.toFixed(6)}, ${userLon.toFixed(6)}`;
                    const address = await verifyLocationWithOSM(userLat, userLon);
                    const { road, village, hamlet, suburb, city, country } = address;
                    
                    // Tampilkan peta menggunakan Leaflet
                    function initMap(lat, lon) {
                        const map = L.map('map-overlay').setView([lat, lon], 16);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 18,
                            attribution: ''
                        }).addTo(map);
                        L.marker([lat, lon]).addTo(map);
                    }
                    initMap(userLat, userLon);

                    // Notifikasi lokasi
                    if (isValid) {
                        notify(`✅ Anda berada dalam radius yang diperbolehkan. (${roundedDistance} m)`, 'success');
                    } else {
                        notify(`❌ Anda berada di luar radius yang diperbolehkan. (${roundedDistance} m)`);
                    }

                    // Deteksi fake GPS (kecepatan)
                    if (window.lastLocation) {
                        detectSpeed(window.lastLocation.lat, window.lastLocation.lon, userLat, userLon, window.lastLocation.time, Date.now());
                    }
                    window.lastLocation = { lat: userLat, lon: userLon, time: Date.now() };

                    if (isValid) {
                        startWebcame(latitude, roundedDistance, road, city);
                    }

                } catch (err) {
                    notify(`❌ Gagal mendeteksi lokasi. ${err}`, 'error');
                    console.error(err);
                }
            }

            // Memulai kamera
            function startWebcame(latitude, radius, road, city) {
                const webcamElement = document.getElementById('webcam');
                const canvasElement = document.getElementById('canvas');
                const webcam = new Webcam(webcamElement, 'user', canvasElement);
                function startFrontCamera() {
                    navigator.mediaDevices.enumerateDevices().then(devices => {
                        const frontCamera = devices.find(device =>
                            device.kind === 'videoinput' && device.label.toLowerCase().includes('front')
                        );
                        if (frontCamera) {
                            webcam.start(frontCamera.deviceId).then(() => {
                                cameraStarted();
                            }).catch(displayError);
                        } else {
                            webcam.start().then(() => {
                                cameraStarted();
                            }).catch(displayError);
                        }
                    }).catch(err => {
                        console.error("Gagal mendapatkan perangkat: ", err);
                        webcam.start().then(() => {
                            cameraStarted();
                        }).catch(displayError);
                    });
                }

                function cameraStarted() {
                    if (webcam.webcamList.length > 1) {
                        $(".btn-cameraFlip").removeClass('d-none');
                    }
                    window.scrollTo(0, 0);
                }

                $(".btn-cameraFlip").click(() => {
                    if (webcam.webcamList.length > 1) {
                        webcam.flip();
                        webcam.start();
                    }
                });

                $(".btn-take-photo").click(() => {
                    removeCapture();
                    const picture = webcam.snap(300, 300);
                    const ctx = canvasElement.getContext("2d");
                    ctx.beginPath();
                    ctx.arc(100, 100, 50, 1.5 * Math.PI, 0.5 * Math.PI, false);

                    const imgData = canvasElement.toDataURL();
                    $.ajax({
                        type: "POST",
                        url: "./module/absen-out/sw-proses.php?action=absen-selfie",
                        data: {
                            img: imgData,
                            latitude: latitude,
                            radius: radius,
                            road: road,
                            city: city,
                            radius_aktif: 'Y'
                        },
                        success: function (data) {
                            const [status, message] = data.split("/");
                            if (status === 'success') {
                                swal({ title: 'Berhasil!', text: message, icon: 'success', timer: 3000 });
                                setTimeout(function() {
                                    window.location.href = "./";
                                }, 3000);
                            } else {
                                swal({ title: 'Oops!', text: data, icon: 'error', timer: 3000 });
                            }
                        }
                    });

                    webcam.stop();
                });

                function removeCapture() {
                    $('#canvas').show();
                    $('.btn-take-photo').addClass('d-none');
                    $('.btn-resume-camera').removeClass('d-none');
                }

                $(".btn-resume-camera").click(() => {
                    webcam.start();
                    $('#canvas').hide();
                    $('.btn-take-photo').removeClass('d-none');
                    $('.btn-resume-camera').addClass('d-none');
                });

                function displayError() {
                    swal({ title: 'Error', text: 'Tidak dapat mengakses kamera', icon: 'error' });
                }

                startFrontCamera();
            }

            // Mulai proses saat halaman dimuat
            const validLat = <?php echo isset($data_lokasi['latitude']) ? floatval($data_lokasi['latitude']) : '0'; ?>;
            const validLon = <?php echo isset($data_lokasi['longitude']) ? floatval($data_lokasi['longitude']) : '0'; ?>;
            const maxDistanceMeters = <?php echo isset($data_lokasi['radius']) ? intval($data_lokasi['radius']) : 0; ?>;
            processLocationAndSelfie(validLat, validLon, maxDistanceMeters);
            </script>
            
        <?php } else {
            /** Selfie tanpa Radius */?>
            <script type="text/javascript">
            // Fungsi untuk menampilkan notifikasi
            function notify(message, type = 'error') {
                swal({
                    title: type === 'success' ? 'Success!' : 'Oops!',
                    text: message,
                    icon: type,
                    timer: 2500
                });
                console.warn(message);
                if (type === 'error') setTimeout(() => location.href = './', 3000);
            }
            
            async function getAddress(lat, lon) {
                const proxyUrl = 'https://cors-anywhere.herokuapp.com/';  // Proxy URL
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`;
                try {
                    const res = await fetch(proxyUrl + url, {
                        method: 'GET',
                        headers: {
                            'User-Agent': 's-widodo.com (swidodo.com@email.com)',  // Ganti dengan informasi yang sesuai
                        }
                    });

                    if (!res.ok) {
                        throw new Error('Failed to fetch data from OpenStreetMap');
                    }
                    const data = await res.json();
                    const addr = data.address;
                    console.log("Address object:", addr); // Debug
                    return {
                        road: addr.road || "",
                        hamlet: addr.hamlet || "",
                        village: addr.village || "",
                        suburb: addr.suburb || "",
                        town: addr.town || "",
                        city: addr.city || "",
                        district: addr.county || "",
                        state: addr.state || "",
                        country: addr.country || "",
                    };
                } catch (error) {
                    console.error('Error fetching address:', error);
                    return {};  // Return empty object or handle the error as needed
                }
            }

            // Geolocation otomatis (tanpa validasi jarak)
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(async pos => {
                    const userLat = pos.coords.latitude;
                    const userLon = pos.coords.longitude;
                    const addressParts = await getAddress(userLat, userLon);
                    const road = addressParts.road;
                    const village = addressParts.village;
                    const hamlet = addressParts.hamlet;
                    const suburb = addressParts.suburb;
                    const city = addressParts.city;
                    const country = addressParts.country;
                    var latitude = ''+userLat.toFixed(6)+', '+userLon.toFixed(6);
                    valid = true;

                    function initMap(userLat, userLon) {
                    map = L.map('map-overlay', {
                        center: [userLat, userLon],
                        zoom: 8,
                        zoomControl: true
                    });
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom:16
                        }).addTo(map);
                        marker = L.marker([userLat, userLon]).addTo(map);
                    }
                    initMap(userLat, userLon);
                    if(valid){
                        /** Kamera ON */
                        const webcamElement=document.getElementById('webcam');const canvasElement=document.getElementById('canvas');const webcam=new Webcam(webcamElement,'user',canvasElement);function startFrontCamera(){navigator.mediaDevices.enumerateDevices().then(devices=>{let frontCamera=devices.find(device=>device.kind==='videoinput'&&device.label.toLowerCase().includes('front'));if(frontCamera){webcam.start(frontCamera.deviceId).then(result=>{cameraStarted();console.log("Kamera depan dimulai")}).catch(err=>{displayError()})}else{console.log('Kamera depan tidak ditemukan');webcam.start().then(result=>{cameraStarted();console.log("Kamera utama dimulai")}).catch(err=>{displayError()})}}).catch(err=>{console.error("Gagal mendapatkan perangkat: ",err);webcam.start().then(result=>{cameraStarted();console.log("Kamera utama dimulai")}).catch(err=>{displayError()})})}
                        startFrontCamera();function cameraStarted(){if(webcam.webcamList.length>1){$(".btn-cameraFlip").removeClass('d-none')}
                        window.scrollTo(0,0)}
                        $('.btn-cameraFlip').click(function(){if(webcam.webcamList.length>1){webcam.flip();webcam.start()}});$(".btn-take-photo").click(()=>{removeCapture();let picture=webcam.snap(300,300);var img=new Image();img.src=picture;var canvas=document.getElementById("canvas");var ctx=canvas.getContext("2d");ctx.beginPath();ctx.arc(100,100,50,1.5*Math.PI,0.5*Math.PI,!1);const imgData=canvasElement.toDataURL();$.ajax({type:"POST",url:"./module/absen-out/sw-proses.php?action=absen-selfie",data:{img:imgData,username:'-',latitude:latitude,radius:0,road:road,city:city,radius_aktif:'N'},success:function(data){const[status,message]=data.split("/");
                            if(status==='success'){
                                swal({title:'Berhasil!',text:message,icon:'success',timer:3000});setTimeout(function() {
                                    window.location.href = "./";
                                }, 3000);
                            }else{swal({title:'Oops!',text:data,icon:'error'})}}});webcam.stop()});function removeCapture(){$('#canvas').show();$('.btn-take-photo').addClass('d-none');$('.btn-resume-camera').removeClass('d-none')}
                        $(".btn-resume-camera").click(()=>{webcam.start();$('#canvas').hide();$('.btn-take-photo').removeClass('d-none');$('.btn-resume-camera').addClass('d-none')})
                        /** End Webcaame */

                    }
                }, err => {
                    alert("❌ Gagal mendeteksi lokasi. Aktifkan GPS atau izinkan akses lokasi.");
                    console.error(err.message);
                },{
                    enableHighAccuracy: true,
                    timeout: 10000
                });
            } else {
                alert("Geolocation tidak didukung di browser ini.");
            }
        </script>
            
<?php }
    break;
}

}?>