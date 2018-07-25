<?php

/**
 * Компонент работы с комментариями объявлений
 * @modified 8.sep.2015
 */

class BBSItemComments_ extends bff\db\Comments
{
    protected function initSettings()
    {
        $this->tblComments = TABLE_BBS_ITEMS_COMMENTS;
        $this->tblItems = TABLE_BBS_ITEMS;
        $this->tblItemsID = 'id';
        $this->preModeration = config::sysAdmin('bbs.comments.premoderation', false, TYPE_BOOL); # премодерация
        $this->moderationEdit = true; # редактирование на этапе модерации
        $this->commentsTree = true; # древовидные
        $this->commentsTree2Levels = true; # не более двух уровней
        # config-ключ (TABLE_CONFIG) для хранения общего счетчика непромодерированных комментариев
        $this->counterKey_UnmoderatedAll = 'bbs_comments_mod';
        # имя поля в таблице (TABLE_BBS_ITEMS) для хранения кол-ва комментариев объявления(видимых пользователю)
        $this->counterKey_ItemComments = 'comments_cnt';
        $this->commentHideReasons[self::commentDeletedByModerator] = _t('bbs', 'Удален модератором');
        $this->commentHideReasons[self::commentDeletedByCommentOwner] = _t('bbs', 'Удален автором комментария');
        $this->commentHideReasons[self::commentDeletedByItemOwner] = _t('bbs', 'Удален владельцем объявления');
        $this->commentHideReasons[self::commentFromBlockedUser] = _t('bbs', 'Комментарий от заблокированного пользователя');

        $this->urlListing = $this->adminLink('edit&tab=comments&id=', 'bbs');
        $this->urlListingAjax = $this->adminLink('comments_ajax', 'bbs');
    }

    /**
     * Получение данных обо всех комментариях к записи
     * @param integer $nItemID ID записи
     * @param integer $nCommentID ID комментария
     * @return array
     */
    public function commentsDataFrontend($nItemID, $nCommentID = 0)
    {
        $return = array('comments'=>array(), 'total'=>0);
        do {
            $sql = array();
            $bind = array(':itemID'=>$nItemID);
            if ($nCommentID > 0) {
                $sql[] = 'C.id = :commentID';
                $bind[':commentID'] = $nCommentID;
            }

            # исключаем непромодерированные
            if ($this->preModeration) {
                $sql[] = 'C.moderated = 1';
            }

            # учитываем ID группы
            if ($this->isGroupUsed()) {
                $sql[] = 'C.'.$this->tblCommentsGroupID.' = :groupID';
                $bind[':groupID'] = $this->groupID;
            }

            $aData = $this->db->tag('bbs-item-comments-data-frontend')->select_key('SELECT C.*, U.login, U.name, U.avatar, U.sex, U.blocked as ublocked
                    FROM '.$this->tblComments.' C
                        LEFT JOIN '.TABLE_USERS.' U ON C.user_id = U.user_id
                    WHERE C.item_id = :itemID'.( ! empty($sql) ? ' AND '.join(' AND ', $sql) : ' ' ).'
                    ORDER BY C.id ASC', 'id', $bind);

            if (empty($aData)) {
                break;
            }

            foreach ($aData as $k=>&$v) {
                if ($v['ublocked'] && !$v['deleted']) {
                    $v['deleted'] = self::commentFromBlockedUser;
                }
                if ($v['numlevel'] > 1 && (! isset($aData[$v['pid']]) && !$nCommentID)) {
                    unset($aData[$k]);
                }
                $v['user_url_profile'] = Users::url('user.profile', array('login' => $v['login']));
                $v['user_url_avatar'] = UsersAvatar::url($v['user_id'], $v['avatar'], UsersAvatar::szNormal, $v['sex']);
            } unset($v);

            if ($nCommentID) {
                $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
                if (isset($aData[$nCommentID])) {
                    $return['total'] = 1;
                    $return['comments'] = array($nCommentID => $aData[$nCommentID]);
                }
                break;
            }

            $return['total'] = sizeof($aData);
            $return['comments'] = $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
        } while (false);

        return $return;
    }

    /**
     * Валидация текста комментария
     * @param string $sMessage текст сообщения
     * @param boolean $bActivateLinks активировать ссылки
     * @return string
     */
    public function validateMessage($sMessage, $bActivateLinks = true)
    {
        $sMessage = $this->input->cleanTextPlain($sMessage, $this->maxMessageLen, false);
        $sMessage = \bff\utils\TextParser::antimat($sMessage);
        $sMessage = bff::filter('bbs.items.comments.message.validate', $sMessage);
        return $sMessage;
    }

    /**
     * Отправка email уведомлений, при добавлении нового комментария
     * @param integer $nItemID ID объявления
     * @param integer $nCommentID ID комментария
     */
    protected function sendEmailNotify($nItemID, $nCommentID)
    {
        $aItemData = BBS::model()->itemData($nItemID, array(
            'id',
            'user_id',
            'link',
            'title'
        ));

        if (empty($aItemData)) return;

        if ($nCommentID) {
            $aCommentData = $this->commentData($nItemID, $nCommentID);
            if (empty($aCommentData)) return;
        } else {
            # уведомляем автора объявления
            $aCommentData = array('user_id' => 0);
        }

        # отправим автору объявления, если это не его комментарий
        if ($aCommentData['user_id'] != $aItemData['user_id']) {
            $aUserData = Users::model()->userDataEnotify($aItemData['user_id'], Users::ENOTIFY_BBS_COMMENTS);
            if ($aUserData) {
                $loginAuto = Users::loginAutoHash($aUserData);
                $aItemData['link'] .= '?alogin='.$loginAuto;

                bff::sendMailTemplate(
                    array(
                        'name'       => $aUserData['name'],
                        'email'      => $aUserData['email'],
                        'user_id'    => $aItemData['user_id'],
                        'item_id'    => $nItemID,
                        'item_link'  => $aItemData['link'],
                        'item_title' => $aItemData['title']
                    ),
                    'bbs_item_comment', $aUserData['email'], false, '', '', $aUserData['lang']
                );
            }
        }

        # отправим остальным пользователям, участвовавшим в переписке
        $aUsers = $this->db->select_one_column('
            SELECT user_id
            FROM ' . $this->tblComments . '
            WHERE item_id = :item_id
              AND user_id NOT IN (:owner_item, :owner_comment)
            GROUP BY user_id', array(
                ':item_id'       => $nItemID,
                ':owner_item'    => $aItemData['user_id'],
                ':owner_comment' => $aCommentData['user_id'],
            ));
        if (!empty($aUsers)) {
            foreach ($aUsers as $userID) {
                if ($userID == $aItemData['user_id']) continue; # автору объявления уже отправили
                $aUserData = Users::model()->userDataEnotify($userID, Users::ENOTIFY_BBS_COMMENTS);
                if ($aUserData) {
                    $loginAuto = Users::loginAutoHash($aUserData);
                    $aItemData['link'] .= '?alogin='.$loginAuto;

                    bff::sendMailTemplate(
                        array(
                            'name'       => $aUserData['name'],
                            'email'      => $aUserData['email'],
                            'user_id'    => $userID,
                            'item_id'    => $nItemID,
                            'item_link'  => $aItemData['link'],
                            'item_title' => $aItemData['title']
                        ),
                        'bbs_item_comment', $aUserData['email'], false, '', '', $aUserData['lang']
                    );
                }
            }
        }
    }

