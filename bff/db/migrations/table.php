<?php namespace bff\db\migrations;

/**
 * Миграции: класс работы с таблицами в процессе миграции
 * @version 0.24
 * @modified 1.nov.2017
 */

use Phinx\Db\Table as PhinxTable;

class Table extends PhinxTable
{
    # Table

    /**
     * {@inheritdoc}
     */
    public function drop()
    {
        if ($this->exists()) {
            parent::drop();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($newTableName)
    {
        if ($this->exists()) {
            return parent::rename($newTableName);
        }

        return $this;
    }

    # Column

    /**
     * {@inheritdoc}
     */
    public function addColumn($columnName, $type = null, $options = array())
    {
        if ( ! $this->hasColumn($columnName)) {
            return parent::addColumn($columnName, $type, $options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeColumn($columnName)
    {
        if ($this->hasColumn($columnName)) {
            return parent::removeColumn($columnName);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function renameColumn($oldName, $newName)
    {
        if ($this->hasColumn($oldName)) {
            return parent::renameColumn($oldName, $newName);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeColumn($columnName, $newColumnType, $options = array())
    {
        if ($this->hasColumn($columnName)) {
            return parent::changeColumn($columnName, $newColumnType, $options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasColumn($columnName)
    {
        return ($this->exists() && parent::hasColumn($columnName));
    }

    # Index

    /**
     * {@inheritdoc}
     */
    public function addIndex($columns, $options = array())
    {
        if ( ! $this->hasIndex($columns)) {
            return parent::addIndex($columns, $options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeIndex($columns)
    {
        if ($this->hasIndex($columns)) {
            return parent::removeIndex($columns);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex($columns)
    {
        return ($this->exists() && parent::hasIndex($columns));
    }

    # Foreign Key

    /**
     * {@inheritdoc}
     */
    public function addForeignKey($columns, $referencedTable, $referencedColumns = array('id'), $options = array())
    {
        if ( ! $this->hasForeignKey($columns)) {
            return parent::addForeignKey($columns, $referencedTable, $referencedColumns, $options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($columns, $constraint = null)
    {
        if ($this->hasForeignKey($columns, $constraint)) {
            return parent::dropForeignKey($columns, $constraint);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasForeignKey($columns, $constraint = null)
    {
        return ($this->exists() && parent::hasForeignKey($columns, $constraint));
    }
}