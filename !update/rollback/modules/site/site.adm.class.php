<?php

/**
 * Права доступа группы:
 *  - site: Настройки сайта
 *      - instructions: Инструкции
 */
class Site_ extends SiteBase
{
    #---------------------------------------------------------------------------------------
    # Настройки сайта

    public function settings()
    {
        if (!$this->haveAccessTo('settings')) {
            return $this->showAccessDenied();
        }

        $aData = array();
        return $this->viewPHP($aData, 'admin.settings');
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

    #---------------------------------------------------------------------------------------
    # Инструкции

    public function instructions()
    {

        /**
         * Формат:
         * array(
         *   'Название закладки' => array(
         *      'уникальный ключ' => array(
         *          't' => 'Название инструкции',
         *          'field' => 'Тип поля' // доступные: 'wy' - wysiwyg, 'text' - input::text
         *      )
         *   ),
         *   'Название закладки' => array(...)
         * )
         */

        return $this->instructionsForm(array());
    }

    #---------------------------------------------------------------------------------------
    #AJAX

    public function ajax()
    {
        if (!$this->security->haveAccessToAdminPanel()) {
            $this->ajaxResponse(Errors::ACCESSDENIED);
        }

        $action = $this->input->get('act', TYPE_STR);
        switch ($action) {
            case 'crop-image-init':
            {
                $p = $this->input->postm(array(
                        'folder'   => TYPE_STR,
                        'filename' => TYPE_STR,
                        'sizes'    => TYPE_ARRAY_ARRAY,
                        'ratio'    => TYPE_UNUM,
                        'module'   => TYPE_STR,
                    )
                );

                if (empty($p['sizes']) || empty($p['filename'])) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                if ($p['module'] == 'publications') {
                    # для модуля Publications, формат формирования путей к изображениям
                    # может изменяться в зависимости от типа публикации
                    $oPublications = bff::module('publications');
                    $pp = $this->input->postm(array(
                            'type'       => TYPE_UINT,
                            'item_id'    => TYPE_UINT,
                            'publicated' => TYPE_STR,
                        )
                    );
                    if (!$pp['type']) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    //custom: не отображать crop-preview шириной 350px
                    if (isset($p['sizes'][2]) && $p['sizes'][2][0] == 350) {
                        unset($p['sizes'][2]);
                    }

                    $oTypeSettings = $oPublications->getTypeSettings($pp['type']);

                    $aItemData = array(
                        'id'         => $pp['item_id'],
                        'size'       => $oTypeSettings->imgp['size_orig'],
                        'filename'   => $p['filename'],
                        'publicated' => $pp['publicated']
                    );

                    $p['url'] = $oPublications->getImagesPath(true, $aItemData, $oTypeSettings);
                    $path = $oPublications->getImagesPath(false, $aItemData, $oTypeSettings);
                } else {
                    if (empty($p['folder'])) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }
                    $p['url'] = bff::url($p['folder'], 'images') . $p['filename'];
                    $path = bff::path($p['folder'], 'images') . $p['filename'];
                }

                if (!file_exists($path)) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $dim = getimagesize($path);
                $p['width'] = $dim[0];
                $p['height'] = $dim[1];

                $aResponse = $p;
                $aResponse['html'] = $this->viewPHP($p, 'admin.crop.image');
                $aResponse['res'] = $this->errors->no();
                $this->ajaxResponse($aResponse);
            }
            break;
            case 'generate-keyword':
            {
                $sTitle = $this->input->post('title', TYPE_STR);
                $this->ajaxResponse(array('res' => true, 'keyword' => mb_strtolower(func::translit($sTitle))));
            }
            break;
            default:
            {
                bff::hook('site.admin.ajax.default.action', $action, $this);
            }
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }



}