<?php namespace bff\extend;

/**
 * Плагинизация: теги
 * @version 0.2
 * @modified 27.aug.2018
 * @copyright Tamaranga
 */

class Tags
{
    # Тег
    const REGEXP_TAG = '/\<\!\-\-(?:\s|\R)*\[(?P<id>{id})(?:\s*(?P<attr>\{((?!<\!\-\-|\-\->).)+?\}))?\](?:\s|\R)*\-\-\>/Uu';

    # Атрибуты тега
    const REGEXP_ATTR_JSON = '/\{(?:[^{}]|(?R))*\}/u';

    protected $data = array();

    protected $minLength = 3;

    /**
     * Добавляем тег
     * @param string $id
     * @param callable $callable
     * @return bool
     */
    public function add($id, callable $callable)
    {
        if (!is_string($id)) {
            return false;
        }
        $id = trim($id);
        if (mb_strlen($id) < $this->minLength) {
            return false;
        }
        if ( ! array_key_exists($id, $this->data)) {
            $this->data[$id] = $callable;
            return true;
        }
        return false;
    }

    /**
     * Проверяем добавлен ли тег
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->data);
    }

    /**
     * Удаляем тег
     * @param string $id
     * @return bool
     */
    public function remove($id)
    {
        if (is_string($id) && array_key_exists($id, $this->data)) {
            unset($this->data[$id]);
            return true;
        }
        return false;
    }

    /**
     * Удаляем все теги
     */
    public function clear()
    {
        $this->data = array();
    }

    /**
     * Обработка тегов в тексте
     * @param string $text
     * @param array|boolean $ids true - добавленные, false - любые найденные, array - список тегов
     * @param boolean $replace заменять теги
     * @return string|array
     */
    public function process($text, $ids = true, $replace = true)
    {
        $pattern = static::REGEXP_TAG;
        $any = '[\\w\\-]{'.$this->minLength.',}';
        if (is_array($ids) && empty($ids)) {
            $ids = false;
        }
        if ($ids === false) {
            $ids = array();
            $pattern = str_replace('{id}', $any, $pattern);
        } else if ($ids === true) {
            if ( ! sizeof($this->data)) {
                return ($replace ? $text : array());
            }
            $ids = &$this->data;
            $pattern = str_replace('{id}', join('|', array_map('preg_quote', array_keys($ids))), $pattern);
        } else if (is_array($ids)) {
            $ids_quoted = array();
            foreach ($ids as $k=>$v) {
                $key = '';
                if (is_int($k) && is_string($v)) {
                    $key = trim($v);
                } else if (is_string($k) && is_callable($v, true)) {
                    $key = trim($k);
                }
                if (mb_strlen($key) >= $this->minLength) {
                    $ids_quoted[] = preg_quote($key);
                }
            }
            if (empty($ids_quoted)) {
                return ($replace ? $text : array());
            }
            $pattern = str_replace('{id}', join('|', $ids_quoted), $pattern);
        }
        if ($replace) {
            $indexes = array();
            return preg_replace_callback($pattern, function($m) use ($ids, &$indexes) {
                $id = $m['id'];
                if (array_key_exists($id, $ids)) {
                    $indexes[$id][] = 1;
                    return call_user_func($ids[$id], (isset($m['attr']) ?
                        $this->attributes($m['attr']) : array()), sizeof($indexes[$id]), $m);
                }
                return $m[0];
            }, $text);
        } else {
            $tags = array();
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
            foreach ($matches as $i => $value) {
                $tags[$i]['match'] = $value[0];
                $tags[$i]['id'] = $value['id'];
                $tags[$i]['attr'] = (isset($value['attr']) ?
                    $this->attributes($value['attr']) : array());
            }
            return $tags;
        }
    }

    /**
     * Атрибуты тега
     * @param string $text
     * @return array
     */
    protected function attributes($text)
    {
        if (preg_match(static::REGEXP_ATTR_JSON, $text, $matches)) {
            if ( ! empty($matches[0])) {
                $value = json_decode($matches[0], true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    return $value;
                } else {
                    $matches[0] = preg_replace('/([\{\,\r\n])?(\w+)\:/ui', '\1"\2":', $matches[0]);
                    $value = json_decode($matches[0], true);
                    if (json_last_error() == JSON_ERROR_NONE) {
                        return $value;
                    }
                }
            }
        }
        return array();
    }
}