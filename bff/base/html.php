<?php namespace bff\base;

/**
 * Класс вспомогательных HTML методов
 * @version 0.23
 * @modified 23.jan.2018
 */

abstract class HTML
{
    /**
     * Преобразует символы в соответствующие HTML-сущности
     * @param string $value
     * @return string
     */
    public static function entities($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Преобразуем все HTML-сущности в соответствующие символы
     * @param string $value
     * @return string
     */
    public static function decode($value)
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Конвертируем специальные HTML символы
     * @param string $value
     * @return string
     */
    public static function specialchars($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Эскейпим строку
     * @param string|array $value данные
     * @param string $type тип: 'html', 'js'
     * @param array $keysOnly список ключей, в случае если $value массив
     * @return array|string
     */
    public static function escape($value, $type = 'html', array $keysOnly = array())
    {
        if (is_bool($type)) {
            if ($type === true) {
                $type = 'html';
            } else {
                return $value;
            }
        }
        if (is_string($value)) {
            return static::escapeValue($value, $type);
        } else if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (is_array($val) || (!empty($keysOnly) && !in_array($key, $keysOnly))) {
                    continue;
                }
                if (is_string($val)) {
                    $value[$key] = static::escapeValue($val, $type);
                }
            }
            return $value;
        }
        return $value;
    }

    /**
     * Эскейпим строку
     * @param string $value данные
     * @param string $type тип: 'html', 'js' ('javascript')
     * @return string
     */
    protected static function escapeValue($value, $type = 'html')
    {
        switch ($type) {
            case 'html':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            case 'js':
            case 'javascript':
                # escape quotes and backslashes, newlines, etc.
                return strtr($value, array(
                        '\\' => '\\\\',
                        "'"  => "\\'",
                        '"'  => '\\"',
                        "\r" => '\\r',
                        "\n" => '\\n',
                        '</' => '<\/'
                    )
                );
        }

        return $value;
    }

    /**
     * Формирование <option> тегов для <select>
     * @param array $aData данные
     * @param integer $nSelectedID ID выбранного элемента
     * @param mixed $mEmpty : false - не добавлять вариант "не выбран"; string - название; array(id,title) - id + название
     * @param string $idKey - ключ ID пунктов
     * @param string $titleKey - ключ названия пунктов
     * @param array $aDataAttributes дополнительные "data-" атрибуты
     * @return string
     */
    public static function selectOptions(array $aData, $nSelectedID = 0, $mEmpty = false, $idKey = false, $titleKey = false, array $aDataAttributes = array())
    {
        $html = '';
        if (!empty($mEmpty)) {
            if (is_string($mEmpty)) {
                $html .= '<option' . (0 == $nSelectedID ? ' selected="selected"' : '') . ' value="0">' . $mEmpty . '</option>';
            } elseif (is_array($mEmpty) && count($mEmpty) == 2) {
                $html .= '<option' . ($mEmpty[0] == $nSelectedID ? ' selected="selected"' : '') . ' value="' . $mEmpty[0] . '">' . $mEmpty[1] . '</option>';
            }
        }

        if (!empty($aData)) {
            if (empty($titleKey) || empty($idKey)) {
                foreach ($aData as $k => $v) {
                    if (is_array($v)) continue;
                    $attr = array('value' => $k);
                    if ($k == $nSelectedID) {
                        $attr[] = 'selected';
                    }
                    $html .= '<option' . static::attributes($attr) . '>' . $v . '</option>';
                }
            } else {
                foreach ($aData as $v) {
                    $attr = array('value' => $v[$idKey]);
                    if ($v[$idKey] == $nSelectedID) {
                        $attr[] = 'selected';
                    }
                    if (!empty($aDataAttributes)) {
                        foreach ($aDataAttributes as $v2) {
                            if (isset($v[$v2])) $attr['data-' . $v2] = $v[$v2];
                        }
                    }
                    $html .= '<option' . static::attributes($attr) . '>' . $v[$titleKey] . '</option>';
                }
            }
        }

        return $html;
    }

