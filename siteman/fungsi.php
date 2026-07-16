<?php
function base_url($path = '')
{
    return '/pendataan_siswa/siteman/' . ltrim($path, '/');
}

function getOptionsAgama($selected = '')
{
    $agamas = [
        '' => '-- Pilih Agama --',
        'Islam',
        'Kristen',
        'Katolik',
        'Hindu',
        'Buddha',
        'Kong Hu Cu',
        'Lainnya'
    ];
    $html = '';

    foreach ($agamas as $agama) {
        $sel = ($agama == $selected) ? 'selected' : '';
        $html .= '<option value="' . $agama . '" ' . $sel . '>' . htmlspecialchars($agama) . '</option>';
    }

    return $html;
}
function getOptionsTempatTinggal($selected = '')
{
    $tempat_tinggal = [
        '' => '-- Pilih Tempat Tinggal --',
        'Bersama orang tua',
        'Kost',
        'Wali',
        'Asrama',
        'Panti Asuhan',
        'Pesantren'
    ];
    $html = '';
    foreach ($tempat_tinggal as $tt) {
        $sel = ($tt == $selected) ? 'selected' : '';
        $html .= '<option value="' . $tt . '" ' . $sel . '>' . htmlspecialchars($tt) . '</option>';
    }
    return $html;
}
function getOptionsKebutuhanKhusus($selected = '')
{
    $kebutuhan_khusus = ['Tidak', 'Netra', 'Rungu', 'Grahita Ringan', 'Grahita Sedang', 'Daksa Ringan', 'Daksa Sedang', 'Lainnya'];
    $html = '';
    foreach ($kebutuhan_khusus as $kk) {
        $sel = ($kk == $selected) ? 'selected' : '';
        $html .= '<option value="' . $kk . '" ' . $sel . '>' . htmlspecialchars($kk) . '</option>';
    }
    return $html;
}
function getOptionsPendidikan($selected = '')
{
    $pendidikan = [
        '' => '-- Pilih Pendidikan --',
        'PAUD',
        'TK/Sederajat',
        'SD/Sederajat',
        'SMP/Sederajat',
        'SMA/Sederajat',
        'D1',
        'D2',
        'D3',
        'S1',
        'S2',
        'S3',
        'Profesi',
        'Putus SD',
        'Putus SMP',
        'Putus SMA',
        'Paket A',
        'Paket B',
        'Paket C',
        'SP1',
        'SP2',
        'Formal',
        'Informal',
        'Lainnya',
        'Tidak Sekolah'
    ];
    $html = '';
    foreach ($pendidikan as $pd) {
        $sel = ($pd == $selected) ? 'selected' : '';
        $html .= '<option value="' . $pd . '" ' . $sel . '>' . htmlspecialchars($pd) . '</option>';
    }
    return $html;
}
function getOptionsPekerjaan($selected = '')
{
    $pekerjaan = [
        '' => '-- Pilih Pekerjaan --',
        'Nelayan',
        'Petani',
        'Peternak',
        'PNS/TNI/POLRI',
        'Karyawan Swasta',
        'Wirausaha',
        'Wiraswata',
        'Pedagang Besar',
        'Pedagang Kecil',
        'Buruh',
        'Pensiunan',
        'Tenaga Kerja Indonesia',
        'Karyawan BUMN',
        'Lainnya',
        'Tidak bekerja',
        'Sudah Meninggal'
    ];

    $html = '';
    foreach ($pekerjaan as $pk) {
        $sel = ($pk == $selected) ? 'selected' : '';
        $html .= '<option value="' . $pk . '" ' . $sel . '>' . htmlspecialchars($pk) . '</option>';
    }
    return $html;
}
function getOptionsPenghasilan($selected = '')
{
    $penghasilan = [
        '' => '-- Pilih Penghasilan --',
        'Kurang dari 500.000',
        '500.000 - 1.000.000',
        '1.000.000 - 2.000.000',
        '2.000.000 - 3.000.000',
        '3.000.000 - 5.000.000',
        '5.000.000 - 10.000.000',
        '10.000.000 - 20.000.000',
        'Lebih dari 20.000.000'
    ];

    $html = '';
    foreach ($penghasilan as $ph) {
        $sel = ($ph == $selected) ? 'selected' : '';
        $html .= '<option value="' . $ph . '" ' . $sel . '>' . htmlspecialchars($ph) . '</option>';
    }
    return $html;
}
function getOptionsModaTransportasi($selected = '')
{
    $moda_transportasi = [
        '' => '-- Pilih Moda Transportasi --',
        'Jalan Kaki',
        'Angkutan Umum Bus/Angkot',
        'Mobil/Bus Antar Jemput',
        'Ojek',
        'Sepeda',
        'Sepeda Motor',
        'Mobil Pribadi',
        'Perahu',
        'Kuda'
    ];
    $html = '';
    foreach ($moda_transportasi as $mt) {
        $sel = ($mt == $selected) ? 'selected' : '';
        $html .= '<option value="' . $mt . '" ' . $sel . '>' . htmlspecialchars($mt) . '</option>';
    }
    return $html;
}
function getOptionsHobi($selected = '')
{
    $hobi = ['Olahraga', 'Seni', 'Musik', 'Membaca', 'Menulis', 'Menggambar', 'Berenang', 'Bersepeda', 'Berkebun', 'Lainnya'];
    $html = '';
    foreach ($hobi as $hb) {
        $sel = ($hb == $selected) ? 'selected' : '';
        $html .= '<option value="' . $hb . '" ' . $sel . '>' . htmlspecialchars($hb) . '</option>';
    }
    return $html;
}
function getOptionsCitaCita($selected = '')
{
    $cita_cita = ['Dokter', 'Guru', 'Insinyur', 'Pengacara', 'Polisi', 'Tentara', 'Wiraswasta', 'PNS', 'Lainnya'];
    $html = '';
    foreach ($cita_cita as $cc) {
        $sel = ($cc == $selected) ? 'selected' : '';
        $html .= '<option value="' . $cc . '" ' . $sel . '>' . htmlspecialchars($cc) . '</option>';
    }
    return $html;
}

