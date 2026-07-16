<?php
/**
 * DATATABLES SERVER-SIDE (PEGawai) - FIX FULL SIAP TEMPEL
 * Fix:
 * - tn/7 Ajax error karena query gagal: kolom lokasi_id -> sebenarnya "lokasi"
 * - loop for yang salah (membuat kolom berulang-ulang) DIHAPUS
 * - search tidak menimpa filter lokasi (filter lokasi tetap berlaku)
 * - output JSON valid + error handling kalau query gagal
 */

session_start();

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:../login/');
  exit;
}

require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
require_once '../../../sw-library/phpqrcode/qrlib.php';
require_once '../../login/user.php';

header('Content-Type: application/json; charset=utf-8');

$onlick = "','";
$onlick = explode(",", $onlick);

$modifikasi = (!empty($_POST['modifikasi'])) ? convert('decrypt', $_POST['modifikasi']) : 'N';
$hapus      = (!empty($_POST['hapus'])) ? convert('decrypt', $_POST['hapus']) : 'N';

// Kolom DB yang dipakai untuk query
$aColumns = [
  'pegawai_id',
  'nip',
  'rfid',
  'qrcode',
  'nama_lengkap',
  'jenis_kelamin',
  'jabatan',
  'avatar',
  'tanggal_registrasi',
  'tanggal_login',
  'status',
  'active'
];

$sIndexColumn = "pegawai_id";
$sTable = "pegawai";

$gaSql = [
  'user' => DB_USER,
  'password' => DB_PASSWD,
  'db' => DB_NAME,
  'server' => DB_HOST,
];

$gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);
if ($gaSql['link']->connect_errno) {
  echo json_encode([
    "iTotalRecords" => 0,
    "iTotalDisplayRecords" => 0,
    "aaData" => [],
    "error" => "DB connect failed: " . $gaSql['link']->connect_error,
  ]);
  exit;
}

// LIMIT
$sLimit = "";
if (isset($_GET['iDisplayStart']) && isset($_GET['iDisplayLength']) && $_GET['iDisplayLength'] != '-1') {
  $start = (int)$_GET['iDisplayStart'];
  $len   = (int)$_GET['iDisplayLength'];
  $sLimit = "LIMIT $start, $len";
}

// ORDER
$sOrder = "ORDER BY pegawai_id DESC";
if (isset($_GET['iSortCol_0'])) {
  $dirs = [];
  for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
    $colIdx = intval($_GET['iSortCol_' . $i]);
    if (isset($_GET['bSortable_' . $colIdx]) && $_GET['bSortable_' . $colIdx] == "true") {
      $col = $aColumns[$colIdx] ?? 'pegawai_id';
      $dir = (isset($_GET['sSortDir_' . $i]) && strtolower($_GET['sSortDir_' . $i]) == 'asc') ? 'ASC' : 'DESC';
      $dirs[] = "$col $dir";
    }
  }
  if ($dirs) $sOrder = "ORDER BY " . implode(", ", $dirs);
}

// WHERE (filter lokasi utk non-admin) - FIX: kolomnya "lokasi"
$sWhere = "";
$level = $current_user['level'] ?? '0';
if ($level !== '1') {
  // di sistem Anda kolom DB = "lokasi" (int)
  // sumber user bisa 'lokasi' atau 'lokasi_id' tergantung user.php
  $userLokasi = $current_user['lokasi'] ?? ($current_user['lokasi_id'] ?? null);
  if ($userLokasi !== null && $userLokasi !== '') {
    $userLokasi = (int)$userLokasi;
    $sWhere = "WHERE lokasi = $userLokasi";
  }
}

// GLOBAL SEARCH (TETAP mempertahankan filter lokasi)
if (isset($_GET['sSearch']) && $_GET['sSearch'] !== "") {
  $q = mysqli_real_escape_string($gaSql['link'], $_GET['sSearch']);
  $sWhere .= ($sWhere === "" ? "WHERE (" : " AND (");
  for ($i = 0; $i < count($aColumns); $i++) {
    $sWhere .= $aColumns[$i] . " LIKE '%" . $q . "%' OR ";
  }
  $sWhere = substr_replace($sWhere, "", -3);
  $sWhere .= ")";
}

// PER-COLUMN SEARCH
for ($i = 0; $i < count($aColumns); $i++) {
  if (
    isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" &&
    isset($_GET['sSearch_' . $i]) && $_GET['sSearch_' . $i] != ''
  ) {
    $q = mysqli_real_escape_string($gaSql['link'], $_GET['sSearch_' . $i]);
    $sWhere .= ($sWhere === "" ? "WHERE " : " AND ");
    $sWhere .= $aColumns[$i] . " LIKE '%" . $q . "%' ";
  }
}

// QUERY DATA
$sQuery = "SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $aColumns) . "
  FROM $sTable
  $sWhere
  $sOrder
  $sLimit";

$rResult = mysqli_query($gaSql['link'], $sQuery);
if (!$rResult) {
  echo json_encode([
    "iTotalRecords" => 0,
    "iTotalDisplayRecords" => 0,
    "aaData" => [],
    "error" => "Query failed: " . mysqli_error($gaSql['link']),
  ]);
  exit;
}

// FILTERED TOTAL
$rResultFilterTotal = mysqli_query($gaSql['link'], "SELECT FOUND_ROWS()");
$aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
$iFilteredTotal = (int)($aResultFilterTotal[0] ?? 0);

