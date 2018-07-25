<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty declension modifier plugin
 *
 * Type:     modifier<br>
 * Name:     declension<br>
 * Purpose:  склонение существительных по правилам (ru|en) языка
 * @author battazo
 * @param integer
 * @param string массив - form1(0), form2(1), form5(2)
 * @param boolean
 * @return string
 */
function smarty_modifier_declension($count, $sForms, $bPrintCount = true, $language='ru')
{
    $n = abs($count);
    
    if($bPrintCount)
        echo $n.' ';
    
//    static $sFromsBuffer = array();
//    if(isset($sFromsBuffer[$sForms]))
//        $sForms = $sFromsBuffer[$sForms];
//    else
        $aForms = explode(';', $sForms);

    if($language == 'ru') {
        $n = $n % 100;
        $n1 = $n % 10;  
        if ($n > 10 && $n < 20) {
            return $aForms[2];
        }
        if ($n1 > 1 && $n1 < 5) {
            return $aForms[1];  
        }
        if ($n1 == 1) {
            return $aForms[0];  
        }
        return $aForms[2];
    }

}

?>