<?php

# Таблицы
define('TABLE_INTERNALMAIL',               DB_PREFIX . 'internalmail'); # сообщения
define('TABLE_INTERNALMAIL_CONTACTS',      DB_PREFIX . 'internalmail_contacts'); # контакты пользователей
define('TABLE_INTERNALMAIL_FOLDERS',       DB_PREFIX . 'internalmail_folders'); # папки сообщений
define('TABLE_INTERNALMAIL_FOLDERS_USERS', DB_PREFIX . 'internalmail_folders_users'); # связь папок с пользователями

class InternalMailModel_ extends Model
{
    /** @var InternalMailBase */
    public $controller;

    /**
     * Отправка сообщения получателю(получателям)
     * @param integer $authorID ID отправителя
     * @param integer $recipientID ID получателя
     * @param integer $shopID ID магазина получателя или 0 (Shops)
     * @param string $message текст сообщения
     * @param string $attachment приложение
     * @param integer $itemID ID объявления (BBS)
     * @param string $template Ключ уведомления
     * @return integer отправленного сообщения или 0 (ошибка отправки)
     */
    public function sendMessage($authorID, $recipientID, $shopID = 0, $message = '', $attachment = '', $itemID = 0, $template = '', array $templateData = array())
    {
        $authorID = intval($authorID);
        if ($authorID <= 0) {
            return 0;
        }
        $recipientID = intval($recipientID);
        if ($recipientID <= 0) {
            return 0;
        }

        $return = bff::filter('internalmail.model.sendMessage.before', false, array(
            'authorID'      => & $authorID,
            'recipientID'   => & $recipientID,
            'shopID'        => & $shopID,
            'message'       => & $message,
            'attachment'    => & $attachment,
            'itemID'        => & $itemID,
            'template'      => & $template,
            'templateData'  => & $templateData,
            ));
        if ($return !== false) {
            return $return;
        }

        if ( ! bff::adminPanel()) {
            $limit = bff::filter('internalmail.model.sendMessage.limit', config::sysAdmin('internalmail.contacts.limit', 0, TYPE_UINT));
            if ($limit > 0) {
                $sent = $this->newMessagesSentToday($authorID);
                if ($sent > $limit) {
                    $messages = $this->messagesSent($authorID, $recipientID);
                    if ($messages == 0) {
                        $this->errors->set(_t('internalmail','К сожалению вы не можете отправить сообщение данному пользователю, так как на сегодня уже исчерпали лимит новых контактов.'));
                        return 0;
                    }
                }
            }
        }


        $message = nl2br($message);
        $messageCreated = $this->db->now();
        $messageID = $this->db->insert(TABLE_INTERNALMAIL, array(
                'author'    => $authorID,
                'recipient' => $recipientID,
                'message'   => $message,
                'attach'    => (!empty($attachment) ? $attachment : ''),
                'is_new'    => 1,
                'created'   => $messageCreated,
                'item_id'   => $itemID,
                'shop_id'   => $shopID,
            ), 'id'
        );

        if (!empty($messageID)) {
            $this->updateContact($authorID, $recipientID, $shopID, $messageID, $messageCreated, false);
            $this->updateContact($recipientID, $authorID, $shopID, $messageID, $messageCreated, true);
            $this->updateNewMessagesCounter($recipientID);

            # уведомление о новом сообщении
            if ($template !== false) {
                $userData = Users::model()->userDataEnotify($recipientID, Users::ENOTIFY_INTERNALMAIL);
                if ($userData) {
                    if ($template === '') {
                        $template = 'internalmail_new_message';
                    }
                    $loginAuto = Users::loginAutoHash($userData);
                    $templateData = array_merge(array(
                        'name'    => $userData['name'],
                        'email'   => $userData['email'],
                        'user_id' => $recipientID,
                        'link'    => InternalMail::url('my.messages', array('alogin' => $loginAuto)),
                        'message' => tpl::truncate(strip_tags($message), 250),
                        'item_id' => $itemID,
                        'shop_id' => $shopID,
                    ), $templateData);
                    if ($itemID > 0) {
                        $itemData = BBS::model()->itemData($itemID, array('id','title','link'));
                        if (!empty($itemData)) {
                            $templateData['item_title'] = $itemData['title'];
                            $templateData['item_link']  = $itemData['link'].'?alogin='.$loginAuto;
                        }
                    }
                    bff::sendMailTemplate($templateData, $template, $userData['email'], false, '', '', $userData['lang']);
                }
            }
            # обновляем счетчик сообщений объявления
            if ($itemID > 0) {
                BBS::model()->itemSave($itemID, array(
                    'messages_total = messages_total + 1',
                    'messages_new = messages_new + 1',
                ));
            }
            return $messageID;
        }

        return 0;
    }

