<?php
require '_config.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');

$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) {
  echo json_encode(['success'=>false,'message'=>'Session tidak valid','data'=>[]]);
  exit;
}

/**
 * FILTER:
 * - jika tglAwal & tglAkhir kosong => tampil semua
 * - jika diisi => validasi YYYY-MM-DD lalu filter range
 */
$tglAwal  = isset($_GET['tglAwal']) ? trim((string)$_GET['tglAwal']) : '';
$tglAkhir = isset($_GET['tglAkhir']) ? trim((string)$_GET['tglAkhir']) : '';
$useDateFilter = ($tglAwal !== '' && $tglAkhir !== '');

$validDate = function(string $s): bool {
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
  [$y,$m,$d] = array_map('intval', explode('-', $s));
  return checkdate($m, $d, $y);
};

$dtAw = '';
$dtAk = '';
if ($useDateFilter) {
  if (!$validDate($tglAwal) || !$validDate($tglAkhir)) {
    echo json_encode(['success'=>false,'message'=>'Format tanggal tidak valid','data'=>[]]);
    exit;
  }
  $dtAw = mysqli_real_escape_string($conn, $tglAwal . ' 00:00:00');
  $dtAk = mysqli_real_escape_string($conn, $tglAkhir . ' 23:59:59');
}

// WHERE tanggal per tabel
$W_tk = $useDateFilter ? " AND tk.tanggal BETWEEN '$dtAw' AND '$dtAk' " : "";
$W_lt = $useDateFilter ? " AND lt.tanggal BETWEEN '$dtAw' AND '$dtAk' " : "";

// Topup: gunakan DATE() supaya aman jika kolom tanggal adalah DATE atau DATETIME
$W_t = "";
if ($useDateFilter) {
  $tAw = mysqli_real_escape_string($conn, $tglAwal);
  $tAk = mysqli_real_escape_string($conn, $tglAkhir);
  $W_t = " AND DATE(t.tanggal) BETWEEN '$tAw' AND '$tAk' ";
}

/**
 * RULE TOPUP:
 * - MANUAL: merchant_order_id & duitku_reference kosong => tampil
 * - DUITKU: merchant/reference terisi => tampil HANYA jika status PAID / SUCCESS
 * - Keterangan Duitku dibuat ringkas TANPA tanggal agar tidak dobel di UI
 */
$sql = "
  (
    /* PEMBELIAN KANTIN (DEBIT) */
    SELECT
      tk.tanggal AS tanggal,
      tk.nominal AS nominal,
      'DEBIT' AS jenis,
      'PEMBELIAN' AS kategori,
      CONCAT('Pembayaran ke kantin ', COALESCE(k.nama,'Kantin')) AS keterangan,

      NULL AS merchant_order_id,
      NULL AS duitku_reference,
      NULL AS topup_status,
      NULL AS paid_at

    FROM transaksi_kantin tk
    LEFT JOIN kantin k ON k.id = tk.id_kantin
    WHERE tk.id_siswa = $id_siswa
    $W_tk
  )
  UNION ALL
  (
    /* TOPUP (KREDIT) - MANUAL + DUITKU (hanya sukses) */
    SELECT
      t.tanggal AS tanggal,
      t.nominal AS nominal,
      'KREDIT' AS jenis,
      'TOPUP' AS kategori,

      CASE
        WHEN (COALESCE(t.merchant_order_id,'') <> '' OR COALESCE(t.duitku_reference,'') <> '') THEN
          'Top Up (Duitku) • Berhasil'
        ELSE
          'Top Up (Sekolah)'
      END AS keterangan,

      t.merchant_order_id AS merchant_order_id,
      t.duitku_reference  AS duitku_reference,
      t.status            AS topup_status,
      t.paid_at           AS paid_at

    FROM topup t
    WHERE t.id_siswa = $id_siswa

      AND (
        /* MANUAL */
        (COALESCE(t.merchant_order_id,'') = '' AND COALESCE(t.duitku_reference,'') = '')
        OR
        /* DUITKU SUKSES SAJA */
        (
          (COALESCE(t.merchant_order_id,'') <> '' OR COALESCE(t.duitku_reference,'') <> '')
          AND UPPER(COALESCE(t.status,'')) IN ('PAID','SUCCESS')
        )
      )

    $W_t
  )
  UNION ALL
  (
    /* TRANSFER KELUAR (DEBIT) */
    SELECT
      lt.tanggal AS tanggal,
      lt.jumlah  AS nominal,
      'DEBIT'    AS jenis,
      'TRANSFER' AS kategori,
      CONCAT('Transfer ke ', COALESCE(penerima.nama_lengkap,'Siswa')) AS keterangan,

      NULL AS merchant_order_id,
      NULL AS duitku_reference,
      NULL AS topup_status,
      NULL AS paid_at

    FROM log_transfer lt
    LEFT JOIN pendaftaran_siswa penerima ON penerima.id = lt.id_penerima
    WHERE lt.id_pengirim = $id_siswa
    $W_lt
  )
  UNION ALL
  (
    /* TRANSFER MASUK (KREDIT) */
    SELECT
      lt.tanggal AS tanggal,
      lt.jumlah  AS nominal,
      'KREDIT'   AS jenis,
      'TRANSFER' AS kategori,
      CONCAT('Transfer dari ', COALESCE(pengirim.nama_lengkap,'Siswa')) AS keterangan,

      NULL AS merchant_order_id,
      NULL AS duitku_reference,
      NULL AS topup_status,
      NULL AS paid_at

    FROM log_transfer lt
    LEFT JOIN pendaftaran_siswa pengirim ON pengirim.id = lt.id_pengirim
    WHERE lt.id_penerima = $id_siswa
    $W_lt
  )
  ORDER BY tanggal DESC
  LIMIT 200
";

$q = mysqli_query($conn, $sql);
if (!$q) {
  echo json_encode([
    'success' => false,
    'message' => 'Query riwayat gagal',
    'data'    => [],
    'db_error'=> mysqli_error($conn)
  ]);
  exit;
}

$data = [];
while ($r = mysqli_fetch_assoc($q)) {
  $data[] = [
    'tanggal'    => $r['tanggal'],
    'nominal'    => (int)$r['nominal'],
    'jenis'      => $r['jenis'],      // KREDIT / DEBIT (jangan diubah agar UI +/- benar)
    'kategori'   => $r['kategori'],   // TOPUP / PEMBELIAN / TRANSFER
    'keterangan' => $r['keterangan'],

    // detail topup duitku untuk modal
    'merchant_order_id' => $r['merchant_order_id'],
    'duitku_reference'  => $r['duitku_reference'],
    'topup_status'      => $r['topup_status'],
    'paid_at'           => $r['paid_at'],
  ];
}

echo json_encode([
  'success' => true,
  'message' => 'Riwayat transaksi',
  'data'    => $data
]);
exit;
