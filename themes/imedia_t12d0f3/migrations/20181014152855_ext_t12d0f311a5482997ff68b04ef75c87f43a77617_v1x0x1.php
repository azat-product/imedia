<?php

use bff\db\migrations\Migration as Migration;

class ExtT12d0f311a5482997ff68b04ef75c87f43a77617V1x0x1 extends Migration
{
    /**
     * Use this function to write migration.
     * Remember to use Table::update instead of Table::save
     */
    public function migrate()
    {
        $this->table('bff_bbs_items_ratings',
            ['engine' => 'InnoDB', 'id' => false, 'primary_key' => ['item_id', 'user_id']]
        )
            ->addColumn('item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('value', 'integer', ['signed' => false, 'null' => false])
            ->addForeignKey(
                'item_id',
                'bff_bbs_items',
                'id',
                ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->create();
    }

    /**
     * Use this function to describe rollback actions
     * Remember to use Table::dropIfExists instead of Table::drop
     */
    public function rollback()
    {
        $exists = $this->table('bff_bbs_items_ratings')->hasForeignKey('item_id');
        if ($exists) {
            $this->table('bff_bbs_items_ratings')->dropForeignKey('item_id');
        }
        $this->dropIfExists('bff_bbs_items_ratings');
    }
}
