<?php
$id = $_GET['id']; // ambil ID dari URL, misal edit.php?id=5

// Pastikan $id divalidasi atau di-escape untuk keamanan
$id = (int) $id; // casting ke integer, lebih aman

$query = $conn->query("
    SELECT p.*, k.nama_kelas, k.kuota 
    FROM pendaftaran_siswa p
    LEFT JOIN kelas k ON p.kelas_id = k.id
    WHERE p.id = $id
");

$data = $query->fetch_assoc();
$tahunAjaran = $data['tahun_ajaran'];
$provinsi_id = $data['provinsi'];
$kota_id = $data['kota'];
$kecamatan_id = $data['kecamatan'];
$desa_id = $data['desa'];
?>

<div class="topbar">
    <h2>Edit Data Siswa</h2>
    <div class="floating-menu">
        <a href="index?page=students" class="btn-float">
            🔙 <span>Kembali</span>
        </a>
    </div>
</div>
<div class="card">
    <form id="formSiswa" action="index?page=edit_proses" method="POST" enctype="multipart/form-data">
        <!-- ID siswa tersembunyi -->
        <input type="hidden" name="id" value="<?= $data['id'] ?>">
        <!-- Tahun Ajaran -->
        <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($tahunAjaran) ?>">
        <p><strong>Tahun Ajaran: <?= htmlspecialchars($tahunAjaran) ?></strong></p>

        <label for="nama_lengkap">Nama Lengkap *</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" required />

        <label for="email">Email *
            <input type="text" id="email" name="email" value="<?= htmlspecialchars($data['email']) ?>" required />
        </label>
        <label for="nisn">NISN *</label>
        <input type="text" id="nisn" name="nisn" value="<?= htmlspecialchars($data['nisn']) ?>" required />

        <label for="kelas">Kelas *</label>
        <select name="kelas" id="kelas" required>
            <option value="<?= $data['kelas_id'] ?>">
                <?= htmlspecialchars($data['nama_kelas']) ?>
            </option>

            <?php
            $result = $conn->query("SELECT id, nama_kelas FROM kelas WHERE tahun_ajaran = '$tahunAjaran' ORDER BY nama_kelas");
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['id'] . '">' . $row['nama_kelas'] . '</option>';
            }
            ?>
        </select>

        <label for="sekolah_asal">Sekolah Asal (SMP) *</label>
        <input type="text" id="sekolah_asal" name="sekolah_asal" value="<?= htmlspecialchars($data['sekolah_asal']) ?>" required />

        <label for="nomor_ijazah">No. Ijazah (SMP) *</label>
        <input type="text" id="nomor_ijazah" name="nomor_ijazah" value="<?= htmlspecialchars($data['nomor_ijazah']) ?>" required />

        <label for="jenis_kelamin">Jenis Kelamin *</label>
        <select id="jenis_kelamin" name="jenis_kelamin" required>
            <option value="<?= $data['jenis_kelamin'] ?>"><?= htmlspecialchars($data['jenis_kelamin']) ?></option>
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
        </select>

        <label for="tempat_lahir">Tempat Lahir *</label>
        <input type="text" id="tempat_lahir" name="tempat_lahir" value="<?= htmlspecialchars($data['tempat_lahir']) ?>" required />

        <label for="tanggal_lahir">Tanggal Lahir *</label>
        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($data['tanggal_lahir']) ?>" required />

        <label for="no_kk">No KK *</label>
        <input type="text" id="no_kk" name="no_kk" value="<?= htmlspecialchars($data['no_kk']) ?>" required />

        <label for="nik">No NIK *</label>
        <input type="text" id="nik" name="nik" value="<?= htmlspecialchars($data['nik']) ?>" required />

        <label for="no_registrasi_akta">No Registrasi Akta Lahir *</label>
        <input type="text" id="no_registrasi_akta" name="no_registrasi_akta" value="<?= htmlspecialchars($data['no_registrasi_akta']) ?>" required />

        <label for="kebutuhan_khusus">Jenis Kebutuhan Khusus (Kosongi jika tidak ada)</label>
        <input type="text" id="kebutuhan_khusus" name="kebutuhan_khusus" value="<?= htmlspecialchars($data['kebutuhan_khusus']) ?>" />

        <label for="agama">Agama *</label>
        <select id="agama" name="agama" required>
            <?= getOptionsAgama($data['agama'] ?? '') ?>
        </select>


        <label>Provinsi:</label>
        <select id="provinsi" required>
            -- Pilih Provinsi --
        </select>
        <input type="hidden" name="provinsi" id="provinsi_nama" value="<?= htmlspecialchars($data['provinsi'] ?? '') ?>">

        <label>Kabupaten/Kota:</label>
        <select id="kabupaten" required></select>
        <input type="hidden" name="kota" id="kabupaten_nama" value="<?= htmlspecialchars($data['kota'] ?? '') ?>">

        <label>Kecamatan:</label>
        <select id="kecamatan" required></select>
        <input type="hidden" name="kecamatan" id="kecamatan_nama" value="<?= htmlspecialchars($data['kecamatan'] ?? '') ?>">

        <label>Desa:</label>
        <select id="desa" required></select>
        <input type="hidden" name="desa" id="desa_nama" value="<?= htmlspecialchars($data['desa'] ?? '') ?>">

        <label for="alamat_rumah">Alamat Rumah (Jalan/Dusun/RT/RW) *</label>
        <textarea id="alamat_rumah" name="alamat" value=""><?= htmlspecialchars($data['alamat']) ?></textarea>

        <!-- koordinat -->
        <label>Koordinat Rumah *</label>
        <input type="text" name="koordinat" id="koordinat_rumah" placeholder="Pilih lokasi pada peta dibawah untuk mendapat koordinat otomatis" value="<?= htmlspecialchars($data['latitude']) . ',' . htmlspecialchars($data['longitude']) ?>" required>
        <input type="hidden" name="latitude" value="<?= $data['latitude'] ?>" required>
        <input type="hidden" name="longitude" value="<?= $data['longitude'] ?>" required>
        <div id="map" style="height:300px;margin-top:10px;"></div>

        <label for="tempat_tinggal">Tempat Tinggal *</label>
        <select id="tempat_tinggal" name="tempat_tinggal" required>
            <?= getOptionsTempatTinggal($data['tempat_tinggal'] ?? '') ?>
        </select>

        <label for="moda_transportasi">Moda Transportasi *</label>
        <select id="moda_transportasi" name="moda_transportasi" required>
            <?= getOptionsModaTransportasi($data['moda_transportasi'] ?? '') ?>
        </select>

        <label for="anak_ke">Anak ke-berapa *</label>
        <input type="number" id="anak_ke" name="anak_ke" min="1" value="<?= htmlspecialchars($data['anak_ke']) ?>" required />

        <label for="jumlah_saudara_kandung">Jumlah Saudara Kandung *</label>
        <input type="number" id="jumlah_saudara_kandung" name="jumlah_saudara_kandung" min="0" value="<?= htmlspecialchars($data['jumlah_saudara_kandung']) ?>" required />

        <label for="tinggi_badan">Tinggi Badan (cm) *</label>
        <input type="number" id="tinggi_badan" name="tinggi_badan" min="0" value="<?= htmlspecialchars($data['tinggi_badan']) ?>" required />

        <label for="berat_badan">Berat Badan (kg) *</label>
        <input type="number" id="berat_badan" name="berat_badan" min="0" value="<?= htmlspecialchars($data['berat_badan']) ?>" required />

        <label for="hobi">Hobi *</label>
        <input type="text" id="hobi" name="hobi" value="<?= htmlspecialchars($data['hobi']) ?>" required />

        <label for="cita_cita">Cita-cita *</label>
        <input type="text" id="cita_cita" name="cita_cita" value="<?= htmlspecialchars($data['cita_cita']) ?>" required />

        <label for="nomor_kip">Nomor KIP/KPS/PKH/KKS (jika ada)</label>
        <input type="text" id="nomor_kip" name="nomor_kip" value="<?= $data['nomor_kip'] ?>" />

        <label for="file_kip">Foto/Scan KIP/KPS/PKH/KKS/KIS (JPG/PDF max 10MB)</label>
        <?php if (!empty($data['file_kip'])): ?>
            <small>Dokumen saat ini: <a href="../uploads/<?= $data['file_kip']; ?>" target="_blank"><?= htmlspecialchars($data['file_kip']) ?></a></small>
        <?php endif; ?>
        <input type="file" id="file_kip" name="file_kip" accept=".pdf,.jpg,.jpeg,.png" />
        <!-- <input type="file" id="file_kip" name="file_kip" <?= empty($data['file_kip']) ? 'required' : '' ?> accept=".pdf,.jpg,.jpeg,.png" /> -->

        <h3>Data Orang Tua / Wali</h3>

        <label for="nama_ayah">Nama Ayah Kandung *</label>
        <input type="text" id="nama_ayah" name="nama_ayah" value="<?= htmlspecialchars($data['nama_ayah']) ?>" required />

        <label for="nik_ayah">NIK Ayah *</label>
        <input type="text" id="nik_ayah" name="nik_ayah" value="<?= htmlspecialchars($data['nik_ayah']) ?>" required />

        <label for="tahun_lahir_ayah">Tahun Lahir Ayah *</label>
        <input type="number" id="tahun_lahir_ayah" name="tahun_lahir_ayah" min="1900" max="2100" value="<?= htmlspecialchars($data['tahun_lahir_ayah']) ?>" required />

        <label for="pendidikan_ayah">Pendidikan Ayah *</label>
        <select id="pendidikan_ayah" name="pendidikan_ayah" required>
            <?= getOptionsPendidikan($data['pendidikan_ayah'] ?? '') ?>
        </select>

        <label for="pekerjaan_ayah">Pekerjaan Ayah *</label>
        <select id="pekerjaan_ayah" name="pekerjaan_ayah" required>
            <?= getOptionsPekerjaan($data['pekerjaan_ayah'] ?? '') ?>
        </select>

        <label for="penghasilan_ayah">Penghasilan Ayah *</label>
        <select id="penghasilan_ayah" name="penghasilan_ayah" required>
            <?= getOptionsPenghasilan($data['penghasilan_ayah'] ?? '') ?>
        </select>

        <label for="nama_ibu">Nama Ibu Kandung *</label>
        <input type="text" id="nama_ibu" name="nama_ibu" value="<?= htmlspecialchars($data['nama_ibu']) ?>" required />

        <label for="nik_ibu">NIK Ibu *</label>
        <input type="text" id="nik_ibu" name="nik_ibu" value="<?= htmlspecialchars($data['nik_ibu']) ?>" required />

        <label for="tahun_lahir_ibu">Tahun Lahir Ibu *</label>
        <input type="number" id="tahun_lahir_ibu" name="tahun_lahir_ibu" min="1900" max="2100" value="<?= htmlspecialchars($data['tahun_lahir_ibu']) ?>" required />

        <label for="pendidikan_ibu">Pendidikan Ibu *</label>
        <select id="pendidikan_ibu" name="pendidikan_ibu" required>
            <?= getOptionsPendidikan($data['pendidikan_ibu'] ?? '') ?>
        </select>
        <label for="pekerjaan_ibu">Pekerjaan Ibu *</label>
        <select id="pekerjaan_ibu" name="pekerjaan_ibu" required>
            <?= getOptionsPekerjaan($data['pekerjaan_ibu'] ?? '') ?>
        </select>

        <label for="penghasilan_ibu">Penghasilan Ibu *</label>
        <select id="penghasilan_ibu" name="penghasilan_ibu" required>
            <?= getOptionsPenghasilan($data['penghasilan_ibu'] ?? '') ?>
        </select>

        <label for="nama_wali">Nama Wali (jika ada)</label>
        <input type="text" id="nama_wali" name="nama_wali" value="<?= htmlspecialchars($data['nama_wali']) ?>" />

        <label for="nik_wali">NIK Wali (jika ada)</label>
        <input type="text" id="nik_wali" name="nik_wali" value="<?= htmlspecialchars($data['nik_wali']) ?>" />

        <label for="tahun_lahir_wali">Tahun Lahir Wali (jika ada)</label>
        <input type="number" id="tahun_lahir_wali" name="tahun_lahir_wali" min="1900" max="2100" value="<?= htmlspecialchars($data['tahun_lahir_wali']) ?>" />

        <label for="pendidikan_wali">Pendidikan Wali (jika ada)</label>
        <select id="pendidikan_wali" name="pendidikan_wali">
            <?= getOptionsPendidikan($data['pendidikan_wali'] ?? '') ?>
        </select>

        <label for="pekerjaan_wali">Pekerjaan Wali (jika ada)</label>
        <select id="pekerjaan_wali" name="pekerjaan_wali">
            <?= getOptionsPekerjaan($data['pekerjaan_wali'] ?? '') ?>
        </select>

        <label for="penghasilan_wali">Penghasilan Wali (jika ada)</label>
        <select id="penghasilan_wali" name="penghasilan_wali">
            <?= getOptionsPenghasilan($data['penghasilan_wali'] ?? '') ?>
        </select>

        <!-- Nomor HP Orang Tua / Wali -->
        <div class="mb-3">
            <label for="nohp_ortu" class="form-label">Nomor HP Orang Tua / Wali *</label>
            <input type="text" class="form-control" id="nohp_ortu" name="nohp_ortu" value="<?= htmlspecialchars($data['nohp_ortu']) ?>" required />
        </div>

        <!-- Nomor HP Siswa -->
        <div class="mb-3">
            <label for="nohp_siswa" class="form-label">Nomor HP Siswa *</label>
            <input type="text" class="form-control" id="nohp_siswa" name="nohp_siswa" value="<?= htmlspecialchars($data['nohp_siswa']) ?>" required />
        </div>



        <label for="file_kk">Foto/Scan Kartu Keluarga (JPG/PDF max 10MB) *</label>
        <?php if (!empty($data['file_kk'])): ?>
            <small>Dokumen saat ini: <a href="../uploads/<?= htmlspecialchars($data['file_kk']) ?>" target="_blank"><?= htmlspecialchars($data['file_kk']) ?></a></small>
        <?php endif; ?>
        <input type="file" id="file_kk" name="file_kk" <?= empty($data['file_kk']) ? 'required' : '' ?> accept=".pdf,.jpg,.jpeg,.png" />


        <label for="file_ijazah">Foto/Scan Ijazah SMP (JPG/PDF max 10MB) *</label>
        <?php if (!empty($data['file_ijazah'])): ?>
            <small>saat ini: <a href="../uploads/<?= htmlspecialchars($data['file_ijazah']) ?>" target="_blank"><?= htmlspecialchars($data['file_ijazah']) ?></a></small>
        <?php endif; ?>
        <input type="file" id="file_ijazah" name="file_ijazah" <?= empty($data['file_ijazah']) ? 'required' : '' ?> accept=".pdf,.jpg,.jpeg,.png" />

        <label>
            <input type="checkbox" name="pernyataan_setuju" value="<?= $data['pernyataan_setuju'] ? 'checked' : '' ?>" <?= $data['pernyataan_setuju'] ? 'checked' : '' ?>>
            Saya menyatakan data yang saya isi adalah benar dan bersedia mengikuti aturan sekolah.
        </label>

        <button type="submit">Simpan</button>
        <!-- <button type="button" id="openModalBtn">Kirim Data</button> -->
    </form>

