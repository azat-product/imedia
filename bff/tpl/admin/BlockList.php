<?php namespace bff\tpl\admin;

use \Pagination, \Module, \tpl;

/**
 * Компонент построения списка
 */
class BlockList extends Block
{
    /** @var BlockListFilters фильтр списка */
    protected $filters;

    /** @var boolean список является таблицей */
    protected $table = true;

    /**
     * Кастомная отрисовка списка
     * @var callable
     */
    protected $customRenderer;

    /** @var array столбцы списка */
    protected $columns = [];

    const COLUMN_ORDER_ASC  = 'asc';
    const COLUMN_ORDER_DESC = 'desc';

    const COLUMN_ALIGN_LEFT  = 'left';
    const COLUMN_ALIGN_RIGHT = 'right';

    const COLUMN_TYPE_ID     = 1;
    const COLUMN_TYPE_TEXT   = 2;
    const COLUMN_TYPE_DATE   = 3;
    const COLUMN_TYPE_CUSTOM = 4;

    /** @var array список записей */
    protected $rows = [];

    /** @var string ключ параметра передающего ID записи */
    protected $rowsIdKey = 'id';

    /** @var array доступные действия */
    protected $rowsActions = [
        self::ACTION_TOGGLE,
        self::ACTION_EDIT,
        self::ACTION_DELETE,
    ];

    const ACTION_TOGGLE  = 'toggle'; # включить/выключить
    const ACTION_EDIT    = 'edit'; # редактировать
    const ACTION_DELETE  = 'delete'; # удалить
    const ACTION_FAV     = 'fav'; # избранные

    /** @var callable Функция фильтрации списка записей */
    protected $rowsFilter;

    /** @var string ключ параметра передающего вложенное действие */
    protected $subActionKey = 'act';

    /** @var string ключ параметра передающего порядок сортировки */
    protected $orderKey = 'order';

    /** @var array текущий порядок сортировки */
    protected $orderActive;

    /** @var string шаблон значения параметра передающего порядок сортировки */
    protected $orderSeparator = '-';

    /** @var Pagination */
    protected $pagination;

    /** @var int Общее кол-во записей */
    protected $total = 0;

    /** @var int Кол-во записей на страницу */
    protected $perPage = 20;

    /** @var string Макрос ссылки на страницу */
    protected $linkHref = '';

    /** @var string Событие нажатия на ссылку перехода на страницу */
    protected $linkOnClick = '';

    /**
     * Конструктор
     * @param Module $controller объект контроллера
     * @param string $method названием метода контроллера работающего со списком
     */
    public function __construct(Module $controller, $action)
    {
        $this->setController($controller, $action);
    }

    public function init()
    {
        if ( ! parent::init()) {
            return false;
        }

        $this->setTemplateName('block.list');

        return true;
    }

    /**
     * Устанавливаем заголовок списка
     * @param string $title текст заголовка
     * @param string|bool $icon иконка
     */
    public function title($title, $icon = true)
    {
        \tplAdmin::adminPageSettings([
            'title' => $title,
            'icon' => $icon,
        ]);
    }

