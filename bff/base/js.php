<?php namespace bff\base;

/**
 * Базовый класс вспомогательных методов работы с JavaScript кодом
 * @abstract
 * @version 0.1
 * @modified 3.dec.2013
 */

abstract class js
{
    # позиции расположения скриптов
    const POS_HEAD = 1; # в шапке сайта
    const POS_CURRENT = 2; # в текущей позиции
    const POS_FOOT = 3; # в подвале сайта

    /** @var string префикс определяющий, новый тег или привязка к существующему, компонент: autocomplete.fb */
    const AUTOCOMPLETE_FB_NEW_PREFIX = '__##';

    protected static $data = array();
    protected static $lastPos = self::POS_CURRENT;
    protected static $defaultPos = self::POS_CURRENT;

    /**
     * Назначаем позицию по-умолчанию
     * @param int $pos (self::POS_...)
     */
    public static function setDefaultPosition($pos)
    {
        self::$defaultPos = $pos;
    }

    /**
     * Начало javascript кода, вызывается после открывающего тега <script>
     * @param int|bool $pos позиция, в которой следует размещать код (self::POS_...) или FALSE (позиция по-умолчанию)
     */
    public static function start($pos = false)
    {
        if (empty($pos)) {
            $pos = static::$defaultPos;
        }
        if ((self::$lastPos = $pos) != self::POS_CURRENT) {
            ob_start();
            ob_implicit_flush(false);
        }
    }

    /**
     * Завершение javascript кода, вызывается перед закрывающим тегом </script>
     * @param bool $bUnshift переместить инициализацию данной кода в самое начало
     */
    public static function stop($bUnshift = false)
    {
        if (self::$lastPos && self::$lastPos != self::POS_CURRENT) {
            if ($bUnshift && !empty(self::$data[self::$lastPos])) {
                array_unshift(self::$data[self::$lastPos], ob_get_clean());
            } else {
                self::$data[self::$lastPos][] = ob_get_clean();
            }
        }
    }

    /**
     * Рендеринг javascript кода для указанной позиции
     * @param int|bool $pos позиция, (self::POS_...)
     * @return string HTML
     */
    public static function renderInline($pos)
    {
        $result = '';
        if (!empty(self::$data[$pos])) {
            $result .= '<script type="text/javascript">' . PHP_EOL . '//<![CDATA[' . PHP_EOL;
            $result .= join(PHP_EOL, self::$data[$pos]);
            $result .= PHP_EOL . "//]]></script>";
        }

        return $result;
    }

}