<?php
require_once __DIR__ . '/../../config/form-fields.php';
sds_seed_form_fields($conn);

function updateStatusPesan($conn)
{
  $statusBaru = (int) ($_POST['status_pesan'] ?? 0);
  $stmt = $conn->prepare("UPDATE formulir SET kirim_pesan = ? WHERE id = 1");

  if ($stmt) {
    $stmt->bind_param("i", $statusBaru);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "✅ Status pengiriman pesan berhasil diperbarui.";
  } else {
    $_SESSION['error'] = "❌ Gagal mengubah status pesan: " . $conn->error;
  }
}

function updateFieldForm($conn)
{
  if (!isset($_POST['field_id'])) return;

  $stmt = $conn->prepare("UPDATE form_fields SET is_active = ?, is_required = ? WHERE id = ?");
  if (!$stmt) {
    $_SESSION['error'] = "❌ Gagal menyiapkan statement: " . $conn->error;
    return;
  }

  try {
    foreach ($_POST['field_id'] as $i => $id) {
      $active = (int) ($_POST['is_active'][$i] ?? 0);
      $required = (int) ($_POST['is_required'][$i] ?? 0);

      $stmt->bind_param("iii", $active, $required, $id);
      $stmt->execute();
    }
    $_SESSION['success'] = "✅ Pengaturan field berhasil disimpan.";
  } catch (Exception $e) {
    $_SESSION['error'] = "❌ Kesalahan saat menyimpan: " . $e->getMessage();
  } finally {
    $stmt->close();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['status_pesan'])) {
    updateStatusPesan($conn);
  } else {
    updateFieldForm($conn);
  }
  header("Location: admin_fields");
  exit;
}

$result = $conn->query("SELECT * FROM form_fields");
$status = $conn->query("SELECT kirim_pesan FROM formulir WHERE id = 1");
$kirimPesanAktif = ($status && $row = $status->fetch_assoc()) ? (bool) $row['kirim_pesan'] : false;
?>


<style>
  .toggle-checkbox {
    width: 42px;
    height: 17px;
    appearance: none;
    background-color: #d1d5db;
    border-radius: 9999px;
    position: relative;
    cursor: pointer;
    transition: background-color 0.3s;
    display: inline-block;
    vertical-align: middle;
  }

  .toggle-checkbox:checked {
    background-color: #3b82f6;
  }

  .toggle-checkbox:before {
    content: '';
    position: absolute;
    width: 15px;
    height: 15px;
    border-radius: 9999px;
    background: white;
    top: 1px;
    left: 1px;
    transition: transform 0.3s;
  }

  .toggle-checkbox:checked:before {
    transform: translateX(1.5rem);
  }
</style>

<!-- FORM -->
<form method="post">
  <div class="topbar mt-0">
    <div class="container-fluid p-0">
      <div class="row align-items-center">
        <div class="col-auto d-sm-block">
          <div class="bg-white rounded w-fit">
            <label class="m-2 flex items-center space-x-3">
              <h4 class="text-center mb-0">Pengaturan Formulir Peserta Didik</h4>
            </label>
          </div>
        </div>
        <div class="col ms-auto text-end">
          <div class="d-flex gap-2 justify-content-end">
            <button
              type="submit"
              name="status_pesan"
              value="<?= $kirimPesanAktif ? '0' : '1' ?>"
              class="btn <?= $kirimPesanAktif ? 'btn-danger' : 'btn-primary' ?>">
              <?= $kirimPesanAktif ? 'Nonaktifkan Pengiriman Pesan' : 'Aktifkan Pengiriman Pesan' ?>
            </button>
            <button type="submit" class="btn btn-success">Simpan Pengaturan</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- NOTIFIKASI -->
  <div class="container mt-3">

  </div>

  <!-- TABEL FIELD -->
  <div class="container p-0">
    <div class="card mt-5">
      <?php foreach (['error', 'success'] as $type): ?>
        <?php if (!empty($_SESSION[$type])): ?>
          <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-message"><?= $_SESSION[$type] ?></div>
          </div>
          <?php unset($_SESSION[$type]); ?>
        <?php endif; ?>
      <?php endforeach; ?>
      <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
        <table class="table table-sm my-0 table-hover table-striped">
          <thead>
            <tr>
              <th style="position: sticky;top: 0;background: white;z-index: 9;padding: 12px 20px;">Label</th>
              <th style="position: sticky;top: 0;background: white;z-index: 9;padding: 12px 10px; text-align: right;">Wajib</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 0;
            while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['label']) ?></td>
                <td class="text-end">
                  <input type="hidden" name="is_required[<?= $i ?>]" value="0">
                  <input type="checkbox" class="toggle-checkbox" name="is_required[<?= $i ?>]" value="1" <?= $row['is_required'] ? 'checked' : '' ?>>
                </td>
                <input type="hidden" name="is_active[<?= $i ?>]" value="1">
                <input type="hidden" name="field_id[<?= $i ?>]" value="<?= $row['id'] ?>">
              </tr>
            <?php $i++;
            endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>
