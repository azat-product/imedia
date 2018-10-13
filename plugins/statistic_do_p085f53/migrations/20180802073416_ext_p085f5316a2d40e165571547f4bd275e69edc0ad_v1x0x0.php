<?php

use bff\db\migrations\Migration as Migration;
use Phinx\Db\Adapter\MysqlAdapter;

class ExtP085f5316a2d40e165571547f4bd275e69edc0adV1x0x0 extends Migration
{
    protected $_tableStatistic;

    protected function init()
    {
        parent::init();
        $this->_tableStatistic = Plugin_Statistic_Do_p085f53_model::TABLE_STATISTIC;
    }

    /**
     * Use this function to write migration.
     * Remember to use Table::update instead of Table::save
     */
    public function migrate()
    {
        if ( ! $this->hasTable($this->_tableStatistic) ) {
            $this->table($this->_tableStatistic, ['engine' => 'InnoDB', 'id' => false])
                ->addColumn('type',   'integer',  ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'null'=>false, 'signed' => false])
                ->addColumn('dte', 'date')
                ->addColumn('value', 'integer', ['limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'signed' => false])
                ->addIndex(['type', 'dte'], ['unique' => true, 'name' => 'id'])
                ->create();
        }

    }

    /**
     * Use this function to describe rollback actions
     * Remember to use Table::dropIfExists instead of Table::drop
     */
    public function rollback()
    {
        $this->dropIfExists($this->_tableStatistic);
    }
}