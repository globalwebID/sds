<div class="card br-0">
    <div class="card-body">
        <div class="row m-n3">
            <div class="top-tab mt-0">
                <div class="container-fluid p-0">
                    <div class="row modal-dialog-centered">
                        <div class="col-auto d-sm-block">
                            <h5 class="card-title mb-0">Berkas Utama</h5>
                        </div>
                        <div class="col-auto ms-auto text-end">
                            <button type="button" id="berkas" class="btn btn-primary" onclick="toggleTambahBerkas()">
                                Tambah Berkas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <tr>
                        <td class="label">Foto Siswa</td>
                        <td style="text-align: right;">
                            <?php if ($student['foto']): ?>
                                <a href="../uploads/<?= $student['foto'] ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Kartu Keluarga</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_kk']): ?>
                                <a href="../uploads/<?= $student['file_kk']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Ijazah SMP</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_ijazah']): ?>
                                <a href="../uploads/<?= $student['file_ijazah']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Akta Kelahiran</td>
                        <td style="text-align: right;">
                            <?php if (!empty($student['file_akta'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($student['file_akta']); ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                </table>

                <div class="container-fluid p-0 mt-4">
                    <div class="row mb-2 modal-dialog-centered">
                        <div class="col-auto d-sm-block">
                            <h5 class="card-title mb-0">Berkas Pendukung</h5>
                        </div>
                    </div>
                </div>
                <table class="table table-sm table-hover mb-0">
                    <tr>
                        <td class="label">KIP (Kartu Indonesia Pintar)</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_kip']): ?>
                                <a href="../uploads/<?= $student['file_kip']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">KPS</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_kip']): ?>
                                <a href="../uploads/<?= $student['file_kip']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">PKH</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_kip']): ?>
                                <a href="../uploads/<?= $student['file_kip']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">KKS</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_kip']): ?>
                                <a href="../uploads/<?= $student['file_kip']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">KIS</td>
                        <td style="text-align: right;">
                            <?php if ($student['file_kip']): ?>
                                <a href="../uploads/<?= $student['file_kip']; ?>" target="_blank">Lihat Berkas</a>
                            <?php else: echo '-';
                            endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row mt-5">
            <div class="table-responsive">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3 modal-dialog-centered">
                        <div class="col-auto d-sm-block">
                            <h5 class="card-title mb-0">Berkas Tambahan</h5>
                        </div>
                    </div>
                </div>
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Berkas</th>
                            <th>File</th>
                            <th>Tanggal Upload</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $id_siswa = $student['id'];
                        $no = 1;
                        $q = $conn->prepare("SELECT id, id_siswa, nama_berkas, file, uploaded_at FROM berkas_tambahan WHERE id_siswa = ? ORDER BY uploaded_at DESC");
                        $q->bind_param("i", $id_siswa);
                        $q->execute();
                        $result = $q->get_result();

                        while ($row = $result->fetch_assoc()):
                            $path = "../uploads/{$student['tahun_ajaran']}/{$student['nisn']}/berkas_tambahan/{$row['file']}";
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_berkas']); ?></td>
                                <td><a href="<?= $path ?>" target="_blank">Lihat File</a></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['uploaded_at'])); ?></td>
                                <td style="text-align: right;">
                                    <form method="post" action="hapus_berkas_tambahan" onsubmit="return confirm('Hapus berkas ini?')">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                        <input type="hidden" name="id_siswa" value="<?= $row['id_siswa']; ?>">
                                        <input type="hidden" name="file" value="<?= $row['file']; ?>">
                                        <input type="hidden" name="tahun_ajaran" value="<?= $student['tahun_ajaran']; ?>">
                                        <input type="hidden" name="nisn" value="<?= $student['nisn']; ?>">
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile;
                        $q->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Berkas -->
<div class="modal fade" id="modalTambahBerkas" tabindex="-1" aria-labelledby="modalTambahBerkasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" enctype="multipart/form-data" action="upload_berkas_tambahan_siswa">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahBerkasLabel">Tambah Berkas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_siswa" value="<?= $student['id']; ?>">
                <input type="hidden" name="tahun_ajaran" value="<?= $student['tahun_ajaran']; ?>">
                <input type="hidden" name="nisn" value="<?= $student['nisn']; ?>">

                <div class="mb-3">
                    <label for="nama_berkas" class="form-label">Nama Berkas</label>
                    <input type="text" class="form-control" name="nama_berkas">
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">Pilih File</label>
                    <input type="file" class="form-control" name="file" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="form-text text-muted">Maksimal 10MB (PDF/JPG/PNG)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>
<script>
    function toggleTambahBerkas() {
        const modal = new bootstrap.Modal(document.getElementById('modalTambahBerkas'));
        modal.show();
    }
</script>
