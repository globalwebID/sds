<?php
/**
 * PARTIAL: BIODATA PESERTA DIDIK (EDIT MODE)
 * - Tidak mengubah fitur, hanya merapikan + memperbaiki bug umum:
 *   1) Kelas tidak bisa dipindah / "Kelas tidak valid." => kirim kelas_id + kelas (legacy)
 *   2) ID dobel (td_nama_kelas) => diperbaiki
 *   3) Upload foto ajax butuh id_siswa => ditambahkan hidden
 *
 * PRASYARAT VARIABEL:
 * - $conn (mysqli)
 * - $id (int)
 * - $student (array) minimal: ['id','tahun_ajaran','nama_lengkap','email','nisn','nipd','kelas_id','nama_kelas','foto', dst]
 * - $tahunAjaran (string) tahun ajaran aktif yang dipakai load kelas
 * - $student (array) sebagai sumber nilai edit; $data boleh ada untuk kompatibilitas
 * - fungsi: show(), getOptionsAgama(), getOptionsTempatTinggal(), getOptionsModaTransportasi()
 */

// =====================================================
// Normalisasi kelas aktif untuk mode edit siswa.
// Jangan hanya memakai pendaftaran_siswa.kelas_id, karena setelah naik kelas
// kelas aktif siswa tersimpan di tabel siswa_kelas untuk tahun ajaran aktif.
// =====================================================
$kelasAktifId = (int)($student['kelas_id'] ?? 0);
$namaKelasAktif = (string)($student['nama_kelas'] ?? '');
$jurusanAktifId = (int)($student['jurusan_id'] ?? 0);
$tahunAjaranAktifEdit = (string)($tahunAjaran ?? '');

