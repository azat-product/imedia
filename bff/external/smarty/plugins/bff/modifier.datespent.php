<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

require_once $smarty->_get_plugin_filepath('modifier','declension');
/**
 * Smarty datespent modifier plugin
 *
 * Type:     modifier<br>
 * Name:     datespent<br>
 * Purpose:  datespent
 * @author battazo
 * @param string
 * @param string
 * @param interger
 * @param interger
 * @param string
 * @param string
 */
function smarty_modifier_datespent($mDatetime, $getTime = false)
{                        
    return tpl::date_format_spent($mDatetime, $getTime);
}   

?>