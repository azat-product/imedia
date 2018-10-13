<?php

use bff\db\migrations\Migration as Migration;
use Phinx\Db\Adapter\MysqlAdapter;

class DoV2x4x1 extends Migration
{
    protected function init()
    {
        parent::init();

        $steps = [
            'sendmail'  => ['t'=>'Почтовые рассылки'],
            'bbsVirtualCatsFix'  => ['t'=>'Виртуальные категории: fix внешнего ключа'],
            'bbsItemsIndexes'  => ['t'=>'Оптимизация sql запросов с таблицей объявлений'],
            'shopsLang' => ['t'=>'Мультиязычность магазинов'],
            'usersSocial' => ['t'=>'Авторизация через соц. сети: провадеры+'],
            'contentPlus' => ['t'=>'Расширение текстовых полей контентных модулей'],
            'sphinxWordforms' => ['t'=>'Sphinx: словоформы'],
            'bbsItemsImagesFK' => ['t'=>'Внешний ключ таблицы изображений объявлений'],
        ];
        if ( ! bff::moduleExists('shops')) {
            unset($steps['shopsLang']);
        }
        $this->setSteps($steps);

    }

    /**
     * Use this function to write migration.
     * Remember to use Table::update instead of Table::save
     */
    public function migrate()
    {
        $output = $this->getOutput();

        # Sendmail
        if ($this->startStep('sendmail')) {

            Sendmail::model();

            $this->execute('UPDATE '.TABLE_MASSEND.' SET status = 1 WHERE status = 0');

            $this->table(TABLE_MASSEND)
                ->addColumn('pid', 'integer', ['limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'signed' => false])
                ->update();

            $this->table(TABLE_MASSEND_RECEIVERS)
                ->addColumn('processed', 'integer',  ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'signed' => false])
                ->update();
        }

        # bbsVirtualCatsFix
        if ($this->startStep('bbsVirtualCatsFix')) {
            $this->table(TABLE_BBS_CATEGORIES)
                ->dropForeignKey('virtual_ptr')
                ->addForeignKey('virtual_ptr', TABLE_BBS_CATEGORIES, 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION', 'constraint' => 'virtual_ptr'])
                ->update();
        }

        # bbsItemsIndexes
        if ($this->startStep('bbsItemsIndexes')) {
            # поля для новых индексов:
            $this->table(TABLE_BBS_ITEMS)
                ->addColumn('is_publicated', 'integer',  ['after' => 'shop_id', 'limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'signed' => false])
                ->addColumn('is_moderating', 'integer',  ['after' => 'is_publicated', 'limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'signed' => false])
                ->addColumn('cat_path', 'string',  ['after' => 'cat_type', 'default' => '', 'limit' => 40 /* 40 ('{4}-' x 8) */])
                ->addColumn('reg_path', 'string',  ['after' => 'reg3_city', 'default' => '', 'limit' => 70 /* 70 ('{12}-' x 5) */])
                ->update();
            $output->writeln('Finished: create index fields');

            # deleted=1  =>  status = STATUS_DELETED
            $this->execute('UPDATE '.TABLE_BBS_ITEMS.'
                SET status = '.BBS::STATUS_DELETED.'
                WHERE deleted = 1');
            $output->writeln('Finished: deleted=1 => status=STATUS_DELETED');

            # конвертируем данные
            BBS::model()->itemsIndexesUpdate(array(), 'is_publicated');
            $output->writeln('Finished: itemsIndexesUpdate + is_publicated');
            BBS::model()->itemsIndexesUpdate(array(), 'is_moderating');
            $output->writeln('Finished: itemsIndexesUpdate + is_moderating');
            BBS::model()->itemsIndexesUpdate(array(), 'cat_path');
            $output->writeln('Finished: itemsIndexesUpdate + cat_path');
            BBS::model()->itemsIndexesUpdate(array(), 'reg_path');
            $output->writeln('Finished: itemsIndexesUpdate + reg_path');

            # список полей дин.свойств
            $dp = BBS::i()->dp();
            $prefix = $dp->datafield_prefix;
            $last = $dp->datafield_int_last;
            if ($last > 16) {
                $last = 16; # ограничение MySQL не более 16 полей в индексе
            }
            $fieldsDP = array();
            for ($i = 1; $i <= $last; $i++) {
                $fieldsDP[] = $prefix.$i;
            }

            # создаем индексы
            $this->table(TABLE_BBS_ITEMS)
                # search_simple:
                ->addIndex(['is_publicated','status','cat_path','reg_path','cat_type','publicated_order'], ['name' => 'search_simple', 'unique' => false])
                # search:
                ->addIndex(['is_publicated','status','cat_path','reg_path','cat_type','imgcnt','owner_type','price_search','addr_lat','district_id','metro_id','svc_fixed','svc_fixed_order','publicated_order'], ['name' => 'search', 'unique' => false])
                # search_dp:
                ->addIndex($fieldsDP, ['name' => 'search_dp', 'unique' => false])
                # search_svc:
                ->addIndex(['svc'], ['name' => 'search_svc', 'unique' => false])
                # search_press:
                ->addIndex(['svc_press_status','svc_press_date','svc_press_date_last'], ['name' => 'search_press', 'unique' => false])
                # users:
                ->addIndex(['user_id','shop_id','is_publicated','status','cat_path','reg_path','publicated_order'], ['name' => 'users', 'unique' => false])
                # moderating:
                ->addIndex(['is_moderating'], ['name' => 'moderating', 'unique' => false])
                ->update();
            $output->writeln('Finished: create indexes');
        }
        
        # Shops
        if (bff::moduleExists('shops') && $this->startStep('shopsLang')) {
            bff::module('shops');
            if ( ! $this->hasTable(TABLE_SHOPS_LANG)) {
                $this->table(TABLE_SHOPS_LANG, ['id' => false])
                    ->addColumn('id',           'integer',  ['limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'signed' => false])
                    ->addColumn('lang',         'char',     ['limit' => 2, 'default' => ''])
                    ->addColumn('title',        'string',   ['limit' => 150, 'default' => ''])
                    ->addColumn('title_edit',   'string',   ['limit' => 100, 'default' => ''])
                    ->addColumn('descr',        'text',     ['null' => true, 'default' => null])
                    ->addColumn('addr_addr',    'string',   ['limit' => 400, 'default' => ''])
                    ->addIndex(['id', 'lang'], ['unique' => true, 'name' => 'lang'])
                    ->addForeignKey('id', TABLE_SHOPS, 'id', ['delete' => 'CASCADE'])
                    ->create();
            } else {
                $this->table(TABLE_SHOPS_LANG)
                    ->addColumn('lang',         'char',     ['limit' => 2, 'default' => ''])
                    ->addColumn('title',        'string',   ['limit' => 150, 'default' => ''])
                    ->addColumn('title_edit',   'string',   ['limit' => 100, 'default' => ''])
                    ->addColumn('descr',        'text',     ['null' => true, 'default' => null])
                    ->addColumn('addr_addr',    'string',   ['limit' => 400, 'default' => ''])
                    ->addIndex(['id', 'lang'], ['unique' => true, 'name' => 'lang'])
                    ->addForeignKey('id', TABLE_SHOPS, 'id', ['delete' => 'CASCADE'])
                    ->update();
            }

            $lang = bff::locale()->getLanguages();
            foreach ($lang as $l) {
                $this->execute('INSERT INTO ' . TABLE_SHOPS_LANG . ' SELECT id, '.$this->db->str2sql($l).', title, title_edit, descr, addr_addr  FROM '.TABLE_SHOPS);
            }

//            $this->table(TABLE_SHOPS)
//                ->removeColumn('title')
//                ->removeColumn('title_edit')
//                ->removeColumn('descr')
//                ->removeColumn('addr_addr')
//                ->update();
        }

        if ($this->startStep('usersSocial')) {
            Users::model();
            $this->table(TABLE_USERS_SOCIAL)
                ->changeColumn('provider_id', 'string', ['limit' => 100, 'default' => ''])
                ->update();
        }

        if ($this->startStep('contentPlus')) {
            # Blog
            if (bff::moduleExists('blog')) {
                bff::module('blog');

                $columns = $this->table(TABLE_BLOG_POSTS)->getColumns();
                $fields = array('content');
                foreach ($columns as $v) {
                    $name = $v->getName();
                    if ( ! in_array($name, $fields)) continue;
                    if ( $v->getLimit() < MysqlAdapter::TEXT_LONG ) {
                        $this->table(TABLE_BLOG_POSTS)
                            ->changeColumn($name, 'text',  ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null])
                            ->update();
                    }
                }
            }

            # Help
            if (bff::moduleExists('help')) {
                bff::module('help');

                $columns = $this->table(TABLE_HELP_QUESTIONS)->getColumns();
                $fields = array('content');
                foreach($columns as $v) {
                    $name = $v->getName();
                    if ( ! in_array($name, $fields)) continue;
                    if ( $v->getLimit() < MysqlAdapter::TEXT_LONG ) {
                        $this->table(TABLE_HELP_QUESTIONS)
                            ->changeColumn($name, 'text',  ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null])
                            ->update();
                    }
                }
            }
        }

        # Sphinx Wordforms
        if ($this->startStep('sphinxWordforms')) {
            if ( ! $this->hasTable(\bff\db\Sphinx::TABLE_WORDFORMS) ) {
                $this->table(\bff\db\Sphinx::TABLE_WORDFORMS, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => ['id']])
                    ->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
                    ->addColumn('module',   'integer',  ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'null'=>false, 'signed' => false])
                    ->addColumn('src', 'string', ['limit' => 250, 'default' => ''])
                    ->addColumn('dest', 'string', ['limit' => 250, 'default' => ''])
                    ->addColumn('created', 'datetime')
                    ->create();
            }
        }

        # Таблица изображений объявлений + внешний ключ
        if ($this->startStep('bbsItemsImagesFK')) {
            $this->table(TABLE_BBS_ITEMS_IMAGES)
                ->dropForeignKey('item_id')
                ->update();
        }

    }

    /**
     * Use this function to describe rollback actions
     * Remember to use Table::dropIfExists instead of Table::drop
     */
    public function rollback()
    {
        # Sendmail
        if ($this->startStep('sendmail')) {

            Sendmail::model();

            $this->table(TABLE_MASSEND)
                ->removeColumn('pid')
                ->update();

            $this->table(TABLE_MASSEND_RECEIVERS)
                ->removeColumn('processed')
                ->update();
        }

        # bbsItemsIndexes
        if ($this->startStep('bbsItemsIndexes')) {
            $this->table(TABLE_BBS_ITEMS)
                ->removeColumn('is_publicated')
                ->removeColumn('is_moderating')
                ->removeColumn('cat_path')
                ->removeColumn('reg_path')
                ->update();
        }

        # Shops
        if (bff::moduleExists('shops') && $this->startStep('shopsLang')) {
            bff::module('shops');

//            $this->table(TABLE_SHOPS)
//                ->addColumn('title',        'string',   ['limit' => 150, 'default' => '',   'after' => 'keyword'])
//                ->addColumn('title_edit',   'string',   ['limit' => 100, 'default' => '',   'after' => 'title'])
//                ->addColumn('descr',        'text',     ['null' => true, 'default' => null, 'after' => 'title_edit'])
//                ->addColumn('addr_addr',    'string',   ['limit' => 400, 'default' => '',   'after' => 'region_id'])
//                ->update();

            $def = bff::locale()->getDefaultLanguage();

            $this->execute('UPDATE '.TABLE_SHOPS.' S, '.TABLE_SHOPS_LANG.' L 
                SET S.title = L.title, S.title_edit = L.title_edit, S.descr = L.descr, S.addr_addr = L.addr_addr 
                WHERE S.id = L.id AND L.lang = '.$this->db->str2sql($def));

            //$this->dropIfExists(TABLE_SHOPS_LANG);
        }

        if ($this->startStep('usersSocial')) {
            Users::model();
            $this->table(TABLE_USERS_SOCIAL)
                ->changeColumn('provider_id', 'integer',  ['limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'signed' => false])
                ->update();
        }

        # Sphinx Wordforms
        if ($this->startStep('sphinxWordforms')) {
            $this->dropIfExists(\bff\db\Sphinx::TABLE_WORDFORMS);
        }
    }
}