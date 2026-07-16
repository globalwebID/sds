<?php
include 'inc/fungsi.php';
checkRole(['superadmin', 'admin']);

$search = trim($_GET['search'] ?? '');
$page   = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit  = 10;

$page = max(1, $page);
$offset = ($page - 1) * $limit;

// ==============================
// QUERY DATA SISWA (pagination + search)
// ==============================
$total_rows = 0;
$siswa = [];

$like = '%' . $search . '%';

if ($search !== '') {
    // total rows
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM pendaftaran_siswa
        WHERE nama_lengkap LIKE ? OR nipd LIKE ? OR nisn LIKE ? OR rfid_uid LIKE ?
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $total_rows = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // data rows
    $stmt = $conn->prepare("
        SELECT id, nama_lengkap, nisn, rfid_uid, saldo, blokir
        FROM pendaftaran_siswa
        WHERE nama_lengkap LIKE ? OR nipd LIKE ? OR nisn LIKE ? OR rfid_uid LIKE ?
        ORDER BY saldo DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ssssii", $like, $like, $like, $like, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $siswa = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $total_rows = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran_siswa"))['total'] ?? 0);

    $data = mysqli_query($conn, "
        SELECT id, nama_lengkap, nisn, rfid_uid, saldo, blokir
        FROM pendaftaran_siswa
        ORDER BY saldo DESC
        LIMIT $limit OFFSET $offset
    ");
    $siswa = $data ? mysqli_fetch_all($data, MYSQLI_ASSOC) : [];
}

// hitung total pages
$total_pages = ($total_rows > 0) ? (int)ceil($total_rows / $limit) : 0;

// info range tampilan
if ($total_rows > 0) {
    $startRow = $offset + 1;
    $endRow   = min($offset + $limit, $total_rows);
} else {
    $startRow = 0;
    $endRow   = 0;
}

// saldo total siswa
$saldo_siswa = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(saldo) AS total FROM pendaftaran_siswa"))['total'] ?? 0);

// helper escape
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// helper url halaman
function pageUrl($p, $search) {
    $params = ['page' => $p];
    if ($search !== '') $params['search'] = $search;
    return '?' . http_build_query($params);
}
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>

