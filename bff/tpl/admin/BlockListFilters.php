<?php namespace bff\tpl\admin;

/**
 * Компонент построения фильтра списка
 */
class BlockListFilters extends Block
{
    /** @var BlockList */
    protected $list;

    /** @var BlockTabs */
    protected $tabs;

    /** @var array поля фильтра */
    protected $filters = [];

    /** @var bool получены ли данные фильтра */
    protected $fed = false;

    const INPUT_CUSTOM       = 1; # Custom поле
    const INPUT_HIDDEN       = 2; # Скрытое поле
    const INPUT_TEXT         = 3; # Однострочный текст
    const INPUT_DATE         = 4; # Поле ввода даты
    const INPUT_AUTOCOMPLETE = 5; # Поле autocomplete TODO
    const INPUT_SELECT       = 6; # Выпадающий список
    const INPUT_CHECKBOX     = 7; # Галочка

    /**
     * Конструктор
     * @param BlockList $list объект работы со списком
     */
    public function __construct(BlockList $list)
    {
        $this->list = $list;
        $this->setParent($list);
    }

    public function init()
    {
        if ( ! parent::init()) {
            return false;
        }

        $this->setTemplateName('block.list.filters');

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
            '-list-filters'
            . ($plus !== '' ? '-' . $plus : '')
        );
    }

    /**
     * Табы фильтрации списка
     * @return BlockTabs
     */
    public function tabs()
    {
        if ($this->tabs === null) {
            $this->tabs = new BlockTabs();
            $this->tabs->setParent($this);
            $this->tabs->setList($this->list);
            $this->tabs->init();
        }

        return $this->tabs;
    }

    /**
     * Получаем настройки фильтра
     * @param string $method источник данных: 'get','post','postget'
     * @param boolean $force принудительно
     */
    protected function feed($method = 'postget', $force = false)
    {
        if ($this->fed && ! $force) {
            return;
        }

        $input = array();
        foreach ($this->filters as $id=>&$filter) {
            $input[$id] = $filter['validation'];
        } unset($filter);
        $data = array();
        switch ($method) {
            case 'postget': $data = $this->input->postgetm($input); break;
            case 'post': $data = $this->input->postm($input); break;
            case 'get': $data = $this->input->getm($input); break;
        }
        foreach ($this->filters as $id=>&$filter) {
            if (array_key_exists($id, $data)) {
                $filter['value'] = $data[$id];
            } else {
                $filter['value'] = $filter['default'];
            }
        } unset($filter);

        $this->fed = true;
    }

    /**
     * Настройки фильтра
     * @param string $method источник данных: 'get','post','postget'
     * @return array
     */
    public function values($method = 'postget')
    {
        if ( ! $this->fed) {
            $this->feed($method);
        }

        $values = array();
        foreach ($this->filters as $id=>&$filter) {
            $values[$id] = $filter['value'];
        } unset($filter);

        return $values;
    }

    /**
     * Значение фильтра по ключу
     * @param string $id ключ фильтра
     * @param mixed $default
     * @return mixed
     */
    public function value($id, $default = null)
    {
        if ( ! $this->fed) {
            $this->feed();
        }
        if (is_string($id) && array_key_exists($id, $this->filters)) {
            return $this->filters[$id]['value'];
        }
        return $default;
    }

    /**
     * Добавляем поле фильтра
     * @param integer $input тип поля
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля
     * @param string $default значение по умолчанию
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации TYPE_, [TYPE_NOTAGS, 'len'=>100]
     * @return $this
     */
    protected function add($input, $id, $title, $default = '', array $extra = [], $validation = TYPE_NOTAGS)
    {
        if ($this->tabs !== null && $this->tabs->getActiveKey() === $id) {
            return $this;
        }
        if ($id === 'page') {
            return $this;
        }

        $this->filters[$id] = array_merge(array(
            'id' => $id,
            'input' => $input,
            'title' => $title,
            'default' => $default,
            'value' => $default,
            'validation' => $validation,
            'attr' => [],
        ), $extra);

        return $this;
    }

    /**
     * Фильтр: собственная реализация поля
     * @param string $id уникальный ключ поля input[name]
     * @param callable $callback функция формирующая HTML
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function custom($id, callable $callback, $default = '', array $extra = [], $validation = TYPE_NOTAGS)
    {
        $extra['callback'] = $callback;
        return $this->add(static::INPUT_CUSTOM, $id, '', $default, $extra, $validation);
    }

    /**
     * Фильтр: скрытое поле input[type=hidden]
     * @param string $id уникальный ключ поля input[name]
     * @param string $default значение по умолчанию
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function hidden($id, $default = '', array $extra = [], $validation = TYPE_NOTAGS)
    {
        return $this->add(static::INPUT_HIDDEN, $id, '', $default, $extra, $validation);
    }

    /**
     * Фильтр: текстовое поле
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля и placeholder (если не указан в extra[attr][placeholder])
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function text($id, $title, array $extra = [], $validation = TYPE_NOTAGS)
    {
        if ($title !== '' && ! isset($extra['attr']['placeholder'])) {
            $extra['attr']['placeholder'] = $title;
        }
        if ( ! array_key_exists('size', $extra)) {
            $extra['size'] = 'medium';
        }
        return $this->add(static::INPUT_TEXT, $id, $title, '', $extra, $validation);
    }

    /**
     * Фильтр: поле ввода числа
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля
     * @param integer $default значение по умолчанию
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function number($id, $title, $default = 0, array $extra = [], $validation = TYPE_UINT)
    {
        $extra['attr']['type'] = 'number';
        if ($title !== '' && ! isset($extra['attr']['placeholder'])) {
            $extra['attr']['placeholder'] = $title;
        }
        if ( ! array_key_exists('size', $extra)) {
            $extra['size'] = 'small';
        }
        return $this->add(static::INPUT_TEXT, $id, $title, $default, $extra, $validation);
    }

    /**
     * Фильтр: выпадающий список
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля
     * @param array|callable $options варианты выбора
     * @param string $default значение по умолчанию
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function select($id, $title, $options, $default = '0', array $extra = [], $validation = TYPE_NOTAGS)
    {
        $extra['empty'] = $title;
        $extra['options'] = $options;
        if ( ! array_key_exists('size', $extra)) {
            $extra['size'] = 'medium';
        }
        return $this->add(static::INPUT_SELECT, $id, $title, $default, $extra, $validation);
    }

    /**
     * Фильтр: галочка
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля
     * @param boolean $default значение по умолчанию
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function checkbox($id, $title, $default = false, array $extra = [], $validation = TYPE_BOOL)
    {
        return $this->add(static::INPUT_CHECKBOX, $id, $title, $default, $extra, $validation);
    }

    /**
     * Фильтр: поле ввода даты
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function date($id, $title, array $extra = [], $validation = TYPE_NOTAGS)
    {
        if ($title !== '' && ! isset($extra['attr']['placeholder'])) {
            $extra['attr']['placeholder'] = $title;
        }
        return $this->add(static::INPUT_DATE, $id, $title, '', $extra, $validation);
    }

    /**
     * Фильтр: autocomplete
     * @param string $id уникальный ключ поля input[name]
     * @param string $title название поля и placeholder (если не указан в extra[attr][placeholder])
     * @param string $url URL источника доступных вариантов
     * @param array|callable $suggest варианты выбора по умолчанию
     * @param array $extra дополнительные параметры
     * @param integer|array $validation настройки валидации
     * @return $this
     */
    public function autocomplete($id, $title, $url, $suggest = [], array $extra = [], $validation = TYPE_NOTAGS)
    {
        if ($title !== '' && ! isset($extra['attr']['placeholder'])) {
            $extra['attr']['placeholder'] = $title;
        }
        $extra['url'] = $url;
        $extra['suggest'] = $suggest;
        return $this->add(static::INPUT_AUTOCOMPLETE, $id, $title, '', $extra, $validation);
    }

    public function __toString()
    {
        return $this->render();
    }

    /**
     * Действия выполняемые перед рендерингом блока
     */
    protected function beforeRender()
    {
        parent::beforeRender();

        $this->feed();
    }
}