<?php

class Geo_ extends GeoBase
{
    public function ajax()
    {
        $aResponse = array();
        if (!$this->security->haveAccessToAdminPanel()) {
            $this->errors->accessDenied();
            $this->ajaxResponseForm($aResponse);
        }
        $action = $this->input->getpost('act', TYPE_STR);
        switch ($action) {
            /**
             * Список станций метро города
             * @param int $nCityID ID города
             */
            case 'city-metro':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $aData = static::cityMetro($nCityID, 0, 'select');
                $aResponse['html'] = $aData['html'];
            } break;
            case 'country-presuggest':
            {
                $nCountryID = $this->input->postget('country', TYPE_UINT);
                $mResult = false;
                if ($nCountryID) {
                    $aData = static::regionPreSuggest($nCountryID, true);
                    $mResult = array();
                    foreach ($aData as $v) {
                        $mResult[] = array($v['id'], $v['title'], $v['metro'], $v['pid']);
                    }
                }
                $this->ajaxResponse($mResult);
            } break;
            case 'district-options':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $mEmpty = $this->input->postget('empty', TYPE_NOTAGS);
                if (!$mEmpty) {
                    $mEmpty = false;
                }
                $aResponse['html'] = static::districtOptions($nCityID, 0, $mEmpty);
            } break;
            default:
            {
                bff::hook('geo.admin.ajax.default.action', $action, $this);
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }


}