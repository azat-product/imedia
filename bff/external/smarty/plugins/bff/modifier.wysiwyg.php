<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty wysiwyg modifier plugin
 *
 * Type:     modifier<br>
 * Name:     wysiwyg<br>
 * Purpose:  wysiwyg
 * @author battazo
 * @param string
 * @param string
 * @param interger
 * @param interger
 * @param string
 * @param string
 */
function smarty_modifier_wysiwyg($sContent, $sFieldName, $nWidth = 575, $nHeight = 300, $sToolbarMode = 'average', $sTheme = 'sd')
{
    static $oWysiwyg;
    if( ! isset($oWysiwyg)) {
        $oWysiwyg = new CWysiwyg();
    }

    return $oWysiwyg->init($sFieldName, $sContent, $nWidth, $nHeight, $sToolbarMode, $sTheme);
}
