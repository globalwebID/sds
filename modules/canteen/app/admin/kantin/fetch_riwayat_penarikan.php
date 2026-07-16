<?php
include 'inc/fungsi.php';

$query = mysqli_query($conn, "SELECT * FROM penarikan WHERE id_kantin = $id_kantin ORDER BY tanggal DESC");

if (mysqli_num_rows($query) === 0) {
    echo "<p class='text-muted'>Belum ada riwayat penarikan.</p>";
} else {
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Tanggal</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>";
    while ($row = mysqli_fetch_assoc($query)) {
        echo "<tr>
            <td>" . date('d M Y H:i', strtotime($row['tanggal'])) . "</td>
            <td>Rp " . number_format($row['jumlah'], 0, ',', '.') . "</td>
            <td>" . htmlspecialchars($row['status']) . "</td>
        </tr>";
    }
    echo "</tbody></table>";
}
