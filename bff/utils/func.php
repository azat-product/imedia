<?php namespace bff\utils;

/**
 * Вспомогательные функции
 * @version 0.54
 * @modified 25.mar.2018
 */
abstract class func
{
    /**
     * Перестраивание массива по ключу
     * @param array $aData массив
     * @param string $sByKey ключ
     * @param boolean $bOneInRows
     * @param boolean|string $multKey ключ для многострочной сортировки
     * @return array
     */
    public static function array_transparent($aData, $sByKey, $bOneInRows = false, $multKey = false)
    {
        if (empty($aData) || !is_array($aData)) {
            return array();
        }

        $aDataResult = array();
        $cnt = count($aData);
        for ($i = 0; $i < $cnt; $i++) {
            if ($bOneInRows) {
                $aDataResult[$aData[$i][$sByKey]] = $aData[$i];
            } else {
                if ( ! empty($multKey)) {
                    $aDataResult[ $aData[$i][$sByKey] ][ $aData[$i][$multKey] ] = $aData[$i];
                } else {
                    $aDataResult[$aData[$i][$sByKey]][] = $aData[$i];
                }
            }
        }

        return $aDataResult;
    }

    /**
     * Наполняем массив значениями по умолчанию
     * @param array $array @ref исходный массив
     * @param array $defaults массив значений по умолчанию
     * @return array
     */
    public static function array_defaults(array &$array, array $defaults = array())
    {
        return ($array = array_merge($defaults, $array));
    }

    /**
     * Multi-dimentions array sort, with ability to sort by two and more dimensions
     * $array = array_subsort($array [, 'col1' [, SORT_FLAG [, SORT_FLAG]]]...);
     * @return mixed
     */
    public static function array_subsort()
    {
        $args = func_get_args();
        $marray = array_shift($args);

        $i = 0;
        $msortline = "return(array_multisort(";
        foreach ($args as $arg) {
            $i++;
            if (is_string($arg)) {
                foreach ($marray as $row) {
                    $sortarr[$i][] = $row[$arg];
                }
            } else {
                $sortarr[$i] = $arg;
            }
            $msortline .= "\$sortarr[" . $i . "],";
        }
        $msortline .= "\$marray));";

        eval($msortline);

        return $marray;
    }

    /**
     * Сортировка массива по приоритету
     * @param array $data @ref массив данных
     * @param string $key
     * @param boolean|integer $preserveKeys сохранять исходные индексы массива
     *   2 -> корректировать ключи для хранения в битовом поле
     */
    public static function sortByPriority(&$data, $key = 'priority', $preserveKeys = false)
    {
        if (!is_array($data)) {
            return;
        }
        if ($preserveKeys === 2) {
            if (sizeof($data) > 32) {
                $data = array_slice($data, 0, 32, true);
            }
        }
        $order = array(); $i = 1;
        foreach ($data as $k=>&$v) {
            $order[$k] = (!empty($v[$key]) ? $v[$key] : $i++);
        } unset($v);
        if ($preserveKeys) {
            if ($preserveKeys === 2) {
                $data2 = array(); $last = 0;
                foreach ($data as $k=>&$v) {
                    if (($k > 1 && ($k%2)) || substr_count(decbin($k), '1') > 1) {
                        for ($i = $last; $i <= 32; $i++) {
                            $j = pow(2, $i);
                            if (!isset($data[$j]) && !isset($data2[$j])) {
                                $k = $j; $last = $i; break;
                            }
                        }
                    }
                    $data2[$k] = $v;
                } unset($v);
                $data = $data2;
            }
            $keys = array_keys($data);
            array_multisort($order, SORT_ASC, SORT_NUMERIC, $data, $keys);
            $data = array_combine($keys, $data);
        } else {
            array_multisort($order, SORT_ASC, $data);
        }
    }

    /**
     * Получаем значение из массива SESSION
     * @param string $sKey ключ
     * @param mixed $mDefault значение по-умолчанию
     * @return mixed
     */
    public static function SESSION($sKey, $mDefault = false)
    {
        return (isset($_SESSION['SESSION'][$sKey]) ? $_SESSION['SESSION'][$sKey] : $mDefault);
    }

    /**
     * Сохраняем значение в массив SESSION
     * @param string $sKey ключ
     * @param mixed $mValue значение
     */
    public static function setSESSION($sKey, $mValue)
    {
        $_SESSION['SESSION'][$sKey] = $mValue;
    }

