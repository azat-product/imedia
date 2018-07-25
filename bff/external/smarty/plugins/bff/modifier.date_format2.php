<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty date_format2 modifier plugin
 *
 * Type:     modifier<br>
 * Name:     date_format2<br>
 * Purpose:  date_format2
 * @author battazo
 * @param string
 * @param string
 * @param interger
 * @param interger
 * @param string
 * @param string
 */
function smarty_modifier_date_format2($mDatetime, $getTime = false, $bSkipYearIfCurrent = false, $glue1=' ', $glue2=' в ')
{
    return tpl::date_format2($mDatetime, $getTime, $bSkipYearIfCurrent, $glue1, $glue2);
}   

?>