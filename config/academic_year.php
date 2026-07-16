<?php
declare(strict_types=1);

/**
 * Pusat pengelolaan Tahun Ajaran SDS.
 *
 * Aturan utama:
 * - Hanya satu tahun ajaran berstatus aktif.
 * - Tahun ajaran baru selalu dibuat sebagai draft.
 * - Mengaktifkan draft otomatis menyelesaikan tahun aktif sebelumnya.
 * - Tahun lama tidak dihapus apabila sudah dipakai oleh tabel lain.
 */

if (!function_exists('sds_academic_year_default_label')) {
    function sds_academic_year_default_label(?DateTimeImmutable $date = null): string
    {
        $date ??= new DateTimeImmutable('now');
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n');
        $start = $month >= 7 ? $year : $year - 1;
        return $start . '/' . ($start + 1);
    }
}

if (!function_exists('sds_academic_year_parse_label')) {
    function sds_academic_year_parse_label(string $label): ?array
    {
        if (!preg_match('/^(\d{4})\/(\d{4})$/', trim($label), $matches)) {
            return null;
        }
        $start = (int)$matches[1];
        $end = (int)$matches[2];
        if ($end !== $start + 1) {
            return null;
        }
        return ['start' => $start, 'end' => $end];
    }
}

if (!function_exists('sds_academic_year_previous_label')) {
    function sds_academic_year_previous_label(string $label): string
    {
        $parsed = sds_academic_year_parse_label($label);
        if (!$parsed) {
            return '';
        }
        return ($parsed['start'] - 1) . '/' . $parsed['start'];
    }
}

if (!function_exists('sds_academic_year_next_label')) {
    function sds_academic_year_next_label(string $label): string
    {
        $parsed = sds_academic_year_parse_label($label);
        if (!$parsed) {
            return '';
        }
        return $parsed['end'] . '/' . ($parsed['end'] + 1);
    }
}

if (!function_exists('sds_academic_year_table_columns')) {
    function sds_academic_year_table_columns(mysqli $conn): array
    {
        // Tidak memakai cache agar migrasi kolom yang terjadi pada request yang
        // sama langsung terbaca oleh helper berikutnya.
        $columns = [];
        $result = $conn->query('SHOW COLUMNS FROM `tahun_ajaran`');
        while ($row = $result->fetch_assoc()) {
            $columns[(string)$row['Field']] = true;
        }
        return $columns;
    }
}