    /**
     * Парсинг даты/времени
     * @param string $sDatetime дата/время
     * @return array
     */
    public static function parse_datetime($sDatetime = '2006-04-05 01:50:00')
    {
        $arr = explode(' ', $sDatetime, 2);
        $arr_res = array('year' => '', 'month' => '', 'day' => '', 'hour' => '', 'min' => '', 'sec' => '');
        if (isset($arr[0])) {
            $arr_date = explode('-', $arr[0], 3);
            if (count($arr_date) == 3) {
                $arr_res['year'] = $arr_date[0];
                $arr_res['month'] = $arr_date[1];
                $arr_res['day'] = $arr_date[2];
            }
        }
        if (isset($arr[1])) {
            $arr_time = explode(':', $arr[1], 3);
            if (count($arr_time) == 3) {
                $arr_res['hour'] = $arr_time[0];
                $arr_res['min'] = $arr_time[1];
                $arr_res['sec'] = $arr_time[2];
            }
        }

        return $arr_res;
    }

    /**
     * Генератор случайной последовательности символов
     * @param integer $nLength кол-во символов (1-32)
     * @param boolean $bNumbersOnly только числа
     * @return string
     */
    public static function generator($nLength = 10, $bNumbersOnly = false)
    {
        if ($nLength > 32) {
            $nLength = 32;
        }
        if ($bNumbersOnly) {
            return mt_rand(($nLength > 1 ? pow(10, $nLength - 1) : 1), ($nLength > 1 ? pow(10, $nLength) - 1 : 9));
        }

        return substr(md5(uniqid(mt_rand(), true)), 0, $nLength);
    }

    /**
     * Транслитерация cyr->lat
     * @param string $text текст для транслитерации
     * @param boolean $isURL адаптировать для URL
     * @param string $encIn кодировка входящий строки
     * @param string $encOut кодировка выходящий строки
     * @return string
     */
    public static function translit($text, $isURL = true, $encIn = false, $encOut = false)
    {
        if (empty($encIn)) {
            $encIn = 'utf-8';
        }
        if (empty($encOut)) {
            $encOut = 'utf-8';
        }

        $text = iconv($encIn, 'utf-8', $text);

        $convert = \bff::filter('utils.func.translit.convert', array(
            # Russian (RU), Ukrainian (UK)
            'Щ'=>'Shh','Ш'=>'Sh','Ч'=>'Ch','Ц'=>'C','Ю'=>'Ju','Я'=>'Ja','Ж'=>'Zh','А'=>'A','Б'=>'B','В'=>'V',
            'Г'=>'G','Д'=>'D','Е'=>'Je','Ё'=>'Jo','З'=>'Z','И'=>'I','І'=>'I','Й'=>'J','К'=>'K','Л'=>'L',
            'М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'Kh',
            'Ь'=>'\'','Ы'=>'Y','Ъ'=>'`','Э'=>'E','Є'=>'Je','Ї'=>'Ji','щ'=>'shh','ш'=>'sh','ч'=>'ch','ц'=>'c',
            'ю'=>'ju','я'=>'ja','ж'=>'zh','а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'je','ё'=>'jo',
            'з'=>'z','и'=>'i','і'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p',
            'р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ь'=>'\'','ы'=>'y','ъ'=>'`','э'=>'e',
            'є'=>'je','ї'=>'ji',
            # Georgian (KA)
            'ა' => 'a','ბ' => 'b','გ' => 'g','დ' => 'd','ე' => 'e','ვ' => 'v','ზ' => 'z','თ' => 't',
            'ი' => 'i','კ' => 'k','ლ' => 'l','მ' => 'm','ნ' => 'n','ო' => 'o','პ' => 'p\'','ჟ' => 'zh',
            'რ' => 'r','ს' => 's','ტ' => 't\'','უ' => 'u','ფ' => 'p','ქ' => 'q','ღ' => 'gh','ყ' => 'y\'',
            'შ' => 'sh','ჩ' => 'ch','ც' => 'c','ძ' => 'dz','წ' => 'w\'','ჭ' => 'ch\'','ხ' => 'x','ჯ' => 'j',
            'ჰ' => 'h',
        ));

        $textBefore = $text;
        $text = str_replace(
            array_keys($convert),
            array_values($convert),
            $text);
        if ($text !== $textBefore) {
            $text = preg_replace("/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]e/", "\${1}e", $text);
            $text = preg_replace("/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]/", "\${1}'", $text);
            $text = preg_replace("/([eyuioaEYUIOA]+)[Kk]h/", "\${1}h", $text);
            $text = preg_replace("/^kh/", "h", $text);
            $text = preg_replace("/^Kh/", "H", $text);
        }
        if ($isURL) {
            $text = preg_replace('/[\?&\']+/', '', $text);
            $text = preg_replace('/[\s,\?&]+/', '-', $text);
            $text = preg_replace('/[\/\'\"\(\)\=\\\]+/', '', $text);
            $text = preg_replace('/[^a-zA-Z0-9_\-]/', '', $text);
            $text = preg_replace("/\-+/", "-", $text); //сжимаем двойные "-"
        }

        return \bff::filter('utils.func.translit', iconv('utf-8', $encOut, $text));
    }