    /**
     * Добавляем столбец списка
     * @param string $id ID столбца
     * @param string $title название столбца
     * @param string|integer $width ширина столбца
     * @param integer $type тип столбца
     * @param array $extra [
     *      integer 'type' тип отображения: false или COLUMN_TYPE_
     *      string 'align' выравнивание: false или COLUMN_ALIGN_
     *      boolean|string 'order' сортировка по столбцу: false или COLUMN_ORDER_
     * ]
     * @param string|callable $render
     * @return $this
     */
    public function column($id, $title, $width = false, array $extra = [], $render = null)
    {
        static $defaults = array(
            # Тип данных
            'type' => ['default'=>self::COLUMN_TYPE_TEXT, 'allowed'=>[
                self::COLUMN_TYPE_ID,
                self::COLUMN_TYPE_TEXT,
                self::COLUMN_TYPE_DATE,
                self::COLUMN_TYPE_CUSTOM,
            ]],
            # Порядок сортировки
            'order' => ['default'=>false, 'allowed'=>[
                self::COLUMN_ORDER_ASC,
                self::COLUMN_ORDER_DESC,
            ]],
            # Выравнивание текста
            'align' => ['default'=>false, 'allowed'=>[
                self::COLUMN_ALIGN_LEFT,
                self::COLUMN_ALIGN_RIGHT,
            ]],
        );
        static $idTypes = array(
            'id'       => self::COLUMN_TYPE_ID,
            'created'  => self::COLUMN_TYPE_DATE,
            'modified' => self::COLUMN_TYPE_DATE,
            'title'    => self::COLUMN_TYPE_TEXT,
        );

        if ( ! is_string($id)) {
            return $this;
        }

        if (empty($extra['type']) && array_key_exists($id, $idTypes)) {
            $extra['type'] = $idTypes[$id];
        }

        foreach ($defaults as $k=>$v) {
            if ( ! array_key_exists($k, $extra)) {
                $extra[$k] = $v['default']; continue;
            }
            if ($extra[$k] instanceof \Closure) {
                $extra[$k] = call_user_func(\Closure::bind($extra[$k], $this), $v);
            }
            if ( ! in_array($extra[$k], $v['allowed'], true)) {
                $extra[$k] = $v['default'];
            }
        }

        $column = array_merge(array(
            'id' => $id,
            'title' => $title,
            'width' => $width,
            'render' => $render,
            'attr.head' => [],
            'attr.cell' => [],
        ), $extra);

        if (array_key_exists('showIf', $column) && $column['showIf'] instanceof \Closure) {
            $column['showIf'] = \Closure::bind($column['showIf'], $this);
        } else {
            $column['showIf'] = false;
        }

        if (empty($render) || ! is_callable($render, true)) {
            unset($column['render']);
        }

        $this->columns[$id] = $column;

        return $this;
    }

    /**
     * Ключ порядка сортировки
     * @param string|null $key устанавливаем новое значение ключа, null - возвращаем текущее
     * @return string
     */
    public function orderKey($key = null)
    {
        if ( ! is_null($key) && ! empty($key) && is_string($key)) {
            $this->orderKey = $key;
        }
        return $this->orderKey;
    }

    /**
     * Текущее значение порядка сортировки
     * @param string|boolean $default ID столбца по умолчанию
     * @param boolean $sqlFormat в SQL формате
     * @return string
     */
    public function order($default = false, $sqlFormat = true)
    {
        if ($this->orderActive === null) {
            $this->orderActive = ['column'=>''];
            $value = $this->input->postget($this->orderKey, TYPE_NOTAGS);
            if (empty($value) && $default !== false) {
                $value = $default;
            }

            if (mb_stripos($value, $this->orderSeparator) > 0) {
                list($column, $direction) = explode($this->orderSeparator, $value, 2);
                if (empty($direction) || !in_array($direction, [
                        static::COLUMN_ORDER_ASC,
                        static::COLUMN_ORDER_DESC
                    ]
                    )
                ) {
                    $direction = static::COLUMN_ORDER_ASC;
                }
            } else {
                $column = $value;
            }

            if (array_key_exists($column, $this->columns)) {
                if ( ! isset($direction)) {
                    $direction = $this->columns[$column]['order'];
                    if (empty($direction)) {
                        $direction = static::COLUMN_ORDER_ASC;
                    }
                }
                $this->orderActive['column'] = $column;
                $this->orderActive['direction'] = $direction;
                $this->orderActive['direction_next'] = ($direction === static::COLUMN_ORDER_ASC ?
                    static::COLUMN_ORDER_DESC :
                    static::COLUMN_ORDER_ASC
                );
                $this->columns[$column]['orderActive'] = $this->orderActive;
            }
        }
        if ($this->orderActive['column']) {
            if ($sqlFormat) {
                return $this->orderActive['column'] . ' ' . mb_strtoupper($this->orderActive['direction']);
            } else {
                return $this->orderActive['column'] . $this->orderSeparator . $this->orderActive['direction'];
            }
        }
        return '';
    }

    /**
     * Доступные действия со строками
     * @param array $actions
     * @param array $extra
     */
    public function rowsActions(array $actions = [], array $extra = [])
    {
        $this->rowsActions = $actions;
    }

