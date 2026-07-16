<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'katalog';
require __DIR__ . '/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;

$q = trim((string)($_GET['q'] ?? ''));
$categoryId = (int)($_GET['kategori'] ?? 0);
$availability = (string)($_GET['ketersediaan'] ?? '');
if (!in_array($availability, ['', 'tersedia', 'habis'], true)) $availability = '';

$categories = [];
$result = $conn->query('SELECT id,nama FROM perpus_kategori_buku WHERE status_aktif=1 ORDER BY nama');
while ($result && ($row = $result->fetch_assoc())) $categories[] = $row;

$where = ['b.status_opac=1'];
$params = [];
$types = '';
if ($q !== '') {
    $where[] = '(b.judul LIKE ? OR b.isbn LIKE ? OR b.nomor_panggil LIKE ? OR p.nama LIKE ? OR a.nama LIKE ? OR s.nama LIKE ?)';
    $like = '%' . $q . '%';
    for ($i=0; $i<6; $i++) $params[] = $like;
    $types .= 'ssssss';
}
if ($categoryId > 0) {
    $where[] = 'b.kategori_id=?';
    $params[] = $categoryId;
    $types .= 'i';
}

$sql = "SELECT b.*,k.nama kategori_nama,tk.nama tipe_koleksi_nama,g.nama gmd_nama,
    COALESCE(p.nama,b.penerbit_teks) penerbit_nama,
    GROUP_CONCAT(DISTINCT a.nama ORDER BY a.nama SEPARATOR ', ') pengarang_nama,
    GROUP_CONCAT(DISTINCT s.nama ORDER BY s.nama SEPARATOR ', ') subyek_nama,
    COUNT(DISTINCT e.id) total_eksemplar,
    COUNT(DISTINCT CASE WHEN e.status='tersedia' THEN e.id END) tersedia
    FROM perpus_buku b
    LEFT JOIN perpus_kategori_buku k ON k.id=b.kategori_id
    LEFT JOIN perpus_tipe_koleksi tk ON tk.id=b.tipe_koleksi_id
    LEFT JOIN perpus_gmd g ON g.id=b.gmd_id
    LEFT JOIN perpus_penerbit p ON p.id=b.penerbit_id
    LEFT JOIN perpus_buku_pengarang ba ON ba.buku_id=b.id
    LEFT JOIN perpus_pengarang a ON a.id=ba.pengarang_id
    LEFT JOIN perpus_buku_subyek bs ON bs.buku_id=b.id
    LEFT JOIN perpus_subyek s ON s.id=bs.subyek_id
    LEFT JOIN perpus_buku_eksemplar e ON e.buku_id=b.id
    WHERE " . implode(' AND ', $where) . " GROUP BY b.id";
if ($availability === 'tersedia') $sql .= ' HAVING tersedia>0';
elseif ($availability === 'habis') $sql .= ' HAVING tersedia=0';
$sql .= ' ORDER BY b.judul LIMIT 1000';
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $refs = [$types]; foreach ($params as $index => &$value) $refs[] = &$value;
    call_user_func_array([$stmt, 'bind_param'], $refs); unset($value);
}
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . '/../partials/master_page_style.php';
?>
<div class="sds-master-page perpus-catalog">
    <div class="sds-hero"><div><h2>Katalog Perpustakaan</h2><p>Pencarian koleksi dan ketersediaan eksemplar dari database SDS.</p></div><div class="sds-hero-actions"><a class="btn btn-outline-primary" href="<?=perpus_h(sds_base_url('perpustakaan/opac/'))?>" target="_blank"><i data-feather="globe" class="me-1"></i>Buka OPAC Publik</a></div></div>
    <?php require __DIR__ . '/../partials/nav.php'; ?>
    <div class="card mb-3"><div class="card-body"><form method="get" class="catalog-filter"><input class="form-control form-control-sm search" name="q" value="<?= perpus_h($q) ?>" placeholder="Cari judul, pengarang, subyek, ISBN, nomor panggil..."><select class="form-select form-select-sm" name="kategori"><option value="0">Semua Kategori</option><?php foreach($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= $categoryId===(int)$category['id']?'selected':'' ?>><?= perpus_h($category['nama']) ?></option><?php endforeach; ?></select><select class="form-select form-select-sm" name="ketersediaan"><option value="">Semua Ketersediaan</option><option value="tersedia" <?= $availability==='tersedia'?'selected':'' ?>>Tersedia</option><option value="habis" <?= $availability==='habis'?'selected':'' ?>>Sedang Tidak Tersedia</option></select><button class="btn btn-sm btn-primary">Cari</button><a class="btn btn-sm btn-outline-secondary" href="katalog">Reset</a></form></div></div>
    <div class="d-flex justify-content-between align-items-center mb-3"><div><strong><?= number_format(count($books),0,',','.') ?></strong> judul ditemukan</div><div class="small text-muted">Maksimal 1.000 judul</div></div>
    <?php if (!$books): ?><div class="card"><div class="card-body text-center text-muted py-5">Koleksi tidak ditemukan.</div></div><?php else: ?><div class="book-grid"><?php foreach($books as $book): ?><article class="book-card"><?php if($book['sampul']): ?><img class="cover" src="../uploads/perpus/buku/<?= rawurlencode(basename($book['sampul'])) ?>" alt=""><?php else: ?><div class="cover-empty"><i data-feather="book-open"></i></div><?php endif; ?><div class="book-content"><div class="book-title"><?= perpus_h($book['judul']) ?></div><div class="meta"><?= perpus_h($book['pengarang_nama'] ?: 'Pengarang belum diisi') ?></div><div class="meta"><?= perpus_h($book['penerbit_nama'] ?: '-') ?><?= $book['tahun_terbit']?' · '.perpus_h($book['tahun_terbit']):'' ?></div><div class="meta mt-1">Panggil: <code><?= perpus_h($book['nomor_panggil'] ?: '-') ?></code></div><div class="mt-2"><span class="badge <?= (int)$book['tersedia']>0?'bg-success':'bg-secondary' ?>"><?= (int)$book['tersedia'] ?> tersedia dari <?= (int)$book['total_eksemplar'] ?></span></div><?php if($book['subyek_nama']): ?><div class="meta mt-2">Subyek: <?= perpus_h($book['subyek_nama']) ?></div><?php endif; ?><?php if($book['deskripsi_fisik']): ?><div class="description"><?= perpus_h($book['deskripsi_fisik']) ?></div><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?>
</div>