if (!empty($conn) && !empty($id) && $tahunAjaranAktifEdit !== '') {
  $stmtKelasAktif = $conn->prepare("
    SELECT
      sk.kelas_id,
      k.nama_kelas,
      k.jurusan_id,
      tk.nama_tingkat,
      tk.urutan_tingkat
    FROM siswa_kelas sk
    JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE sk.siswa_id = ?
      AND sk.tahun_ajaran = ?
      AND k.tahun_ajaran = ?
    ORDER BY sk.id DESC
    LIMIT 1
  ");
  if ($stmtKelasAktif) {
    $stmtKelasAktif->bind_param('iss', $id, $tahunAjaranAktifEdit, $tahunAjaranAktifEdit);
    $stmtKelasAktif->execute();
    $kelasAktif = $stmtKelasAktif->get_result()->fetch_assoc();
    $stmtKelasAktif->close();

    if ($kelasAktif) {
      $kelasAktifId = (int)$kelasAktif['kelas_id'];
      $namaKelasAktif = (string)$kelasAktif['nama_kelas'];
      $jurusanAktifId = (int)$kelasAktif['jurusan_id'];

      // Paksa data yang dipakai view/edit mengikuti kelas aktif.
      $student['kelas_id'] = $kelasAktifId;
      $student['nama_kelas'] = $namaKelasAktif;
      $student['jurusan_id'] = $jurusanAktifId;
      $student['nama_tingkat'] = (string)($kelasAktif['nama_tingkat'] ?? ($student['nama_tingkat'] ?? ''));
      $student['urutan_tingkat'] = (int)($kelasAktif['urutan_tingkat'] ?? ($student['urutan_tingkat'] ?? 0));
    }
  }
}

// Fallback kalau tahun ajaran aktif belum punya relasi siswa_kelas.
// Ambil relasi siswa_kelas terbaru, bukan kelas_id lama dari pendaftaran_siswa.
if (!empty($conn) && !empty($id) && $kelasAktifId <= 0) {
  $stmtKelasLatest = $conn->prepare("
    SELECT sk.kelas_id, k.nama_kelas, k.jurusan_id, tk.nama_tingkat, tk.urutan_tingkat
    FROM siswa_kelas sk
    JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE sk.siswa_id = ?
    ORDER BY sk.tahun_ajaran DESC, sk.id DESC
    LIMIT 1
  ");
  if ($stmtKelasLatest) {
    $stmtKelasLatest->bind_param('i', $id);
    $stmtKelasLatest->execute();
    $kelasLatest = $stmtKelasLatest->get_result()->fetch_assoc();
    $stmtKelasLatest->close();
    if ($kelasLatest) {
      $student['kelas_id'] = (int)$kelasLatest['kelas_id'];
      $student['nama_kelas'] = (string)$kelasLatest['nama_kelas'];
      $student['jurusan_id'] = (int)$kelasLatest['jurusan_id'];
      $student['nama_tingkat'] = (string)($kelasLatest['nama_tingkat'] ?? '');
      $student['urutan_tingkat'] = (int)($kelasLatest['urutan_tingkat'] ?? 0);
    }
  }
}

// Pastikan data wilayah edit memakai data siswa jika array $data kosong/beda.
$data['provinsi'] = $data['provinsi'] ?? ($student['provinsi'] ?? '');
$data['kota'] = $data['kota'] ?? ($student['kota'] ?? '');
$data['kecamatan'] = $data['kecamatan'] ?? ($student['kecamatan'] ?? '');
$data['desa'] = $data['desa'] ?? ($student['desa'] ?? '');
?>

<div class="card br-0">
  <div class="card-body">
    <form method="POST" action="edit_proses" id="formSiswa" enctype="multipart/form-data">
      <input type="hidden" name="mode" value="siswa">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <!-- dipakai upload ajax -->
      <input type="hidden" name="id_siswa" value="<?= (int)$id ?>">

      <!-- Kompatibilitas: sebagian backend pakai 'kelas', sebagian pakai 'kelas_id' -->
      <input type="hidden" name="kelas" id="kelas_legacy" value="<?= (int)($student['kelas_id'] ?? 0) ?>">
      <input type="hidden" name="kelas_id" id="kelas_id_hidden" value="<?= (int)($student['kelas_id'] ?? 0) ?>">

      <div class="row m-n3">
        <div class="top-tab mt-0">
          <div class="container-fluid p-0">
            <div class="row modal-dialog-centered">
              <div class="col-auto d-sm-block">
                <h5 class="card-title mb-0">Biodata Peserta Didik</h5>
              </div>
              <div class="col-auto ms-auto text-end">
                <button type="button" id="editBtn" class="btn btn-primary" onclick="toggleEdit()">Edit Data Siswa</button>
                <button type="submit" id="saveBtn" class="btn btn-success d-none">Simpan</button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-9">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="border-right:1px solid #ccc" id="dataSiswaTable">

              <tr>
                <td class="label">Tahun Ajaran Masuk</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['tahun_ajaran'] ?? ''); ?></span>
                  <input type="text" name="tahun_ajaran" class="form-control d-none" value="<?= htmlspecialchars($tahunAjaran ?? '', ENT_QUOTES) ?>" readonly>
                </td>
              </tr>

              <tr>
                <td class="label">Nama Lengkap</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nama_lengkap'] ?? ''); ?></span>
                  <input type="text" name="nama_lengkap" class="form-control d-none" value="<?= htmlspecialchars($student['nama_lengkap'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Email</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['email'] ?? ''); ?></span>
                  <input type="text" id="email" name="email" class="form-control d-none" value="<?= htmlspecialchars($student['email'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">NISN</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nisn'] ?? ''); ?></span>
                  <input type="text" name="nisn" class="form-control d-none" value="<?= htmlspecialchars($student['nisn'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">NIPD</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nipd'] ?? ''); ?></span>
                  <input type="text" name="nipd" class="form-control d-none" value="<?= htmlspecialchars($student['nipd'] ?? '', ENT_QUOTES) ?>" readonly>
                </td>
              </tr>

              <!-- =========================
                   KELAS (FILTER: tingkat + jurusan)
                   ========================= -->
              <tr>
                <td class="label">Kelas</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nama_kelas'] ?? ''); ?></span>

                  <?php
                  $kelasTerpilihId  = (int)($student['kelas_id'] ?? 0);
                  $tahunAjaranAktif = (string)($tahunAjaran ?? ($student['tahun_ajaran'] ?? ''));

                  // --- 1) tentukan tingkat siswa (X / XI / XII) dari nama_kelas yang tampil ---
                  $namaKelasNow = trim((string)($student['nama_kelas'] ?? ''));
                  $urutanTingkatTarget = 0;
                  if (preg_match('/^X\b/i', $namaKelasNow)) {
                    $urutanTingkatTarget = 1; // X
                  } elseif (preg_match('/^XI\b/i', $namaKelasNow)) {
                    $urutanTingkatTarget = 2; // XI
                  } elseif (preg_match('/^XII\b/i', $namaKelasNow)) {
                    $urutanTingkatTarget = 3; // XII (kalau ada)
                  }

                  // --- 2) ambil jurusan_id & urutan_tingkat fallback dari kelas siswa saat ini (kelas_id) ---
                  $jurusanIdTarget = 0;
                  $namaTingkatTarget = '';
                  if ($kelasTerpilihId > 0) {
                    $stmtMeta = $conn->prepare("
                      SELECT k.jurusan_id, tk.urutan_tingkat, tk.nama_tingkat
                      FROM kelas k
                      LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
                      WHERE k.id = ?
                      LIMIT 1
                    ");
                    $stmtMeta->bind_param("i", $kelasTerpilihId);
                    $stmtMeta->execute();
                    $meta = $stmtMeta->get_result()->fetch_assoc() ?: [];
                    $stmtMeta->close();

                    $jurusanIdTarget = (int)($meta['jurusan_id'] ?? 0);
                    $namaTingkatTarget = (string)($meta['nama_tingkat'] ?? '');

                    // fallback kalau nama_kelas kosong/tidak diawali X/XI/XII
                    if ($urutanTingkatTarget === 0) {
                      $urutanTingkatTarget = (int)($meta['urutan_tingkat'] ?? 0);
                    }
                  }

                  $filterByTingkat = ($urutanTingkatTarget > 0);
                  $filterByJurusan = ($jurusanIdTarget > 0);
                  ?>

                  <!-- PENTING: id jangan dobel dengan td -->
                  <select name="kelas_id" id="select_kelas" class="form-select d-none">
                    <?php if ($kelasTerpilihId > 0): ?>
                      <option value="<?= $kelasTerpilihId ?>" selected>
                        <?= htmlspecialchars($student['nama_kelas'] ?? '-', ENT_QUOTES) ?>
                      </option>
                    <?php else: ?>
                      <option value="" selected disabled>-- Pilih Kelas --</option>
                    <?php endif; ?>

                    <?php
                    // Load kelas sesuai tingkat + jurusan untuk tahun ajaran aktif
                    $sql = "
                      SELECT k.id, k.nama_kelas
                      FROM kelas k
                      LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
                      WHERE k.tahun_ajaran = ?
                    ";
                    $types = "s";
                    $params = [$tahunAjaranAktif];

                    if ($filterByJurusan) {
                      $sql .= " AND k.jurusan_id = ? ";
                      $types .= "i";
                      $params[] = $jurusanIdTarget;
                    }
                    if ($filterByTingkat) {
                      $sql .= " AND tk.urutan_tingkat = ? ";
                      $types .= "i";
                      $params[] = $urutanTingkatTarget;
                    }

                    $sql .= " ORDER BY k.nama_kelas ASC";

                    $stmtKelasList = $conn->prepare($sql);
                    $stmtKelasList->bind_param($types, ...$params);
                    $stmtKelasList->execute();
                    $result = $stmtKelasList->get_result();

                    if ($result) {
                      while ($row = $result->fetch_assoc()) {
                        $kid = (int)$row['id'];
                        if ($kid === $kelasTerpilihId) continue;
                        echo '<option value="'.$kid.'">'.htmlspecialchars((string)$row['nama_kelas'], ENT_QUOTES).'</option>';
                      }
                    }
                    $stmtKelasList->close();
                    ?>
                  </select>

                  <!-- BONUS UI: hint filter yang aktif -->
                  <small class="text-muted d-none" id="hint_kelas">
                    Filter:
                    <b>
                      <?php
                        if ($urutanTingkatTarget === 1) echo 'Kelas X';
                        elseif ($urutanTingkatTarget === 2) echo 'Kelas XI';
                        elseif ($urutanTingkatTarget === 3) echo 'Kelas XII';
                        else echo 'Semua Tingkat';
                      ?>
                    </b>
                    <?php if ($filterByJurusan): ?>
                      | Jurusan ID: <b><?= (int)$jurusanIdTarget ?></b>
                    <?php endif; ?>
                    | Tahun ajaran: <b><?= htmlspecialchars((string)$tahunAjaranAktif, ENT_QUOTES) ?></b>
                  </small>
                </td>
              </tr>
              <!-- =========================
                   END KELAS
                   ========================= -->

              <tr>
                <td class="label">Sekolah Asal</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['sekolah_asal'] ?? ''); ?></span>
                  <input type="text" name="sekolah_asal" class="form-control d-none" value="<?= htmlspecialchars($student['sekolah_asal'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Nomor Ijazah</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nomor_ijazah'] ?? ''); ?></span>
                  <input type="text" name="nomor_ijazah" class="form-control d-none" value="<?= htmlspecialchars($student['nomor_ijazah'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Jenis Kelamin</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['jenis_kelamin'] ?? ''); ?></span>
                  <select id="jenis_kelamin" name="jenis_kelamin" class="form-select d-none">
                    <option value="<?= htmlspecialchars($student['jenis_kelamin'] ?? '', ENT_QUOTES) ?>">
                      <?= htmlspecialchars($student['jenis_kelamin'] ?? '-', ENT_QUOTES) ?>
                    </option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </td>
              </tr>

              <tr>
                <td class="label">Tempat, Tgl Lahir</td>
                <td>:</td>
                <td>
                  <span class="view-text">
                    <?= show($student['tempat_lahir'] ?? ''); ?>,
                    <?= !empty($student['tanggal_lahir']) ? htmlspecialchars(date('d/m/Y', strtotime($student['tanggal_lahir'])), ENT_QUOTES) : '-' ?>
                  </span>
                  <input type="text" name="tempat_lahir" class="form-control d-none" value="<?= htmlspecialchars($student['tempat_lahir'] ?? '', ENT_QUOTES) ?>">
                  <input type="date" name="tanggal_lahir" class="form-control d-none" value="<?= htmlspecialchars($student['tanggal_lahir'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Nomor Kartu Keluarga (KK)</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['no_kk'] ?? ''); ?></span>
                  <input type="text" name="no_kk" class="form-control d-none" value="<?= htmlspecialchars($student['no_kk'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">NIK</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nik'] ?? ''); ?></span>
                  <input type="text" name="nik" class="form-control d-none" value="<?= htmlspecialchars($student['nik'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">No Registrasi Akta Lahir</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['no_registrasi_akta'] ?? ''); ?></span>
                  <input type="text" name="no_registrasi_akta" class="form-control d-none" value="<?= htmlspecialchars($student['no_registrasi_akta'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Agama</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['agama'] ?? ''); ?></span>
                  <select id="agama" name="agama" class="form-select d-none">
                    <?= getOptionsAgama($student['agama'] ?? '') ?>
                  </select>
                </td>
              </tr>

              <tr>
                <td class="label" style="vertical-align:top;">Alamat</td>
                <td style="vertical-align:top;">:</td>
                <td>
                  <div id="alamat_view" class="view-text">
                    <?= show($student['alamat'] ?? ''); ?>,<br>
                    Desa <?= show($student['desa'] ?? ''); ?>,<br>
                    Kecamatan <?= show($student['kecamatan'] ?? ''); ?>,<br>
                    <?= show($student['kota'] ?? ''); ?>
                  </div>

                  <div id="alamat_edit" class="d-none">
                    <label class="d-none">Provinsi:</label>
                    <select id="provinsi" class="form-select d-none"></select>
                    <input type="hidden" name="provinsi" id="provinsi_nama" value="<?= htmlspecialchars($student['provinsi'] ?? '', ENT_QUOTES) ?>">

                    <label class="d-none">Kabupaten/Kota:</label>
                    <select id="kabupaten" class="form-select d-none"></select>
                    <input type="hidden" name="kota" id="kabupaten_nama" value="<?= htmlspecialchars($student['kota'] ?? '', ENT_QUOTES) ?>">

                    <label class="d-none">Kecamatan:</label>
                    <select id="kecamatan" class="form-select d-none"></select>
                    <input type="hidden" name="kecamatan" id="kecamatan_nama" value="<?= htmlspecialchars($student['kecamatan'] ?? '', ENT_QUOTES) ?>">

                    <label class="d-none">Desa:</label>
                    <select id="desa" class="form-select d-none"></select>
                    <input type="hidden" name="desa" id="desa_nama" value="<?= htmlspecialchars($student['desa'] ?? '', ENT_QUOTES) ?>">

                    <label class="d-none" for="alamat_rumah">Alamat Rumah (Jalan/Dusun/RT/RW) *</label>
                    <textarea name="alamat" class="form-control d-none" rows="2"><?= htmlspecialchars($student['alamat'] ?? '', ENT_QUOTES) ?></textarea>
                  </div>
                </td>
              </tr>

              <tr>
                <td class="label">Koordinat Rumah</td>
                <td>:</td>
                <td>
                  <span class="edit-hide"><?= show($student['latitude'] ?? '') . ', ' . show($student['longitude'] ?? '') ?></span>
                  <a href="https://www.google.com/maps?q=<?= htmlspecialchars(($student['latitude'] ?? ''), ENT_QUOTES) ?>,<?= htmlspecialchars(($student['longitude'] ?? ''), ENT_QUOTES) ?>"
                     target="_blank" class="edit-hide">
                    <i class="align-middle" data-feather="map-pin" title="Lihat di Google Maps" style="float:right;"></i>
                  </a>

                  <input type="text" class="form-control d-none" name="koordinat" id="koordinat_rumah"
                         placeholder="Pilih lokasi pada peta dibawah untuk mendapat koordinat otomatis"
                         value="<?= htmlspecialchars(($student['latitude'] ?? '') . ',' . ($student['longitude'] ?? ''), ENT_QUOTES) ?>">
                  <input type="hidden" name="latitude" value="<?= htmlspecialchars($student['latitude'] ?? '', ENT_QUOTES) ?>">
                  <input type="hidden" name="longitude" value="<?= htmlspecialchars($student['longitude'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr class="d-none" id="map_row">
                <td colspan="3" style="border:none;">
                  <div id="map" style="height:300px;"></div>
                </td>
              </tr>

              <tr>
                <td class="label">Tempat Tinggal</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['tempat_tinggal'] ?? ''); ?></span>
                  <select id="tempat_tinggal" name="tempat_tinggal" class="form-select d-none">
                    <?= getOptionsTempatTinggal($student['tempat_tinggal'] ?? '') ?>
                  </select>
                </td>
              </tr>

              <tr>
                <td class="label">Moda Transportasi</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['moda_transportasi'] ?? ''); ?></span>
                  <select id="moda_transportasi" name="moda_transportasi" class="form-select d-none">
                    <?= getOptionsModaTransportasi($student['moda_transportasi'] ?? '') ?>
                  </select>
                </td>
              </tr>

              <tr>
                <td class="label">Anak ke / Saudara Kandung</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['anak_ke'] ?? '') . ' / ' . show($student['jumlah_saudara_kandung'] ?? '') ?></span>
                  <input type="number" name="anak_ke" class="form-control d-none" value="<?= htmlspecialchars($student['anak_ke'] ?? '', ENT_QUOTES) ?>">
                  <input type="number" name="jumlah_saudara_kandung" class="form-control d-none" value="<?= htmlspecialchars($student['jumlah_saudara_kandung'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Tinggi & Berat Badan</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['tinggi_badan'] ?? '') . ' cm / ' . show($student['berat_badan'] ?? '') . ' kg' ?></span>
                  <input type="number" name="tinggi_badan" class="form-control d-none" value="<?= htmlspecialchars($student['tinggi_badan'] ?? '', ENT_QUOTES) ?>">
                  <input type="number" name="berat_badan" class="form-control d-none" value="<?= htmlspecialchars($student['berat_badan'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Hobi / Cita-cita</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['hobi'] ?? '') . ' / ' . show($student['cita_cita'] ?? '') ?></span>
                  <input type="text" name="hobi" class="form-control d-none" value="<?= htmlspecialchars($student['hobi'] ?? '', ENT_QUOTES) ?>">
                  <input type="text" name="cita_cita" class="form-control d-none" value="<?= htmlspecialchars($student['cita_cita'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>


              <tr>
                <td class="label">Nomor HP/WA Siswa</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nohp_siswa'] ?? ''); ?></span>
                  <input type="text" name="nohp_siswa" class="form-control d-none" value="<?= htmlspecialchars($student['nohp_siswa'] ?? '', ENT_QUOTES) ?>">
                </td>
              </tr>

              <tr>
                <td class="label">Nomor HP/WA Orang Tua</td>
                <td>:</td>
                <td>
                  <span class="view-text"><?= show($student['nohp_ortu'] ?? ''); ?></span>
                  <input type="text" name="nohp_ortu" class="form-control d-none" value="<?= htmlspecialchars($student['nohp_ortu'] ?? '', ENT_QUOTES) ?>">
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
            <div id="previewContainerTop" class="mt-3">
              <img id="previewImageTop" src="#" alt="Preview Foto" style="display:none;max-width:200px;border:1px solid #ccc;padding:5px;">
            </div>

            <?php if (!empty($student['foto'])): ?>
              <a href="../uploads/<?= htmlspecialchars($student['foto'], ENT_QUOTES) ?>" data-lightbox="foto-<?= htmlspecialchars($student['nisn'] ?? '', ENT_QUOTES) ?>">
                <img src="../uploads/<?= htmlspecialchars($student['foto'], ENT_QUOTES) ?>" class="img-responsive mt-2" width="130" height="165" style="object-fit:cover;">
              </a>
            <?php else: ?>
              <div class="text-muted">-</div>
            <?php endif; ?>

            <div class="mt-2">
              <label for="foto" class="btn btn-primary">Upload Foto</label>
              <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" style="display:none;" onchange="uploadDanPreviewFoto(this)">
              <div id="previewContainer" class="mt-2">
                <img id="previewImage" src="#" alt="Preview Foto" style="display:none;max-width:200px;border:1px solid #ccc;padding:5px;">
              </div>
            </div>
            <small>Gunakan gambar minimal <br>130px x 165px<br>format .jpg/.png</small>
          </div>
        </div>
      </div>

      <label class="mt-4">
        <input type="checkbox" name="pernyataan_setuju" value="1" <?= !empty($student['pernyataan_setuju']) ? 'checked' : '' ?> disabled>
        <input type="hidden" name="pernyataan_setuju" class="form-control d-none" value="<?= htmlspecialchars($student['pernyataan_setuju'] ?? 0, ENT_QUOTES) ?>">
        Siswa <strong><?= htmlspecialchars($student['nama_lengkap'] ?? '-', ENT_QUOTES) ?></strong> menyatakan data yang saya isi adalah benar dan bersedia mengikuti aturan sekolah.
      </label>
    </form>
  </div>
