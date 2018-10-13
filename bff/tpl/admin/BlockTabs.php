<?php namespace bff\tpl\admin;

use \Exception;

/**
 * Компонент построения табов
 */
class BlockTabs extends Block
{
    /** @var BlockList */
    protected $list;

    /** @var string название параметра передающего активный таб */
    protected $activeKey = 'tab';

    /** @var integer тип значения параметра */
    protected $activeValidation = TYPE_NOTAGS;

    /** @var string источник данных */
    protected $activeMethod = 'postget';

    /** @var mixed ID активного таба */
    protected $activeId = null;

    /** @var mixed ID таба по умолчанию */
    protected $defaultId = null;

    /** @var array Список табов */
    protected $tabs = array();

    public function init()
    {
        if ( ! parent::init()) {
            return false;
        }
        $this->setTemplateName('block.tabs');
        return true;
    }

    /**
     * Возвращаем префикс класса блока
     * @param string $plus
     * @return string
     */
    public function cssClass($plus = '')
    {
        return parent::cssClass(
              ($this->list !== null ? '-list' : '').'-tabs'
            . ($plus !== '' ? '-' . $plus : '')
        );
    }

    /**
     * Устанавливаем объект блока списка
     * @param BlockList $list
     */
    public function setList(BlockList $list)
    {
        $this->list = $list;
    }

    /**
     * Устанавливаем активный таб
     * @param mixed $id ID активного таба
     * @return $this
     */
    public function active($id)
    {
        if (array_key_exists($id, $this->tabs)) {
            $this->tabs[$id]['active'] = true;
            $this->activeId = $id;
        }
        return $this;
    }

    /**
     * Получаем активный таб
     * @return mixed ID активного таба
     */
    public function getActive()
    {
        if ($this->activeId === null) {
            $this->fillActive();
        }
        return $this->activeId;
    }

    /**
     * Включена ли у активного таба ротация
     * @return boolean
     */
    public function isActiveRotation()
    {
        $activeId = $this->getActive();
        return ( ! empty($this->tabs[$activeId]['rotate']));
    }

    /**
     * Устанавливаем настройки параметра передающего активный таб
     * @param string $key название параметра
     * @param integer $validation настройки валидации TYPE_
     * @param string $method источник данных
     */
    public function setActiveSettings($key, $validation = TYPE_NOTAGS, $method = 'postget')
    {
        $this->activeKey = $key;
        $this->activeValidation = $validation;
        $this->activeMethod = $method;
    }

    /**
     * Получаем название параметра передающего активный таб
     * @return string
     */
    public function getActiveKey()
    {
        return $this->activeKey;
    }

    /**
     * Получаем настройки валидации параметра передающего активный таб
     * @return string
     */
    public function getActiveValidation()
    {
        return $this->activeKey;
    }

    /**
     * Получаем активный таб
     * @param string|null $method источник данных: 'get','post','postget','getpost',null(указанный ранее)
     * @return mixed ID активированного таба или false
     */
    protected function fillActive($method = null)
    {
        if (empty($this->tabs)) {
            return false;
        }
        $value = false;
        if ($method === null) {
            $method = $this->activeMethod;
        }
        switch ($method) {
            case 'postget': $value = $this->input->postget($this->activeKey, $this->activeValidation); break;
            case 'getpost': $value = $this->input->getpost($this->activeKey, $this->activeValidation); break;
            case 'post': $value = $this->input->post($this->activeKey, $this->activeValidation); break;
            case 'get': $value = $this->input->get($this->activeKey, $this->activeValidation); break;
        }
        if (array_key_exists($value, $this->tabs)) {
            $this->tabs[$value]['active'] = true;
            $this->activeId = $value;
        } else if (array_key_exists($this->defaultId, $this->tabs)) {
            $this->tabs[$this->defaultId]['active'] = true;
            $this->activeId = $this->defaultId;
        } else {
            $first = reset($this->tabs);
            if ( ! empty($first)) {
                $this->tabs[$first['id']]['active'] = true;
                $this->activeId = $first['id'];
            }
        }
        return $this->activeId;
    }

    /**
     * Добавляем таб
     * @param integer|string $id уникальный ID таба
     * @param string $title название таба
     * @param boolean $default помечаем по умолчанию
     * @param array $extra аттрибуты
     * @return $this
     * @throws Exception
     */
    public function add($id, $title, $default = false, array $extra = [])
    {
        if ( ! is_scalar($id)) {
            throw new Exception(_t('html', '[class]: Идентификатор таба некорректного типа, допустимые: integer, float, string', ['class'=>static::class]));
        } else if (is_bool($id)) {
            $id = ($id ? 1 : 0);
        }

        $this->tabs[$id] = array_merge(array(
            'id' => $id,
            'title' => $title,
            'attr' => [],
            'default' => !empty($default),
            'rotate' => false,
        ), $extra);

        if (!empty($default)) {
            $this->defaultId = $id;
        }

        return $this;
    }

    /**
     * Действия выполняемые перед отрисовкой блока
     */
    protected function beforeRender()
    {
        parent::beforeRender();

        if ($this->activeId === null) {
            $this->fillActive();
        }
    }
}