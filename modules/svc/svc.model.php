<?php

class SvcModel_ extends SvcModelBase
{
    public function svcOptions($nSelectedID = 0)
    {
        $aData = $this->db->select_key('SELECT id, title, module, module_title, keyword FROM ' . TABLE_SVC . ' ORDER BY id', 'keyword');
        if (empty($aData)) {
            return '';
        }
        foreach ($aData as $k => $v) {
            $aData[$k]['title'] = $v['title'] . ' (' .
                (!empty($v['module_title']) ? $v['module_title'] : $v['module']) . ')';
        }
        if (!BBS::PRESS_ON && isset($aData['press'])) {
            unset($aData['press']);
        }

        return HTML::selectOptions($aData, $nSelectedID, false, 'id', 'title');
    }
}