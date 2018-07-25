<?php

/**
 * Права доступа группы:
 *  - contacts: Контакты
 *      - manage: Управление контактами (просмотр/удаление)
 */
class Contacts_ extends ContactsBase
{
    public function listing()
    {
        if (!$this->haveAccessTo('manage')) {
            return $this->showAccessDenied();
        }

        $this->input->postgetm(array(
                'page'  => TYPE_UINT,
                'ctype' => TYPE_UINT,
            ), $aData
        );

        $aData['ctypes'] = $this->getContactTypes();

        $sql = array();
        if (!$aData['ctype']) {
            $aData['ctype'] = key($aData['ctypes']);
        }
        foreach ($aData['ctypes'] as &$v) {
            $v['cnt'] = config::get('contacts_new_' . $v['id']);
        }
        unset($v);
        $sql['ctype'] = $aData['ctype'];

        $nTotal = $this->model->contactsListing($sql, true);
        $oPgn = new Pagination($nTotal, 15, '#', 'jContacts.page(' . Pagination::PAGE_ID . '); return false;');
        $aData['pgn'] = $oPgn->view();
        $aData['list'] = $this->model->contactsListing($sql, false, $oPgn->getLimitOffset());
        $aData['list'] = $this->viewPHP($aData, 'admin.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        return $this->viewPHP($aData, 'admin.listing');
    }

    public function ajax()
    {
        if (!$this->haveAccessTo('manage')) {
            $this->ajaxResponse(Errors::ACCESSDENIED);
        }

        $action = $this->input->get('act', TYPE_STR);
        switch ($action) {
            case 'delete': # удаление
            {
                $nContactID = $this->input->get('id', TYPE_UINT);
                if (!$nContactID) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aData = $this->model->contactData($nContactID);
                if (empty($aData)) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $bRes = $this->model->contactDelete($nContactID);
                if ($bRes && !$aData['viewed']) {
                    $this->updateCounter($aData['ctype'], -1);
                }
                $this->ajaxResponse(Errors::SUCCESS);
            }
            break;
            case 'view': # просмотр, popup
            {
                $nContactID = $this->input->get('id', TYPE_UINT);
                if (!$nContactID) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aData = $this->model->contactData($nContactID);
                if (empty($aData)) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aData['ctypes'] = $this->getContactTypes();
                $aData['ctype'] = $aData['ctypes'][$aData['ctype']];

                if (!$aData['viewed']) {
                    $bRes = $this->model->contactViewed($nContactID);
                    if ($bRes) {
                        $this->updateCounter($aData['ctype']['id'], -1);
                    }
                }

                echo $this->viewPHP($aData, 'admin.view');
                bff::shutdown();
            }
            break;
            case 'counters-refresh': {
                $this->countersRefresh();
                $this->adminRedirect(Errors::SUCCESS, 'listing');
            }
            break;
            default:
            {
                bff::hook('contacts.admin.ajax.default.action', $action, $this);
            }
            break;
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

}