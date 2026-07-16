<?php
if (!isset($_SESSION['admin_id'])) {
    header('Location: login');
    exit;
}

require_once dirname(__DIR__, 4) . '/config/anjungan_runtime.php';
sdsAnjunganEnsureSchema($conn);

if (empty($_SESSION['csrf_anjungan'])) {
    $_SESSION['csrf_anjungan'] = bin2hex(random_bytes(24));
}

if (!function_exists('anjunganRedirect')) {
    function anjunganRedirect(string $tab, string $type, string $message): void
    {
        $_SESSION[$type] = $message;
        header('Location: anjungan_admin#' . $tab);
        exit;
    }
}

if (!function_exists('anjunganDeleteUpload')) {
    function anjunganDeleteUpload(string $directory, ?string $filename): void
    {
        $filename = basename((string)$filename);
        if ($filename === '') {
            return;
        }
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('anjunganUploadImage')) {
    function anjunganUploadImage(array $file, string $directory, string $prefix, int $maxBytes): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file gagal. Silakan pilih file kembali.');
        }
        if ((int)($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Ukuran file terlalu besar.');
        }
        if (!is_uploaded_file((string)$file['tmp_name'])) {
            throw new RuntimeException('File upload tidak valid.');
        }

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$finfo->file((string)$file['tmp_name']);
        } else {
            $mime = (string)mime_content_type((string)$file['tmp_name']);
        }
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Format gambar harus JPG, PNG, WEBP, atau GIF.');
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
        $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
            throw new RuntimeException('Gambar gagal disimpan ke server.');
        }
        return $filename;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['anjungan_action'])) {
    return;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals((string)$_SESSION['csrf_anjungan'], $csrf)) {
    anjunganRedirect('tampilan', 'error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
}

$rootPath = dirname(__DIR__, 4);
$uploadRoot = $rootPath . '/anjungan/assets/uploads';
$action = (string)$_POST['anjungan_action'];

try {
    switch ($action) {
        case 'save_main':
            $nama = trim((string)($_POST['nama_anjungan'] ?? ''));
            $video = trim((string)($_POST['video'] ?? ''));
            $aktif = isset($_POST['aktif']) ? 1 : 0;
            $mediaType = in_array($_POST['media_type'] ?? '', ['video', 'tanpa_video'], true)
                ? (string)$_POST['media_type']
                : 'video';

            if ($nama === '') {
                throw new RuntimeException('Nama Anjungan wajib diisi.');
            }
            if ($mediaType === 'video' && $video === '') {
                throw new RuntimeException('Masukkan URL YouTube atau URL video Anjungan.');
            }
            if ($video !== '' && !sdsAnjunganIsSafeLink($video, false)) {
                throw new RuntimeException('URL video tidak valid.');
            }
            $video = sdsAnjunganNormalizeYoutubeUrl($video);

            $background = anjunganUploadImage(
                $_FILES['background'] ?? [],
                $uploadRoot . '/background',
                'background',
                6 * 1024 * 1024
            );

            $existing = $conn->query("SELECT * FROM `anjungan` ORDER BY `id` ASC LIMIT 1");
            $row = $existing instanceof mysqli_result ? $existing->fetch_assoc() : null;
            if ($row) {
                if ($background !== null) {
                    $stmt = $conn->prepare("UPDATE `anjungan` SET `nama_anjungan`=?, `video`=?, `background`=?, `aktif`=? WHERE `id`=?");
                    $stmt->bind_param('sssii', $nama, $video, $background, $aktif, $row['id']);
                } else {
                    $stmt = $conn->prepare("UPDATE `anjungan` SET `nama_anjungan`=?, `video`=?, `aktif`=? WHERE `id`=?");
                    $stmt->bind_param('ssii', $nama, $video, $aktif, $row['id']);
                }
                $stmt->execute();
                if ($background !== null && !empty($row['background'])) {
                    anjunganDeleteUpload($uploadRoot . '/background', $row['background']);
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO `anjungan` (`nama_anjungan`,`background`,`video`,`aktif`) VALUES (?,?,?,?)");
                $stmt->bind_param('sssi', $nama, $background, $video, $aktif);
                $stmt->execute();
            }

            $settings = sdsAnjunganGetSettings($conn);
            $stmt = $conn->prepare("INSERT INTO `anjungan_pengaturan` (`id`,`media_type`,`tema_default`,`izinkan_pilih_tema`,`tampilkan_jam`,`tampilkan_fullscreen`,`refresh_menit`,`carousel_detik`,`kembali_home_detik`,`maintenance`) VALUES (1,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `media_type`=VALUES(`media_type`)");
            $stmt->bind_param(
                'ssiiiiiii',
                $mediaType,
                $settings['tema_default'],
                $settings['izinkan_pilih_tema'],
                $settings['tampilkan_jam'],
                $settings['tampilkan_fullscreen'],
                $settings['refresh_menit'],
                $settings['carousel_detik'],
                $settings['kembali_home_detik'],
                $settings['maintenance']
            );
            $stmt->execute();

            anjunganRedirect('tampilan', 'success', 'Tampilan utama Anjungan berhasil diperbarui.');
            break;

        case 'remove_background':
            $result = $conn->query("SELECT `id`,`background` FROM `anjungan` ORDER BY `id` ASC LIMIT 1");
            $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
            if ($row) {
                $stmt = $conn->prepare("UPDATE `anjungan` SET `background`=NULL WHERE `id`=?");
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
                anjunganDeleteUpload($uploadRoot . '/background', $row['background']);
            }
            anjunganRedirect('tampilan', 'success', 'Background Anjungan dikembalikan ke tampilan default.');
            break;

        case 'save_operational':
            $tema = in_array($_POST['tema_default'] ?? '', ['nature', 'travel', 'casual'], true)
                ? (string)$_POST['tema_default']
                : 'nature';
            $izinkanTema = isset($_POST['izinkan_pilih_tema']) ? 1 : 0;
            $tampilkanJam = isset($_POST['tampilkan_jam']) ? 1 : 0;
            $tampilkanFullscreen = isset($_POST['tampilkan_fullscreen']) ? 1 : 0;
            $maintenance = isset($_POST['maintenance']) ? 1 : 0;
            $refresh = max(0, min(1440, (int)($_POST['refresh_menit'] ?? 0)));
            $carousel = max(2, min(60, (int)($_POST['carousel_detik'] ?? 3)));
            $idle = max(0, min(7200, (int)($_POST['kembali_home_detik'] ?? 0)));
            $current = sdsAnjunganGetSettings($conn);
            $mediaType = in_array($current['media_type'] ?? '', ['video', 'tanpa_video'], true) ? $current['media_type'] : 'video';

            $stmt = $conn->prepare("INSERT INTO `anjungan_pengaturan` (`id`,`media_type`,`tema_default`,`izinkan_pilih_tema`,`tampilkan_jam`,`tampilkan_fullscreen`,`refresh_menit`,`carousel_detik`,`kembali_home_detik`,`maintenance`) VALUES (1,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `tema_default`=VALUES(`tema_default`),`izinkan_pilih_tema`=VALUES(`izinkan_pilih_tema`),`tampilkan_jam`=VALUES(`tampilkan_jam`),`tampilkan_fullscreen`=VALUES(`tampilkan_fullscreen`),`refresh_menit`=VALUES(`refresh_menit`),`carousel_detik`=VALUES(`carousel_detik`),`kembali_home_detik`=VALUES(`kembali_home_detik`),`maintenance`=VALUES(`maintenance`)");
            $stmt->bind_param('ssiiiiiii', $mediaType, $tema, $izinkanTema, $tampilkanJam, $tampilkanFullscreen, $refresh, $carousel, $idle, $maintenance);
            $stmt->execute();
            anjunganRedirect('operasional', 'success', 'Pengaturan operasional Anjungan berhasil disimpan.');
            break;

        case 'save_news':
            $id = max(0, (int)($_POST['id'] ?? 0));
            $judul = trim((string)($_POST['judul'] ?? ''));
            $tanggal = trim((string)($_POST['tanggal'] ?? ''));
            $tanggalBerakhir = trim((string)($_POST['tanggal_berakhir'] ?? ''));
            $link = trim((string)($_POST['link'] ?? ''));
            $jenis = in_array($_POST['jenis'] ?? '', ['berita', 'pengumuman'], true) ? (string)$_POST['jenis'] : 'berita';
            $prioritas = in_array($_POST['status'] ?? '', ['biasa', 'terbaru', 'populer'], true) ? (string)$_POST['status'] : 'biasa';
            $statusTayang = in_array($_POST['status_tayang'] ?? '', ['draft', 'terbit'], true) ? (string)$_POST['status_tayang'] : 'terbit';

            if ($judul === '' || $tanggal === '') {
                throw new RuntimeException('Judul dan tanggal tayang wajib diisi.');
            }
            if (!sdsAnjunganIsSafeLink($link, true)) {
                throw new RuntimeException('Link berita tidak valid.');
            }
            if ($tanggalBerakhir !== '' && $tanggalBerakhir < $tanggal) {
                throw new RuntimeException('Tanggal berakhir tidak boleh lebih awal dari tanggal tayang.');
            }

            $gambar = anjunganUploadImage(
                $_FILES['gambar'] ?? [],
                $uploadRoot . '/berita',
                'berita',
                4 * 1024 * 1024
            );
            $hasPublishing = sdsAnjunganColumnExists($conn, 'anjungan_berita', 'status_tayang')
                && sdsAnjunganColumnExists($conn, 'anjungan_berita', 'tanggal_berakhir');
            $tanggalBerakhirDb = $tanggalBerakhir !== '' ? $tanggalBerakhir : null;

            if ($id > 0) {
                $oldStmt = $conn->prepare("SELECT `gambar` FROM `anjungan_berita` WHERE `id`=? LIMIT 1");
                $oldStmt->bind_param('i', $id);
                $oldStmt->execute();
                $old = $oldStmt->get_result()->fetch_assoc();
                if (!$old) {
                    throw new RuntimeException('Data berita tidak ditemukan.');
                }

                if ($hasPublishing) {
                    if ($gambar !== null) {
                        $stmt = $conn->prepare("UPDATE `anjungan_berita` SET `judul`=?,`tanggal`=?,`gambar`=?,`link`=?,`jenis`=?,`status`=?,`status_tayang`=?,`tanggal_berakhir`=? WHERE `id`=?");
                        $stmt->bind_param('ssssssssi', $judul, $tanggal, $gambar, $link, $jenis, $prioritas, $statusTayang, $tanggalBerakhirDb, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE `anjungan_berita` SET `judul`=?,`tanggal`=?,`link`=?,`jenis`=?,`status`=?,`status_tayang`=?,`tanggal_berakhir`=? WHERE `id`=?");
                        $stmt->bind_param('sssssssi', $judul, $tanggal, $link, $jenis, $prioritas, $statusTayang, $tanggalBerakhirDb, $id);
                    }
                } else {
                    if ($gambar !== null) {
                        $stmt = $conn->prepare("UPDATE `anjungan_berita` SET `judul`=?,`tanggal`=?,`gambar`=?,`link`=?,`jenis`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('ssssssi', $judul, $tanggal, $gambar, $link, $jenis, $prioritas, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE `anjungan_berita` SET `judul`=?,`tanggal`=?,`link`=?,`jenis`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('sssssi', $judul, $tanggal, $link, $jenis, $prioritas, $id);
                    }
                }
                $stmt->execute();
                if ($gambar !== null) {
                    anjunganDeleteUpload($uploadRoot . '/berita', $old['gambar'] ?? null);
                }
                $message = 'Berita atau pengumuman berhasil diperbarui.';
            } else {
                $dilihat = 0;
                if ($hasPublishing) {
                    $stmt = $conn->prepare("INSERT INTO `anjungan_berita` (`judul`,`tanggal`,`gambar`,`link`,`dilihat`,`jenis`,`status`,`status_tayang`,`tanggal_berakhir`) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('ssssissss', $judul, $tanggal, $gambar, $link, $dilihat, $jenis, $prioritas, $statusTayang, $tanggalBerakhirDb);
                } else {
                    $stmt = $conn->prepare("INSERT INTO `anjungan_berita` (`judul`,`tanggal`,`gambar`,`link`,`dilihat`,`jenis`,`status`) VALUES (?,?,?,?,?,?,?)");
                    $stmt->bind_param('ssssiss', $judul, $tanggal, $gambar, $link, $dilihat, $jenis, $prioritas);
                }
                $stmt->execute();
                $message = 'Berita atau pengumuman berhasil ditambahkan.';
            }
            anjunganRedirect('berita', 'success', $message);
            break;

        case 'delete_news':
            $id = max(0, (int)($_POST['id'] ?? 0));
            $stmt = $conn->prepare("SELECT `gambar` FROM `anjungan_berita` WHERE `id`=? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                throw new RuntimeException('Data berita tidak ditemukan.');
            }
            $delete = $conn->prepare("DELETE FROM `anjungan_berita` WHERE `id`=?");
            $delete->bind_param('i', $id);
            $delete->execute();
            anjunganDeleteUpload($uploadRoot . '/berita', $row['gambar'] ?? null);
            anjunganRedirect('berita', 'success', 'Berita atau pengumuman berhasil dihapus.');
            break;

        case 'save_menu':
            $id = max(0, (int)($_POST['id'] ?? 0));
            $nama = trim((string)($_POST['nama_menu'] ?? ''));
            $link = trim((string)($_POST['link'] ?? ''));
            $jenisTujuan = in_array($_POST['jenis_tujuan'] ?? '', ['iframe', 'eksternal'], true) ? (string)$_POST['jenis_tujuan'] : 'iframe';
            $urutan = max(0, (int)($_POST['urutan'] ?? 0));
            $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif'], true) ? (string)$_POST['status'] : 'aktif';

            if ($nama === '' || $link === '') {
                throw new RuntimeException('Nama layanan dan tujuan wajib diisi.');
            }
            if (!sdsAnjunganIsSafeLink($link, false)) {
                throw new RuntimeException('Tujuan Menu Layanan tidak valid.');
            }

            $icon = anjunganUploadImage($_FILES['icon'] ?? [], $uploadRoot . '/menu', 'menu', 2 * 1024 * 1024);
            $hasJenis = sdsAnjunganColumnExists($conn, 'anjungan_menu', 'jenis_tujuan');

            if ($id > 0) {
                $oldStmt = $conn->prepare("SELECT `icon` FROM `anjungan_menu` WHERE `id`=? LIMIT 1");
                $oldStmt->bind_param('i', $id);
                $oldStmt->execute();
                $old = $oldStmt->get_result()->fetch_assoc();
                if (!$old) {
                    throw new RuntimeException('Menu Layanan tidak ditemukan.');
                }
                if ($hasJenis) {
                    if ($icon !== null) {
                        $stmt = $conn->prepare("UPDATE `anjungan_menu` SET `nama_menu`=?,`link`=?,`jenis_tujuan`=?,`icon`=?,`urutan`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('ssssisi', $nama, $link, $jenisTujuan, $icon, $urutan, $status, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE `anjungan_menu` SET `nama_menu`=?,`link`=?,`jenis_tujuan`=?,`urutan`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('sssisi', $nama, $link, $jenisTujuan, $urutan, $status, $id);
                    }
                } else {
                    if ($icon !== null) {
                        $stmt = $conn->prepare("UPDATE `anjungan_menu` SET `nama_menu`=?,`link`=?,`icon`=?,`urutan`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('sssisi', $nama, $link, $icon, $urutan, $status, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE `anjungan_menu` SET `nama_menu`=?,`link`=?,`urutan`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('ssisi', $nama, $link, $urutan, $status, $id);
                    }
                }
                $stmt->execute();
                if ($icon !== null) {
                    anjunganDeleteUpload($uploadRoot . '/menu', $old['icon'] ?? null);
                }
                $message = 'Menu Layanan berhasil diperbarui.';
            } else {
                if ($icon === null) {
                    throw new RuntimeException('Ikon wajib dipilih untuk Menu Layanan baru.');
                }
                if ($hasJenis) {
                    $stmt = $conn->prepare("INSERT INTO `anjungan_menu` (`nama_menu`,`link`,`jenis_tujuan`,`icon`,`urutan`,`status`) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param('ssssis', $nama, $link, $jenisTujuan, $icon, $urutan, $status);
                } else {
                    $stmt = $conn->prepare("INSERT INTO `anjungan_menu` (`nama_menu`,`link`,`icon`,`urutan`,`status`) VALUES (?,?,?,?,?)");
                    $stmt->bind_param('sssis', $nama, $link, $icon, $urutan, $status);
                }
                $stmt->execute();
                $message = 'Menu Layanan berhasil ditambahkan.';
            }
            anjunganRedirect('layanan', 'success', $message);
            break;

        case 'delete_menu':
            $id = max(0, (int)($_POST['id'] ?? 0));
            $stmt = $conn->prepare("SELECT `icon` FROM `anjungan_menu` WHERE `id`=? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                throw new RuntimeException('Menu Layanan tidak ditemukan.');
            }
            $delete = $conn->prepare("DELETE FROM `anjungan_menu` WHERE `id`=?");
            $delete->bind_param('i', $id);
            $delete->execute();
            anjunganDeleteUpload($uploadRoot . '/menu', $row['icon'] ?? null);
            anjunganRedirect('layanan', 'success', 'Menu Layanan berhasil dihapus.');
            break;

        case 'save_quick':
            $id = max(0, (int)($_POST['id'] ?? 0));
            $nama = trim((string)($_POST['nama'] ?? ''));
            $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
            $aksi = in_array($_POST['aksi'] ?? '', ['iframe', 'langsung', 'modal'], true) ? (string)$_POST['aksi'] : 'iframe';
            $link = trim((string)($_POST['link_url'] ?? ''));
            $target = trim((string)($_POST['target_modal'] ?? ''));
            $urutan = max(0, (int)($_POST['urutan'] ?? 0));
            $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif'], true) ? (string)$_POST['status'] : 'aktif';

            if ($nama === '') {
                throw new RuntimeException('Nama Akses Cepat wajib diisi.');
            }
            if ($aksi === 'modal') {
                if ($target === '') {
                    throw new RuntimeException('Pilih popup tujuan untuk Akses Cepat.');
                }
                $tipe = 'modal';
                $bukaLangsung = 0;
                $link = '';
            } else {
                if ($link === '' || !sdsAnjunganIsSafeLink($link, false)) {
                    throw new RuntimeException('URL atau halaman tujuan Akses Cepat tidak valid.');
                }
                $tipe = 'link';
                $bukaLangsung = $aksi === 'langsung' ? 1 : 0;
                $target = '';
            }

            $icon = anjunganUploadImage($_FILES['icon_url'] ?? [], $uploadRoot . '/topright', 'topright', 2 * 1024 * 1024);
            $hasDirect = sdsAnjunganColumnExists($conn, 'anjungan_topright', 'buka_langsung');

            if ($id > 0) {
                $oldStmt = $conn->prepare("SELECT `icon_url` FROM `anjungan_topright` WHERE `id`=? LIMIT 1");
                $oldStmt->bind_param('i', $id);
                $oldStmt->execute();
                $old = $oldStmt->get_result()->fetch_assoc();
                if (!$old) {
                    throw new RuntimeException('Akses Cepat tidak ditemukan.');
                }
                if ($hasDirect) {
                    if ($icon !== null) {
                        $stmt = $conn->prepare("UPDATE `anjungan_topright` SET `nama`=?,`deskripsi`=?,`link_url`=?,`tipe`=?,`target_modal`=?,`buka_langsung`=?,`urutan`=?,`status`=?,`icon_url`=? WHERE `id`=?");
                        $stmt->bind_param('sssssiissi', $nama, $deskripsi, $link, $tipe, $target, $bukaLangsung, $urutan, $status, $icon, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE `anjungan_topright` SET `nama`=?,`deskripsi`=?,`link_url`=?,`tipe`=?,`target_modal`=?,`buka_langsung`=?,`urutan`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('sssssiisi', $nama, $deskripsi, $link, $tipe, $target, $bukaLangsung, $urutan, $status, $id);
                    }
                } else {
                    if ($icon !== null) {
                        $stmt = $conn->prepare("UPDATE `anjungan_topright` SET `nama`=?,`deskripsi`=?,`link_url`=?,`tipe`=?,`target_modal`=?,`urutan`=?,`status`=?,`icon_url`=? WHERE `id`=?");
                        $stmt->bind_param('sssssissi', $nama, $deskripsi, $link, $tipe, $target, $urutan, $status, $icon, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE `anjungan_topright` SET `nama`=?,`deskripsi`=?,`link_url`=?,`tipe`=?,`target_modal`=?,`urutan`=?,`status`=? WHERE `id`=?");
                        $stmt->bind_param('sssssisi', $nama, $deskripsi, $link, $tipe, $target, $urutan, $status, $id);
                    }
                }
                $stmt->execute();
                if ($icon !== null) {
                    anjunganDeleteUpload($uploadRoot . '/topright', $old['icon_url'] ?? null);
                }
                $message = 'Akses Cepat berhasil diperbarui.';
            } else {
                if ($icon === null) {
                    throw new RuntimeException('Ikon wajib dipilih untuk Akses Cepat baru.');
                }
                if ($hasDirect) {
                    $stmt = $conn->prepare("INSERT INTO `anjungan_topright` (`nama`,`deskripsi`,`icon_url`,`link_url`,`tipe`,`target_modal`,`buka_langsung`,`urutan`,`status`) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('ssssssiis', $nama, $deskripsi, $icon, $link, $tipe, $target, $bukaLangsung, $urutan, $status);
                } else {
                    $stmt = $conn->prepare("INSERT INTO `anjungan_topright` (`nama`,`deskripsi`,`icon_url`,`link_url`,`tipe`,`target_modal`,`urutan`,`status`) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('ssssssis', $nama, $deskripsi, $icon, $link, $tipe, $target, $urutan, $status);
                }
                $stmt->execute();
                $message = 'Akses Cepat berhasil ditambahkan.';
            }
            anjunganRedirect('akses', 'success', $message);
            break;

        case 'delete_quick':
            $id = max(0, (int)($_POST['id'] ?? 0));
            $stmt = $conn->prepare("SELECT `icon_url` FROM `anjungan_topright` WHERE `id`=? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                throw new RuntimeException('Akses Cepat tidak ditemukan.');
            }
            $delete = $conn->prepare("DELETE FROM `anjungan_topright` WHERE `id`=?");
            $delete->bind_param('i', $id);
            $delete->execute();
            anjunganDeleteUpload($uploadRoot . '/topright', $row['icon_url'] ?? null);
            anjunganRedirect('akses', 'success', 'Akses Cepat berhasil dihapus.');
            break;

        default:
            throw new RuntimeException('Aksi Pengaturan Anjungan tidak dikenali.');
    }
} catch (Throwable $e) {
    $tabMap = [
        'save_main' => 'tampilan',
        'remove_background' => 'tampilan',
        'save_operational' => 'operasional',
        'save_news' => 'berita',
        'delete_news' => 'berita',
        'save_menu' => 'layanan',
        'delete_menu' => 'layanan',
        'save_quick' => 'akses',
        'delete_quick' => 'akses',
    ];
    anjunganRedirect($tabMap[$action] ?? 'tampilan', 'error', $e->getMessage());
}