    /**
     * Обработчик события модерации комментариев
     * @param array $aCommentsID ID промодерированных комментариев
     * @param string $sAction тип действия: moderate-one, moderate-many, add, add-admin
     */
    public function onCommentsModerated(array $aCommentsID, $sAction)
    {
        switch ($sAction) {
            case 'moderate-one':
                if ($this->isPreModeration()) {
                    $aData = $this->db->select('SELECT id, item_id
                        FROM '.$this->tblComments.'
                        WHERE '.$this->db->prepareIN('id', $aCommentsID));
                    foreach ($aData as $v) {
                        $this->sendEmailNotify($v['item_id'], $v['id']);
                    }
                }
                break;
            case 'moderate-many':
                if ($this->isPreModeration()) {
                    $aData = $this->db->select('
                        SELECT item_id, COUNT(id) AS cnt, MIN(id) AS id
                        FROM '.$this->tblComments.'
                        WHERE '.$this->db->prepareIN('id', $aCommentsID).'
                        GROUP BY item_id');
                    $aItems = array();
                    foreach ($aData as $v) {
                        if($v['cnt'] == 1) {
                            $this->sendEmailNotify($v['item_id'], $v['id']);
                        }else{
                            $aItems[] = $v['item_id'];
                        }
                    }
                    if( ! empty($aItems)){
                        foreach($aItems as $v){
                            $this->sendEmailNotify($v, 0);
                        }
                    }
                }
                break;
            case 'add':
            case 'add-admin':
                $aData = $this->db->select('SELECT id, item_id
                    FROM '.$this->tblComments.'
                    WHERE '.$this->db->prepareIN('id', $aCommentsID));
                foreach ($aData as $v) {
                    $this->sendEmailNotify($v['item_id'], $v['id']);
                }
                break;
        }
    }

}