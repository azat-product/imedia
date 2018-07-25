<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty filesize modifier plugin
 *
 * Type:     modifier<br>
 * Name:     filesize<br>
 * Purpose:  filesize
 * @author battazo
 * @param string  
 */
function smarty_modifier_filesize($size, $extendedTitle = false)
{
    $units = ($extendedTitle ? array('Байт', 'Килобайт', 'Мегабайт', 'Гигабайт', 'Терабайт') : array('б', 'Кб', 'Мб', 'Гб', 'Тб'));
    for ($i = 0; $size > 1024; $i++) { $size /= 1024; }
    return round($size, 2).' '.$units[$i];
}
?>