    /**
     * Очистка текста сообщения
     * @param string $message текст сообщения
     * @param int $maxLength максимально допустимое кол-во символов или 0 - без ограничений
     * @param bool|array $activateLinks подсветка ссылок
     * @return string очищенный текст
     */
    public function cleanMessage($message, $maxLength = 4000, $activateLinks = true)
    {
        $message = htmlspecialchars($message);
        $message = $this->input->cleanTextPlain($message, $maxLength, $activateLinks);

        # антимат
        $message = \bff\utils\TextParser::antimat($message);

        # дополнительная обработка
        $message = bff::filter('internalmail.message.validate', $message);

        return $message;
    }

    /**
     * Помечаем все сообщения переписки как "прочитанные"
     * @param integer $userID ID текущего пользователя
     * @param integer $interlocutorID ID собеседника
     * @param integer $shopID ID магазина / 0 / -1 (все)
     * @param boolean $bUpdateNewCounter обновлять счетчик кол-ва новых сообщений
     * @return integer кол-во помеченных сообщений
     */
    public function setMessagesReaded($userID, $interlocutorID, $shopID, $updateNewCounter = true)
    {
        # сообщения
        $updateCond = array(
            'recipient' => $userID,
            'author'    => $interlocutorID,
            'is_new'    => 1,
        );
        if ($shopID >= 0) {
            $updateCond['shop_id'] = $shopID;
        }
        $updated = $this->db->update(TABLE_INTERNALMAIL, array(
                'is_new' => 0,
                'readed' => $this->db->now(),
            ), $updateCond
        );

        # контакты
        $updateCond = array(
            'user_id'         => $userID,
            'interlocutor_id' => $interlocutorID,
        );
        if ($shopID >= 0) {
            $updateCond['shop_id'] = $shopID;
        }
        $this->db->update(TABLE_INTERNALMAIL_CONTACTS, array(
                'messages_new' => 0
            ), $updateCond
        );

        # счетчик новых сообщений
        if ($updateNewCounter && !empty($updated)) {
            $this->updateNewMessagesCounter($userID);
        }

        return $updated;
    }

    /**
     * Переключатели сообщения
     * @param integer $nMessageID ID сообщения
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function messageToggle($nMessageID, $sField)
    {
        switch ($sField) {
            case 'blocked': { # Заблокировано
                return $this->toggleInt(TABLE_INTERNALMAIL, $nMessageID, $sField, 'id');
            } break;
        }
    }

    /**
     * Обновляем данные контакта
     * @param integer $userID ID пользователя
     * @param integer $interlocutorID ID собеседника
     * @param integer $shopID ID магазина или 0
     * @param integer $messageID ID последнего сообщения
     * @param string $messageCreated дата создания последнего сообщения
     * @param boolean $messageIsNew является ли сообщение новым (обновляем контакт получателя)
     * @return boolean
     */
    protected function updateContact($userID, $interlocutorID, $shopID, $messageID, $messageCreated, $messageIsNew)
    {
        # обновляем существующий контакт
        $data = array(
            'last_message_id'   => $messageID,
            'last_message_date' => $messageCreated,
            'messages_total = messages_total + 1',
        );
        if ($messageIsNew) {
            $data[] = 'messages_new = messages_new + 1';
        }
        $res = $this->db->update(TABLE_INTERNALMAIL_CONTACTS, $data, array(
                'user_id'         => $userID,
                'interlocutor_id' => $interlocutorID,
                'shop_id'         => $shopID,
            )
        );
        if (empty($res)) {
            # создаем контакт
            $res = $this->db->insert(TABLE_INTERNALMAIL_CONTACTS, array(
                    'user_id'           => $userID,
                    'interlocutor_id'   => $interlocutorID,
                    'shop_id'           => $shopID,
                    'last_message_id'   => $messageID,
                    'last_message_date' => $messageCreated,
                    'messages_total'    => 1,
                    'messages_new'      => ($messageIsNew ? 1 : 0),
                ), false
            );
        }

        return !empty($res);
    }

