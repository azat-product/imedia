<?php

# Таблицы
define('TABLE_CONTACTS', DB_PREFIX . 'contacts'); # контакты

class ContactsModel_ extends Model
{
    public function contactSave($nContactID, $aData)
    {
        if ($nContactID > 0) {
            $aData['modified'] = $this->db->now();

            return $this->db->update(TABLE_CONTACTS, $aData, array('id' => $nContactID));
        } else {
            $aData['created'] = $this->db->now();
            $aData['user_id'] = User::id();
            $aData['user_ip'] = Request::remoteAddress();

            return $this->db->insert(TABLE_CONTACTS, $aData, 'id');
        }
    }

    public function contactData($nContactID)
    {
        return $this->db->one_array('SELECT C.*, U.email as user_email
                        FROM ' . TABLE_CONTACTS . ' C
                            LEFT JOIN ' . TABLE_USERS . ' U ON C.user_id = U.user_id
                        WHERE C.id = :id', array(':id' => $nContactID)
        );
    }

    public function contactViewed($nContactID)
    {
        return $this->contactSave($nContactID, array(
                'viewed' => 1
            )
        );
    }

    public function contactDelete($nContactID)
    {
        return $this->db->delete(TABLE_CONTACTS, array('id' => $nContactID));
    }

    public function contactsListing($aFilter, $bCnt = false, $sqlLimit = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'C');

        if ($bCnt) {
            return (integer)$this->db->one_data('SELECT COUNT(C.id)
                                FROM ' . TABLE_CONTACTS . ' C
                                ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->select('SELECT C.*
                                    FROM ' . TABLE_CONTACTS . ' C
                                    ' . $aFilter['where'] . '
                                    ORDER BY C.created DESC '
            . $sqlLimit, $aFilter['bind']
        );
    }

}