<?php
/**
 * Proses naik kelas otomatis.
 * Revisi:
 * - Tidak bergantung pada tabel tingkat_kelas, cukup memakai kelas.tingkat_id.
 * - X  -> XI
 * - XI -> XII
 * - XII tidak diproses.
 * - Nama rombel ikut berubah, contoh: X AK 1 -> XI AK 1, XI AK 1 -> XII AK 1.
 * - Aman dijalankan ulang: siswa yang sudah punya data pada tahun ajaran baru akan dilewati.
 */

function labelTingkatById(int $tingkatId): string
{
    $map = [
        1 => 'X',
        2 => 'XI',
        3 => 'XII',
    ];

    return $map[$tingkatId] ?? (string)$tingkatId;
}

function angkaTingkatUntukRekap(int $tingkatId): int
{
    $map = [
        1 => 10,
        2 => 11,
        3 => 12,
    ];

    return $map[$tingkatId] ?? $tingkatId;
}

function namaKelasNaik(string $namaKelasLama, int $tingkatBaruId): string
{
    $prefixBaru = labelTingkatById($tingkatBaruId);

    // Hapus awalan tingkat lama jika ada: X, XI, XII.
    $sisaNama = preg_replace('/^(XII|XI|X)\s+/i', '', trim($namaKelasLama));

    return trim($prefixBaru . ' ' . $sisaNama);
}

function hitungTerisiKelas(mysqli $conn, int $kelasId, string $tahunAjaran): int
{
    $stmt = $conn->prepare("\n        SELECT COUNT(*) AS total\n        FROM siswa_kelas sk\n        JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id\n        WHERE sk.kelas_id = ?\n          AND sk.tahun_ajaran = ?\n          AND sk.naik_kelas = 1\n          AND ps.status_aktif = 1\n    ");
    $stmt->bind_param('is', $kelasId, $tahunAjaran);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0);
}

function sinkronTerisiKelas(mysqli $conn, int $kelasId, string $tahunAjaran): void
{
    $total = hitungTerisiKelas($conn, $kelasId, $tahunAjaran);
    $stmt = $conn->prepare("UPDATE kelas SET terisi = ? WHERE id = ?");
    $stmt->bind_param('ii', $total, $kelasId);
    $stmt->execute();
    $stmt->close();
}