    /**
     * Перемещаем собеседника в папку
     * @param integer $userID ID пользователя (кто перемещает)
     * @param integer $interlocutorID ID пользователя собеседника (кого перемещает)
     * @param integer $shopID ID магазина участвующего в переписке или 0
     * @param integer $folderID ID папки (куда перемещает)
     * @param boolean $toggle true - удалять из папки, если собеседник уже в ней находится
     * @return integer
     */
    public function interlocutorToFolder($userID, $interlocutorID, $shopID, $folderID, $toggle = true)
    {
        if ($userID <= 0 || $interlocutorID <= 0 || $folderID <= 0) {
            return 0;
        }

        $exists = $this->db->one_data('SELECT interlocutor_id FROM ' . TABLE_INTERNALMAIL_FOLDERS_USERS . '
                WHERE user_id = :userID
                  AND interlocutor_id = :interlocutorID
                  AND shop_id = :shopID
                  AND folder_id = :folderID',
            array(
                ':userID'         => $userID,
                ':interlocutorID' => $interlocutorID,
                ':shopID'         => $shopID,
                ':folderID'       => $folderID
            )
        );

        if ($exists) {
            if (!$toggle) {
                return 1;
            }
            $this->db->delete(TABLE_INTERNALMAIL_FOLDERS_USERS,
                array(
                    'user_id'         => $userID,
                    'interlocutor_id' => $interlocutorID,
                    'shop_id'         => $shopID,
                    'folder_id'       => $folderID
                )
            );

            return 0;
        } else {
            $this->db->insert(TABLE_INTERNALMAIL_FOLDERS_USERS, array(
                    'user_id'         => $userID,
                    'interlocutor_id' => $interlocutorID,
                    'shop_id'         => $shopID,
                    'folder_id'       => $folderID,
                ), false
            );

            return 1;
        }
    }

    /**
     * Получаем список папок, в которых находится собеседник
     * @param integer $userID ID текущего пользователя
     * @param integer|array $interlocutorID ID собеседников
     * @param boolean $useShopID задействовать shop_id
     * @return array
     */
    public function getInterlocutorFolders($userID, $interlocutorID, $useShopID = false)
    {
        if (empty($interlocutorID)) {
            return array();
        }

        $isArray = true;
        if (!is_array($interlocutorID)) {
            $interlocutorID = array($interlocutorID);
            $isArray = false;
        }
        $interlocutorID = array_map('intval', $interlocutorID);

        $folders = $this->db->select('SELECT folder_id as f, interlocutor_id as id, shop_id as shop
                        FROM ' . TABLE_INTERNALMAIL_FOLDERS_USERS . '
                    WHERE user_id = :userID
                      AND interlocutor_id IN(' . join(',', $interlocutorID) . ')' .
            (!$useShopID ? ' AND shop_id = 0' : ''),
            array(':userID' => $userID)
        );

        if (empty($folders)) {
            return array();
        }

        if (!$isArray) {
            $result = array();
            foreach ($folders as $v) {
                $result[] = $v['f'];
            }

            return $result;
        } else {
            $result = array();
            foreach ($folders as $v) {
                if ($useShopID) {
                    $result[$v['id']][$v['shop']][] = $v['f'];
                } else {
                    $result[$v['id']][] = $v['f'];
                }
            }

            return $result;
        }
    }

    /**
     * Проверяет добавлен ли пользователь собеседником в папку
     * @param integer $userID ID проверяющего
     * @param integer $interlocutorID ID собеседника
     * @param integer $shopID ID магазина или 0
     * @param integer $folderID ID папки
     * @return boolean
     */
    public function isUserInFolder($userID, $interlocutorID, $shopID, $folderID)
    {
        if ($interlocutorID <= 0 || $folderID <= 0) {
            return false;
        }

        return (bool)$this->db->one_data('SELECT COUNT(*)
                 FROM ' . TABLE_INTERNALMAIL_FOLDERS_USERS . '
               WHERE user_id = :interlocutorID
                 AND interlocutor_id = :userID
                 AND shop_id = :shopID
                 AND folder_id = :folder',
            array(
                ':interlocutorID' => $interlocutorID,
                ':shopID'         => $shopID,
                ':userID'         => $userID,
                ':folder'         => $folderID
            )
        );
    }

    /**
     * Создание/обновление папки пользователя
     * @param integer $userID ID пользователя
     * @param integer $folderID ID папки (обновляем) или 0 (создаем)
     * @param array $data данные
     * @return boolean|integer ID созданной папки
     */
    public function userFolderSave($userID, $folderID, array $data = array())
    {
        if ($folderID > 0) {
            if ($userID <= 0 || empty($data)) {
                return false;
            }
            $res = $this->db->update(TABLE_INTERNALMAIL_FOLDERS, $data, array(
                    'id'      => $folderID,
                    'user_id' => $userID,
                )
            );

            return !empty($res);
        } else {
            if ($userID <= 0 || empty($data)) {
                return 0;
            }
            $data['user_id'] = $userID;

            return $this->db->insert(TABLE_INTERNALMAIL_FOLDERS, $data, 'id');
        }
    }