    /**
     * Формирование JSON
     * @param mixed $a данные
     * @param boolean $noNumQuotes не обворачивать число в кавычки
     * @param boolean $nativeJSON использовать функцию json_encode
     * @return string
     */
    public static function php2js($a = false, $noNumQuotes = false, $nativeJSON = true)
    {
        if ($nativeJSON) {
            $options = JSON_UNESCAPED_UNICODE;
            if ($noNumQuotes) {
                $options += JSON_NUMERIC_CHECK;
            }
            return json_encode($a, $options);
        }

        if (is_null($a)) {
            return 'null';
        }
        if ($a === false) {
            return 'false';
        }
        if ($a === true) {
            return 'true';
        }
        if (is_scalar($a)) {
            if (is_float($a)) {
                // Always use "." for floats.
                $a = str_replace(",", ".", strval($a));
            }

            // All scalars are converted to strings to avoid indeterminism.
            // PHP's "1" and 1 are equal for all PHP operators, but
            // JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
            // we should get the same result in the JS frontend (string).
            // Character replacements for JSON.
            static $jsonReplaces = array(
                array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"', "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x1a", "\x1b", "\x1c", "\x1d", "\x1e", "\x1f"),
                array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"', '\u0010', '\u0011', '\u0012', '\u0013', '\u0014', '\u0015', '\u0016', '\u0017', '\u0018', '\u001a', '\u001b', '\u001c', '\u001d', '\u001e', '\u001f')
            );
            if ($noNumQuotes && is_int($a)) {
                return $a;
            } else {
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($a as $v) {
                $result[] = static::php2js($v, $noNumQuotes, false);
            }

            return '[' . join(',', $result) . ']';
        } else {
            foreach ($a as $k => $v) {
                $result[] = static::php2js($k, $noNumQuotes, false) . ': ' . static::php2js($v, $noNumQuotes, false);
            }

            return '{' . join(',', $result) . '}';
        }
    }

    /**
     * Вставка необходимой строки в текст с учетом позиции найденной строки
     * @param string $text текст
     * @param string $search строка поиска
     * @param string $insert строка для вставки
     * @param bool $after после найденной строки (true), перед (false)
     * @return string
     */
    public static function stringInsert($text, $search, $insert, $after = true)
    {
        $index = mb_strpos($text, $search);
        if ($index === false) {
            return $text;
        }
        if ($after) {
            return substr_replace($text, $search . $insert, $index, mb_strlen($search));
        } else {
            return mb_substr($text, 0, $index) . $insert . mb_substr($text, $index);
        }
    }

    /**
     * Безопасный unserialize массива
     * @param mixed $data данные в сериализованном виде
     * @param mixed $default значение по-умолчанию
     * @param array $options
     * @return mixed
     */
    public static function unserialize($data, $default = array(), array $options = array())
    {
        if (is_array($default)) {
            if (empty($data)) {
                return $default;
            }
            if (is_array($data)) {
                return $data;
            }
            $data = strval($data);
            if (mb_strpos($data, 'a:') !== 0) {
                if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) {
                    $data = base64_decode($data, true);
                    if (empty($data) || !static::is_serialized($data)) {
                        return $default;
                    }
                } else {
                    return $default;
                }
            }
            $data = unserialize($data);
            if ($data === false) {
                $e = new \Exception();
                \bff::log('func::unserialize failed:');
                \bff::log($e->getTraceAsString());
            }
            return ( ! empty($data) ? $data : $default );
        }
        return ( ! empty($data) ? unserialize(strval($data)) : $default );
    }

    /**
     * Проверка является ли строка сериализованной строкой
     * @param mixed $data
     * @return bool
     */
    public static function is_serialized($data)
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if ($data[1] !== ':' || mb_strlen($data) < 4) {
            return false;
        }
        if (!preg_match('/^([adObis]):/', $data, $matches)) {
            return false;
        }
        switch ($matches[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$matches[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$matches[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}