function getDataPengisianKelas(mysqli $conn, string $tahunAjaran): array
{
    /**
     * TERISI dihitung realtime dari siswa_kelas,
     * mengikuti aturan halaman "Lihat Siswa":
     * - sk.tahun_ajaran = tahunAjaran (sama dengan kelas.tahun_ajaran)
     * - sk.naik_kelas = 1
     * - ps.status_aktif = 1
     *
     * COUNT(DISTINCT ps.id) agar siswa nonaktif tidak ikut terhitung.
     */
    $sql = "
        SELECT 
            k.id AS kelas_id,
            k.nama_kelas,
            k.kuota,
            k.wali_kelas,
            k.tahun_ajaran,
            tk.nama_tingkat,
            COUNT(DISTINCT ps.id) AS terisi_real
        FROM kelas k
        LEFT JOIN tingkat_kelas tk 
            ON k.tingkat_id = tk.id
        LEFT JOIN siswa_kelas sk
            ON sk.kelas_id = k.id
           AND sk.tahun_ajaran = k.tahun_ajaran
           AND sk.naik_kelas = 1
        LEFT JOIN pendaftaran_siswa ps
            ON ps.id = sk.siswa_id
           AND ps.status_aktif = 1
        WHERE k.tahun_ajaran = ?
        GROUP BY 
            k.id, k.nama_kelas, k.kuota, k.wali_kelas, k.tahun_ajaran, tk.nama_tingkat
        ORDER BY 
            tk.nama_tingkat ASC, k.nama_kelas
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // jaga-jaga: tetap kembalikan struktur yang sama
        return ['jumlah' => [], 'kuota' => []];
    }

    $stmt->bind_param('s', $tahunAjaran);
    $stmt->execute();
    $result = $stmt->get_result();

    $dataKelas = [];

    while ($row = $result->fetch_assoc()) {
        $kuota  = (int)($row['kuota'] ?? 0);
        $terisi = (int)($row['terisi_real'] ?? 0);

        $dataKelas[] = [
            'id'          => (int)$row['kelas_id'],
            'nama_kelas'  => (string)$row['nama_kelas'],
            'wali_kelas'  => (string)($row['wali_kelas'] ?? ''),
            'kuota'       => $kuota,
            'terisi'      => $terisi,
            'tersisa'     => max(0, $kuota - $terisi),
            'penuh'       => $terisi >= $kuota,
            'tahun_ajaran'=> (string)$row['tahun_ajaran'],
            'nama_tingkat'=> $row['nama_tingkat'] ?? '-'
        ];
    }

    // Ambil data kuota untuk referensi tambahan (tetap seperti sebelumnya)
    $kuotaData = [];
    $kuotaSql = "SELECT nama_kelas, kuota FROM kelas WHERE tahun_ajaran = ?";
    $kuotaStmt = $conn->prepare($kuotaSql);
    if ($kuotaStmt) {
        $kuotaStmt->bind_param('s', $tahunAjaran);
        $kuotaStmt->execute();
        $kuotaResult = $kuotaStmt->get_result();

        while ($row = $kuotaResult->fetch_assoc()) {
            $kuotaData[(string)$row['nama_kelas']] = (int)$row['kuota'];
        }
    }

    return [
        'jumlah' => $dataKelas,
        'kuota'  => $kuotaData
    ];
}


