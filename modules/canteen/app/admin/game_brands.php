<?php
include 'inc/fungsi.php';
include 'inc/game_helpers.php';
game_admin_role_guard(['admin', 'superadmin']);

$hasBrands = game_admin_table_exists($conn, 'game_brands');
$successMsg = '';
$errorMsg = '';

$uploadDirFs = dirname(__DIR__) . '/uploads/game-logo/';
$uploadDirDb = 'uploads/game-logo/';

if (!is_dir($uploadDirFs)) {
    @mkdir($uploadDirFs, 0755, true);
}

function game_brand_logo_url(?string $logo): string
{
    $logo = trim((string)$logo);
    if ($logo === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $logo)) {
        return $logo;
    }

    return '../' . ltrim($logo, '/');
}

function game_brand_logo_delete_file(string $path): void
{
    $path = trim($path);
    if ($path === '') return;

    if (preg_match('~^https?://~i', $path)) {
        return;
    }

    $full = dirname(__DIR__) . '/' . ltrim($path, '/');
    if (is_file($full)) {
        @unlink($full);
    }
}

if ($hasBrands && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $brandId = (int)($_POST['brand_id'] ?? 0);

    if ($brandId <= 0) {
        $errorMsg = 'Brand tidak valid.';
    } else {
        $qBrand = mysqli_query($conn, "SELECT * FROM game_brands WHERE id = {$brandId} LIMIT 1");
        $brandRow = $qBrand ? mysqli_fetch_assoc($qBrand) : null;

        if (!$brandRow) {
            $errorMsg = 'Data brand tidak ditemukan.';
        } else {
            if ($action === 'delete_logo') {
                $oldLogo = (string)($brandRow['logo'] ?? '');
                game_brand_logo_delete_file($oldLogo);

                $stmt = mysqli_prepare($conn, "UPDATE game_brands SET logo = NULL WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $brandId);
                    if (mysqli_stmt_execute($stmt)) {
                        $successMsg = 'Logo brand berhasil dihapus.';
                    } else {
                        $errorMsg = 'Gagal menghapus logo dari database.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errorMsg = 'Prepare query gagal: ' . mysqli_error($conn);
                }
            }

            if ($action === 'upload_logo' && $errorMsg === '') {
                if (!isset($_FILES['logo']) || !is_array($_FILES['logo'])) {
                    $errorMsg = 'File logo tidak ditemukan.';
                } elseif ((int)$_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                    $errorMsg = 'Upload logo gagal. Kode error: ' . (int)$_FILES['logo']['error'];
                } else {
                    $tmpName = (string)$_FILES['logo']['tmp_name'];
                    $origName = (string)$_FILES['logo']['name'];
                    $fileSize = (int)$_FILES['logo']['size'];

                    if ($fileSize <= 0) {
                        $errorMsg = 'Ukuran file tidak valid.';
                    } elseif ($fileSize > 2 * 1024 * 1024) {
                        $errorMsg = 'Ukuran logo maksimal 2 MB.';
                    } else {
                        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                        $mime = $finfo ? (string)finfo_file($finfo, $tmpName) : '';
                        if ($finfo) {
                            finfo_close($finfo);
                        }

                        $allowed = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp',
                        ];

                        if (!isset($allowed[$mime])) {
                            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                                $mime = $ext === 'jpeg' ? 'image/jpeg' : ('image/' . $ext);
                                if ($ext === 'jpg') $mime = 'image/jpeg';
                            }
                        }

                        if (!isset($allowed[$mime])) {
                            $errorMsg = 'Format logo harus JPG, PNG, atau WEBP.';
                        } else {
                            $ext = $allowed[$mime];
                            $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string)$brandRow['name']);
                            $fileName = 'brand-' . $brandId . '-' . $safeName . '-' . time() . '.' . $ext;
                            $destFs = $uploadDirFs . $fileName;
                            $destDb = $uploadDirDb . $fileName;

                            if (!move_uploaded_file($tmpName, $destFs)) {
                                $errorMsg = 'Gagal memindahkan file upload ke folder tujuan.';
                            } else {
                                $oldLogo = (string)($brandRow['logo'] ?? '');

                                $stmt = mysqli_prepare($conn, "UPDATE game_brands SET logo = ? WHERE id = ?");
                                if ($stmt) {
                                    mysqli_stmt_bind_param($stmt, 'si', $destDb, $brandId);
                                    if (mysqli_stmt_execute($stmt)) {
                                        game_brand_logo_delete_file($oldLogo);
                                        $successMsg = 'Logo brand berhasil diperbarui.';
                                    } else {
                                        @unlink($destFs);
                                        $errorMsg = 'Gagal menyimpan logo ke database.';
                                    }
                                    mysqli_stmt_close($stmt);
                                } else {
                                    @unlink($destFs);
                                    $errorMsg = 'Prepare query gagal: ' . mysqli_error($conn);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

$brands = [];
if ($hasBrands) {
    $sql = "
        SELECT
            gb.id,
            gb.name,
            gb.logo,
            gb.need_zone_id,
            gb.created_at,
            COUNT(gp.id) AS total_products,
            COALESCE(MIN(gp.profit), 0) AS min_profit,
            COALESCE(MAX(gp.profit), 0) AS max_profit
        FROM game_brands gb
        LEFT JOIN game_products gp ON gp.brand_id = gb.id
        GROUP BY gb.id, gb.name, gb.logo, gb.need_zone_id, gb.created_at
        ORDER BY gb.name ASC
    ";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $brands[] = $row;
        }
    }
}

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="container">
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4" style="background:linear-gradient(135deg,#7c3aed,#2563eb); color:#fff;">
            <div class="justify-content-between align-items-lg-center gap-3">
                <div>
                    <h3 class="mb-2">Logo Brand Game</h3>
                    <p class="mb-3 opacity-75">Upload dan kelola logo untuk setiap brand game. Logo ini akan dipakai di halaman ringkasan dan transaksi admin.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="game.php" class="btn btn-light btn-sm fw-semibold">Kembali ke Game Admin</a>
                    <a href="game_margin.php" class="btn btn-warning btn-sm fw-semibold">Margin Brand</a>
                    <a href="game_transactions.php" class="btn btn-dark btn-sm fw-semibold">Detail Transaksi</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$hasBrands): ?>
        <div class="alert alert-warning shadow-sm border-0">
            Tabel <strong>game_brands</strong> belum ditemukan di database ini.
        </div>
    <?php else: ?>
        <?php if ($successMsg !== ''): ?>
            <div class="alert alert-success shadow-sm border-0"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <?php if ($errorMsg !== ''): ?>
            <div class="alert alert-danger shadow-sm border-0"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Daftar Brand</h5>
                    <span class="badge bg-primary-subtle text-primary-emphasis"><?= number_format(count($brands), 0, ',', '.') ?> brand</span>
                </div>

                <?php if (empty($brands)): ?>
                    <div class="text-muted">Belum ada data brand game.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:90px;">Logo</th>
                                    <th>Brand</th>
                                    <th>Total Produk</th>
                                    <th>Need Zone ID</th>
                                    <th>Margin</th>
                                    <th style="width:320px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($brands as $brand): ?>
                                    <?php $logoUrl = game_brand_logo_url($brand['logo'] ?? ''); ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($logoUrl !== ''): ?>
                                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($brand['name']) ?>" style="width:56px;height:56px;object-fit:contain;border-radius:12px;background:#fff;border:1px solid #e5e7eb;padding:6px;">
                                            <?php else: ?>
                                                <div style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:#f3f4f6;border:1px dashed #d1d5db;margin:auto;font-size:12px;color:#6b7280;">
                                                    No Logo
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($brand['name']) ?></div>
                                            <small class="text-muted">Dibuat: <?= htmlspecialchars((string)$brand['created_at']) ?></small>
                                        </td>
                                        <td><?= number_format((int)$brand['total_products'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ((int)$brand['need_zone_id'] === 1): ?>
                                                <span class="badge bg-warning text-dark">Ya</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tidak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= game_admin_rupiah((int)$brand['min_profit']) ?>
                                            <?php if ((int)$brand['max_profit'] !== (int)$brand['min_profit']): ?>
                                                <small class="text-muted d-block">s/d <?= game_admin_rupiah((int)$brand['max_profit']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" enctype="multipart/form-data" class="row g-2 mb-2">
                                                <input type="hidden" name="action" value="upload_logo">
                                                <input type="hidden" name="brand_id" value="<?= (int)$brand['id'] ?>">
                                                <div class="col-8">
                                                    <input type="file" name="logo" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                                                </div>
                                                <div class="col-4 d-grid">
                                                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Upload</button>
                                                </div>
                                            </form>

                                            <?php if (!empty($brand['logo'])): ?>
                                                <form method="post" onsubmit="return confirm('Hapus logo brand ini?');">
                                                    <input type="hidden" name="action" value="delete_logo">
                                                    <input type="hidden" name="brand_id" value="<?= (int)$brand['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold">Hapus Logo</button>
                                                </form>
                                            <?php else: ?>
                                                <small class="text-muted">Belum ada logo tersimpan.</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-muted mt-3">
                        Format yang disarankan: PNG/WEBP kotak, background transparan, maksimal 2 MB.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'inc/footer.php'; ?>