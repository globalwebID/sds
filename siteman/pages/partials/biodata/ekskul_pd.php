<div class="card br-0">
    <div class="card-body">
        <form method="POST" action="edit_proses" id="formIbu" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="ibu">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row m-n3">
                <div class="top-tab mt-0">
                    <div class="container-fluid p-0">
                        <div class="row modal-dialog-centered">
                            <div class="col-auto d-sm-block">
                                <h5 class="card-title mb-2 mt-2" style="padding: 3px 10px;">Ekstrakurikuler Peserta Didik</h5>
                            </div>
                            <div class="col-auto ms-auto text-end">
                                <a href="ekskul_rekap_nilai?siswa=<?= $student['id'] ?>"
                                    class="btn btn-primary">
                                    Semua Nilai
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <?php if (count($student['ekstrakurikuler']) > 0): $no = 1;
                            foreach ($student['ekstrakurikuler'] as $eks): ?>
                                <?php if ($eks['terdaftar']): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($eks['tahun_ajaran']) ?></td>
                                        <td>
                                            <a href="ekskul_lihat_siswa?id=<?= $eks['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($eks['nama_ekskul']) ?>
                                            </a>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="ekskul_rekap_nilai?siswa=<?= $student['id'] ?>&ekskul=<?= $eks['id'] ?>" class="btn btn-success">Rekap Nilai</a>
                                            <a href="ekskul_rekap_absen_siswa?siswa=<?= $student['id'] ?>&ekskul=<?= $eks['id'] ?>" class="btn btn-warning">Rekap Kehadiran</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada Ekstrakurikuler pada Peserta Didik</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>