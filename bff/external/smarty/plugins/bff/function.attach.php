<?php

function smarty_function_attach($params, &$smarty)
{
    if(empty($params['file'])) return '';
    
    require_once $smarty->_get_plugin_filepath('modifier','filesize'); 

    if($params['scv']) {
        list($file,$size,$ext) = explode(';', $params['file']);
    }
                                               
    if(in_array($ext, array('jpg','jpeg','gif','png'))) {
        list($w, $h) = getimagesize($params['url'].$file);
        if($w<=615 && $h<=500)
        {
            $sResult = "<div align=\"center\"><img src=\"{$params['url']}{$file}\" alt=\"{$file}\" title=\"{$file}\" /></div>";
        } else {
            $sResult = '<div class="attachment"><a href="'.$params['url'].$file.'"  alt="'.$file.'" title="'.$file.'" '.
                    'onclick="return utils.popupMsg(\'\',{img:\''.$params['url'].$file.'\',eschide:true});"'.
                    '>Посмотреть</a> ('.$ext.'; '.smarty_modifier_filesize($size).')</div>';
        }
    } else {
        $sResult = '<div class="attachment"><a href="'.$params['url'].$file.'" alt="'.$file.'" title="'.$file.'">Загрузить</a> ('.$ext.'; '.smarty_modifier_filesize($size).')</div>';
    }
    return $sResult;    
}

?>
