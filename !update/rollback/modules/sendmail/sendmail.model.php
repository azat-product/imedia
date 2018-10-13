<?php

# Работа с почтой - массовая рассылка (sendmail)
define('TABLE_MASSEND',           DB_PREFIX . 'massend');
define('TABLE_MASSEND_RECEIVERS', DB_PREFIX . 'massend_receivers');

class SendmailModel_ extends SendmailModelBase
{
    /**
     * Формируем задание на рассылку и заполняем список получателей
     * @param array $settings данные для рассылки
     * @param integer $subscribeType тип подписки
     * @return bool|integer ID рассылки или false (ошибка)
     */
    public function massendStart($settings, $subscribeType)
    {
        if (empty($settings) || empty($subscribeType)) {
            bff::log('Sendmail::massendStart: empty settings error');
            return false;
        }
        $shopOnly = ! empty($settings['shop_only']);

        # получение общего кол-ва получателей
        $receiversTotal = $this->db->one_data('
            SELECT COUNT(*) FROM ' . TABLE_USERS . '
            WHERE (enotify & ' . $subscribeType . ') AND enotify > 0 AND blocked = 0 AND activated = 1
        '.($shopOnly ? ' AND shop_id > 0 ' : ''));

        $delay = Sendmail::delay();

        $massendID = $this->db->insert(TABLE_MASSEND, array(
            'total'    => $receiversTotal,
            'started'  => date('Y-m-d H:i:s', strtotime('+ '.$delay.' minutes')),
            'settings' => serialize($settings),
            'status'   => Sendmail::STATUS_SCHEDULED,
        ));
        if (empty($massendID)) {
            bff::log('Sendmail::massendStart: unable to create massend record');
            return false;
        }

        # сохраняем ID получателей (пользователей) в базу
        $this->db->exec('
            INSERT INTO '.TABLE_MASSEND_RECEIVERS.' (massend_id, user_id)
            SELECT :massend_id, user_id FROM ' . TABLE_USERS . '
            WHERE (enotify & ' . $subscribeType . ') AND enotify > 0 AND blocked = 0 AND activated = 1 '.
                  ($shopOnly ? ' AND shop_id > 0 ' : '').'
            ORDER BY user_id ASC ', array(':massend_id' => $massendID));

        return $massendID;
    }

    /**
     * Данные о рассылке
     * @param integer $massendID ID рассылки
     * @param array $fields список требуемых полей
     * @return mixed
     */
    public function massendData($massendID, $fields = array())
    {
        if (empty($fields)) {
            $fields = array('*');
        }
        return $this->db->one_array('SELECT '.join(',', $fields).' FROM ' . TABLE_MASSEND . ' WHERE id = :id', array(':id' => $massendID));
    }

    /**
     * Обновление данных о рассылке
     * @param integer $massendID ID рассылки
     * @param array $data данные
     * @return bool
     */
    public function massendSave($massendID, array $data)
    {
        if (empty($data)) {
            return false;
        }
        return $this->db->update(TABLE_MASSEND, $data, array('id' => $massendID));
    }

    /**
     * Прохождение по списку получателей рассылки
     * @param integer $massendID ID рассылки
     * @param callable $callable
     */
    public function massendReceiversIterator($massendID, callable $callable)
    {
        if (empty($massendID)) {
            return;
        }
        $this->db->select_iterator('
            SELECT R.user_id, U.name, U.surname, U.email, U.login, U.lang
            FROM '.TABLE_MASSEND_RECEIVERS.' R, ' . TABLE_USERS . ' U
            WHERE R.massend_id = :id AND R.processed = 0 AND R.user_id = U.user_id', array(':id' => $massendID), $callable);
    }

    /**
     * Обновление данных о получателе рассылки
     * @param integer $massendID ID рассылки
     * @param integer $userID ID пользователя
     * @param array $data данные
     * @return bool
     */
    public function massendReceiverUpdate($massendID, $userID, array $data)
    {
        if (empty($data)) return false;
        return $this->db->update(TABLE_MASSEND_RECEIVERS, $data, array('massend_id' => $massendID, 'user_id' => $userID));
    }

    /**
     * Удаление рассылки
     * @param integer $massendID ID рассылки
     */
    public function massendDelete($massendID)
    {
        $this->db->delete(TABLE_MASSEND, array('id' => $massendID));
        $this->db->delete(TABLE_MASSEND_RECEIVERS, array('massend_id' => $massendID));
    }

}