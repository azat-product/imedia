<?php

define('TABLE_SENDMAIL_WRAPPERS', DB_PREFIX.'sendmail_wrappers');

class SendmailModelBase extends Model
{
    public $langWrappers = array(
        'content' => TYPE_STR,  # Текст
    );

    # --------------------------------------------------------------------
    # Шаблоны писем

    /**
     * Список шаблонов
     * @param array $aFilter фильтр списка шаблонов
     * @return mixed
     */
    public function wrappersListing(array $aFilter = array())
    {
        $aFilter = $this->prepareFilter($aFilter, 'W');

        return $this->db->select_key('SELECT W.id, W.created, W.is_html, W.title
               FROM '.TABLE_SENDMAIL_WRAPPERS.' W
               '.$aFilter['where']
               .' ORDER BY W.num', 'id', $aFilter['bind']);
    }

    /**
     * Список шаблонов (select::options)
     * @param integer $nSelectedID ID выбранного шаблона
     * @param bool $mEmpty пункт по-умолчанию
     * @return string HTML
     */
    public function wrappersOptions($nSelectedID, $mEmpty = false)
    {
        $aData = $this->wrappersListing();
        if (empty($aData)) $aData = array();
        return HTML::selectOptions($aData, $nSelectedID, $mEmpty, 'id', 'title');
    }

    /**
     * Получение данных шаблона
     * @param integer $nWrapperID ID шаблона
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function wrapperData($nWrapperID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT W.*
                    FROM '.TABLE_SENDMAIL_WRAPPERS.' W
                    WHERE W.id = :id',
                    array(':id'=>$nWrapperID));
            if ( ! empty($aData)) {
                $this->db->langFieldsSelect($aData, $this->langWrappers);
            }
        } else {
            //
        }
        return $aData;
    }

    /**
     * Сохранение шаблона
     * @param integer $nWrapperID ID шаблона
     * @param array $aData данные шаблона
     * @return boolean|integer
     */
    public function wrapperSave($nWrapperID, array $aData)
    {
        if (empty($aData)) return false;

        $this->db->langFieldsModify($aData, $this->langWrappers, $aData);

        if ($nWrapperID > 0)
        {
            $aData['modified'] = $this->db->now(); # Дата изменения

            $res = $this->db->update(TABLE_SENDMAIL_WRAPPERS, $aData, array('id'=>$nWrapperID));

            return ! empty($res);
        }
        else
        {
            $aData['created'] = $this->db->now(); # Дата создания
            $aData['created_id'] = $this->security->getUserID(); # Автор
            $aData['modified'] = $this->db->now(); # Дата изменения
            $aData['num'] = $this->db->one_data('SELECT MAX(num) FROM '.TABLE_SENDMAIL_WRAPPERS); # Порядок
            $aData['num'] = intval($aData['num']) + 1;

            $nWrapperID = $this->db->insert(TABLE_SENDMAIL_WRAPPERS, $aData);
            if ($nWrapperID > 0) {

            }
            return $nWrapperID;
        }
    }

    /**
     * Изменение порядка шаблонов
     * @param string $sOrderField поле, по которому производится сортировка
     * @param string $aCond дополнительные условия
     * @return mixed @see rotateTablednd
     */
    public function wrappersRotate($sOrderField, $aCond = '')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . (is_array($aCond) ? join(' AND ', $aCond) : $aCond);
        }

        return $this->db->rotateTablednd(TABLE_SENDMAIL_WRAPPERS, $aCond, 'id', $sOrderField);
    }

    /**
     * Удаление шаблона
     * @param integer $nWrapperID ID шаблона
     * @return boolean
     */
    public function wrapperDelete($nWrapperID)
    {
        if (empty($nWrapperID)) return false;
        $res = $this->db->delete(TABLE_SENDMAIL_WRAPPERS, array('id'=>$nWrapperID));
        if ( ! empty($res)) {
            return true;
        }
        return false;
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_SENDMAIL_WRAPPERS => array('type' => 'fields', 'fields' => $this->langWrappers, 'title' => _t('sendmail', 'Шаблоны писем')),
        );
    }
}