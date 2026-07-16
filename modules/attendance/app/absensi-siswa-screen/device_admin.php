<?php
include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';

date_default_timezone_set('Asia/Jakarta');

/**
 * Admin key via URL
 * Buka: device_admin.php?k=ADMIN_KEY
 */
$ADMIN_KEY = 'c99a133ae06d17a6d4d0b80cb6ddb048';
$k = (string)($_GET['k'] ?? '');
if ($ADMIN_KEY !== '' && !hash_equals($ADMIN_KEY, $k)) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function genToken(int $len = 32): string {
  // token hex 64 chars jika len=32 bytes
  return bin2hex(random_bytes($len));
}

/**
 * Seed ke app_devices agar status page bisa langsung tampil (opsional, tapi nyaman)
 * - Kalau device ping pakai UPSERT, ini tidak wajib.
 * - Tapi tetap saya taruh biar data awal rapi.
 */
function seedAppDevices(mysqli $connection, string $did, string $label): void {
  $sql = "
    INSERT INTO app_devices (device_id, label, ip, ua, first_seen, last_seen, last_page, hits)
    VALUES (?, ?, '-', '-', NOW(), NOW(), '/absensi-siswa-screen/', 0)
    ON DUPLICATE KEY UPDATE
      label = IF(VALUES(label) <> '' , VALUES(label), label)
  ";
  $st = $connection->prepare($sql);
  if ($st) {
    $st->bind_param('ss', $did, $label);
    @$st->execute();
    $st->close();
  }
}

$err = '';
$ok  = '';

$action = (string)($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $did   = strtoupper(trim((string)($_POST['did'] ?? '')));
  $label = trim((string)($_POST['label'] ?? ''));
  $notes = trim((string)($_POST['notes'] ?? ''));
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  // token: bisa manual, bisa generate
  $token = trim((string)($_POST['token'] ?? ''));
  if (isset($_POST['gen_token'])) {
    $token = genToken(16); // 32 hex chars
  }

  if ($did === '' || strlen($did) > 64) {
    $err = 'DID wajib dan max 64 karakter.';
  }

  if ($err === '') {
    if ($action === 'create') {
      $sql = "INSERT INTO app_device_allowlist (did,label,token,is_active,notes)
              VALUES (?,?,?,?,?)
              ON DUPLICATE KEY UPDATE
                label=VALUES(label),
                token=VALUES(token),
                is_active=VALUES(is_active),
                notes=VALUES(notes)";
      $st = $connection->prepare($sql);
      if (!$st) $err = 'Prepare gagal.';
      else {
        $st->bind_param('sssis', $did, $label, $token, $is_active, $notes);
        if (!$st->execute()) $err = 'Execute gagal.';
        else {
          $ok = 'Device tersimpan.';
          if ($is_active === 1) seedAppDevices($connection, $did, $label);
        }
        $st->close();
      }
    }

    if ($action === 'update') {
      $sql = "UPDATE app_device_allowlist
              SET label=?, token=?, is_active=?, notes=?
              WHERE did=?";
      $st = $connection->prepare($sql);
      if (!$st) $err = 'Prepare gagal.';
      else {
        $st->bind_param('ssiss', $label, $token, $is_active, $notes, $did);
        if (!$st->execute()) $err = 'Execute gagal.';
        else {
          $ok = 'Device diperbarui.';
          if ($is_active === 1) seedAppDevices($connection, $did, $label);
        }
        $st->close();
      }
    }

    if ($action === 'delete') {
      $st = $connection->prepare("DELETE FROM app_device_allowlist WHERE did=?");
      if (!$st) $err = 'Prepare gagal.';
      else {
        $st->bind_param('s', $did);
        if (!$st->execute()) $err = 'Execute gagal.';
        else $ok = 'Device dihapus dari allowlist.';
        $st->close();
      }
      // optional: hapus juga dari app_devices jika Anda mau
      // $st2 = $connection->prepare("DELETE FROM app_devices WHERE device_id=?");
      // if ($st2) { $st2->bind_param('s',$did); @$st2->execute(); $st2->close(); }
    }
  }
}

$search = trim((string)($_GET['q'] ?? ''));
if ($search !== '') {
  $like = '%' . $search . '%';
  $st = $connection->prepare("SELECT * FROM app_device_allowlist WHERE did LIKE ? OR label LIKE ? ORDER BY did ASC");
  $rows = [];
  if ($st) {
    $st->bind_param('ss', $like, $like);
    $st->execute();
    $res = $st->get_result();
    while($res && ($r = $res->fetch_assoc())) $rows[] = $r;
    $st->close();
  }
} else {
  $res = $connection->query("SELECT * FROM app_device_allowlist ORDER BY did ASC");
  $rows = [];
  if ($res) while($r = $res->fetch_assoc()) $rows[] = $r;
}