    /**
     * Формирование ленты сообщений пользователей (spy - adm)
     * @param boolean|integer $userFrom ID отправителя или false (все лента)
     * @param boolean $countOnly только подсчет кол-ва
     * @param string $sqlLimit лимит выборки
     * @return array|integer
     */
    public function getMessagesSpyLenta($userFrom = false, $countOnly = false, $sqlLimit = '')
    {
        $filter = array();
        if ($userFrom !== false && $userFrom > 0) {
            $filter[':from'] = array('I.author = :from', ':from' => $userFrom);
        }
        $filter = $this->filter($filter);

        if ($countOnly) {
            return (integer)$this->db->one_data('SELECT COUNT(*) FROM ' . TABLE_INTERNALMAIL.' I '.$filter['where'], $filter['bind']);
        }

        $aData = $this->db->select('SELECT I.id, I.created, I.message, I.is_new, I.shop_id, I.blocked,
                   U1.user_id as from_id, U1.name as from_name, U1.login as from_login, U1.avatar as from_avatar, U1.sex as from_sex,
                   U2.user_id as to_id, U2.name as to_name, U2.login as to_login, U2.avatar as to_avatar, U2.sex as to_sex
                   FROM ' . TABLE_INTERNALMAIL . ' I
                       INNER JOIN ' . TABLE_USERS . ' U1 ON U1.user_id = I.author
                       INNER JOIN ' . TABLE_USERS . ' U2 ON U2.user_id = I.recipient
                   '.$filter['where'].'
                   ORDER BY I.created DESC
                   ' . $sqlLimit, $filter['bind']
        );
        if (empty($aData)) {
            $aData = array();
        }

        return $aData;
    }

    /**
     * Получаем сообщения переписки
     * @param integer $userID ID пользователя, просматривающего свои сообщения
     * @param integer $interlocutorID ID собеседника
     * @param integer $shopID ID магазина или 0
     * @param boolean $countOnly только считаем кол-во
     * @param string $sqlLimit
     * @param array|integer
     */
    public function getConversationMessages($userID, $interlocutorID, $shopID, $countOnly = false, $sqlLimit = '')
    {
        $bind = array(
            ':userID'         => $userID,
            ':interlocutorID' => $interlocutorID,
            ':shopID'         => $shopID,
        );

        if ($countOnly) {
            return $this->db->one_data('SELECT COUNT(M.id)
                        FROM ' . TABLE_INTERNALMAIL . ' M
                        WHERE ( (M.author=:userID AND M.recipient=:interlocutorID) OR
                                (M.author=:interlocutorID AND M.recipient=:userID) )
                              AND M.shop_id = :shopID',
                $bind
            );
        }

        return $this->db->select('SELECT M.*, DATE(M.created) as created_date, (M.author=:userID) as my,
                        (M.recipient=:userID AND M.is_new) as new
                   FROM ' . TABLE_INTERNALMAIL . ' M
                   WHERE ( (M.author=:userID AND M.recipient=:interlocutorID) OR
                           (M.recipient=:userID AND M.author=:interlocutorID) )
                     AND M.shop_id = :shopID
                   ORDER BY M.created ' . (bff::adminPanel() ? 'DESC' : 'ASC') . ' ' . $sqlLimit,
            $bind
        );
    }