</div>

<script>
  // Sinkron nilai kelas untuk kompatibilitas edit_proses:
  // - backend mungkin cek 'kelas' atau 'kelas_id'
  (function () {
    const sel = document.getElementById('select_kelas');
    const legacy = document.getElementById('kelas_legacy');
    const hid = document.getElementById('kelas_id_hidden');
    if (!sel) return;

    function sync() {
      const v = sel.value || '';
      if (legacy) legacy.value = v;
      if (hid) hid.value = v;
    }
    sel.addEventListener('change', sync);
    sync();
  })();

  function toggleEdit() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const saveBtnb = document.getElementById('saveBtnb');

    // tampilkan semua input/select/textarea yang d-none di dalam tabel
    document.querySelectorAll('#dataSiswaTable input, #dataSiswaTable select, #dataSiswaTable textarea').forEach(el => {
      el.classList.remove('d-none');
    });

    // sembunyikan teks view
    document.querySelectorAll('#dataSiswaTable .view-text').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.edit-hide').forEach(el => el.classList.add('d-none'));

    // tampilkan edit alamat + map row
    const alamatEdit = document.getElementById('alamat_edit');
    if (alamatEdit) alamatEdit.classList.remove('d-none');

    const mapRow = document.getElementById('map_row');
    if (mapRow) mapRow.classList.remove('d-none');

    const hintKelas = document.getElementById('hint_kelas');
    if (hintKelas) hintKelas.classList.remove('d-none');

    // tombol
    editBtn.classList.add('d-none');
    saveBtn.classList.remove('d-none');
    saveBtnb.classList.remove('d-none');

    // resize map
    setTimeout(() => {
      if (window.map && typeof map.invalidateSize === 'function') {
        map.invalidateSize();
      }
    }, 300);
  }
