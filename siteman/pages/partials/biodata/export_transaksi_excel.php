<?php
declare(strict_types=1);
require_once dirname(__DIR__,4).'/config/runtime.php';sds_session_start();
if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Akses ditolak.');}
require_once dirname(__DIR__,4).'/db.php';require_once dirname(__DIR__,4).'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$studentId=max(0,(int)($_GET['id']??0));$month=max(0,min(12,(int)($_GET['bulan']??0)));$year=max(0,(int)($_GET['tahun']??0));
$conditions=['student_id'=>$studentId];$whereKantin='tk.id_siswa=?';$whereTopup='id_siswa=?';$types='i';$params=[$studentId];
if($month>0&&$year>0){$whereKantin.=' AND MONTH(tk.tanggal)=? AND YEAR(tk.tanggal)=?';$whereTopup.=' AND MONTH(tanggal)=? AND YEAR(tanggal)=?';$types.='ii';$params[]=$month;$params[]=$year;}
$sql="SELECT tk.tanggal waktu,'Belanja' jenis_transaksi,COALESCE(k.nama,'-') kantin,tk.nominal FROM transaksi_kantin tk LEFT JOIN kantin k ON k.id=tk.id_kantin WHERE {$whereKantin} UNION ALL SELECT tanggal waktu,'Topup' jenis_transaksi,'-' kantin,nominal FROM topup WHERE {$whereTopup} ORDER BY waktu DESC";
$allTypes=$types.$types;$allParams=array_merge($params,$params);$stmt=$conn->prepare($sql);$stmt->bind_param($allTypes,...$allParams);$stmt->execute();$result=$stmt->get_result();
$book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle('RIWAYAT_TRANSAKSI');$sheet->fromArray(['Waktu','Jenis Transaksi','Kantin','Nominal'],null,'A1');$sheet->getStyle('A1:D1')->getFont()->setBold(true);$row=2;$total=0;
while($data=$result->fetch_assoc()){$sheet->setCellValueExplicit('A'.$row,(string)$data['waktu'],DataType::TYPE_STRING);$sheet->setCellValueExplicit('B'.$row,(string)$data['jenis_transaksi'],DataType::TYPE_STRING);$sheet->setCellValueExplicit('C'.$row,(string)$data['kantin'],DataType::TYPE_STRING);$sheet->setCellValue('D'.$row,(int)$data['nominal']);$total+=(int)$data['nominal'];$row++;}$stmt->close();
$sheet->setCellValue('C'.$row,'Total');$sheet->setCellValue('D'.$row,$total);$sheet->getStyle('C'.$row.':D'.$row)->getFont()->setBold(true);$sheet->getStyle('D2:D'.$row)->getNumberFormat()->setFormatCode('#,##0');foreach(range('A','D') as $column)$sheet->getColumnDimension($column)->setAutoSize(true);$sheet->freezePane('A2');$sheet->setAutoFilter('A1:D'.max(1,$row-1));
$filename='riwayat_transaksi_'.$studentId.'_'.date('Ymd_His').'.xlsx';header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Cache-Control: no-store, no-cache, must-revalidate');(new Xlsx($book))->save('php://output');exit;