// function getDataPengisianKelas(mysqli $conn, string $tahunAjaran): array
// {
//     $sql = "
//     SELECT 
//         k.id AS kelas_id,
//         k.nama_kelas,
//         k.kuota,
//         k.wali_kelas,
//         k.tahun_ajaran,
//         tk.nama_tingkat,
//         COUNT(DISTINCT siswa_all.siswa_id) AS jumlah_terisi
//     FROM kelas k
//     LEFT JOIN tingkat_kelas tk ON k.tingkat_id = tk.id
//     LEFT JOIN (
//         SELECT id AS siswa_id, kelas_id, tahun_ajaran FROM pendaftaran_siswa
//         UNION ALL
//         SELECT siswa_id, kelas_id, tahun_ajaran FROM siswa_kelas
//     ) AS siswa_all 
//         ON siswa_all.kelas_id = k.id 
//         AND siswa_all.tahun_ajaran = k.tahun_ajaran
//     WHERE k.tahun_ajaran = ?
//     GROUP BY k.id
//     ORDER BY tk.nama_tingkat ASC, k.nama_kelas
//     ";

//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param('s', $tahunAjaran);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     $dataKelas = [];

//     while ($row = $result->fetch_assoc()) {
//         $dataKelas[] = [
//             'id' => $row['kelas_id'],
//             'nama_kelas' => $row['nama_kelas'],
//             'wali_kelas' => $row['wali_kelas'],
//             'kuota' => (int)$row['kuota'],
//             'terisi' => (int)$row['jumlah_terisi'],
//             'tersisa' => max(0, $row['kuota'] - $row['jumlah_terisi']),
//             'penuh' => $row['jumlah_terisi'] >= $row['kuota'],
//             'tahun_ajaran' => $row['tahun_ajaran'],
//             'nama_tingkat' => $row['nama_tingkat'] ?? '-'
//         ];
//     }

//     // kuotaData tetap seperti sebelumnya
//     $kuotaData = [];
//     $kuotaSql = "SELECT nama_kelas, kuota FROM kelas WHERE tahun_ajaran = ?";
//     $kuotaStmt = $conn->prepare($kuotaSql);
//     $kuotaStmt->bind_param('s', $tahunAjaran);
//     $kuotaStmt->execute();
//     $kuotaResult = $kuotaStmt->get_result();
//     while ($row = $kuotaResult->fetch_assoc()) {
//         $kuotaData[$row['nama_kelas']] = $row['kuota'];
//     }

//     return [
//         'jumlah' => $dataKelas,
//         'kuota' => $kuotaData
//     ];
// }




function catatLog($conn, $admin_id, $aksi, $keterangan = '')
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (admin_id, aksi, keterangan, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $admin_id, $aksi, $keterangan, $ip, $agent);
    $stmt->execute();
}