    /**
     * Получаем список контактов пользователя (frontend)
     * @param integer $userID ID пользователя, просматривающего свои переписки
     * @param integer $shopID ID магазина / 0 / -1
     * @param integer $folderID ID папки
     * @param string $filterMessages строка поиска (фильтр по сообщениям в переписке)
     * @param boolean $countOnly только считаем кол-во
     * @param string $sqlLimit
     * @param array|integer
     */
    public function getContactsListingFront($userID, $shopID, $folderID = 0, $filterMessages = '', $countOnly = false, $sqlLimit = '')
    {
        $bind = array(':userID' => $userID);
        $filterShop = false;
        if ($shopID && in_array($folderID, array(InternalMail::FOLDER_SH_SHOP, InternalMail::FOLDER_SH_USER))) {
            if ($folderID == InternalMail::FOLDER_SH_SHOP) {
                $filterShop = 'shop_id = :shopID';
            } else {
                if ($folderID == InternalMail::FOLDER_SH_USER) {
                    $filterShop = 'shop_id != :shopID';
                }
            }
            $folderID = InternalMail::FOLDER_ALL;
            $bind[':shopID'] = $shopID;
        } else {
            if (!bff::shopsEnabled()) {
                $filterShop = 'shop_id = 0';
            }
        }
        if ($folderID > 0) {
            $bind[':folderID'] = $folderID;
        }
        if (mb_strlen($filterMessages) > 2) {
            if (preg_match('/item:([\d]+)/', $filterMessages, $matches) && !empty($matches[1])) {
                $itemID = intval($matches[1]);
                if ($itemID > 0) {
                    $bind[':itemID'] = $itemID;
                    $filterMessages = ' AND M.item_id = :itemID';
                }
            } else {
                $filterMessages = ' AND ' . $this->db->prepareFulltextQuery($filterMessages, 'message,attach');
            }
        } else {
            $filterMessages = '';
        }

        if ($countOnly) {
            if (!empty($filterMessages)) {
                return $this->db->one_data('SELECT COUNT(U.user_id)
                           FROM ' . TABLE_INTERNALMAIL . ' M,
                                ' . TABLE_USERS . ' U
                           ' . ($folderID > 0 ? ', ' . TABLE_INTERNALMAIL_FOLDERS_USERS . ' F ' : '') . '
                           WHERE ( M.author = :userID OR M.recipient = :userID )
                              AND U.user_id = (CASE WHEN (M.author = :userID) THEN M.recipient ELSE M.author END)
                              ' . ($folderID > 0 ? '
                                AND F.user_id = :userID
                                AND F.shop_id = M.shop_id
                                AND F.interlocutor_id = U.user_id
                                AND F.folder_id = :folderID
                              ' : '') . '
                              ' . ($filterShop ? ' AND M.' . $filterShop : '') .
                    $filterMessages,
                    $bind
                );
            } else {
                return $this->db->one_data('SELECT COUNT(C.interlocutor_id)
                    FROM ' . TABLE_INTERNALMAIL_CONTACTS . ' C
                        ' . ($folderID > 0 ? ' INNER JOIN ' . TABLE_INTERNALMAIL_FOLDERS_USERS . ' F
                            ON F.user_id = C.user_id
                           AND F.shop_id = C.shop_id
                           AND F.interlocutor_id = C.interlocutor_id
                           AND F.folder_id = :folderID ' : '') . '
                    WHERE C.user_id = :userID
                        ' . ($filterShop ? ' AND C.' . $filterShop : ''),
                    $bind
                );
            }
        }

        if (!empty($filterMessages)) {
            $interlocutorsID = $this->db->select('SELECT U.user_id, M.shop_id
                       FROM ' . TABLE_INTERNALMAIL . ' M,
                            ' . TABLE_USERS . ' U
                       ' . ($folderID > 0 ? ', ' . TABLE_INTERNALMAIL_FOLDERS_USERS . ' F ' : '') . '
                       WHERE ( M.author = :userID OR M.recipient = :userID )
                           AND U.user_id = (CASE WHEN (M.author = :userID) THEN M.recipient ELSE M.author END)
                          ' . ($folderID > 0 ? '
                               AND F.user_id = :userID
                               AND F.interlocutor_id = U.user_id
                               AND F.shop_id = M.shop_id
                               AND F.folder_id = :folderID ' : '') . '
                          ' . ($filterShop ? ' AND M.' . $filterShop : '') . '
                          ' . $filterMessages . '  GROUP BY U.user_id, M.shop_id',
                $bind
            );
            if (empty($interlocutorsID)) {
                return array();
            }

            $interlocutorsCondition = array();
            foreach ($interlocutorsID as $v) {
                $interlocutorsCondition[] = '(C.interlocutor_id = ' . $v['user_id'] . ' AND C.shop_id = ' . $v['shop_id'] . ')';
            }
            foreach (array(':folderID', ':itemID') as $k) {
                if (isset($bind[$k])) {
                    unset($bind[$k]);
                }
            }
            $aContacts = $this->db->select_key('SELECT I.user_id, I.name, I.login, I.avatar, I.sex, I.activated,
                           C.messages_total AS msgs_total,
                           C.messages_new AS msgs_new,
                           C.last_message_id AS msgs_last_id,
                           C.last_message_date AS msgs_last_created,
                           C.shop_id
                   FROM ' . TABLE_INTERNALMAIL_CONTACTS . ' C,
                        ' . TABLE_USERS . ' I
                   WHERE C.user_id = :userID
                     ' . ($filterShop ? ' AND C.' . $filterShop : '') . '
                     AND (' . join(' OR ', $interlocutorsCondition) . ')
                     AND C.interlocutor_id = I.user_id
                   ORDER BY C.last_message_date DESC' . $sqlLimit, 'msgs_last_id', $bind
            );
        } else {
            $aContacts = $this->db->select_key('SELECT I.user_id, I.name, I.login, I.avatar, I.sex, I.activated,
                           C.messages_total AS msgs_total,
                           C.messages_new AS msgs_new,
                           C.last_message_id AS msgs_last_id,
                           C.last_message_date AS msgs_last_created,
                           C.shop_id
                   FROM ' . TABLE_INTERNALMAIL_CONTACTS . ' C
                        INNER JOIN ' . TABLE_USERS . ' I ON C.interlocutor_id = I.user_id
                        ' . ($folderID > 0 ? ' INNER JOIN ' . TABLE_INTERNALMAIL_FOLDERS_USERS . ' F
                                ON F.user_id = C.user_id
                               AND F.shop_id = C.shop_id
                               AND F.interlocutor_id = I.user_id
                               AND F.folder_id = :folderID ' : '') . '
                   WHERE C.user_id = :userID
                     ' . ($filterShop ? ' AND C.' . $filterShop : '') . '
                   ORDER BY C.last_message_date DESC' . $sqlLimit, 'msgs_last_id', $bind
            );
        }

        if (empty($aContacts)) {
            return array();
        }

        $aLastMessageID = array();
        $aUsersID = array();
        foreach ($aContacts as &$v) {
            $v['folders'] = array();
            $aUsersID[] = $v['user_id'];
            $aLastMessageID[] = $v['msgs_last_id'];
            unset($v['msgs_last_id'], $v['msgs_last_created']);
        }
        unset($v);

        # связь собеседников с папками
        if ($folderID !== -1) {
            $aUsersFolders = $this->getInterlocutorFolders($userID, $aUsersID, true);
            foreach ($aContacts as &$v) {
                if (isset($aUsersFolders[$v['user_id']][$v['shop_id']])) {
                    $v['folders'] = $aUsersFolders[$v['user_id']][$v['shop_id']];
                }
            }
            unset($v);
        }

        # магазины
        if (bff::shopsEnabled()) {
            $myShopID = User::shopID();
            $aShopsID = array();
            foreach ($aContacts as &$v) {
                if ($v['shop_id'] && $v['shop_id'] != $myShopID) {
                    $aShopsID[] = $v['shop_id'];
                }
            }
            unset($v);

            if (!empty($aShopsID)) {
                $aShopsData = Shops::model()->shopsDataToMessages($aShopsID);
                $oShopLogo = Shops::i()->shopLogo(0);
                foreach ($aContacts as &$v) {
                    if ($v['shop_id'] && isset($aShopsData[$v['shop_id']])) {
                        $shop = & $aShopsData[$v['shop_id']];
                        $shop['logo'] = $oShopLogo->url($shop['id'], $shop['logo'], ShopsLogo::szSmall, false, true);
                        $v['shop'] = $shop;
                    }
                }
                unset($v);
            }
        }

        # данные о последних сообщениях в контактах
        $aLastMessageData = $this->db->select('SELECT id, author, recipient, shop_id, item_id, created, readed, is_new, message, blocked
                    FROM ' . TABLE_INTERNALMAIL . '
                    WHERE id IN (' . join(',', $aLastMessageID) . ')'
        );
        if (!empty($aLastMessageData)) {
            $aItemsID = array();
            foreach ($aLastMessageData as $v) {
                $aContacts[$v['id']]['message'] = $v;
                if ($v['item_id'] > 0) {
                    $aItemsID[$v['id']] = $v['item_id'];
                }
            }
            # данные о прикрепленных объявлениях (BBS)
            if (!empty($aItemsID)) {
                $aItemsData = BBS::model()->itemsDataByFilter(array('id' => array_unique($aItemsID)), array(
                        'id',
                        'user_id',
                        'title',
                        'link'
                    ), array('context'=>'internalmail-contacts-listing-front')
                );
                if (!empty($aItemsData)) {
                    foreach ($aItemsID as $k_id => $item_id) {
                        if (isset($aItemsData[$item_id])) {
                            $aContacts[$k_id]['item'] = $aItemsData[$item_id];
                        }
                    }
                }
            }
        }
        unset($aLastMessageID);

        return $aContacts;
    }

