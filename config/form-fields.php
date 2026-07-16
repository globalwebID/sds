<?php
declare(strict_types=1);

function sds_default_form_fields(): array
{
    return [
        [1,'nama_lengkap','Nama Lengkap','text'], [2,'email','Email','text'],
        [3,'nisn','NISN','text'], [4,'nipd','NIPD','text'],
        [5,'sekolah_asal','Sekolah Asal','text'], [6,'nomor_ijazah','Nomor Ijazah','text'],
        [7,'jenis_kelamin','Jenis Kelamin','select'], [8,'tempat_lahir','Tempat Lahir','text'],
        [9,'tanggal_lahir','Tanggal Lahir','date'], [10,'no_kk','No. KK','text'],
        [11,'nik','NIK','text'], [12,'no_registrasi_akta','No. Registrasi Akta','text'],
        [13,'kebutuhan_khusus','Kebutuhan Khusus','text'], [14,'agama','Agama','select'],
        [15,'alamat','Alamat','text'], [22,'tempat_tinggal','Tempat Tinggal','text'],
        [23,'moda_transportasi','Moda Transportasi','text'], [24,'anak_ke','Anak Ke-','text'],
        [25,'jumlah_saudara_kandung','Jumlah Saudara Kandung','text'], [26,'tinggi_badan','Tinggi Badan','text'],
        [27,'berat_badan','Berat Badan','text'], [28,'hobi','Hobi','text'],
        [29,'cita_cita','Cita-cita','text'], [30,'nomor_kip','Nomor KIP','text'],
        [31,'file_kip','File KIP','file'], [32,'nama_ayah','Nama Ayah','text'],
        [33,'nik_ayah','NIK Ayah','text'], [34,'tahun_lahir_ayah','Tahun Lahir Ayah','text'],
        [35,'pendidikan_ayah','Pendidikan Ayah','text'], [36,'pekerjaan_ayah','Pekerjaan Ayah','text'],
        [37,'penghasilan_ayah','Penghasilan Ayah','text'], [38,'nama_ibu','Nama Ibu','text'],
        [39,'nik_ibu','NIK Ibu','text'], [40,'tahun_lahir_ibu','Tahun Lahir Ibu','text'],
        [41,'pendidikan_ibu','Pendidikan Ibu','text'], [42,'pekerjaan_ibu','Pekerjaan Ibu','text'],
        [43,'penghasilan_ibu','Penghasilan Ibu','text'], [44,'nama_wali','Nama Wali','text'],
        [45,'nik_wali','NIK Wali','text'], [46,'tahun_lahir_wali','Tahun Lahir Wali','text'],
        [47,'pendidikan_wali','Pendidikan Wali','text'], [48,'pekerjaan_wali','Pekerjaan Wali','text'],
        [49,'penghasilan_wali','Penghasilan Wali','text'], [50,'nohp_ortu','No HP Orang Tua','text'],
        [51,'nohp_siswa','No HP Siswa','text'], [52,'file_kk','File KK','file'],
        [53,'file_ijazah','File Ijazah','file'], [54,'foto','Foto','file'],
        [55,'pernyataan_setuju','Pernyataan Setuju','checkbox'], [56,'jurusan_id','Jurusan','select'],
        [57,'provinsi','Provinsi','select'], [58,'kota','Kabupaten/Kota','select'],
        [59,'kecamatan','Kecamatan','select'], [60,'desa','Desa','select'],
        [61,'koordinat','Koordinat Rumah','text'], [62,'nomor_kps','Nomor KPS','text'],
        [63,'nomor_pkh','Nomor PKH','text'], [64,'nomor_kks','Nomor KKS','text'],
        [65,'nomor_kis','Nomor KIS','text'], [66,'file_akta','File Akta Kelahiran','file'],
        [67,'file_kps','File KPS','file'], [68,'file_pkh','File PKH','file'],
        [69,'file_kks','File KKS','file'], [70,'file_kis','File KIS','file'],
    ];
}

function sds_seed_form_fields(mysqli $conn): int
{
    $count = (int)$conn->query('SELECT COUNT(*) FROM form_fields')->fetch_row()[0];
    if ($count > 0) return 0;

    $stmt = $conn->prepare('INSERT INTO form_fields (id, name, label, type, is_active, is_required) VALUES (?, ?, ?, ?, 1, 0)');
    $inserted = 0;
    $conn->begin_transaction();
    try {
        foreach (sds_default_form_fields() as [$id,$name,$label,$type]) {
            $stmt->bind_param('isss', $id, $name, $label, $type);
            $stmt->execute();
            $inserted++;
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    } finally {
        $stmt->close();
    }
    return $inserted;
}

