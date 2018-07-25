<?php namespace bff\utils;

/**
 * Класс обработки ссылок
 * @version 0.451
 * @modified 2.feb.2015
 */

class LinksParser_
{
    // варианты урезания текста ссылок
    const TRUNCATE_NONE   = 0; // text (без урезание)
    const TRUNCATE_END    = 1; // text... (в конце)
    const TRUNCATE_CENTER = 2; // te...xt (в середине)

    /**
     * Установка класса, которым следует помечать внешние ссылки
     * @param string $className класс, которым помечается ссылка
     */
    public function setExternalLinksClass($className = '')
    {
    }

    /**
     * Установка javascript обработчика для ссылок
     * Например: return bfflink(this);
     * Передача this в обработчик необходима для реализации дальнейшего перехода по ссылке
     * @param string $handler обработчик
     */
    public function setJavascriptHandler($handler = '')
    {
    }

    /**
     * Установка локальных доменов
     * @param array $localDomains
     */
    public function setLocalDomains(array $localDomains = array())
    {
    }

    /**
     * Установка типа урезания названия ссылки
     * @param integer $truncateType тип (self::TRUNCATE_)
     * @param integer $truncateLength длина название ссылки, после которой необходимо урезать
     */
    public function setTruncateType($truncateType, $truncateLength = 50)
    {
    }

    /**
     * Парсинг текста
     * @param string $text текст
     * @return string
     */
    public function parse($text)
    {
    }

}