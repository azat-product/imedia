<?php namespace bff\db;

/**
 * Компонент для работы с динамическими свойствами
 * @version 1.807
 * @modified 28.jan.2015
 */

class Dynprops_ extends \Module
{
    # типы динамических свойств
    const typeInputText     = 1;  # Однострочное текстовое поле
    const typeTextarea      = 2;  # Многострочное текстовое поле
    const typeWysiwyg       = 3;  # Текстовый редактор
    const typeRadioYesNo    = 4;  # Выбор Да/Нет
    const typeCheckbox      = 5;  # Флаг
    const typeSelect        = 6;  # Выпадающий список
    const typeSelectMulti   = 7;  # Выпадающий список с мультивыбором (ctrl)
    const typeRadioGroup    = 8;  # Группа св-в с единичным выбором
    const typeCheckboxGroup = 9;  # Группа св-в с множественным выбором
    const typeNumber        = 10; # Число
    const typeRange         = 11; # Диапазон

    public $datafield_prefix     = 'f';
    public $datafield_int_first  = 1;
    public $datafield_int_last   = 10;
    public $datafield_text_first = 11;
    public $datafield_text_last  = 14;

    /**
     * Метод для обработки кеширования
     * @var mixed
     */
    protected $cache_method = false;

    /** @var bool Использовать настройку "Скрывать в поиске по-умолчанию" */
    public $searchHiddens = false;
    /** @var bool Использовать настройку "Указывать диапазоны поиска вручную" (typeNumber, typeRange) */
    public $searchRanges = false;

    /**
     * Мультиязычность
     * @var bool|array Формат: array('ru'=>array(),'ua'=>array())
     */
    public $langs = false;
    public $langText = array('yes'=>'Да','no'=>'Нет','all'=>'Все','select'=>'Выбрать');

    /**
     * Инициализация
     * @param string $ownerColumn название столбца владельца
     * @param string $tableOwners таблица владельцев
     * @param string $tableDynprops таблица параметров свойств
     * @param string $tableDynpropsMulti таблица значений свойств полей с множественным выбором
     * @param mixed $mInherit наследование: false|0 - без наследования, true|1 - полное, 2 - выборочное (частичное)
     * @param string $tableDynpropsIn таблица связывающая свойства с владельцем (при включенном частичном наследовании)
     */
    public function __construct($ownerColumn, $tableOwners, $tableDynprops, $tableDynpropsMulti, $mInherit = false, $tableDynpropsIn = false)
    {
    }

    /**
     * Инициализация дополнительных полей настроек дин. свойств
     * @param array|boolean $aSettings настройки в формате:
     *  array(
     *     'название доп. поля в таблице {tblDynprops}' => array(
     *          'title'=>'Название поля в форме',
     *          'input'=>'Тип поля в форме' - доступны: 'checkbox')
     *  )
     * @example: array('is_allowed'=>array('title'=>'Разрешен','input'=>'checkbox'))
     * или FALSE - возвращаем текущие настройки
     */
    public function extraSettings($aSettings = FALSE)
    {
    }

    /**
     * Формирование шаблона дин. свойств (форма добавления/редактирование, форма поиска, просмотр)
     * @param int|array $mOwnerID ID владельца(нескольких владельцев)
     * @param array|bool $aData данные
     * @param bool|int $mAddInherited включая параметры наследуемых свойств (bool - включать/не включать, 2 - включать с сохранением ключа реального владельца)
     * @param bool $bSearchOnly только свойства отмеченные "для поиска"
     * @param string $sPrefix префикс
     * @param string $sFormType тип формы: имя шаблона в модуле или тип отображения в админ панели: "{tpl}.{type}" => {tpl}: form,search,view, {type}: table,inline,div
     * @param string $sTemplateDir путь к шаблону (false - путь указанный компонентом)
     * @param array $aExtra дополнительные данные, которые необходимо передать в шаблон формы - доступны в шаблоне в переменной $extra
     * @param array $aDynpropsExcludeID id свойств, которые следует исключить из результата
     * @return array
     *   1 владелец: array('form'-html представление свойств, 'id'-id связанных свойств, 'i'-id наследуемых свойств)
     *   N владельцев: array(
     *       'form'=> array(
     *           'id владельца'=> html представление свойств,
     *           ...
     *        ),
     *       'id'    => array( 'id свойства', ...), - id всех возвращаемых свойств
     *       'links' => array(
     *           'id владельца'=>array(
     *               'id' => array( 'id свойства', ... ), // id связанных с владельцем свойств
     *               'i'  => array( 'id свойства', ... )  // id наследуемых владельцем свойств
     *            ), ...
     *        )
     *    )
     */
    public function form($mOwnerID, $aData = false, $mAddInherited = false, $bSearchOnly = false, $sPrefix = 'd', $sFormType = 'form.table', $sTemplateDir = false, $aExtra = array(), $aDynpropsExcludeID = array())
    {
    }