    /**
     * Получаем контакты пользователя (admin-панель)
     * @param integer $userID ID пользователя, просматривающего свои переписки
     * @param integer $shopID ID магазина
     * @param integer $folderID ID папки
     * @param boolean $countOnly только считаем кол-во
     * @param string $sqlLimit
     * @param array
     */
    public function getContactsListingAdm($userID, $shopID, $folderID, $countOnly = false, $sqlLimit = '')
    {
        $bind = array(':userID' => $userID);
        if ($folderID > 0) {
            $bind[':folderID'] = $folderID;
        }
        $shopFilter = false;
        if ($shopID > 0) {
            $bind[':shopID'] = $shopID;
            $shopFilter = 'shop_id = :shopID';
        }

        if ($countOnly) {
            return $this->db->one_data('SELECT COUNT(C.interlocutor_id)
                FROM ' . TABLE_INTERNALMAIL_CONTACTS . ' C
                ' . ($folderID > 0 ? ' INNER JOIN ' . TABLE_INTERNALMAIL_FOLDERS_USERS . ' F
                        ON F.user_id = C.user_id
                       AND F.shop_id = C.shop_id
                       AND F.interlocutor_id = C.interlocutor_id
                       AND F.folder_id = :folderID ' : '') . '
                WHERE C.user_id = :userID' . ($shopFilter ? ' AND C.' . $shopFilter : ''),
                $bind
            );
        }

        $contactsList = $this->db->select_key('SELECT I.user_id, I.name, I.email, I.login, I.admin, I.avatar, I.sex, I.activated,
                   C.messages_total AS msgs_total,
                   C.messages_new AS msgs_new,
                   C.last_message_id AS msgs_last_id,
                   C.last_message_date AS msgs_last_created,
                   C.shop_id
           FROM ' . TABLE_INTERNALMAIL_CONTACTS . ' C
                INNER JOIN ' . TABLE_USERS . ' I ON C.interlocutor_id = I.user_id
           ' . ($folderID > 0 ? ' INNER JOIN ' . TABLE_INTERNALMAIL_FOLDERS_USERS . ' F
                        ON F.user_id = C.user_id
                       AND F.shop_id = C.shop_id
                       AND F.interlocutor_id = I.user_id
                       AND F.folder_id = :folderID ' : '') . '
           WHERE C.user_id = :userID' . ($shopFilter ? ' AND C.' . $shopFilter : '') . '
           ORDER BY C.last_message_date DESC ' .
            $sqlLimit, 'msgs_last_id', $bind
        );

        if (empty($contactsList)) {
            return array();
        }

        $lastMessagesID = array();
        $usersID = array();
        foreach ($contactsList as &$v) {
            $v['folders'] = array();
            $usersID[] = $v['user_id'];
            $lastMessagesID[] = $v['msgs_last_id'];
        }
        unset($v);

        # получаем связь собеседников с папками
        if ($folderID !== -1) {
            $usersFolders = $this->getInterlocutorFolders($userID, $usersID, true);
            foreach ($contactsList as &$v) {
                if (isset($usersFolders[$v['user_id']][$v['shop_id']])) {
                    $v['folders'] = $usersFolders[$v['user_id']][$v['shop_id']];
                }
            }
            unset($v);
        }

        # получаем данные о последних сообщениях в переписках
        $lastMessagesData = $this->db->select('SELECT id, author, recipient, shop_id, created, readed, is_new
                    FROM ' . TABLE_INTERNALMAIL . '
                    WHERE id IN (' . join(',', $lastMessagesID) . ')'
        );
        if (!empty($lastMessagesData)) {
            foreach ($lastMessagesData as $v) {
                $contactsList[$v['id']]['lastmsg'] = $v;
            }
        }
        unset($lastMessagesID, $lastMessagesData);

        return $contactsList;
    }

