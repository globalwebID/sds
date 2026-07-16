<?php
declare(strict_types=1);

final class SdsModuleRegistry
{
    private array $modules=[];
    public function __construct(private readonly string $root,?array $enabled=null)
    {
        $enabled??=[];$manifestRoot=$root.'/modules';
        foreach(glob($manifestRoot.'/*/module.json')?:[] as $file){
            $data=json_decode((string)file_get_contents($file),true);
            if(!is_array($data)||empty($data['id'])||!preg_match('/^[a-z][a-z0-9_-]*$/',(string)$data['id']))continue;
            $id=(string)$data['id'];$legacy=trim((string)($data['legacy_path']??''),'/\\');$source=trim((string)($data['source_path']??''),'/\\');
            $data['manifest_path']=$file;$data['installed']=$source!==''?is_dir($root.'/'.$source):($legacy===''||is_dir($root.'/'.$legacy));
            $data['enabled']=array_key_exists($id,$enabled)?(bool)$enabled[$id]:(bool)($data['default_enabled']??false);
            $data['dependencies']=array_values(array_unique(array_map('strval',(array)($data['dependencies']??[]))));
            $this->modules[$id]=$data;
        }
        ksort($this->modules);
    }
    public function all():array{return array_values($this->modules);}
    public function get(string $id):?array{return $this->modules[$id]??null;}
    public function isEnabled(string $id):bool{return !empty($this->modules[$id]['installed'])&&!empty($this->modules[$id]['enabled'])&&$this->missingDependencies($id)===[];}
    public function missingDependencies(string $id):array
    {
        $missing=[];foreach((array)($this->modules[$id]['dependencies']??[]) as $dependency){if(empty($this->modules[$dependency]['installed'])||empty($this->modules[$dependency]['enabled']))$missing[]=$dependency;}return $missing;
    }
    public function status(string $id):string
    {
        $module=$this->get($id);if(!$module)return 'unknown';if(empty($module['installed']))return 'missing';if(empty($module['enabled']))return 'disabled';if($this->missingDependencies($id))return 'dependency_error';return 'ready';
    }
}