</script>

<script>
  // Hindari reset form saat pageshow (sering bikin select kembali ke nilai awal).
  document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('pageshow', (e) => {
      // DISABLE reset agar pilihan kelas tidak balik lagi setelah submit/back
    });
  });
</script>

<script>
  // Leaflet map (tetap sama)
  var inputLat = document.querySelector('input[name="latitude"]').value;
  var inputLng = document.querySelector('input[name="longitude"]').value;
  var defaultLatLng = (inputLat && inputLng) ? [parseFloat(inputLat), parseFloat(inputLng)] : [-7.781571, 113.212075];

  var map = L.map('map', {
    center: defaultLatLng,
    zoom: 12,
    fullscreenControl: true,
    fullscreenControlOptions: { position: 'topright' }
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap Affan Contributors'
  }).addTo(map);

  var marker;
  if (inputLat && inputLng) {
    marker = L.marker(defaultLatLng).addTo(map);
    document.getElementById('koordinat_rumah').value = inputLat + ', ' + inputLng;
  }

  document.getElementById('koordinat_rumah')?.addEventListener('change', function() {
    const m = this.value.match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/);
    if (m) setMapCoordinate(m[1], m[2], 16);
  });

  map.on('click', function(e) {
    var lat = e.latlng.lat.toFixed(6),
        lng = e.latlng.lng.toFixed(6);
    document.getElementById('koordinat_rumah').value = lat + ', ' + lng;
    document.querySelector('input[name="latitude"]').value = lat;
    document.querySelector('input[name="longitude"]').value = lng;

    if (marker) marker.setLatLng(e.latlng);
    else marker = L.marker(e.latlng).addTo(map);
  });
