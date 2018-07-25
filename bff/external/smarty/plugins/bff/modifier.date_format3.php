<?php

function smarty_modifier_date_format3($sDatetime, $sFormat = false)
{                        
    //get datetime
    if(!$sDatetime) return '';
    return tpl::date_format3($sDatetime, $sFormat);

}   
