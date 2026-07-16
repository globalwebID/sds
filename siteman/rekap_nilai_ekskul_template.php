<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            /* font-family: DejaVu Sans, sans-serif; */
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }

        h1 {
            margin: 0 0 10px
        }

        h2 {
            margin: 0 0 10px
        }

        img {
            display: block;
            margin: 0 auto;
            width: 100%;
            /* max-height: 150px; */
            object-fit: contain;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.info td {
            vertical-align: top;
            padding: 4px 8px;
            text-align: left;
        }

        table.info td.label {
            font-weight: bold;
            width: 220px;
        }

        /* table.info td:nth-child(2) {
            width: 10px;
        } */

        @media print {
            @page {
                size: A4;
                margin: 20mm;
            }

            body {
                margin: 0;
            }

            .container {
                margin: 0 auto;
                page-break-inside: avoid;
            }

        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-auto d-sm-block mt-2 mb-2">
                    <h4 class="mb-0">
                        Rekap Nilai Ekstrakurikuler
                        <?php if ($nama_ekskul_filter): ?>
                            - <?= htmlspecialchars($nama_ekskul_filter) ?>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mt-5">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="info">
                            <tr>
                                <td width="25%"><strong>Nama Peserta Didik</strong></td>
                                <td colspan="5">: <?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
                            </tr>
                            <tr>
                                <td width="25%"><strong>NISN</strong></td>
                                <td colspan="5">: <?= htmlspecialchars($siswa['nisn']) ?></td>
                            </tr>
                            <tr>
                                <td width="25%"><strong>NIPD</strong></td>
                                <td colspan="5">: <?= htmlspecialchars($siswa['nipd']) ?></td>
                            </tr>
                        </table>

                        <?php if ($ekskul_id > 0): ?>
                            <!-- Tabel nilai ekskul spesifik -->
                            <table class="info" border="1">
                                <thead>
                                    <tr>
                                        <th colspan="6" style="text-align: left;">
                                            <strong>Ekstrakurikuler</strong>: <strong><?= htmlspecialchars($nama_ekskul_filter) ?></strong>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th style="text-align: center; width: 20px;">No</th>
                                        <th>Tahun Ajaran</th>
                                        <th>Semester</th>
                                        <th>Nilai</th>
                                        <th>Keterangan</th>
                                        <th>Tanggal Penilaian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($nilaiList) > 0): $no = 1;
                                        foreach ($nilaiList as $nilai): ?>
                                            <tr>
                                                <td style="text-align: center; width: 20px;"><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($nilai['tahun_ajaran']) ?></td>
                                                <td><?= htmlspecialchars($nilai['semester']) ?></td>
                                                <td><?= htmlspecialchars($nilai['nilai']) ?></td>
                                                <td><?= htmlspecialchars($nilai['keterangan']) ?></td>
                                                <td><?= date('d M Y', strtotime($nilai['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Belum ada nilai.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <!-- Tabel per ekskul -->
                            <?php if (count($groupedNilai) > 0): ?>
                                <?php foreach ($groupedNilai as $ekskul): ?>
                                    <div class="table-responsive">
                                        <table class="info" border="1">
                                            <thead>
                                                <tr>
                                                    <th colspan="6" style="background-color: #eee;">
                                                        <strong>Ekstrakurikuler</strong>: <strong><?= htmlspecialchars($ekskul['nama']) ?></strong>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Tahun Ajaran</th>
                                                    <th>Semester</th>
                                                    <th>Nilai</th>
                                                    <th>Keterangan</th>
                                                    <th>Tanggal Penilaian</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1;
                                                foreach ($ekskul['data'] as $nilai): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= htmlspecialchars($nilai['tahun_ajaran']) ?></td>
                                                        <td><?= htmlspecialchars($nilai['semester']) ?></td>
                                                        <td><?= htmlspecialchars($nilai['nilai']) ?></td>
                                                        <td><?= htmlspecialchars($nilai['keterangan']) ?></td>
                                                        <td><?= date('d M Y', strtotime($nilai['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info text-center">Belum ada nilai ekstrakurikuler.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>