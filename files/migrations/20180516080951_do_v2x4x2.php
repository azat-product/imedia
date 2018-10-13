<?php

use bff\db\migrations\Migration as Migration;
use Phinx\Db\Adapter\MysqlAdapter;

class DoV2x4x2 extends Migration
{
    protected $_usersAccess = array(
        array('module'=>'users','method'=>'users-delete','title'=>'Удаление пользователей','number'=>'5'),
        array('module'=>'site','method'=>'settings-system','title'=>'Системные настройки','number'=>'1'),
        array('module'=>'site','method'=>'extensions','title'=>'Дополнения','number'=>'2'),
        array('module'=>'site','method'=>'updates','title'=>'Обновления','number'=>'2'),
        array('module'=>'site','method'=>'localization','title'=>'Локализация','number'=>'9'),
    );

    protected function init()
    {
        parent::init();

        $steps = [
            'usersAccess'    => ['t'=>'Дополнительные права доступа групп пользователей'],
            'sphinxTable'    => ['t'=>'Изменение структуры'],
            'bannersRegions' => ['t'=>'Банеры несколько регионов'],
            'usersFake'      => ['t'=>'Фейковые пользователи'],
            'siteCountersPositions' => ['t'=>'Счетчики + позиции'],
        ];
        $this->setSteps($steps);
    }

    public function migrate()
    {
        # Users: права
        if ($this->startStep('usersAccess')) {
            foreach ($this->_usersAccess as $v) {
                $exist = (int)$this->db->one_data('SELECT COUNT(*) 
                  FROM '.TABLE_MODULE_METHODS.'
                  WHERE module = :module AND method = :method', array(
                    ':module' => $v['module'],
                    ':method' => $v['method'],
                ));
                if ( ! $exist) {
                    $this->execute("INSERT INTO ".TABLE_MODULE_METHODS."(`module`, `method`, `title`, `number`) 
                        VALUES ('".$v['module']."', '".$v['method']."', '".$v['title']."', ".$v['number'].")");
                }
            }
        }

        # sphinxTable
        if ($this->startStep('sphinxTable')) {
            $this->table(\bff\db\Sphinx::TABLE)
                ->addColumn('indexed_delta', 'timestamp', ['after' => 'indexed'])
                ->update();
        }

        # Банеры несколько регионов
        if ($this->startStep('bannersRegions')) {
            Banners::model();
            $tableBannersRegions = DB_PREFIX.'banners_regions';
            if ( ! $this->hasTable($tableBannersRegions)) {
                $this->table($tableBannersRegions, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => ['id']])
                    ->addColumn('id',           'integer',  ['signed' => false, 'identity' => true])
                    ->addColumn('banner_id',    'integer',  ['signed' => false, 'default' => 0])
                    ->addColumn('reg1_country', 'integer',  ['signed' => false, 'default' => 0])
                    ->addColumn('reg2_region',  'integer',  ['signed' => false, 'default' => 0])
                    ->addColumn('reg3_city',    'integer',  ['signed' => false, 'default' => 0])
                    ->addForeignKey('banner_id', TABLE_BANNERS, 'id', ['delete' => 'CASCADE'])
                    ->create();
            }

            if ($this->table(TABLE_BANNERS)->hasColumn('reg1_country')) {
                $this->db->exec('INSERT INTO '.$tableBannersRegions.'(banner_id, reg1_country, reg2_region, reg3_city)
                    SELECT B.id, B.reg1_country, B.reg2_region, B.reg3_city 
                    FROM '.TABLE_BANNERS.' B LEFT JOIN '.$tableBannersRegions.' R ON B.id = R.banner_id 
                        AND B.reg1_country = R.reg1_country AND B.reg2_region = R.reg2_region AND B.reg3_city = R.reg3_city
                    WHERE R.id IS NULL AND B.reg1_country > 0
                ');

                $this->table(TABLE_BANNERS)
                    ->removeColumn('reg1_country')
                    ->removeColumn('reg2_region')
                    ->removeColumn('reg3_city')
                    ->removeColumn('region_id')
                    ->update();
            }
        }

        # Users: fake
        if ($this->startStep('usersFake')) {
            if ( ! $this->table(TABLE_USERS)->hasColumn('fake')) {
                $this->table(TABLE_USERS)
                    ->addColumn('fake',   'integer',  ['after' => 'subscribed', 'limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'null'=>false, 'signed' => false])
                    ->update();
            }
        }

        # Site: counters + position
        if ($this->startStep('siteCountersPositions')) {
            if ( ! $this->table(TABLE_COUNTERS)->hasColumn('code_position')) {
                $this->table(TABLE_COUNTERS)
                    ->addColumn('code_position',   'integer',  ['after' => 'code', 'limit' => 1, 'default' => 0, 'null'=>false, 'signed' => false])
                    ->update();
            }
        }

    }

    public function rollback()
    {
        # Users: права
        if ($this->startStep('usersAccess')) {
            foreach ($this->_usersAccess as $v) {
                $this->execute("DELETE FROM " . TABLE_MODULE_METHODS . " WHERE `module` = '".$v['module']."' AND `method` = '".$v['method']."'");
            }
        }

        # sphinxTable
        if ($this->startStep('sphinxTable')) {
            $this->table(\bff\db\Sphinx::TABLE)
                ->removeColumn('indexed_delta')
                ->update();
        }

        # Банеры несколько регионов
        if ($this->startStep('bannersRegions')) {
            Banners::model();
            $tableBannersRegions = DB_PREFIX.'banners_regions';
            $this->table(TABLE_BANNERS)
                ->addColumn('reg1_country', 'integer',  ['signed' => false, 'default' => 0])
                ->addColumn('reg2_region',  'integer',  ['signed' => false, 'default' => 0])
                ->addColumn('reg3_city',    'integer',  ['signed' => false, 'default' => 0])
                ->addColumn('region_id',    'integer',  ['signed' => false, 'default' => 0])
                ->update();

            $this->db->exec('UPDATE '.TABLE_BANNERS.' B LEFT JOIN '.$tableBannersRegions.' R ON B.id = R.banner_id 
                    SET B.reg1_country = R.reg1_country, B.reg2_region = R.reg2_region, B.reg3_city = R.reg3_city
                ');

            $this->db->exec('UPDATE '.TABLE_BANNERS.' SET region_id = IF(reg3_city > 0, reg3_city, IF(reg2_region > 0, reg2_region, reg1_country))');

            $this->dropIfExists($tableBannersRegions);
        }

        # Users: fake
        if ($this->startStep('usersFake')) {
            $this->table(TABLE_USERS)
                ->removeColumn('fake')
                ->update();
        }

        # Site: counters + position
        if ($this->startStep('siteCountersPositions')) {
            $this->table(TABLE_COUNTERS)
                ->removeColumn('code_position')
                ->update();
        }
    }
}