function naikKelasOtomatis(mysqli $conn, string $tahunLama, string $tahunBaru, array &$rekap = []): bool
{
    $conn->begin_transaction();

    try {
        /**
         * Mapping jurusan lama ke jurusan tahun baru berdasarkan kode_jurusan.
         * Contoh: Akuntansi 2025/2026 -> Akuntansi 2026/2027.
         */
        $mapJurusanBaru = [];
        $stmtJurusan = $conn->prepare("\n            SELECT\n                j_lama.id AS jurusan_lama_id,\n                j_baru.id AS jurusan_baru_id\n            FROM jurusan j_lama\n            JOIN jurusan j_baru\n              ON j_baru.kode_jurusan = j_lama.kode_jurusan\n             AND j_baru.tahun_ajaran = ?\n            WHERE j_lama.tahun_ajaran = ?\n        ");
        $stmtJurusan->bind_param('ss', $tahunBaru, $tahunLama);
        $stmtJurusan->execute();
        $resJurusan = $stmtJurusan->get_result();
        while ($row = $resJurusan->fetch_assoc()) {
            $mapJurusanBaru[(int)$row['jurusan_lama_id']] = (int)$row['jurusan_baru_id'];
        }
        $stmtJurusan->close();

        /**
         * Ambil kelas lama tingkat X dan XI saja.
         * Tingkat XII tidak dinaikkan lagi.
         */
        $stmtKelas = $conn->prepare("\n            SELECT id, nama_kelas, tahun_ajaran, jurusan_id, wali_kelas, kuota, terisi, tingkat_id\n            FROM kelas\n            WHERE tahun_ajaran = ?\n              AND tingkat_id IN (1, 2)\n            ORDER BY tingkat_id ASC, jurusan_id ASC, nama_kelas ASC\n        ");
        $stmtKelas->bind_param('s', $tahunLama);
        $stmtKelas->execute();
        $kelasLamaResult = $stmtKelas->get_result();

        while ($kelas = $kelasLamaResult->fetch_assoc()) {
            $kelasLamaId = (int)$kelas['id'];
            $tingkatLamaId = (int)$kelas['tingkat_id'];
            $tingkatBaruId = $tingkatLamaId + 1;
            $jurusanLamaId = (int)$kelas['jurusan_id'];

            if (!isset($mapJurusanBaru[$jurusanLamaId])) {
                $rekap[] = [
                    'kelas_lama' => $kelas['nama_kelas'],
                    'tingkat_lama' => $tingkatLamaId,
                    'nama_tingkat_lama' => angkaTingkatUntukRekap($tingkatLamaId),
                    'tingkat_baru' => $tingkatBaruId,
                    'nama_tingkat_baru' => angkaTingkatUntukRekap($tingkatBaruId),
                    'jumlah' => 0,
                    'keterangan' => 'Jurusan tahun baru belum tersedia'
                ];
                continue;
            }

            $jurusanBaruId = $mapJurusanBaru[$jurusanLamaId];
            $namaKelasBaru = namaKelasNaik($kelas['nama_kelas'], $tingkatBaruId);
            $waliKelasBaru = (string)$kelas['wali_kelas'];
            $kuota = (int)$kelas['kuota'];

            // Cari/buat kelas tujuan.
            $kelasBaruId = null;
            $cekKelas = $conn->prepare("\n                SELECT id\n                FROM kelas\n                WHERE nama_kelas = ?\n                  AND tahun_ajaran = ?\n                  AND jurusan_id = ?\n                  AND tingkat_id = ?\n                LIMIT 1\n            ");
            $cekKelas->bind_param('ssii', $namaKelasBaru, $tahunBaru, $jurusanBaruId, $tingkatBaruId);
            $cekKelas->execute();
            $resKelas = $cekKelas->get_result();
            if ($rowKelas = $resKelas->fetch_assoc()) {
                $kelasBaruId = (int)$rowKelas['id'];

                $updateKelas = $conn->prepare("\n                    UPDATE kelas\n                    SET wali_kelas = ?, kuota = ?, tingkat_id = ?, jurusan_id = ?\n                    WHERE id = ?\n                ");
                $updateKelas->bind_param('siiii', $waliKelasBaru, $kuota, $tingkatBaruId, $jurusanBaruId, $kelasBaruId);
                $updateKelas->execute();
                $updateKelas->close();
            } else {
                $buatKelas = $conn->prepare("\n                    INSERT INTO kelas (nama_kelas, tahun_ajaran, jurusan_id, wali_kelas, kuota, terisi, tingkat_id)\n                    VALUES (?, ?, ?, ?, ?, 0, ?)\n                ");
                $buatKelas->bind_param('ssisii', $namaKelasBaru, $tahunBaru, $jurusanBaruId, $waliKelasBaru, $kuota, $tingkatBaruId);
                $buatKelas->execute();
                $kelasBaruId = (int)$conn->insert_id;
                $buatKelas->close();
            }
            $cekKelas->close();

            // Ambil siswa aktif dari kelas lama yang status naik_kelas = 1.
            $stmtSiswa = $conn->prepare("\n                SELECT sk.siswa_id\n                FROM siswa_kelas sk\n                JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id\n                WHERE sk.kelas_id = ?\n                  AND sk.tahun_ajaran = ?\n                  AND sk.naik_kelas = 1\n                  AND ps.status_aktif = 1\n                ORDER BY ps.nama_lengkap ASC\n            ");
            $stmtSiswa->bind_param('is', $kelasLamaId, $tahunLama);
            $stmtSiswa->execute();
            $resSiswa = $stmtSiswa->get_result();

            $jumlahNaik = 0;
            while ($siswa = $resSiswa->fetch_assoc()) {
                $siswaId = (int)$siswa['siswa_id'];

                // Jangan dobel jika siswa sudah punya rombel di tahun baru.
                $cekSiswa = $conn->prepare("\n                    SELECT id\n                    FROM siswa_kelas\n                    WHERE siswa_id = ?\n                      AND tahun_ajaran = ?\n                    LIMIT 1\n                ");
                $cekSiswa->bind_param('is', $siswaId, $tahunBaru);
                $cekSiswa->execute();
                $sudahAda = $cekSiswa->get_result()->num_rows > 0;
                $cekSiswa->close();

                if ($sudahAda) {
                    continue;
                }

                $insertSiswa = $conn->prepare("\n                    INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, naik_kelas)\n                    VALUES (?, ?, ?, 1)\n                ");
                $insertSiswa->bind_param('iis', $siswaId, $kelasBaruId, $tahunBaru);
                if ($insertSiswa->execute()) {
                    $jumlahNaik++;

                    // Sinkronkan kelas_id utama agar halaman lama yang masih membaca pendaftaran_siswa.kelas_id ikut benar.
                    $updateSiswa = $conn->prepare("UPDATE pendaftaran_siswa SET kelas_id = ? WHERE id = ?");
                    $updateSiswa->bind_param('ii', $kelasBaruId, $siswaId);
                    $updateSiswa->execute();
                    $updateSiswa->close();
                }
                $insertSiswa->close();
            }
            $stmtSiswa->close();

            sinkronTerisiKelas($conn, $kelasBaruId, $tahunBaru);

            $rekap[] = [
                'kelas_lama' => $kelas['nama_kelas'],
                'kelas_baru' => $namaKelasBaru,
                'tingkat_lama' => $tingkatLamaId,
                'nama_tingkat_lama' => angkaTingkatUntukRekap($tingkatLamaId),
                'tingkat_baru' => $tingkatBaruId,
                'nama_tingkat_baru' => angkaTingkatUntukRekap($tingkatBaruId),
                'jumlah' => $jumlahNaik,
                'keterangan' => $jumlahNaik > 0 ? 'Diproses' : 'Sudah ada / tidak ada siswa baru yang perlu dinaikkan'
            ];
        }
        $stmtKelas->close();

        // Sinkron ulang semua terisi kelas tahun baru agar angka di kuota_kelas akurat.
        $stmtSemuaKelas = $conn->prepare("SELECT id FROM kelas WHERE tahun_ajaran = ?");
        $stmtSemuaKelas->bind_param('s', $tahunBaru);
        $stmtSemuaKelas->execute();
        $resSemuaKelas = $stmtSemuaKelas->get_result();
        while ($row = $resSemuaKelas->fetch_assoc()) {
            sinkronTerisiKelas($conn, (int)$row['id'], $tahunBaru);
        }
        $stmtSemuaKelas->close();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahunLama = $_POST['tahun_lama'] ?? '';
    $tahunBaru = $_POST['tahun_baru'] ?? '';

    if ($tahunLama === '' || $tahunBaru === '') {
        $_SESSION['error'] = 'Tahun ajaran lama dan tahun ajaran baru wajib dipilih.';
        header('Location: kuota_kelas');
        exit;
    }

    if ($tahunLama === $tahunBaru) {
        $_SESSION['error'] = 'Tahun ajaran lama dan tahun ajaran baru tidak boleh sama.';
        header('Location: kuota_kelas');
        exit;
    }

    $rekap = [];

    try {
        naikKelasOtomatis($conn, $tahunLama, $tahunBaru, $rekap);
        $_SESSION['naik_kelas_rekap'] = $rekap;
        $_SESSION['success'] = "Proses naik kelas dari $tahunLama ke $tahunBaru selesai.";
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Proses naik kelas gagal: ' . $e->getMessage();
    }

    header('Location: kuota_kelas');
    exit;
}
