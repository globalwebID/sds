<div class="card br-0">
    <div class="card-body">
        <form method="POST" action="edit_proses" id="formSiswa" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="siswa">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row">
                <div class="top-tab mt-0">
                    <div class="container-fluid p-0 ">
                        <div class="row modal-dialog-centered">
                            <div class="col-auto d-sm-block">
                                <h5 class="card-title">Biodata Peserta Didik</h5>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm table-hover mb-0" id="dataSiswaTable">
                            <tr>
                                <td class="label">Tahun Ajaran Masuk</td>
                                <td>:</td>
                                <td id="td_tahun_ajaran">
                                    <?= show($student['tahun_ajaran']); ?>
                                    <input type="text" name="tahun_ajaran" class="form-control d-none" value="<?= htmlspecialchars($tahunAjaran) ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Nama Lengkap</td>
                                <td>:</td>
                                <td id="td_nama_lengkap">
                                    <?= show($student['nama_lengkap']); ?>
                                    <input type="text" name="nama_lengkap" class="form-control d-none" value="<?= htmlspecialchars($student['nama_lengkap']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Email</td>
                                <td>:</td>
                                <td id="td_email">
                                    <?= show($student['email']); ?>
                                    <input type="text" id="email" name="email" class="form-control d-none" value="<?= htmlspecialchars($student['email']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">NISN</td>
                                <td>:</td>
                                <td id="td_nisn">
                                    <?= show($student['nisn']); ?>
                                    <input type="text" name="nisn" class="form-control d-none" value="<?= htmlspecialchars($student['nisn']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">NIPD</td>
                                <td>:</td>
                                <td id="td_nipd">
                                    <?= show($student['nipd']); ?>
                                    <input type="text" name="nipd" class="form-control d-none" value="<?= htmlspecialchars($student['nipd']) ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Kelas</td>
                                <td>:</td>
                                <td id="td_nama_kelas">
                                    <?= show($student['nama_kelas']); ?>
                                    <select name="kelas" id="td_nama_kelas" class="form-select d-none">
                                        <option value="<?= $student['kelas_id'] ?>">
                                            <?= htmlspecialchars($student['nama_kelas']) ?>
                                        </option>

                                        <?php
                                        $result = $conn->query("SELECT id, nama_kelas FROM kelas WHERE tahun_ajaran = '$tahunAjaran' ORDER BY nama_kelas");
                                        while ($row = $result->fetch_assoc()) {
                                            // Hindari duplikasi opsi yang sudah terpilih
                                            if ($row['id'] != $student['kelas_id']) {
                                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_kelas']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Sekolah Asal</td>
                                <td>:</td>
                                <td id="td_sekolah_asal">
                                    <?= show($student['sekolah_asal']); ?>
                                    <input type="text" name="sekolah_asal" class="form-control d-none" value="<?= htmlspecialchars($student['sekolah_asal']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Nomor Ijazah</td>
                                <td>:</td>
                                <td id="td_nomor_ijazah">
                                    <?= show($student['nomor_ijazah']); ?>
                                    <input type="text" name="nomor_ijazah" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_ijazah']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Jenis Kelamin</td>
                                <td>:</td>
                                <td id="td_jenis_kelamin">
                                    <?= show($student['jenis_kelamin']); ?>
                                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-select d-none">
                                        <option value="<?= $student['jenis_kelamin'] ?>"><?= htmlspecialchars($student['jenis_kelamin']) ?></option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Tempat, Tgl Lahir</td>
                                <td>:</td>
                                <td id="td_tempat_tanggal_lahir">
                                    <?= show($student['tempat_lahir']) . ', ' . show(date('d/m/Y', strtotime($student['tanggal_lahir']))); ?>
                                    <input type="text" name="tempat_lahir" class="form-control d-none" value="<?= htmlspecialchars($student['tempat_lahir']) ?>">
                                    <input type="date" name="tanggal_lahir" class="form-control d-none" value="<?= htmlspecialchars($student['tanggal_lahir']) ?>">
                                </td>
                            </tr>
                            <tr>
                            <tr>
                                <td class="label">Nomor Kartu Keluarga (KK)</td>
                                <td>:</td>
                                <td id="td_no_kk">
                                    <?= show($student['no_kk']); ?>
                                    <input type="text" name="no_kk" class="form-control d-none" value="<?= htmlspecialchars($student['no_kk']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">NIK</td>
                                <td>:</td>
                                <td id="td_nik">
                                    <?= show($student['nik']); ?>
                                    <input type="text" name="nik" class="form-control d-none" value="<?= htmlspecialchars($student['nik']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">No Registrasi Akta Lahir</td>
                                <td>:</td>
                                <td id="td_no_registrasi_akta">
                                    <?= show($student['no_registrasi_akta']); ?>
                                    <input type="text" name="no_registrasi_akta" class="form-control d-none" value="<?= htmlspecialchars($student['no_registrasi_akta']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Agama</td>
                                <td>:</td>
                                <td id="td_agama">
                                    <?= show($student['agama']); ?>
                                    <select id="agama" name="agama" class="form-select d-none">
                                        <?= getOptionsAgama($student['agama'] ?? '') ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label" style="vertical-align: top;">Alamat</td>
                                <td style="vertical-align: top;">:</td>
                                <td id="td_alamat">
                                    <div id="alamat_view">
                                        <?= show($student['alamat']); ?>,<br>
                                        Desa <?= show($student['desa']); ?>,<br>
                                        Kecamatan <?= show($student['kecamatan']); ?>,<br>
                                        <?= show($student['kota']); ?>
                                    </div>
                                    <div id="alamat_edit">
                                        <label class="d-none">Provinsi:</label>
                                        <select id="provinsi" class="form-select d-none">
                                            -- Pilih Provinsi --
                                        </select>
                                        <input type="hidden" name="provinsi" id="provinsi_nama">

                                        <label class="d-none">Kabupaten/Kota:</label>
                                        <select id="kabupaten" class="form-select d-none"></select>
                                        <input type="hidden" name="kota" id="kabupaten_nama">

                                        <label class="d-none">Kecamatan:</label>
                                        <select id="kecamatan" class="form-select d-none"></select>
                                        <input type="hidden" name="kecamatan" id="kecamatan_nama">

                                        <label class="d-none">Desa:</label>
                                        <select id="desa" class="form-select d-none"></select>
                                        <input type="hidden" name="desa" id="desa_nama">

                                        <label class="d-none" for="alamat_rumah">Alamat Rumah (Jalan/Dusun/RT/RW) *</label>
                                        <textarea name="alamat" class="form-control d-none" rows="2"><?= htmlspecialchars($student['alamat']) ?></textarea>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Koordinat Rumah</td>
                                <td>:</td>
                                <td id="td_koordinat">
                                    <span class="edit-hide"><?= show($student['latitude']) . ', ' . show($student['longitude']); ?></span>
                                    <a href="https://www.google.com/maps?q=<?= $student['latitude'] ?>,<?= $student['longitude'] ?>"
                                        target="_blank"
                                        class="edit-hide">
                                        <i class="align-middle" data-feather="map-pin" title="Lihat di Google Maps" style="float: right;"></i>
                                    </a>

                                    <input type="text" class="form-control d-none" name="koordinat" id="koordinat_rumah" placeholder="Pilih lokasi pada peta dibawah untuk mendapat koordinat otomatis" value="<?= htmlspecialchars($student['latitude']) . ',' . htmlspecialchars($student['longitude']) ?>">
                                    <input type="hidden" name="latitude" value="<?= $student['latitude'] ?>">
                                    <input type="hidden" name="longitude" value="<?= $student['longitude'] ?>">
                                </td>
                            </tr>
                            <tr class="d-none">
                                <td colspan="3" style="border: none;">
                                    <div id="map" style="height:300px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Tempat Tinggal</td>
                                <td>:</td>
                                <td id="td_tempat_tinggal">
                                    <?= show($student['tempat_tinggal']); ?>
                                    <select id="tempat_tinggal" name="tempat_tinggal" class="form-select d-none">
                                        <?= getOptionsTempatTinggal($student['tempat_tinggal'] ?? '') ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Moda Transportasi</td>
                                <td>:</td>
                                <td id="td_transportasi">
                                    <?= show($student['moda_transportasi']); ?>
                                    <select id="moda_transportasi" name="moda_transportasi" class="form-select d-none">
                                        <?= getOptionsModaTransportasi($student['moda_transportasi'] ?? '') ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Anak ke / Saudara Kandung</td>
                                <td>:</td>
                                <td id="td_anak_saudara">
                                    <?= show($student['anak_ke']) . ' / ' . show($student['jumlah_saudara_kandung']); ?>
                                    <input type="number" name="anak_ke" class="form-control d-none" value="<?= htmlspecialchars($student['anak_ke']) ?>">
                                    <input type="number" name="jumlah_saudara_kandung" class="form-control d-none" value="<?= htmlspecialchars($student['jumlah_saudara_kandung']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Tinggi & Berat Badan</td>
                                <td>:</td>
                                <td id="td_tinggi_berat">
                                    <?= show($student['tinggi_badan']) . ' cm / ' . show($student['berat_badan']) . ' kg'; ?>
                                    <input type="number" name="tinggi_badan" class="form-control d-none" value="<?= htmlspecialchars($student['tinggi_badan']) ?>">
                                    <input type="number" name="berat_badan" class="form-control d-none" value="<?= htmlspecialchars($student['berat_badan']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Hobi / Cita‑cita</td>
                                <td>:</td>
                                <td id="td_hobi_cita">
                                    <?= show($student['hobi']) . ' / ' . show($student['cita_cita']); ?>
                                    <input type="text" name="hobi" class="form-control d-none" value="<?= htmlspecialchars($student['hobi']) ?>">
                                    <input type="text" name="cita_cita" class="form-control d-none" value="<?= htmlspecialchars($student['cita_cita']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Nomor HP/WA Siswa</td>
                                <td>:</td>
                                <td id="td_nohp_siswa">
                                    <?= show($student['nohp_siswa']); ?>
                                    <input type="text" name="nohp_siswa" class="form-control d-none" value="<?= htmlspecialchars($student['nohp_siswa']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Nomor HP/WA Orang Tua</td>
                                <td>:</td>
                                <td id="td_nohp_ortu">
                                    <?= show($student['nohp_ortu']); ?>
                                    <input type="text" name="nohp_ortu" class="form-control d-none" value="<?= htmlspecialchars($student['nohp_ortu']) ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <button type="submit" id="saveBtnb" class="btn btn-success d-none">Simpan</button>
                    </div>

                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div id="previewContainer" class="mt-3">
                            <img id="previewImage" src="#" alt="Preview Foto" style="display: none; max-width: 200px; border: 1px solid #ccc; padding: 5px;">
                        </div>
                        <?php if ($student['foto']): ?>
                            <a href="../uploads/<?= $student['foto'] ?>" data-lightbox="foto-<?= $student['nisn'] ?>">
                                <img src="../uploads/<?= $student['foto'] ?>" class="img-responsive mt-2" width="130" height="165" style="object-fit: cover;">
                            </a>
                        <?php else: echo '-';
                        endif; ?>
                        <div class="mt-2">
                            <label for="foto" class="btn btn-primary">Upload Foto</label>
                            <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" style="display: none;" onchange="uploadDanPreviewFoto(this)">
                            <div id="previewContainer" class="mt-2">
                                <img id="previewImage" src="#" alt="Preview Foto" style="display: none; max-width: 200px; border: 1px solid #ccc; padding: 5px;">
                            </div>
                        </div>
                        <small>Gunakan gambar minimal <br> 130px x 165px <br> dalam format .jpg</small>
                    </div>
                </div>
            </div>
            <label class="mt-4">
                <input type="checkbox" name="pernyataan_setuju" value="1" <?= $student['pernyataan_setuju'] ? 'checked' : '' ?> disabled>
                <input type="hidden" name="pernyataan_setuju" class="form-control d-none" value="<?= $student['pernyataan_setuju']; ?>">
                Siswa <strong><?= $student['nama_lengkap'] ?></strong> menyatakan data yang saya isi adalah benar dan bersedia mengikuti aturan sekolah.
            </label>
        </form>
    </div>
</div>
<script>
    function uploadDanPreviewFoto(input) {
        const file = input.files[0];
        if (!file) return;

        // Preview Foto
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('previewImage');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);

        // Kirim ke server via AJAX (Fetch)
        const formData = new FormData();
        formData.append('foto', file);
        formData.append('id_siswa', document.querySelector('[name="id_siswa"]').value);
        formData.append('tahun_ajaran', document.querySelector('[name="tahun_ajaran"]').value);
        formData.append('nisn', document.querySelector('[name="nisn"]').value);
        formData.append('csrf', <?= json_encode(sds_csrf_token()) ?>);

        fetch('pages/upload_foto_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(async (response) => {
                const text = await response.text();
                console.log('HTTP', response.status, text);
                if (!response.ok) throw new Error(text || `HTTP ${response.status}`);
                if (!/berhasil/i.test(text)) throw new Error(text || 'Upload gagal');
                return text;
            })
            .then(() => {
                window.sdsNotify('Foto berhasil diunggah!', 'success');
                setTimeout(() => location.reload(), 200);
            })
            .catch(err => {
                window.sdsNotify('Upload gagal: ' + err.message, 'danger');
                console.error(err);
            });
    }
</script>
