<?php

use bff\db\migrations\Migration as Migration;
use Phinx\Db\Adapter\MysqlAdapter;

class ExtP00ae31b93dc9eee14c6ebe5cfc15b1746c3ec3eV1x0x0 extends Migration
{
    /**
     * Use this function to write migration.
     * Remember to use Table::update instead of Table::save
     */
    public function migrate()
    {
        if ( ! $this->hasTable(TABLE_SETKA_IMAGES)) {
            $this->table(TABLE_SETKA_IMAGES)
                ->addColumn('hash', 'char', ['limit' => 32, 'default' => ''])
                ->addColumn('module', 'string', ['limit' => 50, 'default' => ''])
                ->addColumn('filename', 'string', ['limit' => 50, 'default' => ''])
                ->addColumn('ext', 'string', ['limit' => 10, 'default' => ''])
                ->addColumn('name', 'string', ['limit' => 150, 'default' => ''])
                ->addColumn('props', 'text')
                ->addColumn('size', 'integer',  ['limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'null'=>false, 'signed' => false])
                ->addColumn('record_id', 'integer',  ['limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'null'=>false, 'signed' => false])
                ->create();
        }
    }

    /**
     * Use this function to describe rollback actions
     * Remember to use Table::dropIfExists instead of Table::drop
     */
    public function rollback()
    {

    }
}