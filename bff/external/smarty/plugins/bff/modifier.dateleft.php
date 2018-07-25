<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

require_once $smarty->_get_plugin_filepath('modifier','datespent');
/**
 * Smarty dateleft modifier plugin
 *
 * Type:     modifier<br>
 * Name:     dateleft<br>
 * Purpose:  dateleft
 * @author battazo
 * @param string
 * @param string
 * @param interger
 * @param interger
 * @param string                   
 * @param string
 */
function smarty_modifier_dateleft($sDatetime, $getTime = false)
{
    //get datetime
    if(!$sDatetime) return false;
    $date = func::parse_datetime($sDatetime);
     
//    function dateDiff($dformat, $endDate, $beginDate)
//    {
//        $date_parts1 = explode($dformat, $beginDate);
//        $date_parts2 = explode($dformat, $endDate);
//        $start_date  = gregoriantojd($date_parts1[0], $date_parts1[1], $date_parts1[2]);
//        $end_date    = gregoriantojd($date_parts2[0], $date_parts2[1], $date_parts2[2]);
//        return $end_date - $start_date;
//    }

//    $date1="07/11/2003";
//    $date2="09/04/2004";
//    print "If we minus " . $date1 . " from " . $date2 . " we get " . dateDiff("/", $date2, $date1) . ".";
//    If we minus 07/11/2003 from 09/04/2004 we get 421.

//$dob="08/12/1975";
//echo "If you were born on " . $dob . ", then today your age is approximately " . 
//round(dateDiff("/", date("m/d/Y", time()), $dob)/365, 0) . " years.";
//If you were born on 08/12/1975, then today your age is approximately 30 years.
    
    smarty_modifier_datespent($sDatetime, $getTime);
}   

?>