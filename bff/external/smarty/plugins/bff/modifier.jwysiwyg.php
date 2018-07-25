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
function smarty_modifier_jwysiwyg($sContent, $sFieldName, $nWidth=575, $nHeight=300, $sType='normal', $sJSObjectName = '')
{
    return tpl::jwysiwyg($sContent, $sFieldName, $nWidth, $nHeight, $sType, $sJSObjectName);
}
