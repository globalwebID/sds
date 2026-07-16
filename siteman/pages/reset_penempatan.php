<?php
declare(strict_types=1);

$tahun=trim((string)($_POST['tahun_ajaran']??''));
$tingkatId=(int)($_POST['tingkat_id']??0);
$back=static function(string $message,bool $success=false)use($tahun):never{$_SESSION[$success?'success':'error']=$message;header('Location: kuota_kelas?tahun='.urlencode($tahun));exit;};
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!sds_csrf_verify((string)($_POST['csrf']??'')))$back('Sesi formulir berakhir. Muat ulang halaman dan coba kembali.');
if($tahun===''||$tingkatId<=0)$back('Tahun ajaran atau tingkat kelas tidak valid.');
$stmt=$conn->prepare('SELECT nama_tingkat FROM tingkat_kelas WHERE id=? LIMIT 1');$stmt->bind_param('i',$tingkatId);$stmt->execute();$tingkat=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$tingkat)$back('Tingkat kelas tidak ditemukan.');

try{
    $conn->begin_transaction();
    $stmt=$conn->prepare('SELECT id FROM kelas WHERE tahun_ajaran=? AND tingkat_id=? FOR UPDATE');$stmt->bind_param('si',$tahun,$tingkatId);$stmt->execute();$classes=$stmt->get_result();$classIds=[];while($row=$classes->fetch_assoc())$classIds[]=(int)$row['id'];$stmt->close();
    if(!$classIds)throw new RuntimeException('Rombel untuk tingkat dan tahun ajaran tersebut tidak ditemukan.');
    $stmt=$conn->prepare('UPDATE pendaftaran_siswa ps JOIN kelas k ON k.id=ps.kelas_id SET ps.kelas_id=NULL WHERE k.tahun_ajaran=? AND k.tingkat_id=?');$stmt->bind_param('si',$tahun,$tingkatId);$stmt->execute();$studentsReset=$stmt->affected_rows;$stmt->close();
    $historyDeleted=0;$deleteStmt=$conn->prepare('DELETE FROM siswa_kelas WHERE tahun_ajaran=? AND kelas_id=?');
    foreach($classIds as $classId){$deleteStmt->bind_param('si',$tahun,$classId);$deleteStmt->execute();$historyDeleted+=$deleteStmt->affected_rows;}
    $deleteStmt->close();
    $stmt=$conn->prepare('UPDATE kelas k SET terisi=(SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id=k.id AND sk.tahun_ajaran=k.tahun_ajaran) WHERE k.tahun_ajaran=? AND k.tingkat_id=?');$stmt->bind_param('si',$tahun,$tingkatId);$stmt->execute();$stmt->close();
    $conn->commit();
    if(function_exists('catatLog'))catatLog($conn,(int)($_SESSION['admin_id']??0),'Reset Kelas','Tahun '.$tahun.', tingkat '.$tingkat['nama_tingkat'].': '.$historyDeleted.' penempatan dihapus');
    $back('Reset tingkat '.$tingkat['nama_tingkat'].' berhasil. '.$historyDeleted.' penempatan dihapus dan '.$studentsReset.' data siswa dilepaskan dari rombel.',true);
}catch(Throwable $e){try{$conn->rollback();}catch(Throwable){}error_log('[SDS reset kelas] '.$e->getMessage());$back('Reset kelas gagal: '.$e->getMessage());}