function generateNIPD($conn, $jurusan_id, $kode_jurusan)
{
    // Ambil total siswa untuk menentukan angka global (7001, 7002, ...)
    $result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran_siswa WHERE jurusan_id = $jurusan_id");
    $total_siswa = mysqli_fetch_assoc($result_total)['total'];
    $kode_global = 7000 + ($total_siswa + 1); // 7001 untuk siswa pertama, 7002 untuk kedua, dst.

    // Ambil jumlah siswa per jurusan
    $jumlah_jurusan = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran_siswa WHERE jurusan_id = ?");
    $stmt->bind_param("i", $jurusan_id);
    $stmt->execute();
    $stmt->bind_result($jumlah_jurusan);
    $stmt->fetch();
    $stmt->close();

    // Hitung nomor urut dalam jurusan (001, 002, dst.)
    $nomor_urut = str_pad($jumlah_jurusan + 1, 3, '0', STR_PAD_LEFT);

    // Gabungkan menjadi NIPD akhir
    return "$kode_global/$nomor_urut.$kode_jurusan";
}

// function generateNIPD($conn, $jurusan_id, $kode_jurusan)
// {
//     // Ambil jumlah siswa di jurusan tersebut yang sudah memiliki NIPD
//     $stmt = $conn->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran_siswa WHERE jurusan_id = ? AND nipd IS NOT NULL");
//     $stmt->bind_param("i", $jurusan_id);
//     $stmt->execute();
//     $jumlah_jurusan = 0;
//     $stmt->bind_result($jumlah_jurusan);
//     $stmt->fetch();
//     $stmt->close();

//     // Kode global berdasarkan urutan siswa per jurusan (dimulai dari 7001)
//     $kode_global = 7000 + ($jumlah_jurusan + 1); // 7001 untuk siswa pertama di jurusan ini

//     // Hitung nomor urut dalam jurusan (001, 002, dst.)
//     $nomor_urut = str_pad($jumlah_jurusan + 1, 3, '0', STR_PAD_LEFT);

//     // Gabungkan menjadi NIPD akhir
//     return "$kode_global/$nomor_urut.$kode_jurusan";
// }

// function generateNIPDByJurusan($conn, $jurusan_id, $kode_jurusan) {
//     // Ambil semua siswa dalam jurusan tersebut yang belum memiliki NIPD, urutkan berdasarkan nama_lengkap
//     $query = "SELECT id, nama_lengkap FROM pendaftaran_siswa 
//               WHERE jurusan_id = ? AND nipd IS NULL 
//               ORDER BY nama_lengkap ASC";
//     $stmt = $conn->prepare($query);
//     $stmt->bind_param("i", $jurusan_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     $urutan = 1; // mulai dari 7001
//     while ($row = $result->fetch_assoc()) {
//         $kode_global = 7000 + $urutan;
//         $nomor_urut = str_pad($urutan, 3, '0', STR_PAD_LEFT);
//         $nipd = "$kode_global/$nomor_urut.$kode_jurusan";

//         // Update NIPD ke database
//         $update = $conn->prepare("UPDATE pendaftaran_siswa SET nipd = ? WHERE id = ?");
//         $update->bind_param("si", $nipd, $row['id']);
//         $update->execute();
//         $update->close();

//         $urutan++;
//     }

//     $stmt->close();

// }