// TOTAL (ikuti filter lokasi juga biar konsisten)
$sQueryTotal = "SELECT COUNT($sIndexColumn) FROM $sTable " . ($sWhere ? preg_replace('/^\s*WHERE\s+/i', 'WHERE ', $sWhere) : '');
$rResultTotal = mysqli_query($gaSql['link'], $sQueryTotal);
if ($rResultTotal) {
  $aResultTotal = mysqli_fetch_array($rResultTotal);
  $iTotal = (int)($aResultTotal[0] ?? 0);
} else {
  // fallback kalau query total gagal
  $iTotal = $iFilteredTotal;
}

$output = [
  "iTotalRecords" => $iTotal,
  "iTotalDisplayRecords" => $iFilteredTotal,
  "aaData" => []
];

$no = 0;
while ($aRow = mysqli_fetch_array($rResult, MYSQLI_ASSOC)) {
  $no++;

  // AVATAR
  $avatarFile = $aRow['avatar'] ?? '';
  if ($avatarFile && file_exists('../../../sw-content/avatar/' . $avatarFile)) {
    $avatar = '
      <a class="open-popup-link" href="../sw-content/avatar/' . strip_tags($avatarFile) . '">
        <img src="../sw-content/avatar/' . strip_tags($avatarFile) . '" class="imaged w100 rounded-circle" height="50">
      </a>';
  } else {
    $avatar = '<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded-circle" height="50">';
  }

  // ONLINE/OFFLINE
  $status = (($aRow['status'] ?? '') == 'Online')
    ? '<small class="badge badge-dot" style="font-size:13px;"><i class="bg-success"></i>Online</small>'
    : '<small class="badge badge-dot" style="font-size:13px;"><i class="bg-danger"></i>Offline</small>';

  // QRCODE GENERATE (buat folder kalau belum ada)
  $qrcode = strip_tags($aRow['qrcode'] ?? '');
  $qrcodeDir = "../../../sw-content/qrcode/";
  if (!is_dir($qrcodeDir)) {
    @mkdir($qrcodeDir, 0755, true);
  }
  $filepath = $qrcodeDir . "pegawai_$qrcode.png";
  if ($qrcode && !file_exists($filepath)) {
    @QRCode::png($qrcode, $filepath, 'QR_ECLEVEL_Q', 10, 1);
  }

  if ($qrcode && file_exists($filepath)) {
    $foto_qrcode = '<a class="open-popup-link" href="../sw-content/qrcode/pegawai_' . strip_tags($qrcode) . '.png" title="' . strip_tags($aRow['nama_lengkap'] ?? '') . '">
        <img src="../sw-content/qrcode/pegawai_' . strip_tags($qrcode) . '.png" class="imaged w100 rounded-circle" height="50">
      </a>';
  } else {
    $foto_qrcode = '-';
  }

  // ACTIVE
  $active = (($aRow['active'] ?? '') == 'Y')
    ? '<span class="badge badge-info">Aktif</span>'
    : '<span class="badge badge-danger">Tidak Aktif</span>';

  // BUTTONS
  if ($modifikasi == 'Y') {
    $btn_update = '<a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-forgot" data-id="' . strip_tags(epm_encode($aRow['pegawai_id'] ?? '')) . '" data-toggle="tooltip" title="Resset Password">
        <i class="fas fa-key"></i>
      </a>
      <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . 'pegawai&op=update&id=' . htmlentities(convert('encrypt', $aRow['pegawai_id'] ?? '')) . '' . $onlick[1] . ';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
        <i class="fas fa-edit"></i>
      </a>';
  } else {
    $btn_update = '
      <a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip" title="Resset Password">
        <i class="fas fa-key"></i>
      </a>
      <a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-error" data-toggle="tooltip" title="Edit">
        <i class="fas fa-edit"></i>
      </a>';
  }

  if ($hapus == 'Y') {
    $btn_hapus = '<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['pegawai_id'] ?? '')) . '" title="Hapus">
        <i class="fas fa-trash"></i>
      </a>';
  } else {
    $btn_hapus = '<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" title="Hapus">
        <i class="fas fa-trash"></i>
      </a>';
  }

  // TANGGAL LOGIN aman
  $tglLogin = $aRow['tanggal_login'] ?? '';
  if ($tglLogin && strtotime($tglLogin) !== false) {
    $tglLoginHtml = date('d-m-Y', strtotime($tglLogin)) . '<br>' . date('H:i:s', strtotime($tglLogin));
  } else {
    $tglLoginHtml = '-';
  }

  // SUSUN ROW (sekali saja! jangan pakai loop for)
  $row = [];
  $row[] = '<div class="text-center"><input name="id[]" value="' . (int)$aRow['pegawai_id'] . '" type="checkbox"></div>';
  $row[] = '<div class="text-center">' . $no . '</div>';
  $row[] = '<div class="text-center">' . $avatar . '</div>';
  $row[] = '<div class="text-center">' . $foto_qrcode . '</div>';
  $row[] = '<b>' . strip_tags($aRow['nama_lengkap'] ?? '-') . '</b><br>NIP. ' . strip_tags($aRow['nip'] ?? '-');
  $row[] = strip_tags($aRow['rfid'] ?? '-');
  $row[] = ucfirst($aRow['jabatan'] ?? '-');
  $row[] = strip_tags($aRow['jenis_kelamin'] ?? '-');
  $row[] = $tglLoginHtml;
  $row[] = '<div class="text-center">' . $active . '<br>' . $status . '</div>';
  $row[] = '<div class="text-center">
      <a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-reset-qrcode" data-id="' . epm_encode($aRow['pegawai_id'] ?? '') . '" data-toggle="tooltip" title="Reset Qrcode">
        <i class="fas fa-qrcode"></i>
      </a>
      ' . $btn_update . $btn_hapus . '
    </div>';

  $output['aaData'][] = $row;
}

echo json_encode($output);
