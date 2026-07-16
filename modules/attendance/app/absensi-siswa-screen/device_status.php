<?php
include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';

date_default_timezone_set('Asia/Jakarta');

$KEY = 'c99a133ae06d17a6d4d0b80cb6ddb048';
if (!isset($_GET['k']) || !hash_equals($KEY, (string)$_GET['k'])) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

$ACTIVE_SECONDS = 120;

$q = $connection->query("SELECT device_id,label,ip,ua,first_seen,last_seen,last_image,last_page,hits
                         FROM app_devices
                         ORDER BY last_seen DESC
                         LIMIT 200");
$rows = [];
if ($q) { while($r = $q->fetch_assoc()) $rows[] = $r; }

$now = time();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Status Perangkat</title>
<style>
  body{font-family:system-ui,Segoe UI,Arial,sans-serif;margin:16px;background:#0b1220;color:#e5e7eb}
  .wrap{max-width:1200px;margin:auto}
  table{width:100%;border-collapse:collapse;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden}
  th,td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.08);font-size:14px;vertical-align:top}
  th{background:rgba(255,255,255,.06);text-align:left}
  .pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
  .on{background:rgba(34,197,94,.18);color:#22c55e;border:1px solid rgba(34,197,94,.35)}
  .off{background:rgba(239,68,68,.18);color:#ef4444;border:1px solid rgba(239,68,68,.35)}
  .muted{opacity:.75;font-size:12px}
  .thumb{width:150px;height:auto;border-radius:10px;border:1px solid rgba(255,255,255,.15);display:block}
</style>
</head>
<body>
<div class="wrap">
  <h2>Status perangkat (aktif/tidak) + IP + Live Snapshot</h2>
  <div class="muted">Aktif jika last_seen &lt; <?= (int)$ACTIVE_SECONDS ?> detik. (Auto refresh tiap 10 detik)</div>
  <br>

  <table>
    <thead>
      <tr>
        <th>Status</th>
        <th>Kamera</th>
        <th>Label</th>
        <th>IP</th>
        <th>Last seen</th>
        <th>Device ID</th>
        <th>Hits</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r):
        $last = strtotime($r['last_seen'] ?? '') ?: 0;
        $isOn = ($now - $last) <= $ACTIVE_SECONDS;
        $img  = (string)($r['last_image'] ?? '');
      ?>
      <tr>
        <td><span class="pill <?= $isOn?'on':'off' ?>"><?= $isOn?'AKTIF':'TIDAK' ?></span></td>

        <td>
          <?php if ($img !== ''): ?>
            <img
              class="thumb"
              src="<?= h($img) ?>?t=<?= (int)$last ?>"
              alt="snapshot"
              loading="lazy"
              onerror="this.style.display='none';"
            >
            <div class="muted" style="margin-top:6px"><?= $isOn ? 'live' : 'terakhir' ?></div>
          <?php else: ?>
            <span class="muted">-</span>
          <?php endif; ?>
        </td>

        <td><?= h($r['label'] ?: '-') ?><div class="muted"><?= h($r['last_page'] ?: '') ?></div></td>
        <td><?= h($r['ip'] ?: '-') ?></td>
        <td><?= h($r['last_seen'] ?: '-') ?><div class="muted">first: <?= h($r['first_seen'] ?: '-') ?></div></td>
        <td class="muted"><?= h($r['device_id'] ?: '-') ?><div class="muted"><?= h($r['ua'] ?: '') ?></div></td>
        <td><?= (int)($r['hits'] ?? 0) ?></td>
      </tr>
      <?php endforeach; ?>

      <?php if (!count($rows)): ?>
      <tr><td colspan="7">Belum ada perangkat yang tercatat.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  // auto refresh biar "live"
  setTimeout(function(){ location.reload(); }, 10000);
</script>
</body>
</html>
