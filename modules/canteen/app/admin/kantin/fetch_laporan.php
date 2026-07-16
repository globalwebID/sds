<?php
include 'inc/fungsi.php';

$periode = $_GET['periode'] ?? 'harian';

switch ($periode) {
    case 'mingguan':
        $group = "YEAR(tanggal), WEEK(tanggal)";
        $label = "CONCAT('Minggu ke-', WEEK(tanggal), ' (', DATE_FORMAT(MIN(tanggal), '%d %b'), ' - ', DATE_FORMAT(MAX(tanggal), '%d %b'), ')')";
        break;
    case 'bulanan':
        $group = "YEAR(tanggal), MONTH(tanggal)";
        $label = "DATE_FORMAT(tanggal, '%M %Y')";
        break;
    case 'harian':
    default:
        $group = "DATE(tanggal)";
        $label = "DATE_FORMAT(tanggal, '%d %b %Y')";
        break;
}

$query = "
    SELECT $label AS periode, SUM(nominal) AS total
    FROM transaksi_kantin
    WHERE id_kantin = ?
    GROUP BY $group
    ORDER BY MIN(tanggal) DESC
    LIMIT 12
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_kantin);
$stmt->execute();
$result = $stmt->get_result();

echo "
<style>
.table {
    --bs-table-color-type: initial;
    --bs-table-bg-type: initial;
    --bs-table-color-state: initial;
    --bs-table-bg-state: initial;
    --bs-table-color: none;
    --bs-table-bg: none;
    --bs-table-border-color: var(--bs-border-color);
    --bs-table-accent-bg: transparent;
    --bs-table-striped-color: var(--bs-emphasis-color);
    --bs-table-striped-bg: rgba(var(--bs-emphasis-color-rgb), 0.05);
    --bs-table-active-color: var(--bs-emphasis-color);
    --bs-table-active-bg: rgba(var(--bs-emphasis-color-rgb), 0.1);
    --bs-table-hover-color: var(--bs-emphasis-color);
    --bs-table-hover-bg: rgba(var(--bs-emphasis-color-rgb), 0.075);
    width: 100%;
    margin-bottom: 1rem;
    vertical-align: top;
    border-color: var(--bs-table-border-color);
}
    .table-custom th, .table-custom td {
        font-size: 1.15rem;
        padding: 1rem;
    }
    .table-custom thead {
        background: #198754;
        color: white;
        font-weight: bold;
    }
    .table-custom tbody tr {
        background-color: #fff;
    }
    .table-custom tbody tr:hover {
        background-color: #f1f1f1;
    }
    .table-custom .currency {
        color: #0d6efd;
        font-weight: bold;
    }
        
</style>

<div class='table-responsive'>
    <table class='table table-bordered table-custom'>
        <thead>
            <tr>
                <th><i class='bi bi-calendar-event'></i> Tanggal</th>
                <th><i class='bi bi-cash-coin'></i> Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['periode']}</td>
            <td class='currency'>💰 Rp " . number_format($row['total'], 0, ',', '.') . "</td>
          </tr>";
}
echo "  </tbody>
    </table>
</div>";
