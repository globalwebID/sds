<?php
/**
 * Runtime helper untuk Pengaturan Anjungan SDS.
 * Aman dipanggil berulang kali dari halaman admin maupun halaman publik.
 */

if (!function_exists('sdsAnjunganColumnExists')) {
    function sdsAnjunganColumnExists(mysqli $conn, string $table, string $column): bool
    {
        static $cache = [];
        $allowedTables = ['anjungan', 'anjungan_berita', 'anjungan_menu', 'anjungan_topright'];
        if (!in_array($table, $allowedTables, true)) {
            return false;
        }
        $cacheKey = $table . '.' . $column;
        if (!empty($cache[$cacheKey])) {
            return true;
        }

        $safeColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$safeColumn}'");
        $exists = $result instanceof mysqli_result && $result->num_rows > 0;
        if ($exists) {
            $cache[$cacheKey] = true;
        }
        return $exists;
    }
}

if (!function_exists('sdsAnjunganEnsureSchema')) {
    function sdsAnjunganEnsureSchema(mysqli $conn): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $conn->query(
                "CREATE TABLE IF NOT EXISTS `anjungan_pengaturan` (
                    `id` tinyint unsigned NOT NULL DEFAULT 1,
                    `media_type` enum('video','tanpa_video') NOT NULL DEFAULT 'video',
                    `tema_default` enum('nature','travel','casual') NOT NULL DEFAULT 'nature',
                    `izinkan_pilih_tema` tinyint(1) NOT NULL DEFAULT 1,
                    `tampilkan_jam` tinyint(1) NOT NULL DEFAULT 1,
                    `tampilkan_fullscreen` tinyint(1) NOT NULL DEFAULT 1,
                    `refresh_menit` smallint unsigned NOT NULL DEFAULT 0,
                    `carousel_detik` smallint unsigned NOT NULL DEFAULT 3,
                    `kembali_home_detik` smallint unsigned NOT NULL DEFAULT 0,
                    `maintenance` tinyint(1) NOT NULL DEFAULT 0,
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
            $conn->query("INSERT IGNORE INTO `anjungan_pengaturan` (`id`) VALUES (1)");

            if (!sdsAnjunganColumnExists($conn, 'anjungan_berita', 'status_tayang')) {
                $conn->query("ALTER TABLE `anjungan_berita` ADD COLUMN `status_tayang` enum('draft','terbit') NOT NULL DEFAULT 'terbit' AFTER `status`");
            }
            if (!sdsAnjunganColumnExists($conn, 'anjungan_berita', 'tanggal_berakhir')) {
                $conn->query("ALTER TABLE `anjungan_berita` ADD COLUMN `tanggal_berakhir` date DEFAULT NULL AFTER `status_tayang`");
            }
            if (!sdsAnjunganColumnExists($conn, 'anjungan_menu', 'jenis_tujuan')) {
                $conn->query("ALTER TABLE `anjungan_menu` ADD COLUMN `jenis_tujuan` enum('iframe','eksternal') NOT NULL DEFAULT 'iframe' AFTER `link`");
            }
            if (!sdsAnjunganColumnExists($conn, 'anjungan_topright', 'buka_langsung')) {
                $conn->query("ALTER TABLE `anjungan_topright` ADD COLUMN `buka_langsung` tinyint(1) NOT NULL DEFAULT 0 AFTER `target_modal`");
            }
        } catch (Throwable $e) {
            // Aplikasi lama tetap dapat berjalan dengan nilai default bila akun DB tidak memiliki hak ALTER.
        }
    }
}

if (!function_exists('sdsAnjunganSettingsDefaults')) {
    function sdsAnjunganSettingsDefaults(): array
    {
        return [
            'id' => 1,
            'media_type' => 'video',
            'tema_default' => 'nature',
            'izinkan_pilih_tema' => 1,
            'tampilkan_jam' => 1,
            'tampilkan_fullscreen' => 1,
            'refresh_menit' => 0,
            'carousel_detik' => 3,
            'kembali_home_detik' => 0,
            'maintenance' => 0,
        ];
    }
}

if (!function_exists('sdsAnjunganGetSettings')) {
    function sdsAnjunganGetSettings(mysqli $conn): array
    {
        sdsAnjunganEnsureSchema($conn);
        $defaults = sdsAnjunganSettingsDefaults();
        try {
            $result = $conn->query("SELECT * FROM `anjungan_pengaturan` WHERE `id` = 1 LIMIT 1");
            if ($result instanceof mysqli_result && $result->num_rows > 0) {
                return array_merge($defaults, $result->fetch_assoc());
            }
        } catch (Throwable $e) {
        }
        return $defaults;
    }
}

if (!function_exists('sdsAnjunganNormalizeYoutubeUrl')) {
    function sdsAnjunganNormalizeYoutubeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $videoId = '';
        $parts = @parse_url($url);
        if (is_array($parts)) {
            $host = strtolower((string)($parts['host'] ?? ''));
            $path = trim((string)($parts['path'] ?? ''), '/');

            if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
                $videoId = explode('/', $path)[0] ?? '';
            } elseif (strpos($host, 'youtube.com') !== false) {
                if (preg_match('~^(?:embed|shorts)/([A-Za-z0-9_-]{6,})~', $path, $match)) {
                    $videoId = $match[1];
                } elseif (!empty($parts['query'])) {
                    parse_str($parts['query'], $query);
                    $videoId = (string)($query['v'] ?? '');
                }
            }
        }

        if ($videoId !== '' && preg_match('/^[A-Za-z0-9_-]{6,}$/', $videoId)) {
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        return $url;
    }
}

if (!function_exists('sdsAnjunganIsSafeLink')) {
    function sdsAnjunganIsSafeLink(string $value, bool $allowEmpty = true): bool
    {
        $value = trim($value);
        if ($value === '') {
            return $allowEmpty;
        }
        if (preg_match('/^(?:javascript|data|vbscript):/i', $value)) {
            return false;
        }
        if (preg_match('~^https?://~i', $value)) {
            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        }
        return !preg_match('/[\r\n]/', $value);
    }
}

if (!function_exists('sdsAnjunganPublishedCondition')) {
    function sdsAnjunganPublishedCondition(mysqli $conn, string $alias = ''): string
    {
        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        $conditions = [];
        if (sdsAnjunganColumnExists($conn, 'anjungan_berita', 'status_tayang')) {
            $conditions[] = "{$prefix}`status_tayang` = 'terbit'";
        }
        $conditions[] = "({$prefix}`tanggal` IS NULL OR {$prefix}`tanggal` <= CURDATE())";
        if (sdsAnjunganColumnExists($conn, 'anjungan_berita', 'tanggal_berakhir')) {
            $conditions[] = "({$prefix}`tanggal_berakhir` IS NULL OR {$prefix}`tanggal_berakhir` >= CURDATE())";
        }
        return implode(' AND ', $conditions);
    }
}
