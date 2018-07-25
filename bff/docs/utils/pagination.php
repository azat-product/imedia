<?php namespace bff\utils;

/**
 * Компонент выполняющий формирование "постраничной навигации"
 * @version 0.23
 * @created 18.dec.2013
 * @modified 8.apr.2015
 */
class Pagination extends \Component
{
    /** Макрос заменяемый в строке запроса на "page=pageID" */
    const PAGE_PARAM = '{page-param}';

    /** Макрос заменяемый в строке запроса на "pageID" */
    const PAGE_ID = '{page-id}';

    /** @var string ключ текущей страницы в GET/POST запросе */
    public $pageVar = 'page';

    /** @var int кол-во ссылок на "соседние страниц" */
    public $pageNeighbours = 2;

    /** @var bool выполнять валидацию номера текущей страницы (получаемой из запроса) */
    public $validateCurrentPage = true;

    /**
     * Конструктор
     * @param integer $nItemsTotal общее кол-во записей
     * @param integer $nPageSize кол-во записей на странице
     * @param array|string $mLinkHref URL для ссылки перехода с подставляемыми макросами self::PAGE_
     *    string - полная ссылка с макросом
     *    array - ссылка(link) + параметры(query)
     * @param string $sLinkOnClick javascript для ссылки перехода с подставляемыми макросами self::PAGE_
     */
    public function __construct($nItemsTotal, $nPageSize, $mLinkHref, $sLinkOnClick = '')
    {
    }

    /**
     * @return integer кол-во записей на странице
     */
    public function getPageSize()
    {
    }

    /**
     * @param integer $value кол-во записей на странице
     */
    public function setPageSize($value)
    {
    }

    /**
     * @return integer общее кол-во записей, по-умолчанию 0
     */
    public function getItemsTotal()
    {
    }

    /**
     * @param integer $value общее кол-во записей
     */
    public function setItemsTotal($value)
    {
    }

    /**
     * @return integer номер последней страницы (общее кол-во страниц)
     */
    public function getPageLast()
    {
    }

    /**
     * @param boolean $bRecalc выполнить пересчет текущей страницы
     * @return integer номер текущей страницы, по-умолчанию 1
     */
    public function getCurrentPage($bRecalc = false)
    {
    }

    /**
     * @param integer $value номер текущей страницы, 1+
     */
    public function setCurrentPage($value)
    {
    }

    /**
     * @return integer SQL OFFSET
     */
    public function getOffset()
    {
    }

    /**
     * @return integer SQL LIMIT
     */
    public function getLimit()
    {
    }

    /**
     * @return string SQL LIMIT + OFFSET
     */
    public function getLimitOffset()
    {
    }

    /**
     * Формирование шаблона постраничной навигации
     * @param array $aSettings доп. настройки элементов навигации
     * @param string $sTemplate шаблон
     * @param string|bool $mTemplatePath путь к шаблону, false - TPL_PATH
     * @return string
     */
    public function view(array $aSettings = array(), $sTemplate = 'pagination.standart', $mTemplatePath = false)
    {
    }

}