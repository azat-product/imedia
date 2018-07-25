<?php namespace bff\db\migrations;

/**
 * Миграции: базовый класс миграции
 * @version 0.24
 * @modified 13.mar.2018
 */

use Phinx\Migration\AbstractMigration;

abstract class Migration extends AbstractMigration
{
    /** @var \bff\db\Database */
    protected $db;
    /**
     * Шаги миграции
     * @var array
     */
    protected $steps = [];
    /**
     * Объект расширения для которого выполняются миграции или false (миграции ядра)
     * @var \bff\extend\Extension|boolean
     */
    protected $extension = false;

    protected function init()
    {
        parent::init();

        $this->db = \bff::database();
        $this->extension = \config::get('app.internal.migration.extension', false);
    }

    protected function setSteps(array $steps = array())
    {
        $this->steps = $steps;
    }

    protected function hasStep($step)
    {
        return array_key_exists($step, $this->steps);
    }

    protected function startStep($step)
    {
        if ($this->hasStep($step)) {
            $this->getOutput()->writeln('Start step: "'.$step.'"');
            return true;
        }
        return false;
    }

    /**
     * @return \bff\extend\Extension|boolean
     */
    public function getExtension()
    {
        return $this->extension;
    }

    abstract public function migrate();

    abstract public function rollback();

    public function up()
    {
        try {
            $this->getAdapter()->beginTransaction();
            $this->migrate();
            $this->getAdapter()->commitTransaction();
        } catch (\Exception $e) {
            $this->getAdapter()->rollbackTransaction();
            $this->rollback();
            throw new \Exception($e);
        }
    }

    public function down()
    {
        try {
            $this->getAdapter()->beginTransaction();
            $this->rollback();
            $this->getAdapter()->commitTransaction();
        } catch (\Exception $e) {
            $this->getAdapter()->rollbackTransaction();
            throw new \Exception($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function table($tableName, $options = array())
    {
        return new Table($tableName, $options, $this->getAdapter());
    }

    public function dropIfExists($table)
    {
        if ($this->table($table)->exists()) {
            $this->table($table)->drop();
        }
    }
}