<div class="container">

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-people-fill text-success card-icon me-3"></i>
                    <div>
                        <h5>Total Siswa</h5>
                        <p class="mb-0 fw-bold"><?= (int)$total_rows ?> orang</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-success">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 text-white card-icon me-3"></i>
                    <div>
                        <h5 class="text-white">Total Saldo Siswa</h5>
                        <p class="mb-0 fw-bold text-white">Rp <?= number_format($saldo_siswa, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERT AJAX -->
    <div id="ajaxAlertWrap" style="display:none;"></div>

    <h4 class="mb-4">💳 DAFTAR KARTU PELAJAR SISWA</h4>

    <form method="get" class="d-flex mb-3">
        <input
            id="searchInput"
            type="text"
            name="search"
            class="form-control me-2"
            placeholder="Cari nama / NIPD / NISN / Scan Kartu..."
            value="<?= e($search) ?>"
            autofocus>
        <button class="btn btn-success me-2" type="submit">Cari</button>
        <a href="siswa.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover shadow-sm bg-white rounded">
            <thead class="table-success">
                <tr>
                    <th style="width:60px">#</th>
                    <th>Nama</th>
                    <th style="width:160px">NISN</th>
                    <!--<th style="width:180px">RFID UID</th>-->
                    <th style="width:140px">Saldo</th>
                    <th style="width:150px"></th>
                </tr>
            </thead>
            <tbody id="siswaTableBody">
                <?php if (!empty($siswa)): ?>
                    <?php $no = $offset + 1; ?>
                    <?php foreach ($siswa as $row): ?>
                        <?php
                        $id = (int)$row['id'];
                        $isBlocked = ((int)($row['blokir'] ?? 0) === 1);
                        $hasRFID   = !empty($row['rfid_uid']);

                        $labelBtn  = $hasRFID ? 'Edit Kode' : 'Scan Kode';
                        $btnClass  = $hasRFID ? 'btn-warning' : 'btn-primary';

                        $blokirBtnClass = $isBlocked ? 'btn-success' : 'btn-secondary';
                        $blokirBtnLabel = $isBlocked ? 'Buka Blokir' : 'Blokir Kartu';
                        ?>
                        <tr id="row-siswa-<?= $id ?>" data-id="<?= $id ?>" data-blokir="<?= $isBlocked ? 1 : 0 ?>">
                            <td><?= $no++ ?></td>
                            <td class="col-nama">
                                <?= e($row['nama_lengkap']) ?>
                                <?php if ($isBlocked): ?>
                                    <span class="badge bg-danger ms-1 badge-blokir">Diblokir</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['nisn']) ?></td>
                            <!--<td><?= e($row['rfid_uid']) ?></td>-->
                            <td>Rp <?= number_format((int)$row['saldo'], 0, ',', '.') ?></td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn <?= $blokirBtnClass ?> me-1 btn-blokir"
                                    data-id="<?= $id ?>"
                                    data-isblocked="<?= $isBlocked ? 1 : 0 ?>"
                                    onclick="openBlokirModal(this)">
                                    <?= e($blokirBtnLabel) ?>
                                </button>

                                <!--<button-->
                                <!--    type="button"-->
                                <!--    class="btn <?= $btnClass ?>"-->
                                <!--    onclick="openInputRFIDModal(<?= $id ?>, '<?= e($row['nama_lengkap']) ?>', '<?= e($row['rfid_uid']) ?>')">-->
                                <!--    <?= e($labelBtn) ?>-->
                                <!--</button>-->
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data siswa.</td>
                    </tr>
                <?php endif ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Menampilkan <strong><?= $startRow ?></strong>–<strong><?= $endRow ?></strong> dari <strong><?= (int)$total_rows ?></strong> data
                </div>

                <nav aria-label="Pagination siswa">
                    <ul class="pagination mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page <= 1) ? '#' : pageUrl(1, $search) ?>" aria-label="First">««</a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page <= 1) ? '#' : pageUrl($page - 1, $search) ?>" aria-label="Previous">«</a>
                        </li>

                        <?php
                        $maxButtons = 7;
                        $half = (int)floor($maxButtons / 2);

                        $start = max(1, $page - $half);
                        $end   = min($total_pages, $start + $maxButtons - 1);
                        $start = max(1, $end - $maxButtons + 1);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . e(pageUrl(1, $search)) . '">1</a></li>';
                            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = ($i === $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . e(pageUrl($i, $search)) . '">' . $i . '</a></li>';
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="' . e(pageUrl($total_pages, $search)) . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page >= $total_pages) ? '#' : pageUrl($page + 1, $search) ?>" aria-label="Next">»</a>
                        </li>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page >= $total_pages) ? '#' : pageUrl($total_pages, $search) ?>" aria-label="Last">»»</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Modal Input RFID -->
<div class="modal fade" id="inputRFIDModal" tabindex="-1" aria-labelledby="inputRFIDModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="proses_rfid.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Input Kode Kartu Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="back_page" value="<?= (int)($page ?? 1) ?>">
                <input type="hidden" name="back_search" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES) ?>">

                <input type="hidden" name="siswa_id" id="rfidSiswaId">
                <div class="mb-3">
                    <label class="form-label">Nama Siswa</label>
                    <input type="text" class="form-control text-center" id="rfidNama" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Scan Kartu RFID</label>
                    <input type="text" class="form-control text-center" id="rfid_uid" name="rfid_uid"
                           placeholder="Tempelkan kartu ke reader" required>
                    <small class="form-text text-muted">Pastikan kursor berada di kotak ini saat scan.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Blokir -->
