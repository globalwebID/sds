<?php
$id = (int)($_GET['id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$currentPage = max(1, (int)($_GET['halaman'] ?? 1));
$limit = 10;
$offset = ($currentPage - 1) * $limit;

$searchActive = $search !== '';
$nama_ekskul = '';

// Ambil nama ekskul
$stmt = $conn->prepare("SELECT nama_ekskul FROM ekstrakurikuler WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nama_ekskul);
$stmt->fetch();
$stmt->close();

// Build WHERE & Parameter Binding
$where = "WHERE es.ekstrakurikuler_id = ?";
$params = [$id];
$types = "i";

if ($searchActive) {
    $where .= " AND (ps.nama_lengkap LIKE ? OR ps.nisn LIKE ? OR ps.nipd LIKE ?)";
    $searchLike = "%$search%";
    array_push($params, $searchLike, $searchLike, $searchLike);
    $types .= "sss";
}

// Data Query
$sql = "
    SELECT 
    ps.id AS siswa_id,
    ps.nipd,
    ps.nama_lengkap,
    k.nama_kelas,
    k.tingkat_id,
    tk.nama_tingkat,
    e.tahun_ajaran,
    es.id AS es_id,
    ng.nilai AS nilai_ganjil,
    ne.nilai AS nilai_genap
FROM ekstrakurikuler_siswa es
JOIN pendaftaran_siswa ps ON ps.id = es.siswa_id
JOIN siswa_kelas sk ON sk.siswa_id = ps.id
JOIN kelas k ON k.id = sk.kelas_id
JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
JOIN ekstrakurikuler e ON e.id = es.ekstrakurikuler_id
    AND e.tahun_ajaran = sk.tahun_ajaran
    AND k.tahun_ajaran = sk.tahun_ajaran
LEFT JOIN nilai_ekskul ng ON ng.siswa_id = ps.id AND ng.ekskul_id = es.ekstrakurikuler_id AND ng.semester = 'Ganjil'
LEFT JOIN nilai_ekskul ne ON ne.siswa_id = ps.id AND ne.ekskul_id = es.ekstrakurikuler_id AND ne.semester = 'Genap'
$where
ORDER BY ps.nama_lengkap ASC
LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();
$stmt->close();

// Hitung total data untuk pagination
$countSql = "
    SELECT COUNT(*) AS total
    FROM ekstrakurikuler_siswa es
    JOIN pendaftaran_siswa ps ON es.siswa_id = ps.id
    JOIN kelas k ON ps.kelas_id = k.id
    $where
";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalData = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$countStmt->close();

$totalPages = ceil($totalData / $limit);
?>

<div class="topbar">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto">
                <a href="ekskul" class="btn btn-secondary">Kembali</a>
                <a href="ekskul_nilai_siswa?ekskul_id=<?= $id ?>&mode=massal&semester=Ganjil" class="btn btn-success">Input Nilai Sem.Ganjil</a>
                <a href="ekskul_nilai_siswa?ekskul_id=<?= $id ?>&mode=massal&semester=Genap" class="btn btn-success">Input Nilai Sem.Genap</a>
                <a href="ekskul_absen_siswa?ekskul_id=<?= $id ?>" class="btn btn-warning">Absen Siswa</a>
                <a href="ekskul_rekap_absen?ekskul_id=<?= $id ?>" class="btn btn-primary">Rekap Absen</a>
            </div>

            <div class="col-auto ms-auto text-end">
                <form method="get" action="ekskul_lihat_siswa" class="row row-cols-md-auto align-items-center g-1">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                    <div class="col-12">
                        <input type="text" class="form-control" name="search" placeholder="Nama, NISN, NIPD..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Tampilkan</button>
                        <?php if ($search): ?>
                            <a href="ekskul_lihat_siswa?id=<?= htmlspecialchars($id) ?>" class="btn btn-danger">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mt-5">
    <?php if ($search): ?>
        <div class="filter-info filter-cari mt-2">
            <strong>🔍 Filter aktif:</strong>
            <span style="background:#d1ecf1; color:#0c5460; padding:4px 8px; margin:5px; border-radius:4px;">
                Nama: <em><?= htmlspecialchars($search) ?></em>
                <a href="ekskul_lihat_siswa?id=<?= htmlspecialchars($id) ?>" class="ms-2 text-decoration-none">❌</a>
            </span>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-message">
                <?= $_SESSION['error'] ?>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-message">
                <?= $_SESSION['success'] ?>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <div class="card-header">
        <h4>Daftar Siswa di Ekstrakurikuler: <?= htmlspecialchars($nama_ekskul) ?></h4>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-sm table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <!-- <th>TAHUN AJARAN</th> -->
                    <th>NAMA PESERTA DIDIK</th>
                    <th>NIPD</th>
                    <th>ROMBEL</th>
                    <th class="text-center">GANJIL</th>
                    <th class="text-center">GENAP</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && $data->num_rows > 0): $no = $offset + 1; ?>
                    <?php while ($siswa = $data->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <!-- <td><?= htmlspecialchars($siswa['tahun_ajaran']) ?></td> -->
                            <td>
                                <a href="student_view?id=<?= $siswa['siswa_id'] ?>#ekskul">
                                    <?= htmlspecialchars($siswa['nama_lengkap']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($siswa['nipd']) ?></td>
                            <td><?= angkaKeRomawi($siswa['nama_tingkat']) ?> <?= htmlspecialchars($siswa['nama_kelas']) ?></td>
                            <td class="text-center"><?= is_numeric($siswa['nilai_ganjil']) ? $siswa['nilai_ganjil'] : '-' ?></td>
                            <td class="text-center"><?= is_numeric($siswa['nilai_genap']) ? $siswa['nilai_genap'] : '-' ?></td>
                            <td class="text-end">
                                <a href="ekskul_hapus_siswa?id=<?= $siswa['es_id'] ?>&ekskul_id=<?= $id ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus siswa ini dari ekstrakurikuler?')">Hapus Anggota</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada siswa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="card-body">
        <nav>
            <ul class="pagination justify-content-center">
                <?php
                $baseQuery = $_GET;
                unset($baseQuery['halaman']);
                $baseUrl = 'ekskul_lihat_siswa';

                $range = 2;
                $start = max(1, $currentPage - $range);
                $end = min($totalPages, $currentPage + $range);

                if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $currentPage - 1])) ?>">&laquo;</a>
                    </li>
                <?php endif;

                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor;

                if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $currentPage + 1])) ?>">&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>