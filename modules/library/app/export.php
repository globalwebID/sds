<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
perpus_session_start();
define('SDS_PERPUSTAKAAN_APP', true);
$root = dirname(__DIR__);
require_once $root . '/db.php';
require_once $root . '/config/perpus.php';
require_once __DIR__ . '/lib/XlsxLite.php';
sds_perpus_ensure_schema($conn);
$perpusUser = perpus_require_login($conn);
$type = strtolower(trim((string)($_GET['type'] ?? '')));
$school = 'Sekolah';
$q=$conn->query('SELECT nama_sekolah FROM pengaturan LIMIT 1');if($q&&($x=$q->fetch_assoc()))$school=(string)$x['nama_sekolah'];
$titleMap=['anggota'=>'DATA ANGGOTA PERPUSTAKAAN','koleksi'=>'DATA KOLEKSI BIBLIOGRAFI','eksemplar'=>'DATA EKSEMPLAR DAN BARCODE','pinjaman_aktif'=>'PINJAMAN AKTIF','riwayat'=>'RIWAYAT PEMINJAMAN','terlambat'=>'PINJAMAN TERLAMBAT','denda'=>'REKAP DENDA','kunjungan'=>'DATA KUNJUNGAN','anggota_tanpa_rfid'=>'ANGGOTA BELUM MEMILIKI RFID'];
if(!isset($titleMap[$type])){http_response_code(400);exit('Jenis export tidak valid.');}
$headers=[];$rows=[];$widths=[];$textColumns=[];
if(in_array($type,['anggota','anggota_tanpa_rfid'],true)){
 $headers=['Nomor Anggota','Jenis','Nama','Identitas','Unit/Rombel','Tipe Anggota','Status','Berlaku Sampai','RFID'];$widths=[20,16,30,20,24,20,18,18,22];$textColumns=[0,3,8];
 $r=$conn->query('SELECT a.*,tm.nama tipe_member FROM perpus_anggota a LEFT JOIN perpus_tipe_member tm ON tm.id=a.tipe_member_id ORDER BY a.id');
 while($r&&($x=$r->fetch_assoc())){$p=sds_perpus_identity_profile($conn,(string)$x['pemilik_tipe'],(int)($x['pemilik_id']??0),$x);$uid='';if(in_array($x['pemilik_tipe'],['siswa','pegawai'],true)){$stmt=$conn->prepare('SELECT uid FROM kartu_rfid WHERE pemilik_tipe=? AND pemilik_id=? LIMIT 1');$oid=(int)$x['pemilik_id'];$ownerType=(string)$x['pemilik_tipe'];$stmt->bind_param('si',$ownerType,$oid);$stmt->execute();$uid=(string)($stmt->get_result()->fetch_assoc()['uid']??'');$stmt->close();}if($type==='anggota_tanpa_rfid'&&$uid!=='')continue;$rows[]=[(string)$x['nomor_anggota'],ucfirst((string)$x['pemilik_tipe']),$p['nama'],$p['identitas'],$p['unit'],(string)($x['tipe_member']??''),ucfirst((string)$x['status_keanggotaan']),(string)($x['tanggal_berakhir']??''),$uid];}
}elseif($type==='koleksi'){
 $textColumns=[2,10,11];$headers=['ID Buku','Judul','ISBN','Pengarang','Penerbit','Tahun','Kategori','Tipe Koleksi','GMD','Bahasa','Klasifikasi','Nomor Panggil','Tampil OPAC','Jumlah Eksemplar','Tersedia'];$widths=[12,38,18,28,24,12,20,20,18,16,15,20,15,18,14];
 $sql="SELECT b.*,kb.nama kategori,tk.nama tipe,g.nama gmd,COALESCE(NULLIF(p.nama,''),b.penerbit_teks) penerbit,(SELECT GROUP_CONCAT(pa.nama ORDER BY pa.nama SEPARATOR '; ') FROM perpus_buku_pengarang bp JOIN perpus_pengarang pa ON pa.id=bp.pengarang_id WHERE bp.buku_id=b.id) pengarang,(SELECT COUNT(*) FROM perpus_buku_eksemplar e WHERE e.buku_id=b.id AND e.status<>'nonaktif') jumlah,(SELECT COUNT(*) FROM perpus_buku_eksemplar e WHERE e.buku_id=b.id AND e.status='tersedia') tersedia FROM perpus_buku b LEFT JOIN perpus_kategori_buku kb ON kb.id=b.kategori_id LEFT JOIN perpus_tipe_koleksi tk ON tk.id=b.tipe_koleksi_id LEFT JOIN perpus_gmd g ON g.id=b.gmd_id LEFT JOIN perpus_penerbit p ON p.id=b.penerbit_id ORDER BY b.judul";
 $r=$conn->query($sql);while($r&&($x=$r->fetch_assoc()))$rows[]=[(int)$x['id'],$x['judul'],$x['isbn'],$x['pengarang'],$x['penerbit'],$x['tahun_terbit'],$x['kategori'],$x['tipe'],$x['gmd'],$x['bahasa'],$x['klasifikasi'],$x['nomor_panggil'],(int)$x['status_opac'],(int)$x['jumlah'],(int)$x['tersedia']];
}elseif($type==='eksemplar'){
 $textColumns=[2,3,4];$headers=['ID','Judul','ISBN','Barcode','Nomor Inventaris','Tipe Koleksi','Lokasi Rak','Kondisi','Status','Sumber Pengadaan','Harga','Tanggal Pengadaan','Catatan'];$widths=[12,36,18,22,22,20,20,15,15,22,16,18,28];
 $r=$conn->query("SELECT e.*,b.judul,b.isbn,tk.nama tipe FROM perpus_buku_eksemplar e JOIN perpus_buku b ON b.id=e.buku_id LEFT JOIN perpus_tipe_koleksi tk ON tk.id=e.tipe_koleksi_id ORDER BY b.judul,e.barcode");while($r&&($x=$r->fetch_assoc()))$rows[]=[(int)$x['id'],$x['judul'],$x['isbn'],$x['barcode'],$x['nomor_inventaris'],$x['tipe'],$x['lokasi_rak'],$x['kondisi_fisik'],$x['status'],$x['sumber_pengadaan'],(float)$x['harga'],$x['tanggal_pengadaan'],$x['catatan']];
}elseif(in_array($type,['pinjaman_aktif','riwayat','terlambat'],true)){
 $textColumns=[1,5];$headers=['Tanggal Pinjam','Nomor Anggota','Nama','Unit','Judul','Barcode','Jatuh Tempo','Tanggal Kembali','Status','Perpanjangan','Denda','Status Denda'];$widths=[18,20,30,22,36,20,18,20,16,16,16,18];
 $where=$type==='pinjaman_aktif'?"pd.status='dipinjam'":($type==='terlambat'?"pd.status='dipinjam' AND pd.tanggal_jatuh_tempo<CURDATE()":'1=1');
 $r=$conn->query("SELECT p.tanggal_pinjam,pd.*,a.nomor_anggota,a.pemilik_tipe,a.pemilik_id,a.legacy_nama,a.legacy_nis,a.legacy_kelas,a.legacy_jurusan,b.judul,e.barcode FROM perpus_peminjaman_detail pd JOIN perpus_peminjaman p ON p.id=pd.peminjaman_id JOIN perpus_anggota a ON a.id=p.anggota_id LEFT JOIN perpus_buku b ON b.id=pd.buku_id LEFT JOIN perpus_buku_eksemplar e ON e.id=pd.eksemplar_id WHERE $where ORDER BY p.tanggal_pinjam DESC,pd.id DESC");
 while($r&&($x=$r->fetch_assoc())){$p=sds_perpus_identity_profile($conn,(string)$x['pemilik_tipe'],(int)($x['pemilik_id']??0),$x);$rows[]=[$x['tanggal_pinjam'],$x['nomor_anggota'],$p['nama'],$p['unit'],$x['judul'],$x['barcode'],$x['tanggal_jatuh_tempo'],$x['tanggal_kembali'],$x['status'],(int)($x['jumlah_perpanjangan']??0),(float)$x['denda'],$x['denda_status']];}
}elseif($type==='denda'){
 $textColumns=[0];$headers=['Nomor Anggota','Nama','Unit','Judul','Tanggal Kembali','Denda Transaksi','Dibayar/Disesuaikan','Sisa','Status'];$widths=[20,30,22,36,20,18,20,18,18];
 $r=$conn->query("SELECT pd.*,a.nomor_anggota,a.pemilik_tipe,a.pemilik_id,a.legacy_nama,a.legacy_nis,a.legacy_kelas,a.legacy_jurusan,b.judul,COALESCE((SELECT SUM(dp.nominal) FROM perpus_denda_pembayaran dp WHERE dp.detail_id=pd.id),0) dibayar FROM perpus_peminjaman_detail pd JOIN perpus_peminjaman p ON p.id=pd.peminjaman_id JOIN perpus_anggota a ON a.id=p.anggota_id LEFT JOIN perpus_buku b ON b.id=pd.buku_id WHERE pd.denda>0 ORDER BY pd.tanggal_kembali DESC,pd.id DESC");while($r&&($x=$r->fetch_assoc())){$p=sds_perpus_identity_profile($conn,(string)$x['pemilik_tipe'],(int)($x['pemilik_id']??0),$x);$rows[]=[$x['nomor_anggota'],$p['nama'],$p['unit'],$x['judul'],$x['tanggal_kembali'],(float)$x['denda'],(float)$x['dibayar'],max(0,(float)$x['denda']-(float)$x['dibayar']),$x['denda_status']];}
}elseif($type==='kunjungan'){
 $textColumns=[1];$headers=['Waktu Kunjungan','Nomor Anggota','Nama','Jenis','Unit','Sumber'];$widths=[22,20,30,16,24,16];
 $r=$conn->query("SELECT k.*,a.nomor_anggota,a.pemilik_tipe,a.pemilik_id,a.legacy_nama,a.legacy_nis,a.legacy_kelas,a.legacy_jurusan FROM perpus_kunjungan k JOIN perpus_anggota a ON a.id=k.anggota_id ORDER BY k.waktu_kunjungan DESC");while($r&&($x=$r->fetch_assoc())){$p=sds_perpus_identity_profile($conn,(string)$x['pemilik_tipe'],(int)($x['pemilik_id']??0),$x);$rows[]=[$x['waktu_kunjungan'],$x['nomor_anggota'],$p['nama'],ucfirst((string)$x['pemilik_tipe']),$p['unit'],$x['sumber']];}
}
$filename='perpus_'.$type.'_'.date('Ymd_His').'.xlsx';
PerpusXlsxLite::download($filename,[['name'=>'DATA','title'=>$titleMap[$type],'subtitle'=>$school.' · Dicetak '.date('d/m/Y H:i'),'headers'=>$headers,'rows'=>$rows,'widths'=>$widths,'text_columns'=>$textColumns]]);
