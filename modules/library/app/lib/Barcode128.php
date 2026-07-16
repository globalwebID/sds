<?php
final class PerpusBarcode128
{
    private const PATTERNS = [
        '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'
    ];
    public static function svg(string $value, int $height=44, float $module=1.25): string
    {
        $value = preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
        if ($value === '') $value = '-';
        $codes=[104]; $checksum=104; $position=1;
        foreach (str_split($value) as $char) { $code=ord($char)-32; $codes[]=$code; $checksum += $code*$position; $position++; }
        $codes[]=$checksum%103; $codes[]=106;
        $quiet=10; $totalModules=$quiet*2;
        foreach($codes as $code) $totalModules += array_sum(array_map('intval',str_split(self::PATTERNS[$code])));
        $width=$totalModules*$module; $x=$quiet*$module; $rects='';
        foreach($codes as $code){$pattern=self::PATTERNS[$code];foreach(str_split($pattern) as $i=>$w){$bar=(int)$w*$module;if($i%2===0)$rects.='<rect x="'.round($x,3).'" y="0" width="'.round($bar,3).'" height="'.$height.'"/>';$x+=$bar;}}
        return '<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Barcode" viewBox="0 0 '.round($width,3).' '.$height.'" width="100%" height="'.$height.'" preserveAspectRatio="none"><g fill="#000">'.$rects.'</g></svg>';
    }
}