$edit = strtoupper(trim((string)($_GET['edit'] ?? '')));
$editRow = null;
if ($edit !== '') {
  $st = $connection->prepare("SELECT * FROM app_device_allowlist WHERE did=? LIMIT 1");
  if ($st) {
    $st->bind_param('s', $edit);
    $st->execute();
    $res = $st->get_result();
    if ($res && $res->num_rows) $editRow = $res->fetch_assoc();
    $st->close();
  }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Device Allowlist</title>
<style>
  body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#0b1220;color:#e5e7eb;margin:16px}
  .wrap{max-width:1200px;margin:auto}
  .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px;margin-bottom:14px}
  input,textarea{width:100%;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:#0b1220;color:#e5e7eb}
  label{display:block;margin:8px 0 6px;opacity:.9}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top;font-size:14px}
  th{opacity:.85;text-align:left}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .btn{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#e5e7eb;text-decoration:none;cursor:pointer}
  .btn.ok{border-color:rgba(34,197,94,.35);background:rgba(34,197,94,.12)}
  .btn.danger{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.12)}
  .msg-ok{padding:10px 12px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.35);margin-bottom:10px}
  .msg-err{padding:10px 12px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);margin-bottom:10px}
  .muted{opacity:.75;font-size:12px}
  code{opacity:.95}
</style>
</head>
<body>
<div class="wrap">
  <h2>CRUD Device Allowlist</h2>
  <div class="muted">Akses: <code>device_admin.php?k=ADMIN_KEY</code></div>
  <br>

  <?php if ($ok): ?><div class="msg-ok"><?= h($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg-err"><?= h($err) ?></div><?php endif; ?>

  <div class="card">
    <h3><?= $editRow ? 'Edit Device' : 'Tambah Device' ?></h3>

    <form method="post">
      <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">

      <div class="grid">
        <div>
          <label>DID</label>
          <input name="did" value="<?= h($editRow['did'] ?? '') ?>" <?= $editRow ? 'readonly' : '' ?> required>
          <div class="muted">Contoh: KIOSK1, KIOSK2, TV_TU, LAB1</div>
        </div>

        <div>
          <label>Label</label>
          <input name="label" value="<?= h($editRow['label'] ?? '') ?>">
        </div>
      </div>

      <label>Token per Device (opsional tapi disarankan)</label>
      <div class="grid">
        <div>
          <input name="token" value="<?= h($editRow['token'] ?? '') ?>" placeholder="kosongkan jika tidak dipakai">
          <div class="muted">Kalau diisi, device_ping.php akan menolak device tanpa token ini.</div>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-end">
          <button class="btn" type="submit" name="gen_token" value="1">Generate Token</button>
          <span class="muted">Generate akan mengisi otomatis.</span>
        </div>
      </div>

      <label>Catatan</label>
      <textarea name="notes" rows="2"><?= h($editRow['notes'] ?? '') ?></textarea>

      <label style="margin-top:10px">
        <input type="checkbox" name="is_active" value="1" <?= (!$editRow || (int)$editRow['is_active']===1) ? 'checked' : '' ?>>
        Aktif
      </label>

      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn ok" type="submit"><?= $editRow ? 'Simpan' : 'Tambah' ?></button>
        <?php if ($editRow): ?>
          <a class="btn" href="device_admin.php?k=<?= h($k) ?>">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Daftar Device</h3>

    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
      <input type="hidden" name="k" value="<?= h($k) ?>">
      <input name="q" value="<?= h($search) ?>" placeholder="Cari DID / Label">
      <button class="btn" type="submit">Cari</button>
      <a class="btn" href="device_admin.php?k=<?= h($k) ?>">Reset</a>
    </form>

    <table>
      <thead>
        <tr>
          <th>DID</th>
          <th>Label</th>
          <th>Status</th>
          <th>Token</th>
          <th>Notes</th>
          <th>Waktu</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><code><?= h($r['did']) ?></code></td>
          <td><?= h($r['label']) ?></td>
          <td><?= ((int)$r['is_active']===1) ? 'AKTIF' : 'NONAKTIF' ?></td>
          <td class="muted"><code><?= h($r['token']) ?></code></td>
          <td><?= h($r['notes']) ?></td>
          <td class="muted">
            created: <?= h($r['created_at']) ?><br>
            updated: <?= h($r['updated_at']) ?>
          </td>
          <td>
            <a class="btn" href="device_admin.php?k=<?= h($k) ?>&edit=<?= h($r['did']) ?>">Edit</a>

            <form method="post" style="display:inline" onsubmit="return confirm('Hapus DID ini dari allowlist?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="did" value="<?= h($r['did']) ?>">
              <button class="btn danger" type="submit">Hapus</button>
            </form>

            <div class="muted" style="margin-top:6px">
              Link contoh: <code>?did=<?= h($r['did']) ?>&label=<?= h($r['label']) ?></code>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!count($rows)): ?>
        <tr><td colspan="7">Belum ada device.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>