    /**
     * Формирование шаблона прикрепленного (child) свойства на основе параметров child-свойства
     * @param array $aDynpropChildren параметры child-свойств дин.свойства
     * @param array $aAttr доп. атрибуты: ['name'=>name-префикс для child-свойства или FALSE, 'id'=>ID атрибут, ...]
     * @param bool $bSearch для поиска
     * @param string|bool $mTemplateName название шаблона или FALSE
     * @param bool|string $sTemplateDir путь к шаблону (в случае если $sFormType - название шаблона)
     * @param array $aExtra дополнительные данные, которые необходимо передать в шаблон - доступны в шаблоне в переменной $extra / $aData['extra']
     * @return string
     */
    public function formChild($aDynpropChildren, array $aAttr = array(), $bSearch = false, $mTemplateName = 'form.child', $sTemplateDir = false, array $aExtra = array())
    {
    }

    /**
     * Формирование шаблона прикрепленного (child) свойства на основе пары parentID + parentValue
     * @param integer $nParentID ID parent-свойства
     * @param integer $nParentValue ID значения parent-свойства
     * @param array $aAttr доп. атрибуты: ['name'=>name-префикс для child-свойства или FALSE, 'id'=>ID атрибут, ...]
     * @param bool $bSearch для поиска
     * @param string|bool $mTemplateName название шаблона или FALSE
     * @param bool $sTemplateDir путь к шаблону (в случае если $sFormType - название шаблона)
     * @param array $aExtra дополнительные данные, которые необходимо передать в шаблон - доступны в шаблоне в переменной $extra / $aData['extra']
     * @return string
     */
    public function formChildByParentIDValue($nParentID, $nParentValue, array $aAttr = array(), $bSearch = false, $mTemplateName = 'form.child', $sTemplateDir = false, array $aExtra = array())
    {
    }

    /**
     * Формируем столбцы <ul class="left"><li></li></ul><ul class="left">...</ul><div class="clear" />
     * @core-doc
     * @param array $aData данные
     * @param array $aValues значения
     * @param callback $funcLI функция формирующая <li> на основе данных
     * @param array $aAttrUL атрибуты тега UL
     * @param integer $nFirstColMax максимальное кол-во элементов в первой колонке
     * @param integer $nColsMax максимальное кол-во колонок
     * @return string HTML
     */
    public function formCols($aData, $aValues, $funcLI, array $aAttrUL = array(), $nFirstColMax = 6, $nColsMax = 2)
    {
    }

    /**
     * Получаем параметры свойств по ID одного владельца
     * @param int $nOwnerID ID владельца
     * @param bool|int $mAddInherited включая параметры наследуемых свойств (2 - )
     * @param bool $bMulti получать значения свойств с множественным выбором
     * @param bool $bSearchOnly только свойства отмеченные "для поиска"
     * @return array параметры свойств
     */
    public function getByOwner($nOwnerID, $mAddInherited = false, $bMulti = true, $bSearchOnly = false)
    {
    }

