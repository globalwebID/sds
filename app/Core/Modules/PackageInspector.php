<?php
declare(strict_types=1);

final class SdsPackageInspector
{
    public function __construct(private readonly string $root) {}

    public function inspect(string $zipPath,?string $expectedChecksum=null):array
    {
        $real=realpath($zipPath);
        if($real===false||!is_file($real))throw new RuntimeException('File paket tidak ditemukan.');
        if(strtolower(pathinfo($real,PATHINFO_EXTENSION))!=='zip')throw new RuntimeException('Paket wajib berformat ZIP.');
        $checksum=hash_file('sha256',$real);
        if($expectedChecksum!==null&&!hash_equals(strtolower(trim($expectedChecksum)),$checksum))throw new RuntimeException('Checksum SHA-256 paket tidak cocok.');
        $zip=new ZipArchive();if($zip->open($real)!==true)throw new RuntimeException('Paket ZIP rusak atau tidak dapat dibuka.');
        try{
            $raw=$zip->getFromName('package.json');
            if($raw===false)throw new RuntimeException('package.json tidak ditemukan.');
            $meta=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
            foreach(['format','id','package','version','type'] as $key)if(!isset($meta[$key])||$meta[$key]==='')throw new RuntimeException('Metadata paket tidak lengkap: '.$key);
            if(!in_array((int)$meta['format'],[1,2],true))throw new RuntimeException('Format paket belum didukung.');
            if(!preg_match('/^[a-z][a-z0-9_-]*$/',(string)$meta['id']))throw new RuntimeException('ID modul tidak valid.');
            if(!in_array($meta['type'],['overlay','module-overlay'],true))throw new RuntimeException('Tipe paket tidak didukung.');
            $entries=[];
            for($i=0;$i<$zip->numFiles;$i++){
                $stat=$zip->statIndex($i);$name=str_replace('\\','/',(string)($stat['name']??''));
                if($name===''||str_contains($name,"\0")||str_starts_with($name,'/')||preg_match('#(^|/)\.\.(/|$)#',$name)||preg_match('#^[A-Za-z]:/#',$name))throw new RuntimeException('Path berbahaya di dalam paket: '.$name);
                if($name!=='package.json'&&!str_ends_with($name,'/'))$entries[]=$name;
            }
            if((int)$meta['format']===2){
                $hashes=$meta['file_hashes']??null;if(!is_array($hashes)||count($hashes)!==count($entries))throw new RuntimeException('Daftar checksum file tidak lengkap.');
                foreach($entries as $entry){$expected=$hashes[$entry]??'';$contents=$zip->getFromName($entry);if($contents===false||!preg_match('/^[a-f0-9]{64}$/',$expected)||!hash_equals($expected,hash('sha256',$contents)))throw new RuntimeException('Checksum file paket tidak cocok: '.$entry);}
            }
            if(isset($meta['files'])&&(int)$meta['files']!==count($entries))throw new RuntimeException('Jumlah file paket tidak konsisten.');
            $manifestName='modules/'.$meta['id'].'/module.json';$manifestRaw=$zip->getFromName($manifestName);
            if($manifestRaw===false)throw new RuntimeException('Manifest modul tidak ditemukan: '.$manifestName);
            $manifest=json_decode($manifestRaw,true,32,JSON_THROW_ON_ERROR);
            if(($manifest['id']??null)!==$meta['id']||($manifest['package']??null)!==$meta['package']||($manifest['version']??null)!==$meta['version'])throw new RuntimeException('Metadata paket dan manifest modul tidak konsisten.');
            require_once $this->root.'/app/Core/Modules/ModuleRegistry.php';
            $enabledFile=$this->root.'/config/modules.php';$enabled=is_file($enabledFile)?require $enabledFile:[];
            $registry=new SdsModuleRegistry($this->root,is_array($enabled)?$enabled:[]);
            $missing=[];foreach((array)($manifest['dependencies']??[]) as $dependency)if($registry->status((string)$dependency)!=='ready')$missing[]=(string)$dependency;
            if($missing)throw new RuntimeException('Dependensi belum siap: '.implode(', ',$missing));
            return ['path'=>$real,'checksum'=>$checksum,'metadata'=>$meta,'manifest'=>$manifest,'entries'=>$entries,'files'=>count($entries),'size'=>filesize($real)];
        }finally{$zip->close();}
    }
}