</div>

<div id="confirmModal" class="modal">
    <div class="modal-content">
        <p>Apakah kamu yakin data yang kamu masukkan sudah benar?</p>
        <div class="modal-buttons">
            <button class="btn-confirm" id="btnSubmit">Kirim Data</button>
            <button class="btn-cancel" id="btnCancel">Batal</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('confirmModal');
    const openBtn = document.getElementById('openModalBtn');
    const btnCancel = document.getElementById('btnCancel');
    const btnSubmit = document.getElementById('btnSubmit');
    const form = document.getElementById('formSiswa');

    // klik "Kirim Data"
    openBtn.addEventListener('click', () => {
        /* cek semua elemen required */
        if (form.checkValidity()) {
            // valid â†’ tampilkan modal
            modal.style.display = 'block';
        } else {
            // tidak valid â†’ munculkan pesan validasi standar
            form.reportValidity(); // memicu bubble pesan bawaan browser
        }
    });

    // batal
    btnCancel.addEventListener('click', () => modal.style.display = 'none');

    // konfirmasi â†’ submit form
    btnSubmit.addEventListener('click', () => {
        modal.style.display = 'none';
        form.submit();
    });

    // klik di luar konten modal
    window.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });
</script>
<script>
    // Menangani pengisian form
    document.getElementById('formSiswa').addEventListener('submit', function(event) {
        event.preventDefault(); // Mencegah pengiriman form default
        // Validasi dan kirim data ke server
        this.submit();
    });
