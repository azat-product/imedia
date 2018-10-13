<?php

use bff\db\migrations\Migration as Migration;
use Phinx\Db\Adapter\MysqlAdapter;

class DoV2x3x3 extends Migration
{
    protected function init()
    {
        parent::init();

        $this->setSteps([
            'templates'   => ['t'=>'Шаблоны дин. свойств'],
            'virtualcats' => ['t'=>'Виртуальные категории'],
            'contacts'    => ['t'=>'Контакты +'],
            'usercomment' => ['t'=>'Заметка о пользователе'],
        ]);
    }

    public function migrate()
    {
        # Шаблоны дин. свойств: заголовок и описание
        if ($this->hasStep('templates')) {
            $this->table(TABLE_BBS_CATEGORIES)
                ->addColumn('tpl_title_enabled', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default'=>0, 'signed'=>false])
                ->update();

            $this->table(TABLE_BBS_CATEGORIES_LANG)
                ->addColumn('tpl_title_list', 'text')
                ->addColumn('tpl_title_view', 'text')
                ->addColumn('tpl_descr_list', 'text')
                ->update();

            $this->table(TABLE_BBS_ITEMS)
                ->addColumn('title_list', 'string',
                    ['limit' => 150, 'default' => '', 'after' => 'title']
                )
                ->addColumn('descr_list', 'text',
                    ['after' => 'descr']
                )
                ->update();

            $this->table(TABLE_BBS_ITEMS_LANG)
                ->addColumn('title_list', 'string',
                    ['limit' => 150, 'default' => '', 'after' => 'title']
                )
                ->addColumn('descr_list', 'text',
                    ['after' => 'descr']
                )
                ->update();
        }

        # Виртуальные категории
        if ($this->hasStep('virtualcats')) {
            $this->table(TABLE_BBS_ITEMS)
                ->addColumn('cat_id_virtual', 'integer',
                    ['signed' => false, 'null' => true, 'default' => null, 'after' => 'cat_id4']
                )
                ->addForeignKey('cat_id_virtual', TABLE_BBS_CATEGORIES, 'id')
                ->update();

            $this->table(TABLE_BBS_CATEGORIES)
                ->addColumn('virtual_ptr', 'integer',
                    ['signed' => false, 'null' => true, 'default' => null, 'after' => 'pid']
                )
                ->addForeignKey('virtual_ptr', TABLE_BBS_CATEGORIES, 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->update();
        }

        # Контакты +
        if ($this->hasStep('contacts')) {
            $contactsConvert = function ($table) {
                $this->execute("UPDATE " . $table . " 
                    SET contacts = CONCAT('{' , 
                        IF(skype <> '', CONCAT('\"skype\": \"', skype, '\"'), ''), 
                        IF(icq <> '', CONCAT(IF(skype <> '', ',', ''), '\"icq\": \"', icq, '\"'), ''), '}');"
                );
            };

            # users:
            $this->table(TABLE_USERS)
                ->addColumn('contacts', 'text', ['null' => true, 'default' => null, 'after' => 'icq'])
                ->update();
            $contactsConvert(TABLE_USERS);

            # bbs:
            $this->table(TABLE_BBS_ITEMS)
                ->addColumn('_contacts', 'text', ['after' => 'contacts'])
                ->update();
            $this->execute('UPDATE ' . TABLE_BBS_ITEMS . ' SET _contacts = contacts');
            $contactsConvert(TABLE_BBS_ITEMS);

            # shops:
            if (bff::moduleExists('shops')) {
                Shops::i();
                $this->table(TABLE_SHOPS)
                    ->addColumn('contacts', 'text', ['null' => true, 'default' => null, 'after' => 'icq'])
                    ->update();
                $contactsConvert(TABLE_SHOPS);
            }
        }

        # Заметка о пользователе
        if ($this->hasStep('usercomment')) {
            $this->table(TABLE_USERS)
                ->addColumn('admin_comment', 'text', ['after' => 'lang'])
                ->update();
        }
    }

    public function rollback()
    {
        # Шаблоны дин. свойств: заголовок и описание
        if ($this->hasStep('templates')) {
            $this->table(TABLE_BBS_CATEGORIES)
                ->removeColumn('tpl_title_enabled')
                ->update();

            $this->table(TABLE_BBS_CATEGORIES_LANG)
                ->removeColumn('tpl_title_list')
                ->removeColumn('tpl_title_view')
                ->removeColumn('tpl_descr_list')
                ->update();

            $this->table(TABLE_BBS_ITEMS)
                ->removeColumn('title_list')
                ->removeColumn('descr_list')
                ->update();

            $this->table(TABLE_BBS_ITEMS_LANG)
                ->removeColumn('title_list')
                ->removeColumn('descr_list')
                ->update();
        }

        # Виртуальные категории
        if ($this->hasStep('virtualcats')) {
            $this->table(TABLE_BBS_ITEMS)
                ->dropForeignKey('cat_id_virtual')
                ->removeColumn('cat_id_virtual')
                ->update();

            $this->table(TABLE_BBS_CATEGORIES)
                ->dropForeignKey('virtual_ptr')
                ->removeColumn('virtual_ptr')
                ->update();
        }

        # Контакты +
        if ($this->hasStep('contacts')) {
            $this->table(TABLE_USERS)
                 ->removeColumn('contacts')
                 ->update();

            if (bff::moduleExists('shops')) {
                Shops::i();
                $this->table(TABLE_SHOPS)
                    ->removeColumn('contacts')
                    ->update();
            }

            if ($this->table(TABLE_BBS_ITEMS)->hasColumn('_contacts')) {
                $this->execute("UPDATE " . TABLE_BBS_ITEMS . " SET contacts = _contacts");
                $this->table(TABLE_BBS_ITEMS)
                     ->removeColumn('_contacts')
                     ->update();
            }
        }

        # Заметка о пользователе
        if ($this->hasStep('usercomment')) {
            $this->table(TABLE_USERS)
                ->removeColumn('admin_comment')
                ->update();
        }
    }
}