    /**
     * Формирование списков на основе данных (<ul><li>...</ul>)
     * @param array $aData данные
     * @param array $aValues значения
     * @param callback $cChildren функция, формирующая элементы списка на основе данных; входящие параметры: $k, $v, $values(значения)
     * @param int|array $mCols int - требуемое кол-во колонок, array - подсчет колонок на основе данных array(кол-во колонок=> мин. кол-во данных, ...)
     * @param array $aParentAttr атрибуты тега контейнера
     * @param string $sParentTag тег контейнера, по-умолчанию 'ul'
     * @return string HTML
     */
    public static function renderList($aData, $aValues, $cChildren, $mCols = 3, array $aParentAttr = array('style' => 'float:left;'), $sParentTag = 'ul')
    {
        if (empty($aData) || !is_array($aData) || !is_callable($cChildren) || empty($sParentTag)) return '';

        $aParentAttr = static::attributes($aParentAttr);
        $total = sizeof($aData);
        $cols = 1;
        if (is_int($mCols)) {
            $cols = $mCols;
        } else if (is_array($mCols)) {
            foreach ($mCols as $colsTo => $min) {
                if ($total >= $min) $cols = $colsTo; else break;
            }
        }
        $items_in_col = ceil($total / $cols);
        $break_column = $items_in_col;
        $i = 0;
        $col_i = 1;
        $sHTML = '<' . $sParentTag . $aParentAttr . '>';
        reset($aData);
        while (list($k, $v) = each($aData)) {
            if ($i == $break_column) {
                $col_i++;
                $sHTML .= '</' . $sParentTag . '><' . $sParentTag . $aParentAttr . '>';
                if ($col_i < $cols) $break_column += $items_in_col;
            }
            $sHTML .= $cChildren($k, $v, $aValues);
            $i++;
        }
        $sHTML .= '</' . $sParentTag . '><div class="clearfix" style="clear:both;"></div>';

        return $sHTML;
    }

    /**
     * Формируем HTML ссылку mailto + обфусцируем е-mail адрес для защиты от спам-ботов
     * @param string $email
     * @param string $title
     * @param array $attributes
     * @return string HTML
     */
    public static function mailto($email, $title = null, array $attributes = array())
    {
        $email = static::email($email);
        if (is_null($title)) $title = $email;
        $email = '&#109;&#097;&#105;&#108;&#116;&#111;&#058;' . $email;

        return '<a href="' . $email . '"' . static::attributes($attributes) . '>' . static::entities($title) . '</a>';
    }

    /**
     * Обфусцируем е-mail адрес для защиты от спам-ботов
     * @param string $email
     * @return string
     */
    public static function email($email)
    {
        return str_replace('@', '&#64;', static::obfuscate($email));
    }

    /**
     * Формируем HTML атрибуты
     * @param array $attributes
     * @return string
     */
    public static function attributes($attributes, array $defaults = array(), array $except = array())
    {
        $html = array();
        if (empty($attributes) || !is_array($attributes)) {
            return '';
        }

        # наполняем значениями по-умолчанию
        if (!empty($defaults)) {
            foreach ($defaults as $key=>$v) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $v;
                }
            }
        }

        foreach ($attributes as $key => $value) {
            # пропускаем по ключу
            if (!empty($except) && in_array($key, $except, true)) {
                continue;
            }
            if (is_array($value)) {
                $value = join(' ', $value);
            }
            # в случае если ключ числовой, подразумеваем что нужно использовать
            # только значение, например для таких атрибутов как disabled="disabled",...
            if (is_numeric($key)) $key = $value;

            if (!is_null($value)) {
                $html[] = $key . '="' . static::entities($value) . '"';
            }
        }

        return (count($html) > 0) ? ' ' . join(' ', $html) : '';
    }

    /**
     * Дополняем HTML атрибуты
     * @param array $attributes @ref атрибуты
     * @param string $key ключ атрибута
     * @param mixed $value значение
     */
    public static function attributeAdd(array & $attributes, $key, $value)
    {
        if (isset($attributes[$key])) {
            if ( ! is_array($attributes[$key])) {
                $attributes[$key] = array($attributes[$key]);
            }
            $attributes[$key][] = $value;
        } else {
            $attributes[$key] = $value;
        }
    }

    /**
     * Обфусцируем строку для защиты от спам-ботов
     * @param string $value
     * @return string
     */
    public static function obfuscate($value)
    {
        $safe = '';
        foreach (str_split($value) as $letter) {
            # каждый символ кодируем в hex или entity
            switch (rand(1, 3)) {
                case 1:
                    $safe .= '&#' . ord($letter) . ';';
                    break;
                case 2:
                    $safe .= '&#x' . dechex(ord($letter)) . ';';
                    break;
                case 3:
                    $safe .= $letter;
                    break;
            }
        }

        return $safe;
    }

}