<div class="modal fade" id="blokirModal" tabindex="-1" aria-labelledby="blokirModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="blokirForm" method="POST" action="javascript:void(0)">
                <div class="modal-header">
                    <h5 class="modal-title" id="blokirModalLabel">Konfirmasi Aksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p id="blokirModalText">Apakah Anda yakin?</p>
                    <input type="hidden" id="blokirId">
                    <input type="hidden" id="blokirAksi">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger" id="btnBlokirSubmit">Ya, Lanjutkan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ===== Alert helper =====
    function showAjaxAlert(type, message) {
        const wrap = document.getElementById('ajaxAlertWrap');
        wrap.style.display = 'block';
        wrap.innerHTML = `
          <div class="alert alert-${type} mt-3 position-relative" style="z-index: 1;">
            ${message}
          </div>
        `;
        setTimeout(() => {
            wrap.style.display = 'none';
            wrap.innerHTML = '';
        }, 3500);
    }

    // ===== RFID Modal =====
    function openInputRFIDModal(id, nama, rfid = '') {
        document.getElementById('rfidSiswaId').value = id;
        document.getElementById('rfidNama').value = nama;
        document.getElementById('rfid_uid').value = rfid;

        const modalEl = document.getElementById('inputRFIDModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        setTimeout(() => document.getElementById('rfid_uid').focus(), 300);
    }

    // ===== BLOKIR AJAX =====
    let currentBtn = null;

    function openBlokirModal(btn) {
        currentBtn = btn;

        const id = btn.getAttribute('data-id');
        const isBlocked = btn.getAttribute('data-isblocked') === '1';

        document.getElementById('blokirId').value = id;
        document.getElementById('blokirAksi').value = isBlocked ? 'buka' : 'blokir';

        document.getElementById('blokirModalText').textContent = isBlocked
            ? 'Apakah Anda yakin ingin membuka blokir kartu ini?'
            : 'Apakah Anda yakin ingin memblokir kartu ini?';

        const modal = new bootstrap.Modal(document.getElementById('blokirModal'));
        modal.show();
    }

    document.getElementById('blokirForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const id = document.getElementById('blokirId').value;
        const aksi = document.getElementById('blokirAksi').value;

        const submitBtn = document.getElementById('btnBlokirSubmit');
        submitBtn.disabled = true;

        fetch('ajax_blokir_kartu.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: `id=${encodeURIComponent(id)}&aksi=${encodeURIComponent(aksi)}`
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Gagal memproses permintaan.');
            return data;
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Gagal memproses.');

            // Update UI tombol + badge
            const nowBlocked = (data.blokir === 1);

            // tombol
            if (currentBtn) {
                currentBtn.classList.remove('btn-secondary', 'btn-success');
                currentBtn.classList.add(nowBlocked ? 'btn-success' : 'btn-secondary');
                currentBtn.textContent = nowBlocked ? 'Buka Blokir' : 'Blokir Kartu';
                currentBtn.setAttribute('data-isblocked', nowBlocked ? '1' : '0');
            }

            // badge
            const tr = document.getElementById('row-siswa-' + id);
            if (tr) {
                tr.setAttribute('data-blokir', nowBlocked ? '1' : '0');
                const tdNama = tr.querySelector('.col-nama');
                const badge = tdNama ? tdNama.querySelector('.badge-blokir') : null;

                if (nowBlocked) {
                    if (!badge && tdNama) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge bg-danger ms-1 badge-blokir';
                        newBadge.textContent = 'Diblokir';
                        tdNama.appendChild(document.createTextNode(' '));
                        tdNama.appendChild(newBadge);
                    }
                } else {
                    if (badge) badge.remove();
                }
            }

            // tutup modal
            const modalEl = document.getElementById('blokirModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            showAjaxAlert('success', nowBlocked ? 'Kartu berhasil diblokir.' : 'Blokir kartu berhasil dibuka.');
        })
        .catch(err => {
            showAjaxAlert('danger', err.message || 'Terjadi kesalahan.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            currentBtn = null;
        });
    });

    // Fokus input search setelah render
    window.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('searchInput');
        if (input) setTimeout(() => input.focus(), 150);
    });
</script>

<?php include 'inc/footer.php'; ?>
