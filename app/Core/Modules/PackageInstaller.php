<?php
declare(strict_types=1);

final class SdsPackageInstaller
{
    public function __construct(private readonly string $root) {}

    public function stage(string $zipPath,?string $checksum=null):array
    {
        require_once __DIR__.'/PackageInspector.php';
        $inspection=(new SdsPackageInspector($this->root))->inspect($zipPath,$checksum);
        $work=$this->makeWorkDirectory((string)$inspection['metadata']['id']);$stage=$work.'/stage';mkdir($stage,0750,true);
        $zip=new ZipArchive();if($zip->open($inspection['path'])!==true)throw new RuntimeException('Paket ZIP tidak dapat dibuka kembali.');
        try{
            foreach($inspection['entries'] as $entry){$destination=$stage.'/'.$entry;$directory=dirname($destination);if(!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory))throw new RuntimeException('Folder staging gagal dibuat.');$input=$zip->getStream($entry);if(!is_resource($input))throw new RuntimeException('File paket gagal dibaca: '.$entry);$output=fopen($destination,'wb');if(!is_resource($output)){fclose($input);throw new RuntimeException('File staging gagal dibuat: '.$entry);}stream_copy_to_stream($input,$output);fclose($input);fclose($output);}
        }finally{$zip->close();}
        foreach((array)($inspection['metadata']['file_hashes']??[]) as $entry=>$expected)if(!hash_equals((string)$expected,hash_file('sha256',$stage.'/'.$entry)))throw new RuntimeException('Verifikasi staging gagal: '.$entry);
        file_put_contents($work.'/inspection.json',json_encode($inspection['metadata']+['package_checksum'=>$inspection['checksum']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL,LOCK_EX);
        return $inspection+['work_directory'=>$work,'stage_directory'=>$stage];
    }

    public function apply(string $zipPath,?string $checksum=null):array
    {
        $lockPath=$this->root.'/storage/module-installer.lock';$lock=fopen($lockPath,'c+');if(!is_resource($lock)||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Instalasi modul lain sedang berjalan.');
        $staged=null;$changed=[];$created=[];$backup=null;$databaseBackup=null;
        try{
            $staged=$this->stage($zipPath,$checksum);$backup=$staged['work_directory'].'/backup';mkdir($backup,0750,true);
            $modulePrefix='modules/'.(string)$staged['metadata']['id'].'/database/migrations/';$migrations=array_values(array_filter($staged['entries'],static fn(string $entry):bool=>(str_starts_with($entry,'install/migrations/')||str_starts_with($entry,$modulePrefix))&&str_ends_with(strtolower($entry),'.sql')));
            $pending=$this->pendingMigrations((string)$staged['metadata']['id'],$migrations,$staged['stage_directory']);
            if($pending)$databaseBackup=$this->createDatabaseBackup();
            foreach($staged['entries'] as $entry){
                $source=$staged['stage_directory'].'/'.$entry;$destination=$this->root.'/'.$entry;
                if(is_file($destination)&&hash_equals(hash_file('sha256',$source),hash_file('sha256',$destination)))continue;
                if(is_file($destination)){$copy=$backup.'/'.$entry;if(!is_dir(dirname($copy))&&!mkdir(dirname($copy),0750,true)&&!is_dir(dirname($copy)))throw new RuntimeException('Folder backup file gagal dibuat.');if(!copy($destination,$copy))throw new RuntimeException('Backup file gagal: '.$entry);$changed[]=$entry;}else{$created[]=$entry;}
                if(!is_dir(dirname($destination))&&!mkdir(dirname($destination),0750,true)&&!is_dir(dirname($destination)))throw new RuntimeException('Folder tujuan gagal dibuat.');if(!copy($source,$destination))throw new RuntimeException('Pemasangan file gagal: '.$entry);
            }
            foreach($pending as $migration)$this->applyMigration((string)$staged['metadata']['id'],$migration,$staged['stage_directory'].'/'.$migration);
            $this->recordInstallation($staged['metadata'],$staged['checksum'],'installed',['changed'=>$changed,'created'=>$created,'database_backup'=>$databaseBackup]);
            file_put_contents($staged['work_directory'].'/installed.json',json_encode(['status'=>'installed','changed'=>$changed,'created'=>$created,'migrations'=>$pending,'database_backup'=>$databaseBackup,'installed_at'=>date(DATE_ATOM)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL,LOCK_EX);
            return $staged+['changed'=>$changed,'created'=>$created,'migrations'=>$pending,'database_backup'=>$databaseBackup,'backup_directory'=>$backup];
        }catch(Throwable $e){
            if($staged){foreach(array_reverse($created) as $entry)if(is_file($this->root.'/'.$entry))@unlink($this->root.'/'.$entry);foreach(array_reverse($changed) as $entry)if(is_file($backup.'/'.$entry))@copy($backup.'/'.$entry,$this->root.'/'.$entry);try{$this->recordInstallation($staged['metadata'],$staged['checksum'],'failed',['error'=>$e->getMessage(),'database_backup'=>$databaseBackup]);}catch(Throwable){}file_put_contents($staged['work_directory'].'/failed.txt',date(DATE_ATOM).' '.$e->getMessage().PHP_EOL.'Database recovery point: '.($databaseBackup??'-').PHP_EOL,LOCK_EX);}throw $e;
        }finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}@unlink($lockPath);}
    }

    private function makeWorkDirectory(string $module):string
    {
        $base=$this->root.'/storage/module-installer';if(!is_dir($base)&&!mkdir($base,0750,true)&&!is_dir($base))throw new RuntimeException('Folder kerja installer gagal dibuat.');$path=$base.'/'.date('Ymd-His').'-'.$module.'-'.bin2hex(random_bytes(4));if(!mkdir($path,0750,true))throw new RuntimeException('Sesi staging gagal dibuat.');return $path;
    }

    private function pendingMigrations(string $module,array $migrations,string $stage):array
    {
        require_once $this->root.'/config/runtime.php';$db=sds_mysqli('main');$pending=[];
        $statement=$db->prepare('SELECT checksum FROM sds_module_migrations WHERE module_id=? AND (migration=? OR migration LIKE ?) ORDER BY id DESC LIMIT 1');
        foreach($migrations as $migration){$checksum=hash_file('sha256',$stage.'/'.$migration);$migrationLike='%/'.basename($migration);$statement->bind_param('sss',$module,$migration,$migrationLike);$statement->execute();$row=$statement->get_result()->fetch_assoc();if($row&& !hash_equals((string)$row['checksum'],$checksum))throw new RuntimeException('Migrasi pernah diterapkan dengan isi berbeda: '.$migration);if(!$row)$pending[]=$migration;}
        $statement->close();$db->close();return $pending;
    }

    private function createDatabaseBackup():string
    {
        $command=[PHP_BINARY,$this->root.'/tools/backup.php'];$pipes=[];$process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$this->root);if(!is_resource($process))throw new RuntimeException('Proses backup database tidak dapat dimulai.');fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($process);if($code!==0)throw new RuntimeException('Backup database gagal: '.trim($stderr?:$stdout));if(!preg_match('/^File:\s*(.+)$/mi',$stdout,$match)||!is_file(trim($match[1])))throw new RuntimeException('Lokasi backup database tidak ditemukan.');return trim($match[1]);
    }

    private function applyMigration(string $module,string $migration,string $path):void
    {
        $cfg=sds_database_config('main');$xamppRoot=dirname(dirname(PHP_BINARY));$binary=$xamppRoot.'/mysql/bin/mysql.exe';if(!is_file($binary))$binary='mysql';$escape=static fn(string $value):string=>'"'.str_replace(['\\','"'],['\\\\','\\"'],$value).'"';$defaultsFile=tempnam(sys_get_temp_dir(),'sds-module-db-');if($defaultsFile===false)throw new RuntimeException('Konfigurasi sementara database gagal dibuat.');
        $defaults="[client]\n".'host='.$escape((string)$cfg['host'])."\n".'port='.(int)$cfg['port']."\n".'user='.$escape((string)$cfg['username'])."\n".'password='.$escape((string)$cfg['password'])."\ndefault-character-set=utf8mb4\n";
        try{if(file_put_contents($defaultsFile,$defaults,LOCK_EX)===false)throw new RuntimeException('Konfigurasi sementara database gagal ditulis.');@chmod($defaultsFile,0600);$pipes=[];$process=proc_open([$binary,'--defaults-extra-file='.$defaultsFile,(string)$cfg['database']],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$this->root);if(!is_resource($process))throw new RuntimeException('Klien MySQL tidak dapat dijalankan.');$sql=file_get_contents($path);fwrite($pipes[0],(string)$sql);fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($process);if($code!==0)throw new RuntimeException('Migrasi gagal '.$migration.': '.trim($stderr?:$stdout));$db=sds_mysqli('main');$checksum=hash_file('sha256',$path);$statement=$db->prepare('INSERT INTO sds_module_migrations (module_id,migration,checksum) VALUES (?,?,?)');$statement->bind_param('sss',$module,$migration,$checksum);$statement->execute();$statement->close();$db->close();}finally{@unlink($defaultsFile);}
    }

    private function recordInstallation(array $metadata,string $checksum,string $status,array $details):void
    {
        require_once $this->root.'/config/runtime.php';$db=sds_mysqli('main');$module=(string)$metadata['id'];$package=(string)$metadata['package'];$version=(string)$metadata['version'];$json=json_encode($details,JSON_UNESCAPED_SLASHES);$statement=$db->prepare('INSERT INTO sds_module_installations (module_id,package_name,version,package_checksum,status,details) VALUES (?,?,?,?,?,?)');$statement->bind_param('ssssss',$module,$package,$version,$checksum,$status,$json);$statement->execute();$statement->close();$db->close();
    }
}