function generateNIPDByJurusan($conn, $jurusan_id, $kode_jurusan, $tahun_ajaran)
{
    // Ambil pengaturan NIPD untuk jurusan ini dan tahun ajaran tersebut
    $pengaturan_stmt = $conn->prepare("
        SELECT kode_depan, urutan_awal 
        FROM pengaturan_nipd 
        WHERE jurusan_id = ? AND tahun_ajaran = ?
    ");
    $pengaturan_stmt->bind_param("is", $jurusan_id, $tahun_ajaran);
    $pengaturan_stmt->execute();
    $pengaturan_result = $pengaturan_stmt->get_result();

    if ($pengaturan_result->num_rows === 0) {
        // Tidak ada pengaturan ditemukan
        return;
    }

    $pengaturan = $pengaturan_result->fetch_assoc();
    $kode_depan = (int)$pengaturan['kode_depan'];
    $urutan_awal = (int)$pengaturan['urutan_awal'];
    $urutan = $urutan_awal;
    $pengaturan_stmt->close();

    // Ambil siswa tanpa NIPD
    $stmt = $conn->prepare("
        SELECT ps.id, ps.nama_lengkap 
        FROM pendaftaran_siswa ps
        JOIN kelas k ON ps.kelas_id = k.id
        WHERE ps.nipd IS NULL 
        AND k.jurusan_id = ? 
        AND k.tahun_ajaran = ?
        ORDER BY ps.nama_lengkap ASC
    ");
    $stmt->bind_param("is", $jurusan_id, $tahun_ajaran);
    $stmt->execute();
    $result = $stmt->get_result();

    $jumlah_siswa = 0;
    while ($row = $result->fetch_assoc()) {
        $kode_global = $kode_depan + ($urutan - $urutan_awal);
        $nomor_urut = str_pad($urutan, 3, '0', STR_PAD_LEFT);
        $nipd = "$kode_global/$nomor_urut.$kode_jurusan";

        // Update ke database
        $update = $conn->prepare("UPDATE pendaftaran_siswa SET nipd = ? WHERE id = ?");
        $update->bind_param("si", $nipd, $row['id']);
        $update->execute();
        $update->close();

        $urutan++;
        $jumlah_siswa++;
    }

    $stmt->close();

    // Jika ada siswa yang di-generate, simpan kode_akhir dan urutan_akhir
    if ($jumlah_siswa > 0) {
        $urutan_akhir = $urutan - 1;
        $kode_akhir = $kode_depan + ($urutan_akhir - $urutan_awal);

        $update_pengaturan = $conn->prepare("
            UPDATE pengaturan_nipd 
            SET kode_akhir = ?, urutan_akhir = ? 
            WHERE jurusan_id = ? AND tahun_ajaran = ?
        ");
        $update_pengaturan->bind_param("iiis", $kode_akhir, $urutan_akhir, $jurusan_id, $tahun_ajaran);
        $update_pengaturan->execute();
        $update_pengaturan->close();
    }

    return true;
}






/**
 * Generate NIS/NIPD untuk siswa aktif kelas X pada tahun ajaran aktif.
 *
 * Format benar: nomor_global/nomor_tengah.kode_jurusan_spektrum
 * Contoh: 16721/1303.8.2.1
 *
 * Catatan penting:
 * - 16721 adalah nomor urut global siswa sekolah dan tidak reset per jurusan.
 * - 1303 adalah nomor/kode tengah yang diatur per jurusan pada tabel pengaturan_nipd.urutan_awal.
 * - 8.2.1 adalah kode_jurusan spektrum dari tabel jurusan.
 * - Generate hanya untuk siswa aktif kelas X tahun ajaran aktif dan NIS/NIPD kosong.
 * - Urutan jurusan mengikuti kode spektrum bertitik, misalnya 4.1.1 -> 8.1.1 -> 8.2.1 -> 8.3.1 -> 8.3.3.
 * - Di dalam jurusan, siswa diurutkan nama A-Z, bukan berdasarkan kelas.
 * - Fungsi ini tidak mengubah pembagian kelas dan tidak mengubah nomor absen.
 * - Fungsi ini tidak mengubah pengaturan_nipd.urutan_awal / Nomor Tengah Awal Jurusan.
 * - Yang disimpan setelah generate hanya kode_akhir dan urutan_akhir sebagai riwayat terakhir.
 */
function generateNIPDKelasXAktif(mysqli $conn, string $tahunAjaran, int $nomorAwal): array
{
    $tahunAjaran = trim($tahunAjaran);

    if ($tahunAjaran === '') {
        return ['ok' => false, 'jumlah' => 0, 'message' => 'Tahun ajaran aktif tidak valid.'];
    }

    if ($nomorAwal <= 0) {
        return ['ok' => false, 'jumlah' => 0, 'message' => 'Nomor awal global NIS/NIPD tidak valid.'];
    }

    /**
     * Validasi setiap jurusan kelas X yang punya siswa aktif dan NIS kosong.
     * kode_jurusan sekarang boleh mengikuti kode Spektrum SMK, misalnya 4.1.1 atau 8.2.1.
     */
    $cekPengaturan = $conn->prepare("
        SELECT DISTINCT j.nama_jurusan, j.kode_jurusan
        FROM pendaftaran_siswa ps
        JOIN siswa_kelas sk
            ON sk.siswa_id = ps.id
            AND BINARY sk.tahun_ajaran = BINARY ps.tahun_ajaran
        JOIN kelas k
            ON k.id = sk.kelas_id
            AND BINARY k.tahun_ajaran = BINARY ps.tahun_ajaran
        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
        JOIN jurusan j
            ON j.id = ps.jurusan_id
            AND BINARY j.tahun_ajaran = BINARY ps.tahun_ajaran
        LEFT JOIN pengaturan_nipd p
            ON p.jurusan_id = j.id
            AND BINARY p.tahun_ajaran = BINARY j.tahun_ajaran
        WHERE BINARY ps.tahun_ajaran = BINARY ?
          AND tk.urutan_tingkat = (SELECT MIN(tk_awal.urutan_tingkat) FROM tingkat_kelas tk_awal)
          AND ps.status_aktif = 1
          AND (ps.nipd IS NULL OR ps.nipd = '')
          AND (
              j.kode_jurusan IS NULL
              OR TRIM(j.kode_jurusan) = ''
              OR TRIM(j.kode_jurusan) NOT REGEXP '^[0-9]+([.][0-9]+)*$'
              OR p.urutan_awal IS NULL OR p.urutan_awal <= 0
          )
        ORDER BY
            CAST(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 1) AS UNSIGNED) ASC,
            CASE
                WHEN TRIM(j.kode_jurusan) REGEXP '^[0-9]+[.][0-9]+'
                THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 2), '.', -1) AS UNSIGNED)
                ELSE 0
            END ASC,
            CASE
                WHEN TRIM(j.kode_jurusan) REGEXP '^[0-9]+[.][0-9]+[.][0-9]+'
                THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 3), '.', -1) AS UNSIGNED)
                ELSE 0
            END ASC,
            TRIM(j.kode_jurusan) ASC,
            j.nama_jurusan ASC
    ");
    if (!$cekPengaturan) {
        return ['ok' => false, 'jumlah' => 0, 'message' => 'Query validasi pengaturan NIS/NIPD gagal: ' . $conn->error];
    }
    $cekPengaturan->bind_param('s', $tahunAjaran);
    $cekPengaturan->execute();
    $pengaturanResult = $cekPengaturan->get_result();
    $belumDiset = [];
    while ($row = $pengaturanResult->fetch_assoc()) {
        $belumDiset[] = $row['nama_jurusan'] . ' (kode jurusan/spektrum: ' . $row['kode_jurusan'] . ')';
    }
    $cekPengaturan->close();

    if (!empty($belumDiset)) {
        return [
            'ok' => false,
            'jumlah' => 0,
            'message' => 'Generate dibatalkan. Kode jurusan/spektrum atau nomor tengah awal NIS/NIPD belum valid untuk: ' . implode(', ', $belumDiset) . '.'
        ];
    }

    // Ambil NIS/NIPD yang sudah dipakai agar tidak terjadi duplikasi.
    $used = [];
    $usedStmt = $conn->prepare("
        SELECT nipd
        FROM pendaftaran_siswa
        WHERE nipd IS NOT NULL AND nipd <> ''
    ");
    if ($usedStmt && $usedStmt->execute()) {
        $usedResult = $usedStmt->get_result();
        while ($row = $usedResult->fetch_assoc()) {
            $used[(string)$row['nipd']] = true;
        }
        $usedStmt->close();
    }

    /**
     * Ambil siswa aktif kelas X yang NIS/NIPD-nya masih kosong.
     * Urutan final sesuai alur sekolah:
     * 1) kelompok jurusan berdasarkan kode spektrum terkecil,
     * 2) nama siswa A-Z di dalam jurusan,
     * 3) ID siswa sebagai pengunci urutan jika ada nama sama.
     *
     * PENTING:
     * - Urutan NIS tidak lagi memakai nama kelas/rombel.
     * - Generate NIS tidak mengubah pembagian kelas.
     * - Generate NIS tidak mengubah nomor absen.
     */
    $stmt = $conn->prepare("
        SELECT DISTINCT
            ps.id,
            ps.nama_lengkap,
            j.id AS jurusan_id,
            j.kode_jurusan,
            j.nama_jurusan,
            p.urutan_awal AS nomor_tengah_awal
        FROM pendaftaran_siswa ps
        JOIN siswa_kelas sk
            ON sk.siswa_id = ps.id
            AND BINARY sk.tahun_ajaran = BINARY ps.tahun_ajaran
        JOIN kelas k
            ON k.id = sk.kelas_id
            AND BINARY k.tahun_ajaran = BINARY ps.tahun_ajaran
        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
        JOIN jurusan j
            ON j.id = ps.jurusan_id
            AND BINARY j.tahun_ajaran = BINARY ps.tahun_ajaran
        JOIN pengaturan_nipd p
            ON p.jurusan_id = j.id
            AND BINARY p.tahun_ajaran = BINARY j.tahun_ajaran
        WHERE BINARY ps.tahun_ajaran = BINARY ?
          AND tk.urutan_tingkat = (SELECT MIN(tk_awal.urutan_tingkat) FROM tingkat_kelas tk_awal)
          AND ps.status_aktif = 1
          AND (ps.nipd IS NULL OR ps.nipd = '')
        ORDER BY
            CAST(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 1) AS UNSIGNED) ASC,
            CASE
                WHEN TRIM(j.kode_jurusan) REGEXP '^[0-9]+[.][0-9]+'
                THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 2), '.', -1) AS UNSIGNED)
                ELSE 0
            END ASC,
            CASE
                WHEN TRIM(j.kode_jurusan) REGEXP '^[0-9]+[.][0-9]+[.][0-9]+'
                THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(j.kode_jurusan), '.', 3), '.', -1) AS UNSIGNED)
                ELSE 0
            END ASC,
            TRIM(j.kode_jurusan) ASC,
            UPPER(TRIM(ps.nama_lengkap)) ASC,
            ps.id ASC
    ");
    if (!$stmt) {
        return ['ok' => false, 'jumlah' => 0, 'message' => 'Query siswa kelas X gagal: ' . $conn->error];
    }

    $stmt->bind_param('s', $tahunAjaran);
    $stmt->execute();
    $result = $stmt->get_result();

    $update = $conn->prepare("UPDATE pendaftaran_siswa SET nipd = ? WHERE id = ?");
    if (!$update) {
        $stmt->close();
        return ['ok' => false, 'jumlah' => 0, 'message' => 'Query update NIS/NIPD gagal: ' . $conn->error];
    }

    $conn->begin_transaction();

    $nomorGlobal = $nomorAwal;
    $nomorTengahPerJurusan = [];
    $jumlah = 0;
    $nomorPertama = null;
    $nomorTerakhir = null;
    $rekapJurusan = [];

    try {
        while ($row = $result->fetch_assoc()) {
            $jurusanId = (int)$row['jurusan_id'];
            $kodeJurusan = trim((string)$row['kode_jurusan']);

            if (!isset($nomorTengahPerJurusan[$jurusanId])) {
                $nomorTengahPerJurusan[$jurusanId] = (int)$row['nomor_tengah_awal'];
            }

            // Jika NIS/NIPD yang terbentuk sudah dipakai, lompatkan nomor global dan nomor tengah jurusan ini.
            do {
                $nomorTengah = $nomorTengahPerJurusan[$jurusanId];
                $nipd = $nomorGlobal . '/' . $nomorTengah . '.' . $kodeJurusan;
                if (!isset($used[$nipd])) {
                    break;
                }
                $nomorGlobal++;
                $nomorTengahPerJurusan[$jurusanId]++;
            } while (true);

            $siswaId = (int)$row['id'];
            $update->bind_param('si', $nipd, $siswaId);
            if (!$update->execute()) {
                throw new Exception('Gagal update NIS/NIPD untuk siswa ID ' . $siswaId . ': ' . $update->error);
            }

            $used[$nipd] = true;
            if ($nomorPertama === null) {
                $nomorPertama = $nomorGlobal;
            }
            $nomorTerakhir = $nomorGlobal;

            if (!isset($rekapJurusan[$jurusanId])) {
                $rekapJurusan[$jurusanId] = [
                    'jumlah' => 0,
                    'nama' => $row['nama_jurusan'],
                    'kode_jurusan' => $kodeJurusan,
                    'kode_akhir' => $nomorGlobal,
                    'urutan_akhir' => $nomorTengah,
                ];
            }
            $rekapJurusan[$jurusanId]['jumlah']++;
            $rekapJurusan[$jurusanId]['kode_akhir'] = $nomorGlobal;
            $rekapJurusan[$jurusanId]['urutan_akhir'] = $nomorTengah;

            $nomorGlobal++;
            $nomorTengahPerJurusan[$jurusanId]++;
            $jumlah++;
        }

        // Simpan riwayat nomor terakhir per jurusan agar admin bisa melihat posisi terakhir setelah generate.
        // PENTING: Jangan update kode_depan dan urutan_awal di sini.
        // Nomor Tengah Awal Jurusan harus tetap menjadi angka patokan manual admin.
        if (!empty($rekapJurusan)) {
            $updatePengaturan = $conn->prepare("
                UPDATE pengaturan_nipd
                SET kode_akhir = ?, urutan_akhir = ?
                WHERE jurusan_id = ? AND BINARY tahun_ajaran = BINARY ?
            ");
            if (!$updatePengaturan) {
                throw new Exception('Query update pengaturan NIS/NIPD gagal: ' . $conn->error);
            }
            foreach ($rekapJurusan as $jurusanId => $r) {
                $kodeAkhir = (int)$r['kode_akhir'];
                $urutanAkhir = (int)$r['urutan_akhir'];
                $updatePengaturan->bind_param('iiis', $kodeAkhir, $urutanAkhir, $jurusanId, $tahunAjaran);
                if (!$updatePengaturan->execute()) {
                    throw new Exception('Gagal update pengaturan NIS/NIPD jurusan ID ' . $jurusanId . ': ' . $updatePengaturan->error);
                }
            }
            $updatePengaturan->close();
        }

        $update->close();
        $stmt->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        $update->close();
        $stmt->close();
        return ['ok' => false, 'jumlah' => 0, 'message' => $e->getMessage()];
    }

    if ($jumlah === 0) {
        return [
            'ok' => true,
            'jumlah' => 0,
            'message' => 'Tidak ada siswa aktif kelas X tahun ajaran ' . $tahunAjaran . ' yang NIS/NIPD-nya masih kosong.'
        ];
    }

    return [
        'ok' => true,
        'jumlah' => $jumlah,
        'message' => 'NIS/NIPD berhasil digenerate untuk ' . $jumlah . ' siswa aktif kelas X tahun ajaran ' . $tahunAjaran . '. Rentang nomor global: ' . $nomorPertama . ' sampai ' . $nomorTerakhir . '.'
    ];
}

