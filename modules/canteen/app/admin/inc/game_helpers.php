<?php

if (!function_exists('game_admin_table_exists')) {
    function game_admin_table_exists(mysqli $conn, string $table): bool
    {
        $table = mysqli_real_escape_string($conn, $table);
        $q = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
        return $q && mysqli_num_rows($q) > 0;
    }
}

if (!function_exists('game_admin_scalar')) {
    function game_admin_scalar(mysqli $conn, string $sql, string $field = 'total'): int
    {
        $q = mysqli_query($conn, $sql);
        if (!$q) return 0;
        $r = mysqli_fetch_assoc($q);
        return (int)($r[$field] ?? 0);
    }
}

if (!function_exists('game_admin_rupiah')) {
    function game_admin_rupiah(int $nominal): string
    {
        return 'Rp ' . number_format($nominal, 0, ',', '.');
    }
}

if (!function_exists('game_admin_status_badge')) {
    function game_admin_status_badge(string $status): string
    {
        $status = strtoupper(trim($status));
        $class = 'secondary';
        $label = $status !== '' ? $status : 'UNKNOWN';

        switch ($status) {
            case 'SUCCESS':
                $class = 'success';
                $label = 'BERHASIL';
                break;
            case 'PROCESSING':
                $class = 'warning text-dark';
                $label = 'DIPROSES';
                break;
            case 'FAILED':
                $class = 'danger';
                $label = 'GAGAL';
                break;
            case 'REFUNDED':
                $class = 'secondary';
                $label = 'REFUND';
                break;
            case 'CREATED':
                $class = 'primary';
                $label = 'DIBUAT';
                break;
        }

        return '<span class="badge bg-' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('game_admin_role_guard')) {
    function game_admin_role_guard(array $allowedRoles): void
    {
        $role = $_SESSION['role'] ?? null;
        if (!in_array($role, $allowedRoles, true)) {
            header('Location: login.php?error=Akses ditolak');
            exit;
        }
    }
}