</script>


<!-- Modal Notifikasi -->
<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; padding:20px; width:300px; margin:100px auto; border-radius:8px; text-align:center;">
        <h2>Berhasil!</h2>
        <p>Data berhasil disimpan.</p>
        <button onclick="window.location.href='index?page=student_view&id=<?= $id ?>'">Tutup</button>
    </div>
</div>
<?php
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    echo "<script>window.onload = function() { showSuccessModal(); };</script>";
}
?>
<script>
    function showSuccessModal() {
        document.getElementById('successModal').style.display = 'block';
    }
</script>



<script>
    // Peta + koordinat rumah mengikuti pilihan wilayah.
    var inputLat = document.querySelector('input[name="latitude"]').value;
    var inputLng = document.querySelector('input[name="longitude"]').value;
    var defaultLatLng = (inputLat && inputLng && parseFloat(inputLat) !== 0 && parseFloat(inputLng) !== 0)
        ? [parseFloat(inputLat), parseFloat(inputLng)]
        : [-7.781571, 113.212075];

    var map = L.map('map').setView(defaultLatLng, 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap Affan Contributor'
    }).addTo(map);

    var marker = null;
    if (inputLat && inputLng && parseFloat(inputLat) !== 0 && parseFloat(inputLng) !== 0) {
        marker = L.marker(defaultLatLng).addTo(map);
        document.getElementById('koordinat_rumah').value = parseFloat(inputLat).toFixed(6) + ', ' + parseFloat(inputLng).toFixed(6);
    }

    function setKoordinatMarker(lat, lng, zoom) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);
        if (isNaN(lat) || isNaN(lng)) return;

        var latLng = [lat, lng];
        map.setView(latLng, zoom || 15);

        if (marker) {
            marker.setLatLng(latLng);
        } else {
            marker = L.marker(latLng).addTo(map);
        }

        document.getElementById('koordinat_rumah').value = lat.toFixed(6) + ', ' + lng.toFixed(6);
        document.querySelector('input[name="latitude"]').value = lat.toFixed(6);
        document.querySelector('input[name="longitude"]').value = lng.toFixed(6);
    }

    map.on('click', function(e) {
        setKoordinatMarker(e.latlng.lat, e.latlng.lng, map.getZoom());
    });