/**
 * Reset NIS/NIPD hanya untuk siswa aktif kelas X tahun ajaran aktif.
 */
function resetNIPDKelasXAktif(mysqli $conn, string $tahunAjaran): int
{
    $stmt = $conn->prepare("\n        UPDATE pendaftaran_siswa ps\n        JOIN siswa_kelas sk \n            ON sk.siswa_id = ps.id \n            AND BINARY sk.tahun_ajaran = BINARY ps.tahun_ajaran\n        JOIN kelas k \n            ON k.id = sk.kelas_id \n            AND BINARY k.tahun_ajaran = BINARY ps.tahun_ajaran\n        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id\n        SET ps.nipd = NULL\n        WHERE BINARY ps.tahun_ajaran = BINARY ?\n          AND tk.urutan_tingkat = (SELECT MIN(tk_awal.urutan_tingkat) FROM tingkat_kelas tk_awal)\n          AND ps.status_aktif = 1\n    ");
    if (!$stmt) {
        throw new Exception('Query reset NIS/NIPD gagal: ' . $conn->error);
    }
    $stmt->bind_param('s', $tahunAjaran);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return max(0, (int)$affected);
}


function resetNIPDTahunAjaran($conn, $tahun_ajaran)
{
    $query = "
        UPDATE pendaftaran_siswa ps
        JOIN kelas k ON ps.kelas_id = k.id
        SET ps.nipd = NULL
        WHERE k.tahun_ajaran = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $tahun_ajaran);
    $stmt->execute();
    $stmt->close();
}

// Fungsi untuk konversi angka ke Romawi
function angkaKeRomawi($angka)
{
    $map = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII'
    ];
    return $map[$angka] ?? $angka;
}