    /**
     * Возвращает кол-во новых сообщений
     * @param integer $userID ID пользователя просматривающего свои сообщения
     * @return integer
     */
    protected function getNewMessagesCount($userID)
    {
        return (integer)$this->db->one_data('SELECT COUNT(*) FROM ' . TABLE_INTERNALMAIL . '
                   WHERE recipient = :userID AND is_new = 1',
            array(':userID' => $userID)
        );
    }

    /**
     * Обновляем счетчик новых сообщений пользователя
     * @param integer $userID ID пользователя
     * @return integer текущее значение счетчика
     */
    protected function updateNewMessagesCounter($userID)
    {
        $newMessagesCount = $this->getNewMessagesCount($userID);
        $this->security->userCounter('internalmail_new', $newMessagesCount, $userID, false);

        return $newMessagesCount;
    }

    /**
     * Формирование списка получателей по email адресу
     * @param string $searchQuery первые символы email адреса
     * @param integer $currentUserID ID текущего пользователя
     * @param integer $limit максимальное кол-во совпадений
     * @return array
     */
    public function suggestInterlocutors($searchQuery, $currentUserID, $limit = 10) # adm
    {
        /**
         * получаем список подходящих по email'у собеседников, исключая:
         * - текущего пользователя
         * - запретивших им писать (im_noreply=1)
         * - заблокированных пользователей
         */
        return $this->db->select('SELECT U.user_id as id, U.email
                FROM ' . TABLE_USERS . ' U
                WHERE U.user_id != :userID
                  AND U.email LIKE (:q)
                  '.(!bff::adminPanel() ? ' AND U.im_noreply = 0 ' : '').'
                ORDER BY U.email' .
            $this->db->prepareLimit(0, ($limit > 0 ? $limit : 10))
            , array(':q' => $searchQuery . '%', ':userID' => $currentUserID)
        );
    }

