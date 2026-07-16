<?php
declare(strict_types=1);

$tahun=trim((string)($_POST['tahun_ajaran']??''));
$tingkatId=(int)($_POST['tingkat_id']??0);
$back=static function(string $message,bool $success=false)use($tahun):never{$_SESSION[$success?'success':'error']=$message;header('Location: kuota_kelas?tahun='.urlencode($tahun));exit;};
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!sds_csrf_verify((string)($_POST['csrf']??'')))$back('Sesi formulir berakhir. Muat ulang halaman dan coba kembali.');
if($tahun===''||$tingkatId<=0)$back('Tahun ajaran atau tingkat kelas tidak valid.');

$stmt=$conn->prepare('SELECT tk.nama_tingkat FROM tingkat_kelas tk WHERE tk.id=? LIMIT 1');$stmt->bind_param('i',$tingkatId);$stmt->execute();$tingkat=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$tingkat)$back('Tingkat kelas tidak ditemukan.');

try{
    $conn->begin_transaction();
    $stmt=$conn->prepare("SELECT k.id,k.jurusan_id,k.kuota,COUNT(sk.id) terisi FROM kelas k LEFT JOIN siswa_kelas sk ON sk.kelas_id=k.id AND sk.tahun_ajaran=k.tahun_ajaran WHERE k.tahun_ajaran=? AND k.tingkat_id=? GROUP BY k.id,k.jurusan_id,k.kuota ORDER BY k.jurusan_id,k.id FOR UPDATE");
    $stmt->bind_param('si',$tahun,$tingkatId);$stmt->execute();$classResult=$stmt->get_result();$classesByMajor=[];
    while($row=$classResult->fetch_assoc()){$row['tersedia']=max(0,(int)$row['kuota']-(int)$row['terisi']);$classesByMajor[(int)$row['jurusan_id']][]=$row;}$stmt->close();
    if(!$classesByMajor)throw new RuntimeException('Belum ada rombel untuk tingkat dan tahun ajaran tersebut.');

    $placed=0;$skippedCapacity=0;
    $candidateStmt=$conn->prepare("SELECT ps.id FROM pendaftaran_siswa ps WHERE ps.jurusan_id=? AND ps.tahun_ajaran=? AND ps.status_aktif=1 AND NOT EXISTS(SELECT 1 FROM siswa_kelas sk WHERE sk.siswa_id=ps.id AND sk.tahun_ajaran=?) ORDER BY ps.nama_lengkap,ps.id FOR UPDATE");
    $insertStmt=$conn->prepare('INSERT INTO siswa_kelas(siswa_id,kelas_id,tahun_ajaran,naik_kelas) VALUES(?,?,?,1)');
    $updateStudent=$conn->prepare('UPDATE pendaftaran_siswa SET kelas_id=? WHERE id=?');
    foreach($classesByMajor as $majorId=>$classes){
        $candidateStmt->bind_param('iss',$majorId,$tahun,$tahun);$candidateStmt->execute();$candidateResult=$candidateStmt->get_result();$studentIds=[];while($row=$candidateResult->fetch_assoc())$studentIds[]=(int)$row['id'];
        $studentIndex=0;
        foreach($classes as $class){$classId=(int)$class['id'];$available=(int)$class['tersedia'];for($slot=0;$slot<$available&&isset($studentIds[$studentIndex]);$slot++,$studentIndex++){$studentId=$studentIds[$studentIndex];$insertStmt->bind_param('iis',$studentId,$classId,$tahun);$insertStmt->execute();$updateStudent->bind_param('ii',$classId,$studentId);$updateStudent->execute();$placed++;}}
        $skippedCapacity+=max(0,count($studentIds)-$studentIndex);
    }
    $candidateStmt->close();$insertStmt->close();$updateStudent->close();
    $sync=$conn->prepare('UPDATE kelas k SET terisi=(SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id=k.id AND sk.tahun_ajaran=k.tahun_ajaran) WHERE k.tahun_ajaran=? AND k.tingkat_id=?');$sync->bind_param('si',$tahun,$tingkatId);$sync->execute();$sync->close();
    $conn->commit();
    if(function_exists('catatLog'))catatLog($conn,(int)($_SESSION['admin_id']??0),'Generate Kelas','Tahun '.$tahun.', tingkat '.$tingkat['nama_tingkat'].': '.$placed.' siswa ditempatkan');
    $message=$placed.' peserta didik berhasil ditempatkan ke rombel tingkat '.$tingkat['nama_tingkat'].'.';if($skippedCapacity>0)$message.=' '.$skippedCapacity.' peserta didik belum ditempatkan karena kuota penuh.';if($placed===0&&$skippedCapacity===0)$message='Tidak ada peserta didik baru yang perlu ditempatkan untuk tingkat dan tahun ajaran tersebut.';
    $back($message,true);
}catch(Throwable $e){try{$conn->rollback();}catch(Throwable){}error_log('[SDS generate kelas] '.$e->getMessage());$back('Generate kelas gagal: '.$e->getMessage());}
