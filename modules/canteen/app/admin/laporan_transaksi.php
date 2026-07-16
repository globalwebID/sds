<?php
include 'inc/fungsi.php';
checkRole(['superadmin', 'admin']);

/**
 * RULE:
 * - Topup manual/sekolah: merchant_order_id & duitku_reference NULL => tetap tampil walau status PENDING (karena manual)
 * - Topup Duitku: merchant_order_id atau duitku_reference NOT NULL => HANYA tampil jika status = PAID
 *   (PENDING/FAILED/EXPIRED tidak ditampilkan dan tidak dihitung sebagai transaksi)
 */
$filter_topup_valid = "
(
  (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL)
  OR tp.status = 'PAID'
)
";

// transaksi terakhir dari transaksi_kantin, topup, penarikan, transfer
$log_result = mysqli_query($conn, "
    SELECT 
        t.tanggal AS waktu,
        s.nama_lengkap AS nama_siswa,
        s.rfid_uid,
        k.nama AS nama_kantin,
        t.nominal,
        'Pembelian' AS jenis,
        '-' AS sumber_topup,
        '-' AS petugas_topup
    FROM transaksi_kantin t
    JOIN pendaftaran_siswa s ON t.id_siswa = s.id
    JOIN kantin k ON t.id_kantin = k.id

    UNION ALL

    SELECT 
        tp.tanggal AS waktu,
        s.nama_lengkap AS nama_siswa,
        s.rfid_uid,
        '-' AS nama_kantin,
        tp.nominal,
        'Topup' AS jenis,
        CASE
            WHEN (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL) THEN 'SEKOLAH'
            ELSE 'MERCHANT'
        END AS sumber_topup,
        COALESCE(u.username, '-') AS petugas_topup
    FROM topup tp
    JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
    LEFT JOIN users u ON tp.petugas_id = u.id
    WHERE $filter_topup_valid

    UNION ALL

    SELECT 
        p.tanggal AS waktu,
        '-' AS nama_siswa,
        '-' AS rfid_uid,
        k.nama AS nama_kantin,
        p.jumlah AS nominal,
        'Penarikan' AS jenis,
        '-' AS sumber_topup,
        '-' AS petugas_topup
    FROM penarikan p
    JOIN kantin k ON p.id_kantin = k.id

    UNION ALL

    SELECT 
        l.tanggal AS waktu,
        pengirim.nama_lengkap AS nama_siswa,
        pengirim.rfid_uid,
        penerima.nama_lengkap AS nama_kantin,
        l.jumlah AS nominal,
        'Transfer Keluar' AS jenis,
        '-' AS sumber_topup,
        '-' AS petugas_topup
    FROM log_transfer l
    JOIN pendaftaran_siswa pengirim ON l.id_pengirim = pengirim.id
    JOIN pendaftaran_siswa penerima ON l.id_penerima = penerima.id

    UNION ALL

    SELECT 
        l.tanggal AS waktu,
        penerima.nama_lengkap AS nama_siswa,
        penerima.rfid_uid,
        pengirim.nama_lengkap AS nama_kantin,
        l.jumlah AS nominal,
        'Transfer Masuk' AS jenis,
        '-' AS sumber_topup,
        '-' AS petugas_topup
    FROM log_transfer l
    JOIN pendaftaran_siswa pengirim ON l.id_pengirim = pengirim.id
    JOIN pendaftaran_siswa penerima ON l.id_penerima = penerima.id

    ORDER BY waktu DESC
");

// if (!$log_result) {
//     die('Query error: ' . mysqli_error($conn));
// }

$last_log = mysqli_fetch_assoc($log_result);
$last_waktu = $last_log ? date('d M Y H:i', strtotime($last_log['waktu'])) : 'Belum ada aktivitas';
?>
<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>
<div class="container">
    <?php include 'partials/dashboard_cards.php'; ?>

    <ul class="nav nav-tabs mb-4 border-0 shadow-sm bg-primary-subtle p-2 rounded-pill justify-content-center" id="logTabs" role="tablist" style="overflow-x:auto;">
        <!-- ✅ TAB SEMUA (BARU) -->
        <li class="nav-item me-2" role="presentation">
            <button class="nav-link active rounded-pill px-4 py-2 fw-bold text-dark bg-light" id="semua-tab" data-bs-toggle="tab" data-bs-target="#semua" type="button" role="tab">
                📌 Semua
            </button>
        </li>

        <li class="nav-item me-2" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-bold text-dark bg-light" id="kantin-tab" data-bs-toggle="tab" data-bs-target="#kantin" type="button" role="tab">
                🍔 Kantin
            </button>
        </li>
        <li class="nav-item me-2" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-bold text-dark bg-light" id="transfer-keluar-tab" data-bs-toggle="tab" data-bs-target="#transfer-keluar" type="button" role="tab">
                💸 Transfer Keluar
            </button>
        </li>
        <li class="nav-item me-2" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-bold text-dark bg-light" id="transfer-masuk-tab" data-bs-toggle="tab" data-bs-target="#transfer-masuk" type="button" role="tab">
                💰 Transfer Masuk
            </button>
        </li>
        <li class="nav-item me-2" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-bold text-dark bg-light" id="topup-tab" data-bs-toggle="tab" data-bs-target="#topup" type="button" role="tab">
                🔼 Topup
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-bold text-dark bg-light" id="penarikan-tab" data-bs-toggle="tab" data-bs-target="#penarikan" type="button" role="tab">
                🔽 Penarikan
            </button>
        </li>
    </ul>

    <?php
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h4 id="judul-laporan" class="mb-0 flex-grow-1">📄 Laporan Semua</h4>

        <div class="d-flex align-items-center gap-2">
            <label for="filter-bulan" class="mb-0 me-1">Bulan:</label>
            <select id="filter-bulan" class="form-select form-select-sm">
                <option value="">Bulan</option>
                <?php foreach ($bulanIndo as $num => $nama): ?>
                    <option value="<?= $num ?>" <?= ($num == date('n')) ? 'selected' : '' ?>><?= $nama ?></option>
                <?php endforeach; ?>
            </select>

            <label for="filter-tahun" class="mb-0 ms-2 me-1">Tahun:</label>
            <select id="filter-tahun" class="form-select form-select-sm">
                <?php for ($y = 2023; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <button class="btn btn-success px-3 ms-2" onclick="exportLaporan()">Export</button>
        </div>
    </div>

    <div class="tab-content" id="logTabsContent">
        <?php
        mysqli_data_seek($log_result, 0);

        $logs = [
            'Semua' => [],
            'Pembelian' => [],
            'Topup' => [],
            'Penarikan' => [],
            'Transfer Keluar' => [],
            'Transfer Masuk' => []
        ];

        while ($log = mysqli_fetch_assoc($log_result)) {
            $logs['Semua'][] = $log;
            if (isset($logs[$log['jenis']])) {
                $logs[$log['jenis']][] = $log;
            }
        }

        function renderJenisBadge($jenis, $sumber_topup = '-', $petugas_topup = '-') {
            $badgeClass = 'bg-light text-dark';
            switch ($jenis) {
                case 'Pembelian': $badgeClass = 'bg-danger'; break;
                case 'Topup': $badgeClass = 'bg-success'; break;
                case 'Penarikan': $badgeClass = 'bg-warning text-dark'; break;
                case 'Transfer Keluar': $badgeClass = 'bg-secondary'; break;
                case 'Transfer Masuk': $badgeClass = 'bg-primary'; break;
            }

            $html = '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($jenis) . '</span>';

            if ($jenis === 'Topup') {
                $src = strtoupper(trim((string)$sumber_topup));
                if ($src === 'MERCHANT') {
                    $html .= ' <span class="badge bg-info text-dark ms-1">Merchant</span>';
                } else {
                    $html .= ' <span class="badge bg-dark ms-1">Sekolah</span>';
                }

                // tampilkan petugas kecil di bawah badge (biar cepat kebaca)
                if (!empty($petugas_topup) && $petugas_topup !== '-') {
                    $html .= '<div class="small text-muted mt-1">Petugas: ' . htmlspecialchars($petugas_topup) . '</div>';
                }
            }

            return $html;
        }

        function renderLogTable($entries, $mode = '')
        {
            if (empty($entries)) {
                return '<p class="text-muted">Tidak ada data.</p>';
            }

            ob_start();

            // mode: semua / jenis tertentu
            if ($mode === 'semua') {
                $columns = ['Waktu', 'Nama', 'Jenis', 'Detail', 'Nominal'];
            } else {
                $jenis = $entries[0]['jenis'];

                switch ($jenis) {
                    case 'Pembelian':
                        $columns = ['Waktu', 'Nama Siswa', 'Jenis', 'Kantin', 'Nominal'];
                        break;
                    case 'Transfer Keluar':
                        $columns = ['Waktu', 'Nama Siswa', 'Jenis', 'Penerima', 'Nominal'];
                        break;
                    case 'Transfer Masuk':
                        $columns = ['Waktu', 'Nama Siswa', 'Jenis', 'Pengirim', 'Nominal'];
                        break;
                    case 'Topup':
                        // ✅ Tambah kolom Petugas di tab Topup
                        $columns = ['Waktu', 'Nama Siswa', 'Jenis', 'Petugas Topup', 'Nominal'];
                        break;
                    case 'Penarikan':
                        $columns = ['Waktu', 'Jenis', 'Kantin', 'Nominal'];
                        break;
                    default:
                        $columns = ['Waktu', 'Nama Siswa', 'Jenis', 'Nominal'];
                }
            }
        ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-success">
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?= $col ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $log): ?>
                            <tr data-waktu="<?= htmlspecialchars($log['waktu']) ?>">
                                <?php foreach ($columns as $col): ?>
                                    <td>
                                        <?php
                                        switch ($col) {
                                            case 'Waktu':
                                                echo date('d M Y H:i', strtotime($log['waktu']));
                                                break;

                                            case 'Nama Siswa':
                                                echo htmlspecialchars($log['nama_siswa']);
                                                break;

                                            case 'Nama':
                                                echo htmlspecialchars(($log['nama_siswa'] ?? '-') !== '-' ? $log['nama_siswa'] : '-');
                                                break;

                                            case 'Jenis':
                                                echo renderJenisBadge(
                                                    (string)$log['jenis'],
                                                    (string)($log['sumber_topup'] ?? '-')
                                                );
                                                break;

                                            case 'Kantin':
                                            case 'Pengirim':
                                            case 'Penerima':
                                                echo htmlspecialchars($log['nama_kantin']);
                                                break;

                                            case 'Petugas Topup':
                                                echo htmlspecialchars((string)($log['petugas_topup'] ?? '-'));
                                                break;

                                            case 'Detail':
                                                // tampilkan detail ringkas: kantin/penerima/pengirim/penarikan
                                                $jenis = (string)($log['jenis'] ?? '');
                                                if ($jenis === 'Pembelian') {
                                                    echo 'Kantin: ' . htmlspecialchars((string)$log['nama_kantin']);
                                                } elseif ($jenis === 'Transfer Keluar') {
                                                    echo 'Ke: ' . htmlspecialchars((string)$log['nama_kantin']);
                                                } elseif ($jenis === 'Transfer Masuk') {
                                                    echo 'Dari: ' . htmlspecialchars((string)$log['nama_kantin']);
                                                } elseif ($jenis === 'Penarikan') {
                                                    echo 'Kantin: ' . htmlspecialchars((string)$log['nama_kantin']);
                                                } elseif ($jenis === 'Topup') {
                                                    $src = strtoupper((string)($log['sumber_topup'] ?? 'SEKOLAH'));
                                                    $pet = (string)($log['petugas_topup'] ?? '-');
                                                    echo ($src === 'MERCHANT' ? 'Merchant (Duitku)' : 'Sekolah/Manual');
                                                    if ($pet !== '-' && $pet !== '') echo '<div class="small text-muted">Petugas: ' . htmlspecialchars($pet) . '</div>';
                                                } else {
                                                    echo '-';
                                                }
                                                break;

                                            case 'Nominal':
                                                echo 'Rp ' . number_format((int)$log['nominal'], 0, ',', '.');
                                                break;
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
            return ob_get_clean();
        }
        ?>

        <!-- ✅ TAB SEMUA -->
        <div class="tab-pane fade show active" id="semua" role="tabpanel">
            <?= renderLogTable($logs['Semua'], 'semua') ?>
        </div>

        <div class="tab-pane fade" id="kantin" role="tabpanel">
            <?= renderLogTable($logs['Pembelian']) ?>
        </div>
        <div class="tab-pane fade" id="transfer-keluar" role="tabpanel">
            <?= renderLogTable($logs['Transfer Keluar']) ?>
        </div>
        <div class="tab-pane fade" id="transfer-masuk" role="tabpanel">
            <?= renderLogTable($logs['Transfer Masuk']) ?>
        </div>
        <div class="tab-pane fade" id="topup" role="tabpanel">
            <?= renderLogTable($logs['Topup']) ?>
        </div>
        <div class="tab-pane fade" id="penarikan" role="tabpanel">
            <?= renderLogTable($logs['Penarikan']) ?>
        </div>
    </div>
</div>

<script>
    function updateDashboard() {
        fetch('api/get_dashboard_data.php')
            .then(res => res.json())
            .then(data => {
                const formatRp = num => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

                document.getElementById('transaksi-hari-ini').innerText = data.total_transaksi_hari_ini + ' transaksi';
                document.getElementById('nominal-hari-ini').innerText = formatRp(data.total_nominal_transaksi_hari_ini);
                document.getElementById('total-transaksi').innerText = data.total_transaksi + ' transaksi';
                document.getElementById('total-nominal').innerText = formatRp(data.total_nominal_transaksi);
                document.getElementById('saldo-siswa').innerText = formatRp(data.total_saldo);
                document.getElementById('saldo-kantin').innerText = formatRp(data.total_nominal_kantin);
                document.getElementById('total-topup').innerText = formatRp(data.total_nominal_topup);
                document.getElementById('saldo-sekarang').innerText = formatRp(data.total_saldo_sekarang);

                const tbody = document.getElementById('log-transaksi-body');
                if (tbody) {
                    tbody.innerHTML = '';
                    data.log.forEach(log => {
                        const row = `<tr>
                            <td>${log.waktu}</td>
                            <td>${log.nama_siswa}</td>
                            <td>${log.jenis}</td>
                            <td>${log.nama_kantin}</td>
                            <td>${formatRp(log.nominal)}</td>
                        </tr>`;
                        tbody.innerHTML += row;
                    });
                }
            })
            .catch(err => console.error('Gagal ambil data:', err));
    }

    updateDashboard();
    setInterval(updateDashboard, 3000);
</script>

<script>
    function filterTabel() {
        const bulan = document.getElementById('filter-bulan').value;
        const tahun = document.getElementById('filter-tahun').value;

        document.querySelectorAll('.tab-pane table tbody tr').forEach(row => {
            const waktuStr = row.getAttribute('data-waktu');
            if (!waktuStr) return;

            const date = new Date(waktuStr);
            const cocokBulan = !bulan || (date.getMonth() + 1 == parseInt(bulan));
            const cocokTahun = !tahun || (date.getFullYear() == parseInt(tahun));

            row.style.display = (cocokBulan && cocokTahun) ? '' : 'none';
        });
    }

    document.getElementById('filter-bulan').addEventListener('change', filterTabel);
    document.getElementById('filter-tahun').addEventListener('change', filterTabel);
    window.addEventListener('DOMContentLoaded', filterTabel);
</script>

<script>
    const tabTitles = {
        'semua': '📄 Laporan Semua',
        'kantin': '📄 Laporan Kantin',
        'transfer-keluar': '📄 Laporan Transfer Keluar',
        'transfer-masuk': '📄 Laporan Transfer Masuk',
        'topup': '📄 Laporan Topup',
        'penarikan': '📄 Laporan Penarikan'
    };

    const judulLaporan = document.getElementById('judul-laporan');

    document.querySelectorAll('#logTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target').substring(1);
            judulLaporan.innerText = tabTitles[targetId] || '📄 Laporan';
        });
    });

    function exportLaporan() {
        const activeTab = document.querySelector('#logTabs button.active');
        const jenis = activeTab ? activeTab.getAttribute('data-bs-target').substring(1) : 'semua';
        const bulan = document.getElementById('filter-bulan').value;
        const tahun = document.getElementById('filter-tahun').value;

        const params = new URLSearchParams({ jenis, bulan, tahun });
        window.open('export_excel.php?' + params.toString(), '_blank');
    }
</script>

<?php include 'inc/footer.php'; ?>