</script>

<script>
  // Wilayah Indonesia + geocode sampai Desa/Kelurahan untuk edit siswa.
  // Catatan:
  // - Hidden input selalu diisi nilai lama terlebih dahulu agar tidak hilang saat API gagal match.
  // - Saat halaman edit dibuka, setelah preselect sampai desa, map otomatis diarahkan ke desa/kelurahan.
  // - Saat admin mengganti provinsi/kab/kec/desa, geocode mengikuti pilihan terakhir.
  const selectedProv = "<?= htmlspecialchars(($data['provinsi'] ?? '') ?: ($student['provinsi'] ?? ''), ENT_QUOTES) ?>";
  const selectedKab  = "<?= htmlspecialchars(($data['kota'] ?? '') ?: ($student['kota'] ?? ''), ENT_QUOTES) ?>";
  const selectedKec  = "<?= htmlspecialchars(($data['kecamatan'] ?? '') ?: ($student['kecamatan'] ?? ''), ENT_QUOTES) ?>";
  const selectedDesa = "<?= htmlspecialchars(($data['desa'] ?? '') ?: ($student['desa'] ?? ''), ENT_QUOTES) ?>";

  const $prov = document.getElementById('provinsi');
  const $kab  = document.getElementById('kabupaten');
  const $kec  = document.getElementById('kecamatan');
  const $desa = document.getElementById('desa');

  let wilayahInitializing = true;

  function setHidden(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val || '';
  }

  // Pastikan nilai lama sudah ada sejak awal. Ini mencegah data lama tertimpa kosong saat API gagal.
  setHidden('provinsi_nama', selectedProv);
  setHidden('kabupaten_nama', selectedKab);
  setHidden('kecamatan_nama', selectedKec);
  setHidden('desa_nama', selectedDesa);

  function normalizeWilayahName(name) {
    return String(name || '')
      .toUpperCase()
      .replace(/\b(PROVINSI|PROPINSI|KABUPATEN|KAB\.|KOTA|KOTAMADYA|KECAMATAN|KEC\.|DESA|KELURAHAN|KEL\.)\b/gi, '')
      .replace(/[^A-Z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function findWilayah(items, selectedName) {
    const target = normalizeWilayahName(selectedName);
    if (!target) return null;

    return items.find(x => normalizeWilayahName(x.name) === target)
        || items.find(x => normalizeWilayahName(x.name).replace(/\s+/g, '') === target.replace(/\s+/g, ''))
        || items.find(x => normalizeWilayahName(x.name).includes(target))
        || items.find(x => target.includes(normalizeWilayahName(x.name)));
  }

  function ensureOldOption(select, selectedName, placeholder) {
    if (!select || !selectedName) return;
    const exists = Array.from(select.options).some(opt => normalizeWilayahName(opt.text) === normalizeWilayahName(selectedName));
    if (!exists) {
      const opt = document.createElement('option');
      opt.value = '__old__';
      opt.textContent = selectedName;
      opt.selected = true;
      select.appendChild(opt);
    }
  }

  function setMapCoordinate(lat, lng, zoom = 14) {
    if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;
    const latLng = [parseFloat(lat), parseFloat(lng)];
    const koorInput = document.getElementById('koordinat_rumah');
    if (koorInput) koorInput.value = latLng[0].toFixed(6) + ', ' + latLng[1].toFixed(6);
    const latInput = document.querySelector('input[name="latitude"]');
    const lngInput = document.querySelector('input[name="longitude"]');
    if (latInput) latInput.value = latLng[0].toFixed(6);
    if (lngInput) lngInput.value = latLng[1].toFixed(6);

    if (window.map) {
      map.setView(latLng, zoom);
      if (marker) marker.setLatLng(latLng);
      else marker = L.marker(latLng).addTo(map);
      setTimeout(() => map.invalidateSize(), 200);
    }
  }

  function selectedText(select) {
    if (!select) return '';
    return select.options[select.selectedIndex]?.text || '';
  }

  async function fetchNominatim(query) {
    const url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&countrycodes=id&q=' + encodeURIComponent(query);
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    return Array.isArray(data) && data.length ? data[0] : null;
  }

  async function geocodeWilayahByLevel(level = 'desa') {
    const desa = normalizeWilayahName(document.getElementById('desa_nama')?.value || selectedText($desa) || selectedDesa);
    const kec = normalizeWilayahName(document.getElementById('kecamatan_nama')?.value || selectedText($kec) || selectedKec);
    const kab = normalizeWilayahName(document.getElementById('kabupaten_nama')?.value || selectedText($kab) || selectedKab);
    const prov = normalizeWilayahName(document.getElementById('provinsi_nama')?.value || selectedText($prov) || selectedProv);

    if (!prov) return;

    const queries = [];
    if (level === 'desa' && desa && kec && kab && prov) queries.push([desa, kec, kab, prov, 'Indonesia'].join(', '));
    if (level === 'desa' && desa && kab && prov) queries.push([desa, kab, prov, 'Indonesia'].join(', '));
    if (level === 'desa' && desa && kec && prov) queries.push([desa, kec, prov, 'Indonesia'].join(', '));
    if ((level === 'desa' || level === 'kecamatan') && kec && kab && prov) queries.push([kec, kab, prov, 'Indonesia'].join(', '));
    if ((level === 'desa' || level === 'kecamatan') && kec && prov) queries.push([kec, prov, 'Indonesia'].join(', '));
    if ((level === 'desa' || level === 'kecamatan' || level === 'kota') && kab && prov) queries.push([kab, prov, 'Indonesia'].join(', '));
    queries.push([prov, 'Indonesia'].join(', '));

    const unique = [...new Set(queries.filter(Boolean))];
    let zoom = 8;
    if (level === 'kota') zoom = 11;
    if (level === 'kecamatan') zoom = 13;
    if (level === 'desa') zoom = 16;

    for (const q of unique) {
      try {
        const result = await fetchNominatim(q);
        if (result && result.lat && result.lon) {
          setMapCoordinate(result.lat, result.lon, zoom);
          return true;
        }
      } catch (e) {
        console.warn('Geocode gagal:', q, e);
      }
    }

    console.warn('Koordinat wilayah tidak ditemukan:', { desa, kec, kab, prov, level, unique });
    return false;
  }

  function fillSelect(select, items, placeholder) {
    if (!select) return;
    select.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = placeholder;
    select.appendChild(opt0);

    items.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.id;
      opt.textContent = it.name;
      select.appendChild(opt);
    });
  }

  async function loadJSON(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error('Gagal load ' + url);
    return res.json();
  }

  async function initWilayah() {
    try {
      const provs = await loadJSON('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
      fillSelect($prov, provs, '-- Pilih Provinsi --');

      if (selectedProv) {
        const foundProv = findWilayah(provs, selectedProv);
        if (foundProv) {
          $prov.value = foundProv.id;
          setHidden('provinsi_nama', foundProv.name);
          await loadKab(foundProv.id, true);
        } else {
          ensureOldOption($prov, selectedProv);
          setHidden('provinsi_nama', selectedProv);
        }
      }

      // Setelah chain preselect selesai, arahkan map sampai level paling detail yang tersedia.
      if (selectedDesa) await geocodeWilayahByLevel('desa');
      else if (selectedKec) await geocodeWilayahByLevel('kecamatan');
      else if (selectedKab) await geocodeWilayahByLevel('kota');
      else if (selectedProv) await geocodeWilayahByLevel('provinsi');
    } catch (e) {
      console.error('Gagal load provinsi/wilayah:', e);
      await geocodeWilayahByLevel(selectedDesa ? 'desa' : (selectedKec ? 'kecamatan' : (selectedKab ? 'kota' : 'provinsi')));
    } finally {
      wilayahInitializing = false;
    }
  }

  async function loadKab(provId, preselect = false) {
    const kab = await loadJSON(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${provId}.json`);
    fillSelect($kab, kab, '-- Pilih Kabupaten --');
    fillSelect($kec, [], '-- Pilih Kecamatan --');
    fillSelect($desa, [], '-- Pilih Desa --');

    if (!preselect) {
      setHidden('kabupaten_nama', '');
      setHidden('kecamatan_nama', '');
      setHidden('desa_nama', '');
      return;
    }

    setHidden('kabupaten_nama', selectedKab);
    setHidden('kecamatan_nama', selectedKec);
    setHidden('desa_nama', selectedDesa);

    if (selectedKab) {
      const found = findWilayah(kab, selectedKab);
      if (found) {
        $kab.value = found.id;
        setHidden('kabupaten_nama', found.name);
        await loadKec(found.id, true);
      } else {
        ensureOldOption($kab, selectedKab);
      }
    }
  }

  async function loadKec(kabId, preselect = false) {
    const kec = await loadJSON(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${kabId}.json`);
    fillSelect($kec, kec, '-- Pilih Kecamatan --');
    fillSelect($desa, [], '-- Pilih Desa --');

    if (!preselect) {
      setHidden('kecamatan_nama', '');
      setHidden('desa_nama', '');
      return;
    }

    setHidden('kecamatan_nama', selectedKec);
    setHidden('desa_nama', selectedDesa);

    if (selectedKec) {
      const found = findWilayah(kec, selectedKec);
      if (found) {
        $kec.value = found.id;
        setHidden('kecamatan_nama', found.name);
        await loadDesa(found.id, true);
      } else {
        ensureOldOption($kec, selectedKec);
      }
    }
  }

  async function loadDesa(kecId, preselect = false) {
    const desa = await loadJSON(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${kecId}.json`);
    fillSelect($desa, desa, '-- Pilih Desa --');

    if (!preselect) {
      setHidden('desa_nama', '');
      return;
    }

    setHidden('desa_nama', selectedDesa);
    if (selectedDesa) {
      const found = findWilayah(desa, selectedDesa);
      if (found) {
        $desa.value = found.id;
        setHidden('desa_nama', found.name);
      } else {
        ensureOldOption($desa, selectedDesa);
      }
    }
  }

  $prov?.addEventListener('change', async function() {
    const provId = this.value;
    const provName = selectedText(this);
    setHidden('provinsi_nama', provName);
    setHidden('kabupaten_nama', '');
    setHidden('kecamatan_nama', '');
    setHidden('desa_nama', '');
    if (provId && provId !== '__old__') await loadKab(provId, false);
    await geocodeWilayahByLevel('provinsi');
  });

  $kab?.addEventListener('change', async function() {
    const kabId = this.value;
    const kabName = selectedText(this);
    setHidden('kabupaten_nama', kabName);
    setHidden('kecamatan_nama', '');
    setHidden('desa_nama', '');
    if (kabId && kabId !== '__old__') await loadKec(kabId, false);
    await geocodeWilayahByLevel('kota');
  });

  $kec?.addEventListener('change', async function() {
    const kecId = this.value;
    const kecName = selectedText(this);
    setHidden('kecamatan_nama', kecName);
    setHidden('desa_nama', '');
    if (kecId && kecId !== '__old__') await loadDesa(kecId, false);
    await geocodeWilayahByLevel('kecamatan');
  });

  $desa?.addEventListener('change', async function() {
    const desaName = selectedText(this);
    setHidden('desa_nama', desaName);
    await geocodeWilayahByLevel('desa');
  });

  initWilayah();
</script>

<script>
  function uploadDanPreviewFoto(input) {
    const file = input.files[0];
    if (!file) return;

    // Preview
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('previewImage');
      if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
      const previewTop = document.getElementById('previewImageTop');
      if (previewTop) { previewTop.src = e.target.result; previewTop.style.display = 'block'; }
    };
    reader.readAsDataURL(file);

    const formData = new FormData();
    formData.append('foto', file);
    formData.append('id_siswa', document.querySelector('[name="id_siswa"]').value);
    formData.append('tahun_ajaran', document.querySelector('[name="tahun_ajaran"]').value);
    formData.append('nisn', document.querySelector('[name="nisn"]').value);
    formData.append('csrf', <?= json_encode(sds_csrf_token()) ?>);

    fetch('pages/upload_foto_ajax.php', { method: 'POST', body: formData })
      .then(async (res) => {
        const text = await res.text();
        console.log('HTTP', res.status, text);

        if (!res.ok) throw new Error(`HTTP ${res.status}: ${text}`);

        // anggap sukses kalau response mengandung kata "berhasil"
        if (!/berhasil/i.test(text)) {
          throw new Error(text || 'Upload gagal (respon server tidak jelas)');
        }

        window.sdsNotify('Foto berhasil diunggah!', 'success');
        setTimeout(() => location.reload(), 200);
      })
      .catch(err => {
        window.sdsNotify('Upload gagal: ' + err.message, 'danger');
        console.error(err);
      });
  }
</script>
