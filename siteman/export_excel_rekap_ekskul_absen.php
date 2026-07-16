<?php
declare(strict_types=1);
require_once '../config/runtime.php'; sds_session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(403); exit('Akses ditolak.'); }
require_once '../db.php'; require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$ekskulId=max(0,(int)($_GET['ekskul_id']??0));$awal=(string)($_GET['awal']??date('Y-m-01'));$akhir=(string)($_GET['akhir']??date('Y-m-d'));
$stmt=$conn->prepare('SELECT DISTINCT tanggal FROM ekskul_absensi WHERE ekskul_id=? AND tanggal BETWEEN ? AND ? ORDER BY tanggal');$stmt->bind_param('iss',$ekskulId,$awal,$akhir);$stmt->execute();$dates=[];$res=$stmt->get_result();while($x=$res->fetch_assoc())$dates[]=$x['tanggal'];$stmt->close();
$stmt=$conn->prepare('SELECT ps.id,ps.nama_lengkap FROM ekstrakurikuler_siswa es JOIN pendaftaran_siswa ps ON ps.id=es.siswa_id WHERE es.ekstrakurikuler_id=? ORDER BY ps.nama_lengkap');$stmt->bind_param('i',$ekskulId);$stmt->execute();$students=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
$stmt=$conn->prepare('SELECT siswa_id,tanggal,status FROM ekskul_absensi WHERE ekskul_id=? AND tanggal BETWEEN ? AND ?');$stmt->bind_param('iss',$ekskulId,$awal,$akhir);$stmt->execute();$attendance=[];$res=$stmt->get_result();while($x=$res->fetch_assoc())$attendance[$x['siswa_id']][$x['tanggal']]=$x['status'];$stmt->close();
$book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle('REKAP_EKSKUL');$headers=['No','Nama Siswa',...array_map(fn($d)=>date('d/m/Y',strtotime($d)),$dates),'Hadir','Izin','Sakit','Alfa'];$sheet->fromArray($headers,null,'A1');$sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
$row=2;$no=1;foreach($students as $student){$counts=['H'=>0,'I'=>0,'S'=>0,'A'=>0];$values=[$no++,$student['nama_lengkap']];foreach($dates as $date){$raw=strtoupper((string)($attendance[$student['id']][$date]??''));$code=match($raw){'H','HADIR'=>'H','I','IZIN','IJIN'=>'I','S','SAKIT'=>'S','A','ALFA','ALPA'=>'A',default=>''};if($code!=='')$counts[$code]++;$values[]=['H'=>'HADIR','I'=>'IZIN','S'=>'SAKIT','A'=>'ALPA'][$code]??'-';}$values=array_merge($values,array_values($counts));foreach($values as $i=>$value){$cell=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).$row;if($i===0||$i>=count($values)-4)$sheet->setCellValue($cell,(int)$value);else$sheet->setCellValueExplicit($cell,(string)$value,DataType::TYPE_STRING);}$row++;}
foreach(range(1,count($headers)) as $i)$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);$sheet->freezePane('A2');$sheet->setAutoFilter('A1:'.$sheet->getHighestColumn().max(1,$row-1));
$filename='rekap_absensi_ekskul_'.date('Ymd_His').'.xlsx';header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Cache-Control: no-store, no-cache, must-revalidate');(new Xlsx($book))->save('php://output');exit;