</script>

<script>
    const selectedProv = <?= json_encode($data['provinsi'] ?? '') ?>;
    const selectedKab = <?= json_encode($data['kota'] ?? '') ?>;
    const selectedKec = <?= json_encode($data['kecamatan'] ?? '') ?>;
    const selectedDesa = <?= json_encode($data['desa'] ?? '') ?>;

    const provinsiSelect = document.getElementById('provinsi');
    const kabupatenSelect = document.getElementById('kabupaten');
    const kecamatanSelect = document.getElementById('kecamatan');
    const desaSelect = document.getElementById('desa');

    // Isi hidden input sejak awal agar data lama tidak hilang ketika opsi API wilayah gagal match.
    document.getElementById('provinsi_nama').value = selectedProv || '';
    document.getElementById('kabupaten_nama').value = selectedKab || '';
    document.getElementById('kecamatan_nama').value = selectedKec || '';
    document.getElementById('desa_nama').value = selectedDesa || '';

    function clearSelect(select, placeholder) {
        if (!select) return;
        select.innerHTML = '<option value="">' + placeholder + '</option>';
    }

    function normalisasiNamaWilayah(text) {
        text = (text || '').toString().trim().toUpperCase();
        text = text.replace(/^PROVINSI\s+/i, '');
        text = text.replace(/^PROPINSI\s+/i, '');
        text = text.replace(/^KABUPATEN\s+/i, '');
        text = text.replace(/^KAB\.\s*/i, '');
        text = text.replace(/^KOTA\s+/i, '');
        text = text.replace(/^KECAMATAN\s+/i, '');
        text = text.replace(/^KEC\.\s*/i, '');
        text = text.replace(/^DESA\s*\/\s*KEL\.\s*/i, '');
        text = text.replace(/^DESA\s*\/\s*KELURAHAN\s*/i, '');
        text = text.replace(/^DESA\s+/i, '');
        text = text.replace(/^KELURAHAN\s+/i, '');
        text = text.replace(/^KEL\.\s*/i, '');
        text = text.replace(/\s+/g, ' ');
        return text.trim();
    }

    function sameWilayah(apiName, savedName) {
        if (!savedName) return false;
        var a = normalisasiNamaWilayah(apiName);
        var b = normalisasiNamaWilayah(savedName);
        return a === b || a.indexOf(b) !== -1 || b.indexOf(a) !== -1;
    }

    function addCurrentOptionIfMissing(select, savedName, label) {
        if (!select || !savedName) return;
        var exists = Array.from(select.options).some(function(opt) {
            return opt.text === savedName || sameWilayah(opt.text, savedName);
        });
        if (!exists) {
            var option = document.createElement('option');
            option.value = '__saved__';
            option.text = savedName;
            option.selected = true;
            select.add(option);
        }
    }

    function getSelectedText(id) {
        var select = document.getElementById(id);
        if (!select || select.selectedIndex < 0) return '';
        var text = select.options[select.selectedIndex].text || '';
        if (text.indexOf('--') === 0) return '';
        return text.trim();
    }

    function fetchNominatim(query) {
        var url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&countrycodes=id&q=' + encodeURIComponent(query);
        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (Array.isArray(data) && data.length > 0) return data[0];
                return null;
            });
    }

    var geocodeTimer = null;
    function geocodeAlamatTerpilih(level) {
        clearTimeout(geocodeTimer);
        geocodeTimer = setTimeout(function() {
            var desa = normalisasiNamaWilayah(document.getElementById('desa_nama').value || getSelectedText('desa'));
            var kecamatan = normalisasiNamaWilayah(document.getElementById('kecamatan_nama').value || getSelectedText('kecamatan'));
            var kota = normalisasiNamaWilayah(document.getElementById('kabupaten_nama').value || getSelectedText('kabupaten'));
            var provinsi = normalisasiNamaWilayah(document.getElementById('provinsi_nama').value || getSelectedText('provinsi'));
            if (!provinsi) return;

            var queries = [];
            if (desa && kecamatan && kota && provinsi) queries.push([desa, kecamatan, kota, provinsi, 'Indonesia'].join(', '));
            if (desa && kota && provinsi) queries.push([desa, kota, provinsi, 'Indonesia'].join(', '));
            if (desa && kecamatan && provinsi) queries.push([desa, kecamatan, provinsi, 'Indonesia'].join(', '));
            if (kecamatan && kota && provinsi) queries.push([kecamatan, kota, provinsi, 'Indonesia'].join(', '));
            if (kota && provinsi) queries.push([kota, provinsi, 'Indonesia'].join(', '));
            queries.push([provinsi, 'Indonesia'].join(', '));
            queries = queries.filter(function(item, index) { return item && queries.indexOf(item) === index; });

            var zoom = 11;
            if (level === 'provinsi') zoom = 8;
            if (level === 'kota') zoom = 11;
            if (level === 'kecamatan') zoom = 13;
            if (level === 'desa') zoom = 16;

            function cobaQuery(index) {
                if (index >= queries.length) {
                    console.warn('Koordinat wilayah tidak ditemukan:', { desa, kecamatan, kota, provinsi });
                    return;
                }
                fetchNominatim(queries[index]).then(function(result) {
                    if (result && result.lat && result.lon) {
                        setKoordinatMarker(result.lat, result.lon, zoom);
                        return;
                    }
                    cobaQuery(index + 1);
                }).catch(function() { cobaQuery(index + 1); });
            }
            cobaQuery(0);
        }, 350);
    }

    function loadProvinsi() {
        clearSelect(provinsiSelect, '-- Pilih Provinsi --');
        clearSelect(kabupatenSelect, '-- Pilih Kabupaten/Kota --');
        clearSelect(kecamatanSelect, '-- Pilih Kecamatan --');
        clearSelect(desaSelect, '-- Pilih Desa --');

        fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var matchedProvId = '';
                data.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.text = item.name;
                    if (sameWilayah(item.name, selectedProv)) {
                        option.selected = true;
                        matchedProvId = item.id;
                        document.getElementById('provinsi_nama').value = item.name;
                    }
                    provinsiSelect.add(option);
                });

                addCurrentOptionIfMissing(provinsiSelect, selectedProv, 'Provinsi tersimpan');

                if (matchedProvId) {
                    loadKabupaten(matchedProvId, selectedKab, selectedKec, selectedDesa, false);
                } else {
                    addCurrentOptionIfMissing(kabupatenSelect, selectedKab, 'Kabupaten tersimpan');
                    addCurrentOptionIfMissing(kecamatanSelect, selectedKec, 'Kecamatan tersimpan');
                    addCurrentOptionIfMissing(desaSelect, selectedDesa, 'Desa tersimpan');
                }
            });
    }

    function loadKabupaten(provId, selectedKabupaten, selectedKecamatan, selectedDesaValue, doGeocode) {
        clearSelect(kabupatenSelect, '-- Pilih Kabupaten/Kota --');
        clearSelect(kecamatanSelect, '-- Pilih Kecamatan --');
        clearSelect(desaSelect, '-- Pilih Desa --');
        if (!provId || provId === '__saved__') return;

        fetch('https://www.emsifa.com/api-wilayah-indonesia/api/regencies/' + provId + '.json')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var matchedKabId = '';
                data.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.text = item.name;
                    if (sameWilayah(item.name, selectedKabupaten)) {
                        option.selected = true;
                        matchedKabId = item.id;
                        document.getElementById('kabupaten_nama').value = item.name;
                    }
                    kabupatenSelect.add(option);
                });

                addCurrentOptionIfMissing(kabupatenSelect, selectedKabupaten, 'Kabupaten tersimpan');

                if (matchedKabId) {
                    loadKecamatan(matchedKabId, selectedKecamatan, selectedDesaValue, false);
                } else {
                    addCurrentOptionIfMissing(kecamatanSelect, selectedKecamatan, 'Kecamatan tersimpan');
                    addCurrentOptionIfMissing(desaSelect, selectedDesaValue, 'Desa tersimpan');
                }
                if (doGeocode) geocodeAlamatTerpilih('provinsi');
            });
    }

    function loadKecamatan(kabId, selectedKecamatan, selectedDesaValue, doGeocode) {
        clearSelect(kecamatanSelect, '-- Pilih Kecamatan --');
        clearSelect(desaSelect, '-- Pilih Desa --');
        if (!kabId || kabId === '__saved__') return;

        fetch('https://www.emsifa.com/api-wilayah-indonesia/api/districts/' + kabId + '.json')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var matchedKecId = '';
                data.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.text = item.name;
                    if (sameWilayah(item.name, selectedKecamatan)) {
                        option.selected = true;
                        matchedKecId = item.id;
                        document.getElementById('kecamatan_nama').value = item.name;
                    }
                    kecamatanSelect.add(option);
                });

                addCurrentOptionIfMissing(kecamatanSelect, selectedKecamatan, 'Kecamatan tersimpan');

                if (matchedKecId) {
                    loadDesa(matchedKecId, selectedDesaValue, false);
                } else {
                    addCurrentOptionIfMissing(desaSelect, selectedDesaValue, 'Desa tersimpan');
                }
                if (doGeocode) geocodeAlamatTerpilih('kota');
            });
    }

    function loadDesa(kecId, selectedDesaValue, doGeocode) {
        clearSelect(desaSelect, '-- Pilih Desa --');
        if (!kecId || kecId === '__saved__') return;

        fetch('https://www.emsifa.com/api-wilayah-indonesia/api/villages/' + kecId + '.json')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                data.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.text = item.name;
                    if (sameWilayah(item.name, selectedDesaValue)) {
                        option.selected = true;
                        document.getElementById('desa_nama').value = item.name;
                    }
                    desaSelect.add(option);
                });

                addCurrentOptionIfMissing(desaSelect, selectedDesaValue, 'Desa tersimpan');
                if (doGeocode) geocodeAlamatTerpilih('kecamatan');
            });
    }

    provinsiSelect.addEventListener('change', function() {
        var provName = getSelectedText('provinsi');
        document.getElementById('provinsi_nama').value = provName;
        document.getElementById('kabupaten_nama').value = '';
        document.getElementById('kecamatan_nama').value = '';
        document.getElementById('desa_nama').value = '';
        loadKabupaten(this.value, '', '', '', true);
    });

    kabupatenSelect.addEventListener('change', function() {
        var kabName = getSelectedText('kabupaten');
        document.getElementById('kabupaten_nama').value = kabName;
        document.getElementById('kecamatan_nama').value = '';
        document.getElementById('desa_nama').value = '';
        loadKecamatan(this.value, '', '', true);
    });

    kecamatanSelect.addEventListener('change', function() {
        var kecName = getSelectedText('kecamatan');
        document.getElementById('kecamatan_nama').value = kecName;
        document.getElementById('desa_nama').value = '';
        loadDesa(this.value, '', true);
    });

    desaSelect.addEventListener('change', function() {
        var desaName = getSelectedText('desa');
        document.getElementById('desa_nama').value = desaName;
        geocodeAlamatTerpilih('desa');
    });

    // Jika admin mengetik koordinat manual, simpan juga ke hidden latitude/longitude.
    document.getElementById('koordinat_rumah').addEventListener('change', function() {
        var parts = (this.value || '').split(',');
        if (parts.length >= 2) {
            var lat = parseFloat(parts[0]);
            var lng = parseFloat(parts[1]);
            if (!isNaN(lat) && !isNaN(lng)) setKoordinatMarker(lat, lng, 16);
        }
    });

    loadProvinsi();
</script>
</body>

</html>
