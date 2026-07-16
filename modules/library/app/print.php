<?php

declare(strict_types=1);
require_once __DIR__ . '/auth.php';
perpus_session_start();
$root = dirname(__DIR__);
require_once $root . '/db.php';
require_once $root . '/config/perpus.php';
require_once __DIR__ . '/lib/Barcode128.php';
sds_perpus_ensure_schema($conn);
$perpusUser = perpus_require_login($conn);
function ph($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
$school = ['nama_sekolah' => 'Sekolah', 'logo' => ''];
$r = $conn->query('SELECT nama_sekolah,logo FROM pengaturan LIMIT 1');
if ($r && ($x = $r->fetch_assoc())) $school = array_merge($school, $x);
$logo = !empty($school['logo']) ? sds_base_url('uploads/logo/' . rawurlencode(basename((string)$school['logo']))) : '';
$type = (string)($_POST['type'] ?? $_GET['type'] ?? '');
$title = 'Cetak Perpustakaan';
$content = '';
if ($type === 'member_cards') {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['member_ids'] ?? [])))));
    if (!$ids) exit('Pilih minimal satu anggota.');
    $ids = array_slice($ids, 0, 200);
    $list = implode(',', $ids);
    $members = [];
    $q = $conn->query("SELECT a.*,kr.uid rfid_uid FROM perpus_anggota a LEFT JOIN kartu_rfid kr ON kr.pemilik_tipe=a.pemilik_tipe AND kr.pemilik_id=a.pemilik_id WHERE a.id IN ($list) ORDER BY a.nomor_anggota");
    while ($q && ($row = $q->fetch_assoc())) {
        $row['profile'] = sds_perpus_identity_profile($conn, (string)$row['pemilik_tipe'], (int)$row['pemilik_id'], $row);
        $members[] = $row;
    }
    $withoutPhoto = !empty($_POST['without_photo']);
    $title = 'Kartu Anggota Perpustakaan';
    ob_start(); ?><div class="member-grid"><?php foreach ($members as $m): $p = $m['profile'];
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                $photo = '';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                if (!$withoutPhoto && !empty($p['foto'])) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    $raw = ltrim((string)$p['foto'], '/');
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    $photo = str_contains($raw, '/') ? sds_base_url($raw) : sds_base_url('uploads/' . $raw);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                } ?><section class="member-card">
                <div class="card-head"><?php if ($logo): ?><img src="<?= ph($logo) ?>" alt="Logo"><?php endif; ?><div><strong>KARTU ANGGOTA PERPUSTAKAAN</strong><span><?= ph($school['nama_sekolah']) ?></span></div>
                </div>
                <div class="card-main">
                    <div class="photo"><?php if ($photo): ?><img src="<?= ph($photo) ?>" alt="Foto"><?php else: ?><span><?= ph(mb_strtoupper(mb_substr($p['nama'], 0, 1))) ?></span><?php endif; ?></div>
                    <div class="identity">
                        <h3><?= ph($p['nama']) ?></h3>
                        <dl>
                            <dt>No Anggota</dt>
                            <dd><?= ph($m['nomor_anggota']) ?></dd>
                            <dt>Identitas</dt>
                            <dd><?= ph($p['identitas']) ?></dd>
                            <dt>Unit</dt>
                            <dd><?= ph($p['unit']) ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="card-code"><?= PerpusBarcode128::svg((string)$m['nomor_anggota'], 32, 1.15) ?><small><?= ph($m['nomor_anggota']) ?><?= !empty($m['rfid_uid']) ? ' · RFID ' . ph($m['rfid_uid']) : '' ?></small></div>
            </section><?php endforeach; ?></div><?php $content = ob_get_clean();
                                            } elseif ($type === 'barcodes') {
                                                $bookId = (int)($_POST['book_id'] ?? 0);
                                                if ($bookId <= 0) exit('Buku tidak valid.');
                                                $status = (string)($_POST['copy_status'] ?? 'all');
                                                $mode = (string)($_POST['label_mode'] ?? 'barcode');
                                                $stmt = $conn->prepare('SELECT * FROM perpus_buku WHERE id=? LIMIT 1');
                                                $stmt->bind_param('i', $bookId);
                                                $stmt->execute();
                                                $book = $stmt->get_result()->fetch_assoc();
                                                $stmt->close();
                                                if (!$book) exit('Buku tidak ditemukan.');
                                                $sql = 'SELECT * FROM perpus_buku_eksemplar WHERE buku_id=?';
                                                if ($status !== 'all') $sql .= ' AND status=?';
                                                $sql .= ' ORDER BY barcode';
                                                $stmt = $conn->prepare($sql);
                                                if ($status !== 'all') $stmt->bind_param('is', $bookId, $status);
                                                else $stmt->bind_param('i', $bookId);
                                                $stmt->execute();
                                                $copies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                                $stmt->close();
                                                if (!$copies) exit('Tidak ada eksemplar sesuai filter.');
                                                $title = $mode === 'catalog' ? 'Label Katalog' : 'Barcode Eksemplar';
                                                ob_start(); ?><div class="label-grid"><?php foreach ($copies as $copy): ?><section class="book-label"><?php if ($mode === 'catalog'): ?><div class="school-mini"><?= ph($school['nama_sekolah']) ?></div><strong class="call-number"><?= ph($book['nomor_panggil'] ?: $book['klasifikasi'] ?: '-') ?></strong>
                    <div class="label-title"><?= ph($book['judul']) ?></div><small><?= ph($copy['barcode']) ?></small><?php else: ?><div class="school-mini"><?= ph($school['nama_sekolah']) ?></div>
                    <div class="label-title"><?= ph($book['judul']) ?></div><?= PerpusBarcode128::svg((string)$copy['barcode'], 38, 1.15) ?><strong><?= ph($copy['barcode']) ?></strong><small><?= ph($book['nomor_panggil'] ?: $book['klasifikasi'] ?: '') ?></small><?php endif; ?>
            </section><?php endforeach; ?></div><?php $content = ob_get_clean();
                                            } else {
                                                exit('Jenis cetak tidak valid.');
                                            }
                                                ?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= ph($title) ?></title>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #eef1f4;
            color: #111;
            font: 12px Arial, sans-serif
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #343a40;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .toolbar button {
            background: #007bff;
            color: #fff;
            border: 0;
            border-radius: 4px;
            padding: 9px 16px;
            font-weight: bold;
            cursor: pointer
        }

        .sheet {
            background: #fff;
            max-width: 210mm;
            min-height: 297mm;
            margin: 15px auto;
            padding: 10mm;
            box-shadow: 0 2px 12px #0002
        }

        .member-grid {
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            gap: 6mm;
            justify-content: center
        }

        .member-card {
            width: 85.6mm;
            height: 54mm;
            border: 1px solid #333;
            border-radius: 3mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: linear-gradient(145deg, #fff, #eff6ff)
        }

        .card-head {
            height: 13mm;
            background: #0b4f9c;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 3mm;
            padding: 2mm 3mm
        }

        .card-head img {
            width: 9mm;
            height: 9mm;
            object-fit: contain;
            background: #fff;
            border-radius: 50%;
            padding: 1mm
        }

        .card-head strong {
            display: block;
            font-size: 9px
        }

        .card-head span {
            display: block;
            font-size: 8px;
            margin-top: 1mm
        }

        .card-main {
            display: flex;
            gap: 3mm;
            padding: 3mm 3mm 1mm;
            flex: 1
        }

        .photo {
            width: 18mm;
            height: 23mm;
            border: 1px solid #bbb;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover
        }

        .photo span {
            font-size: 20px;
            font-weight: bold;
            color: #6c757d
        }

        .identity {
            flex: 1;
            min-width: 0
        }

        .identity h3 {
            font-size: 11px;
            margin: 0 0 1.5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .identity dl {
            margin: 0;
            display: grid;
            grid-template-columns: 18mm 1fr;
            gap: .6mm;
            font-size: 7.5px
        }

        .identity dt {
            font-weight: bold
        }

        .identity dd {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .card-code {
            padding: 0 3mm 2mm;
            text-align: center
        }

        .card-code svg {
            height: 8mm
        }

        .card-code small {
            display: block;
            font-size: 6.5px;
            margin-top: .5mm
        }

        .label-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4mm
        }

        .book-label {
            height: 34mm;
            border: 1px solid #777;
            padding: 2.5mm;
            text-align: center;
            overflow: hidden;
            break-inside: avoid
        }

        .book-label svg {
            height: 12mm;
            margin: 1mm 0
        }

        .book-label strong {
            display: block;
            font-size: 9px
        }

        .school-mini {
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden
        }

        .label-title {
            font-size: 7.5px;
            line-height: 1.2;
            height: auto;
            overflow: hidden;
            margin: 1mm 0
        }

        .call-number {
            font-size: 18px !important;
            margin: 2mm 0
        }

        .book-label small {
            display: block;
            font-size: 7px
        }

        @page {
            size: A4;
            margin: 8mm
        }

        @media print {
            body {
                background: #fff
            }

            .toolbar {
                display: none
            }

            .sheet {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: none;
                min-height: 0
            }

            .member-grid {
                grid-template-columns: repeat(2, 85.6mm);
                gap: 5mm
            }

            .label-grid {
                gap: 3mm
            }
        }
    </style>
</head>

<body>
    <div class="toolbar"><span><?= ph($title) ?> · <?= count($members ?? $copies ?? []) ?> data</span><button onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main class="sheet"><?= $content ?></main>
</body>

</html>