<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekstrakurikuler_id'], $_POST['siswa_id'])) {
    $ekstrakurikuler_id = (int)$_POST['ekstrakurikuler_id'];
    $siswa_ids = $_POST['siswa_id'];

    if (is_array($siswa_ids) && $ekstrakurikuler_id > 0) {
        $stmt = $conn->prepare("INSERT IGNORE INTO ekstrakurikuler_siswa (ekstrakurikuler_id, siswa_id) VALUES (?, ?)");

        foreach ($siswa_ids as $siswa_id) {
            $siswa_id = (int)$siswa_id;
            if ($siswa_id > 0) {
                $stmt->bind_param("ii", $ekstrakurikuler_id, $siswa_id);
                $stmt->execute();
            }
        }

        $stmt->close();

        header("Location: ekskul_tambah_siswa?id=" . $ekstrakurikuler_id . "&success=1");
        exit;
    }
}

// Filter dan pagination
$limit = 10;
$currentPage = isset($_GET['halaman']) && is_numeric($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($currentPage - 1) * $limit;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = $_GET['search'] ?? '';
$tahunAjaran = $_GET['tahun'] ?? '';
$kelas = $_GET['kelas'] ?? '';

// Ambil nama ekstrakurikuler
$nama_ekskul = '';
if ($id > 0) {
    $query = $conn->prepare("SELECT nama_ekskul FROM ekstrakurikuler WHERE id = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $query->bind_result($nama_ekskul);
    $query->fetch();
    $query->close();
}

// Fungsi untuk menghapus parameter dari URL
function buildUrlWithout($excludeKey)
{
    $url = 'index?';
    $params = $_GET;
    unset($params[$excludeKey]);
    return $url . http_build_query($params);
}

// Siapkan kondisi WHERE dan parameter
$where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $where .= " AND (ps.nama_lengkap LIKE ? OR ps.nisn LIKE ? OR ps.nipd LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($tahunAjaran)) {
    $where .= " AND k.tahun_ajaran = ?";
    $params[] = $tahunAjaran;
    $types .= 's';
}

if (!empty($kelas)) {
    $where .= " AND k.nama_kelas = ?";
    $params[] = $kelas;
    $types .= 's';
}

// Hitung total data
$countQuery = "SELECT COUNT(*) FROM pendaftaran_siswa ps 
               JOIN kelas k ON ps.kelas_id = k.id 
               $where
               AND NOT EXISTS (
                   SELECT 1 FROM ekstrakurikuler_siswa es
                   WHERE es.siswa_id = ps.id AND es.ekstrakurikuler_id = ?
               )";

$countTypes = $types . 'i'; // id ekskul di akhir
$countParams = $params;
$countParams[] = $id;

$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($countTypes, ...$countParams);
$countStmt->execute();
$countStmt->bind_result($totalRows);
$countStmt->fetch();
$countStmt->close();

$totalPages = ceil($totalRows / $limit);

// Ambil data siswa sesuai filter dan pagination
$query = "SELECT 
        ps.id, 
        ps.nipd, 
        ps.nama_lengkap,
        tk.nama_tingkat, 
        k.nama_kelas
    FROM pendaftaran_siswa ps
    JOIN siswa_kelas sk ON sk.siswa_id = ps.id
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    JOIN ekstrakurikuler e ON e.tahun_ajaran = sk.tahun_ajaran
          $where
          AND NOT EXISTS (
            SELECT 1 
            FROM ekstrakurikuler_siswa es
            WHERE es.siswa_id = ps.id 
              AND es.ekstrakurikuler_id = ?
        )
    ORDER BY ps.nama_lengkap ASC
    LIMIT ? OFFSET ? ";

// $query = "
//     SELECT 
//         ps.id, 
//         ps.nipd, 
//         ps.nama_lengkap, 
//         k.nama_kelas
//     FROM pendaftaran_siswa ps
//     JOIN siswa_kelas sk ON sk.id_siswa = ps.id
//     JOIN kelas k ON k.id = sk.id_kelas
//     JOIN ekstrakurikuler e ON e.tahun_ajaran = sk.tahun_ajaran
//     WHERE 1=1
//         $where
//         AND NOT EXISTS (
//             SELECT 1 
//             FROM ekstrakurikuler_siswa es
//             WHERE es.siswa_id = ps.id 
//               AND es.ekstrakurikuler_id = ?
//         )
//     ORDER BY ps.nama_lengkap ASC
//     LIMIT ? OFFSET ?
// ";

$typesFull = $types . 'i' . 'ii'; // tambahkan ekskul_id, limit, offset
$paramsFull = $params;
$paramsFull[] = $id;
$paramsFull[] = $limit;
$paramsFull[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($typesFull, ...$paramsFull);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="topbar">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto d-sm-block">
                <div class="col-12">
                    <a href="ekskul" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
            <div class="col-auto ms-auto text-end">
                <form method="get" action="ekskul_tambah_siswa" class="row row-cols-md-auto align-items-center g-1">
                    <input type="hidden" name="" value="ekskul_tambah_siswa">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                    <div class="col-12">
                        <input type="text" class="form-control" name="search" placeholder="Nama, NISN, NIPD..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-12">
                        <select name="tahun" class="form-select">
                            <option value="">- Semua Tahun -</option>
                            <?php
                            $tahunList = $conn->query("SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC");
                            while ($row = $tahunList->fetch_assoc()):
                                $selected = ($row['tahun_ajaran'] === $tahunAjaran) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($row['tahun_ajaran']) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($row['tahun_ajaran']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <select name="kelas" class="form-select">
                            <option value="">- Semua Kelas -</option>
                            <?php
                            $kelasList = $conn->query("SELECT DISTINCT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
                            while ($row = $kelasList->fetch_assoc()):
                                $selected = ($row['nama_kelas'] === $kelas) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($row['nama_kelas']) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($row['nama_kelas']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Tampilkan</button>
                        <?php if ($search || $tahunAjaran || $kelas): ?>
                            <a href="ekskul_tambah_siswa?id=<?= htmlspecialchars($id) ?>" class="btn btn-danger">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card mt-5">
            <?php if ($search || $tahunAjaran || $kelas): ?>
                <div class="filter-info filter-cari">
                    <strong>🔍 Filter aktif:</strong>

                    <?php if ($search): ?>
                        <span style="background:#d1ecf1; color:#0c5460; padding:4px 8px; margin:5px; border-radius:4px;">
                            Nama: <em><?= htmlspecialchars($search) ?></em>
                            <a href="<?= buildUrlWithout('search') ?>" style="color:#0c5460; margin-left:6px; text-decoration:none;">❌</a>
                        </span>
                    <?php endif; ?>

                    <?php if ($tahunAjaran): ?>
                        <span style="background:#fff3cd; color:#856404; padding:4px 8px; margin:5px; border-radius:4px;">
                            Tahun: <em><?= htmlspecialchars($tahunAjaran) ?></em>
                            <a href="<?= buildUrlWithout('tahun') ?>" style="color:#856404; margin-left:6px; text-decoration:none;">❌</a>
                        </span>
                    <?php endif; ?>

                    <?php if ($kelas): ?>
                        <span style="background:#e2e3e5; color:#41464b; padding:4px 8px; margin:5px; border-radius:4px;">
                            Kelas: <em><?= htmlspecialchars($kelas) ?></em>
                            <a href="<?= buildUrlWithout('kelas') ?>" style="color:#41464b; margin-left:6px; text-decoration:none;">❌</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div style="padding: 10px; margin: 10px 0; background: #d4edda; color: #155724; border-radius: 4px;">
                    ✅ Siswa berhasil ditambahkan ke ekstrakurikuler.
                </div>
            <?php endif; ?>
            <div class="card-header">
                <h4 class="card-title">Tambah Peserta Ekstrakurikuler: <strong><?= htmlspecialchars($nama_ekskul) ?></strong></h4>
            </div>
            <div class="table-responsive">
                <form method="POST" action="">
                    <input type="hidden" name="ekstrakurikuler_id" value="<?= htmlspecialchars($id) ?>">

                    <table class="table table-striped table-sm table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="checkAll"></th>
                                <th>NAMA PESERTA DIDIK</th>
                                <th>NIPD</th>
                                <th>ROMBEL</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="siswa_id[]" value="<?= $s['id'] ?>"></td>
                                    <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($s['nipd']) ?></td>
                                    <td><?= angkaKeRomawi($s['nama_tingkat']) ?> <?= htmlspecialchars($s['nama_kelas']) ?></td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="ekstrakurikuler_id" value="<?= htmlspecialchars($id) ?>">
                                            <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-primary">Tambah</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="card-body">
                        <div class="pagination" style="justify-content: space-between;">
                            <div class="left">
                                <button type="submit" class="btn btn-success">Tambahkan</button>
                            </div>
                            <div class="right">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-md">
                                        <?php
                                        $baseQuery = $_GET;
                                        unset($baseQuery['halaman']);
                                        $baseUrl = 'index';

                                        // Tentukan berapa halaman yang ingin ditampilkan sebelum/sesudah halaman aktif
                                        $range = 2; // tampilkan 2 halaman sebelum dan sesudah

                                        // Tombol « sebelumnya
                                        if ($currentPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $currentPage - 1])) ?>">&laquo;</a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;</span>
                                            </li>
                                        <?php endif; ?>

                                        <?php
                                        // Nomor halaman
                                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++):
                                            if ($i == $currentPage): ?>
                                                <li class="page-item active">
                                                    <span class="page-link"><?= $i ?></span>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $i])) ?>"><?= $i ?></a>
                                                </li>
                                        <?php endif;
                                        endfor;
                                        ?>

                                        <!-- Tombol » berikutnya -->
                                        <?php if ($currentPage < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $currentPage + 1])) ?>">&raquo;</a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>

                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('checkAll').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="siswa_id[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>