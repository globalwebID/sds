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
                                <h5 class="card-title mb-2 mt-2" style="padding: 3px 10px;">Riwayat Rombel Peserta Didik</h5>
                            </div>
                            <div class="col-auto ms-auto text-end">
                                <a href="#"
                                    class="btn btn-secondary">
                                    Semua Nilai
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mt-3 mb-0">
                        <tr>
                            <th>No</th>
                            <th>Tahun Ajaran</th>
                            <th>Kelas</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        <?php if (count($student['riwayat_kelas']) > 0): $no = 1;
                            foreach ($student['riwayat_kelas'] as $rk): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($rk['tahun_ajaran']) ?></td>
                                    <td>
                                        <a href="kuota_kelas_siswa?kelas_id=<?= $rk['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($rk['nama_kelas']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                            $statusRombel = $rk['status_rombel'] ?? '-';
                                            if ($statusRombel === 'Tidak Naik') {
                                                echo '<span class="badge bg-danger">Tidak Naik</span>';
                                            } elseif ($statusRombel === 'Naik') {
                                                echo '<span class="badge bg-success">Naik</span>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                        ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="#" class="btn btn-secondary">Rekap Nilai</a>
                                        <a href="#" class="btn btn-secondary">Rekap Kehadiran</a>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada Rombel pada Peserta Didik</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>