    /**
     * Удаление связи сообщений с объявлениями
     * @param array $itemsID ID объявлений
     */
    public function unlinkMessagesItemsID(array $itemsID = array())
    {
        if (!empty($itemsID)) {
            $this->db->update(TABLE_INTERNALMAIL,
                array('item_id' => 0),
                array('item_id' => $itemsID)
            );
        }
    }

    /**
     * Блокировка всех сообщений пользователя
     * @param integer $nUserID ID пользователя
     */
    public function blockUserMessages($nUserID)
    {
        if (!empty($nUserID) && $nUserID > 0) {
            return $this->db->update(TABLE_INTERNALMAIL,
                array('blocked' => 1),
                array('author' => $nUserID, 'blocked' => 0)
            );
        }
        return 0;
    }

    /**
     * Количество новых сообщений отправленных пользователем сегодня
     * @param integer $userID ID пользователя
     * @return int
     */
    public function newMessagesSentToday($userID)
    {
        $today = $this->db->select_one_column('SELECT recipient 
            FROM '.TABLE_INTERNALMAIL.' 
            WHERE author = :user AND created >= :from AND created <= :to 
            GROUP BY recipient', array(
            ':user' => $userID,
            ':from' => date('Y-m-d 00:00:00'),
            ':to' => date('Y-m-d 23:59:59'),
        ));
        if (empty($today)) return 0;
        $latest = $this->db->select_one_column('SELECT recipient 
            FROM '.TABLE_INTERNALMAIL.' 
            WHERE author = :user AND '.$this->db->prepareIN('recipient', $today).' AND created < :from 
            GROUP BY recipient', array(
            ':user' => $userID,
            ':from' => date('Y-m-d 00:00:00'),
        ));
        $today = array_diff($today, $latest);
        return count($today);
    }

    public function messagesSent($authorID, $recipientID)
    {
        return (int)$this->db->one_data('SELECT COUNT(*) FROM '.TABLE_INTERNALMAIL.' 
            WHERE author = :author AND recipient = :recipient', array(
            ':author' => $authorID,
            ':recipient' => $recipientID,
        ));
    }


    /**
     * Список сообщений по фильтру
     * @param array $filter
     * @param array $fields
     * @param array $opt
     * @return int|mixed
     */
    public function messagesByFilter($filter, $fields, $opt = array())
    {
        func::array_defaults($opt, array(
            'oneArray' => true,
            'group'     => '',
            'order'     => '',
        ));

        $filter = $this->prepareFilter($filter);
        if (empty($fields)) {
            return (int)$this->db->one_data('SELECT COUNT(*) FROM ' . TABLE_INTERNALMAIL . $filter['where'], $filter['bind']);
        } else {
            if ($opt['oneArray']) {
                return $this->db->one_array('SELECT ' . join(',', $fields) . ' FROM ' . TABLE_INTERNALMAIL . $filter['where'].' LIMIT 1', $filter['bind']);
            } else {
                return $this->db->select('SELECT ' . join(',', $fields) . ' FROM ' . TABLE_INTERNALMAIL . $filter['where']
                    .( ! empty($opt['group']) ? ' GROUP BY '.$opt['group'] : '')
                    .( ! empty($opt['order']) ? ' ORDER BY '.$opt['order'] : ''), $filter['bind']);
            }
        }
    }

    /**
     * Метод обрабатывающий ситуацию с удалением пользователя
     * @param integer $userID ID пользователя
     * @param array $options доп. параметры удаления
     */
    public function onUserDeleted($userID, array $options = array())
    {
        if (empty($userID)) return;

        $users = array();
        $filter = array(
            'is_new' => 1,
            'author' => $userID,
        );
        $data = $this->messagesByFilter($filter, array('recipient'), array('oneArray' => false, 'group' => 'recipient'));
        foreach ($data as $v) {
            if ( ! in_array($v['recipient'], $users)) {
                $users[] = $v['recipient'];
            }
        }

        $this->db->delete(TABLE_INTERNALMAIL, array('author' => $userID));
        $this->db->delete(TABLE_INTERNALMAIL, array('recipient' => $userID));
        $this->db->delete(TABLE_INTERNALMAIL_CONTACTS, array('user_id' => $userID));
        $this->db->delete(TABLE_INTERNALMAIL_CONTACTS, array('interlocutor_id' => $userID));
        $this->db->delete(TABLE_INTERNALMAIL_FOLDERS, array('user_id' => $userID));
        $this->db->delete(TABLE_INTERNALMAIL_FOLDERS_USERS, array('user_id' => $userID));
        $this->db->delete(TABLE_INTERNALMAIL_FOLDERS_USERS, array('interlocutor_id' => $userID));

        if ( ! empty($users)) {
            foreach($users as $v) {
                $this->updateNewMessagesCounter($v);
            }
        }

    }

}