if (!function_exists('sds_academic_year_schema_ready')) {
    function sds_academic_year_schema_ready(mysqli $conn): bool
    {
        try {
            $columns = sds_academic_year_table_columns($conn);
            foreach (['status', 'semester_aktif', 'tanggal_mulai', 'tanggal_selesai', 'is_active'] as $required) {
                if (empty($columns[$required])) {
                    return false;
                }
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('sds_academic_year_ensure_schema')) {
    function sds_academic_year_ensure_schema(mysqli $conn): void
    {
        $conn->query("CREATE TABLE IF NOT EXISTS `tahun_ajaran` (
            `tahun_ajaran_id` INT NOT NULL AUTO_INCREMENT,
            `tahun_ajaran` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`tahun_ajaran_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $result = $conn->query('SHOW COLUMNS FROM `tahun_ajaran`');
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[(string)$row['Field']] = true;
        }

        $definitions = [
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER `tahun_ajaran`",
            'semester_aktif' => "VARCHAR(10) NOT NULL DEFAULT 'ganjil' AFTER `status`",
            'tanggal_mulai' => "DATE NULL AFTER `semester_aktif`",
            'tanggal_selesai' => "DATE NULL AFTER `tanggal_mulai`",
            'is_active' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `tanggal_selesai`",
            'activated_at' => "DATETIME NULL AFTER `is_active`",
            'activated_by' => "INT NULL AFTER `activated_at`",
            'completed_at' => "DATETIME NULL AFTER `activated_by`",
            'completed_by' => "INT NULL AFTER `completed_at`",
            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `completed_by`",
            'updated_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
        ];

        foreach ($definitions as $column => $definition) {
            if (empty($columns[$column])) {
                $conn->query("ALTER TABLE `tahun_ajaran` ADD COLUMN `{$column}` {$definition}");
                $columns[$column] = true;
            }
        }

        // Rapikan nilai status yang mungkin berasal dari source lama.
        $conn->query("UPDATE `tahun_ajaran`
            SET `status` = 'draft'
            WHERE `status` IS NULL OR `status` NOT IN ('draft','active','completed','archived')");
        $conn->query("UPDATE `tahun_ajaran`
            SET `semester_aktif` = 'ganjil'
            WHERE `semester_aktif` IS NULL OR `semester_aktif` NOT IN ('ganjil','genap')");
        $conn->query("UPDATE `tahun_ajaran` SET `is_active` = 0 WHERE `is_active` IS NULL");

        // Isi tanggal standar dari label tahun ajaran jika belum tersedia.
        $rows = $conn->query('SELECT tahun_ajaran_id, tahun_ajaran, tanggal_mulai, tanggal_selesai FROM tahun_ajaran');
        $stmtDates = $conn->prepare('UPDATE tahun_ajaran SET tanggal_mulai=?, tanggal_selesai=? WHERE tahun_ajaran_id=?');
        while ($row = $rows->fetch_assoc()) {
            $parsed = sds_academic_year_parse_label((string)$row['tahun_ajaran']);
            if (!$parsed) {
                continue;
            }
            $startDate = !empty($row['tanggal_mulai']) ? (string)$row['tanggal_mulai'] : $parsed['start'] . '-07-01';
            $endDate = !empty($row['tanggal_selesai']) ? (string)$row['tanggal_selesai'] : $parsed['end'] . '-06-30';
            $id = (int)$row['tahun_ajaran_id'];
            $stmtDates->bind_param('ssi', $startDate, $endDate, $id);
            $stmtDates->execute();
        }
        $stmtDates->close();

        // Pastikan hanya satu baris aktif. Bila belum ada, pilih label kalender
        // yang cocok; jika tidak tersedia, gunakan tahun terbaru agar aplikasi
        // lama tetap dapat berjalan setelah patch dipasang.
        $activeRows = [];
        $result = $conn->query("SELECT tahun_ajaran_id, tahun_ajaran FROM tahun_ajaran WHERE is_active=1 OR status='active' ORDER BY tahun_ajaran DESC, tahun_ajaran_id DESC");
        while ($row = $result->fetch_assoc()) {
            $activeRows[] = $row;
        }

        $activeId = 0;
        $activeLabel = '';
        if ($activeRows) {
            $activeId = (int)$activeRows[0]['tahun_ajaran_id'];
            $activeLabel = (string)$activeRows[0]['tahun_ajaran'];
        } else {
            $defaultLabel = sds_academic_year_default_label();
            $stmt = $conn->prepare('SELECT tahun_ajaran_id, tahun_ajaran FROM tahun_ajaran WHERE tahun_ajaran=? ORDER BY tahun_ajaran_id DESC LIMIT 1');
            $stmt->bind_param('s', $defaultLabel);
            $stmt->execute();
            $selected = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$selected) {
                $selected = $conn->query('SELECT tahun_ajaran_id, tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC, tahun_ajaran_id DESC LIMIT 1')->fetch_assoc();
            }
            if ($selected) {
                $activeId = (int)$selected['tahun_ajaran_id'];
                $activeLabel = (string)$selected['tahun_ajaran'];
            }
        }

        if ($activeId > 0) {
            $conn->query('UPDATE tahun_ajaran SET is_active=0 WHERE tahun_ajaran_id<>' . $activeId);
            $stmt = $conn->prepare("UPDATE tahun_ajaran SET is_active=1, status='active', activated_at=COALESCE(activated_at,NOW()) WHERE tahun_ajaran_id=?");
            $stmt->bind_param('i', $activeId);
            $stmt->execute();
            $stmt->close();

            // Status tahun lain dirapikan berdasarkan posisinya terhadap tahun aktif.
            $stmtOlder = $conn->prepare("UPDATE tahun_ajaran SET status='completed' WHERE tahun_ajaran_id<>? AND tahun_ajaran<? AND status<>'archived'");
            $stmtOlder->bind_param('is', $activeId, $activeLabel);
            $stmtOlder->execute();
            $stmtOlder->close();

            $stmtNewer = $conn->prepare("UPDATE tahun_ajaran SET status='draft' WHERE tahun_ajaran_id<>? AND tahun_ajaran>? AND status='active'");
            $stmtNewer->bind_param('is', $activeId, $activeLabel);
            $stmtNewer->execute();
            $stmtNewer->close();
        }
    }
}

if (!function_exists('sds_academic_year_get_active')) {
    function sds_academic_year_get_active(mysqli $conn): array
    {
        if (sds_academic_year_schema_ready($conn)) {
            $result = $conn->query("SELECT * FROM tahun_ajaran WHERE is_active=1 AND status='active' ORDER BY tahun_ajaran_id DESC LIMIT 1");
            $row = $result->fetch_assoc();
            if ($row) {
                return $row;
            }
        }

        // Fallback kompatibilitas untuk database yang belum sempat dimigrasikan.
        $defaultLabel = sds_academic_year_default_label();
        $stmt = $conn->prepare('SELECT tahun_ajaran_id, tahun_ajaran FROM tahun_ajaran WHERE tahun_ajaran=? ORDER BY tahun_ajaran_id DESC LIMIT 1');
        $stmt->bind_param('s', $defaultLabel);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            $row = $conn->query('SELECT tahun_ajaran_id, tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC, tahun_ajaran_id DESC LIMIT 1')->fetch_assoc();
        }

        $month = (int)date('n');
        return ($row ?: ['tahun_ajaran_id' => 0, 'tahun_ajaran' => $defaultLabel]) + [
            'status' => 'active',
            'semester_aktif' => ($month >= 1 && $month <= 6) ? 'genap' : 'ganjil',
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'is_active' => 1,
        ];
    }
}

if (!function_exists('sds_academic_year_maybe_create_next_draft')) {
    function sds_academic_year_maybe_create_next_draft(mysqli $conn, array $active): bool
    {
        if (!sds_academic_year_schema_ready($conn)) {
            return false;
        }
        $label = (string)($active['tahun_ajaran'] ?? '');
        $parsed = sds_academic_year_parse_label($label);
        if (!$parsed) {
            return false;
        }

        // Draft berikutnya baru dibuat otomatis ketika memasuki semester genap
        // atau tanggal server sudah berada di tahun akhir tahun ajaran aktif.
        $semester = strtolower((string)($active['semester_aktif'] ?? 'ganjil'));
        $currentYear = (int)date('Y');
        if ($semester !== 'genap' && $currentYear < $parsed['end']) {
            return false;
        }

        $next = sds_academic_year_next_label($label);
        $stmt = $conn->prepare('SELECT tahun_ajaran_id FROM tahun_ajaran WHERE tahun_ajaran=? LIMIT 1');
        $stmt->bind_param('s', $next);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($exists) {
            return false;
        }

        $nextParsed = sds_academic_year_parse_label($next);
        $startDate = $nextParsed['start'] . '-07-01';
        $endDate = $nextParsed['end'] . '-06-30';
        $stmt = $conn->prepare("INSERT INTO tahun_ajaran (tahun_ajaran,status,semester_aktif,tanggal_mulai,tanggal_selesai,is_active) VALUES (?,'draft','ganjil',?,?,0)");
        $stmt->bind_param('sss', $next, $startDate, $endDate);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}

if (!function_exists('sds_academic_year_usage_summary')) {
    function sds_academic_year_usage_summary(mysqli $conn, string $label): array
    {
        $database = (string)$conn->query('SELECT DATABASE() AS db')->fetch_assoc()['db'];
        $stmt = $conn->prepare("SELECT TABLE_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=? AND COLUMN_NAME='tahun_ajaran'
              AND TABLE_NAME<>'tahun_ajaran' AND TABLE_NAME NOT LIKE 'tmp\\_%'
            ORDER BY TABLE_NAME");
        $stmt->bind_param('s', $database);
        $stmt->execute();
        $tables = $stmt->get_result();

        $summary = [];
        $total = 0;
        while ($row = $tables->fetch_assoc()) {
            $table = (string)$row['TABLE_NAME'];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                continue;
            }
            $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `{$table}` WHERE `tahun_ajaran`=?");
            $countStmt->bind_param('s', $label);
            $countStmt->execute();
            $count = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();
            if ($count > 0) {
                $summary[$table] = $count;
                $total += $count;
            }
        }
        $stmt->close();
        return ['total' => $total, 'tables' => $summary];
    }
}
