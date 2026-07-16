<div class="card br-0">
    <div class="card-body">
        <div class="row m-n3">
            <div class="top-tab mt-0">
                <div class="container-fluid p-0">
                    <div class="row modal-dialog-centered">
                        <div class="col-auto d-sm-block">
                            <h5 class="card-title mb-0">Rekapitulasi Pelanggaran Peseta Didik</h5>
                        </div>
                        <div class="col-auto ms-auto text-end">
                            <button type="button" id="berkas" class="btn btn-primary" onclick="toggleTambahPelanggaran()">
                                Tambah Pelanggaran
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mt-2 mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Bukti Pelanggaran</th>
                            <th>Tanggal Upload</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $id_psiswa = $student['id'];
                        $no = 1;

                        // Query data pelanggaran
                        $bp = $conn->prepare("SELECT id, id_psiswa, nama_pelanggaran, file, uploaded_at FROM berkas_pelanggaran WHERE id_psiswa = ? ORDER BY uploaded_at DESC");
                        $bp->bind_param("i", $id_psiswa);
                        $bp->execute();
                        $result = $bp->get_result();
                        ?>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $path = "../uploads/{$student['tahun_ajaran']}/{$student['nisn']}/berkas_pelanggaran/{$row['file']}";
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggaran']); ?></td>
                                    <td>
                                        <?php if (file_exists($path)): ?>
                                            <a href="<?= $path ?>" target="_blank">Lihat File</a>
                                        <?php else: ?>
                                            <span class="text-danger">File tidak ditemukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d-m-Y H:i', strtotime($row['uploaded_at'])); ?></td>
                                    <td style="text-align: right;">
                                        <form method="post" action="hapus_berkas_pelanggaran" onsubmit="return confirm('Hapus berkas ini?')">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <input type="hidden" name="id_psiswa" value="<?= $row['id_psiswa']; ?>">
                                            <input type="hidden" name="file" value="<?= $row['file']; ?>">
                                            <input type="hidden" name="tahun_ajaran" value="<?= $student['tahun_ajaran']; ?>">
                                            <input type="hidden" name="nisn" value="<?= $student['nisn']; ?>">
                                            <button type="submit" class="btn btn-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada Pelanggaran pada Peserta Didik</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php $bp->close(); ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pelanggaran -->
<div class="modal fade" id="modalTambahPelanggaran" tabindex="-1" aria-labelledby="modalTambahPelanggaranLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" enctype="multipart/form-data" action="upload_berkas_pelanggaran_siswa">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahPelanggaranLabel">Tambah Berkas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_psiswa" value="<?= $student['id']; ?>">
                <input type="hidden" name="tahun_ajaran" value="<?= $student['tahun_ajaran']; ?>">
                <input type="hidden" name="nisn" value="<?= $student['nisn']; ?>">

                <div class="mb-3">
                    <label for="nama_pelanggaran" class="form-label">Jenis Pelanggaran</label>
                    <input type="text" class="form-control" name="nama_pelanggaran">
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
    function toggleTambahPelanggaran() {
        const modal = new bootstrap.Modal(document.getElementById('modalTambahPelanggaran'));
        modal.show();
    }
</script>