    /**
     * Устанавливаем функцию фильтрации списка записей
     * Функция принимает данные о записи и возвращает false если запись не следует отображать
     * @param \Closure $filter callback($row) return boolean
     */
    public function rowsFilter(\Closure $filter)
    {
        $this->rowsFilter = $filter->bindTo($this);
    }

    /**
     * Добавляем записи в список
     * @param array $rows набор записей
     */
    public function rows(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Переопределяем ключ ID записи
     * @param string $key ключ
     */
    public function rowsIdKey($key)
    {
        if ( ! empty($key)) {
            $this->rowsIdKey = $key;
        }
    }

    /**
     * Рендеринг списка
     * @param array $options доп. параметры:
     *     boolean 'columns' - ренедрить заголовок таблицы
     *     integer 'rowId' - ID строки
     * @return string HTML
     */
    protected function rowsRender(array $options = [])
    {
        if ($this->table) {
            return $this->render(array_merge([
                    'columns'     => true,
                    'rowId'       => 0,
                    'noJsRender' => true,
                ], $options), 'block.list.table');
        } else {
            return $this->render(array_merge([
                'rowId'       => 0,
                'noJsRender' => true,
            ], $options), 'block.list.custom');
        }
    }

    /**
     * Рендеринг отдельной строки
     * @param integer $id ID строки
     * @return string HTML
     */
    protected function rowRender($id, array $options = array())
    {
        $options['rowId'] = $id;
        if ($this->table) {
            if ( ! array_key_exists('columns', $options)) {
                $options['columns'] = false;
            }
        }
        return $this->rowsRender($options);
    }

    /**
     * Отрисовка списка отличная от табличной
     * @param callable $renderer функция отрисовки принимающая данные о строке
     */
    public function custom(callable $renderer)
    {
        if (is_callable($renderer, true)) {
            $this->table = false;
            $this->customRenderer = $renderer;
        }
    }

    /**
     * Ключ текущего вложенного действия или false
     * @return string|boolean
     */
    public function subAction()
    {
        $subAction = $this->input->postget($this->subActionKey, TYPE_NOTAGS);
        if (empty($subAction)) {
            return false;
        }
        return $subAction;
    }

    /**
     * Блок фильтров списка
     * @return BlockListFilters
     */
    public function filters()
    {
        if ($this->filters === null) {
            $this->filters = new BlockListFilters($this);
            $this->filters->init();
        }

        return $this->filters;
    }

    /**
     * Значение фильтра
     * @param string $id ID фильтра
     * @param mixed $default значение по умолчанию
     * @return mixed
     */
    public function filter($id, $default = null)
    {
        return $this->filters()->value($id, $default);
    }

    /**
     * Блок табов фильтра
     * @return BlockTabs
     */
    public function tabs()
    {
        return $this->filters()->tabs();
    }

    /**
     * Текущий активный таб
     * @return mixed
     */
    public function tab()
    {
        return $this->filters()->tabs()->getActive();
    }

    /**
     * Блок постраничной навигации
     * @return Pagination
     */
    public function pagination()
    {
        if ($this->pagination === null) {
            $this->pagination = new Pagination($this->total, $this->perPage, $this->linkHref, $this->linkOnClick);
        }

        return $this->pagination;
    }

    /**
     * Рендеринг постраничной навигации
     * @param array $settings
     * @return string HTML
     */
    public function pages(array $settings = [])
    {
        return $this->pagination()->view($settings);
    }

    /**
     * Устанавливаем общее кол-во записей
     * @param integer $total кол-во
     */
    public function total($total)
    {
        $this->total = $total;
        $this->pagination()->setItemsTotal($total);
    }

    /**
     * Устанавливаем отображаемое кол-во записей на страницу
     * @param integer $perPage кол-во
     */
    public function perPage($perPage)
    {
        $this->perPage = $perPage;
        $this->pagination()->setPageSize($perPage);
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->pagination()->getLimit();
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->pagination()->getOffset();
    }

    /**
     * Возвращаем название CSS класса блока
     * @param string $plus
     * @return string
     */
    public function cssClass($plus = '')
    {
        return parent::cssClass(
            '-list'
            . ($plus !== '' ? '-' . $plus : '')
        );
    }

    /**
     * Действия выполняемые перед отрисовкой блока
     */
    protected function beforeRender()
    {
        parent::beforeRender();
    }
}