    /**
     * Получаем параметры свойств нескольких владельцев
     * @param int|array $aOwnerID id владельца(-ев)
     * @param bool|int $mAddInherited включая параметры наследуемых свойств
     * @param bool $bMulti получать значения свойств с множественным выбором
     * @param bool $bSearchOnly только свойства отмеченные "для поиска"
     * @return array параметры свойств
     */
    public function getByOwners($aOwnerID, $mAddInherited = false, $bMulti = true, $bSearchOnly = false)
    {
    }
    
    /**
     * Получаем параметры свойств по ID
     * @param mixed $aDynpropsID id свойств(а)
     * @param bool $bMulti получать значения свойств с множественным выбором
     * @param bool $bSearchOnly только свойства помеченные "для поиска"
     * @param bool $bOne формируем параметры только для одного свойства
     * @return array параметры свойств
     */
    public function getByID($aDynpropsID, $bMulti = true, $bSearchOnly = false, $bOne = false)
    {
    }
    
    /**
     * Подготовка запроса сохранения значений свойств по ID владельца(-ев)
     * @param int|array $mOwnerID id владельца (нескольких владельцев)
     * @param array $aDynpropsData значения свойств: array(id владельца=>значения, ...)
     * @param array $aDynprops параметры свойств: без группировки по id владельца
     * @param string $sQueryType тип запроса: 'insert', 'update'
     * @param bool $bBindResult возвращать результат в виде array(key=>value, ...)
     * @param string $sKey ключ
     * @return array
     */
    public function prepareSaveDataByOwner($mOwnerID, $aDynpropsData, $aDynprops, $sQueryType = 'insert', $bBindResult = false, $sKey = 'id')
    {
    }

    /**
     * Подготовка запроса сохранения значений свойств по их ID
     * @param array $aDynpropsData значения свойств: array(id свойства=>значение, ...)
     * @param array $aDynprops параметры свойств
     * @param string $sQueryType тип запроса: 'insert', 'update'
     * @param bool $bBindResult возвращать результат в виде array(key=>value, ...)
     * @param string $sKey ключ
     * @return array
     */
    public function prepareSaveDataByID($aDynpropsData, $aDynprops, $sQueryType = 'insert', $bBindResult = false, $sKey = 'id')
    {
    }
    
    /**
     * Подготовка запроса поиска сущностей по значениям свойств
     * Доступный формат значений:
     * 1) f[df] = v(select,check,radio) или f[df][] = v(checkboxes list)
     * 2) fc[df-child] = v(select,check,radio) или fc[df-child][id-child][] = v(checkboxes list)
     * @core-doc
     * @param array $aData значения свойств: array(id | data_field свойства=>значение, ...)
     * @param array|bool $aDataChildren значения child-свойств
     * @param array $aDynprops параметры дин.свойств
     * @param string $sTablePrefix префикс таблицы записей, например: 'I.'
     * @param string $sKey тип ключа: 'id', 'data_field'
     * @return string
     */
    public function prepareSearchQuery($aData, $aDataChildren = false, $aDynprops, $sTablePrefix, $sKey = 'data_field')
    {
    }
    
    /**
     * Подготовка шаблона на основе значений свойств
     * @param array $aDynpropsData значения свойств: array(id свойства=>значение, ...)
     * @param array $aDynprops параметры свойств
     * @param array $aTemplates шаблоны @example: array('key'=>'Текст {floor}, {email} текст', 'key'=>'Текст2 {place}', ...)
     * @param array $aExtraReplace доп. замена в шаблонах
     * @param string $sKey ключ
     * @return array
     */
    public function prepareTemplateByCacheKeys($aDynpropsData, $aDynprops, $aTemplates, $aExtraReplace = array(), $sKey = 'id')
    {
    }
    
    /**
     * Возвращает название свойства
     * @param int $nType тип свойства (self::type...)
     * @return string
     */
    static public function getTypeTitle($nType)
    {
    }

    /**
     * Включено ли частичное наследование
     * @return bool
     */
    public function isInheritParticular()
    {
    }

    /**
     * Установка текущей локали
     * @param string $key
     */
    public function setCurrentLanguage($key)
    {
    }
}