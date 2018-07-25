<?php

class Plugin_Bbs_title_filter_p05f9ce extends Plugin
{
    public function init()
    {
        parent::init();

        $this->setSettings(array(
            'plugin_title'   => 'Фильтр заголовка и описания объявлений',
            'plugin_version' => '1.0.2',
            'plugin_alias'   => 'bbs_title_filter',
            'extension_id'   => 'p05f9ce6d5d268e15a0dd97ef061671fc19f08aa',
        ));

        /**
         * Настройки заполняемые в админ. панели
         */

        $min_length = 1;

        $this->configSettings(array(
            'capitalize_first_letter' => array(
                'title' => $this->langAdmin('Капитализация первой буквы'),
                'input' => 'checkbox',
                'default' => true,
            ),
            'D1' => array(
                'input' => 'divider',
            ),
            'remove_emoji' => array(
                'title' => $this->langAdmin('Удаление Emoji'),
                'input' => 'checkbox',
                'default' => true,
            ),
            'D2' => array(
                'input' => 'divider',
            ),
            'remove_symbols_begin' => array(
                'title' => $this->langAdmin('Удаление символов в начале строки'),
                'input' => 'checkbox',
                'default' => false,
            ),
            'symbols_begin' => array(
                'title' => $this->langAdmin('Удаляемые символы в начале строки'),
                'placeholder' => $this->langAdmin('Укажите требуемые символы в одну строку, например: [symbols]', array('symbols'=>'!?*(){}|:<>')),
                'input' => 'textarea',
                'default' => '',
            ),
            'D3' => array(
                'input' => 'divider',
            ),
            'remove_symbols_end' => array(
                'title' => $this->langAdmin('Удаление символов в конце строки'),
                'input' => 'checkbox',
                'default' => false,
            ),
            'symbols_end' => array(
                'title' => $this->langAdmin('Удаляемые символы в конце строки'),
                'placeholder' => $this->langAdmin('Укажите требуемые символы в одну строку'),
                'input' => 'textarea',
                'default' => '',
            ),
            'D4' => array(
                'input' => 'divider',
            ),
            'single_repeated_symbols' => array(
                'title' => $this->langAdmin('Приводить повторяющиеся символы к одному'),
                'input' => 'checkbox',
                'default' => false,
            ),
            'case_repeated_symbols' => array(
                'title' => $this->langAdmin('Учитывать регистр при приведении'),
                'input' => 'checkbox',
                'default' => false,
            ),
            'repeated_symbols' => array(
                'title' => $this->langAdmin('Приводимые символы'),
                'placeholder' => $this->langAdmin('Укажите требуемые символы в одну строку'),
                'input' => 'textarea',
                'default' => '',
//                'onChange'  => function($new_value, $old_value) {
//                    return json_encode($new_value); # преобразуем символы в коды
//                },
            ),
            'D5' => array(
                'input' => 'divider',
            ),
            'lower_case' => array(
                'title' => $this->langAdmin('Приводить к нижнему регистру слова длиннее заданных'),
                'input' => 'checkbox',
                'default' => false,
            ),
            'lower_case_length' => array(
                'title' => $this->langAdmin('Минимальная длина приводимых слов'),
                'input' => 'number',
                'default' => 3,
                'min' => $min_length, # Минимально допустимая длина слова
                'onChange'  => function($new_value, $old_value) use ($min_length) {
                    if (empty($new_value)) {
                        return $old_value;
                    } else if ($new_value < $min_length) {
                        $this->errors->set($this->langAdmin('Минимальный интервал [value] меньше допустимого [min]', array(
                            'value' => $new_value,
                            'min' => $min_length,
                        )));
                        return $min_length;
                    }
                    return $new_value;
                },
            ),
            'D6' => array(
                'input' => 'divider',
            ),
            'space_comma' => array(
                'title' => $this->langAdmin('Добавлять пробел после запятой'),
                'input' => 'checkbox',
                'default' => true,
            ),
            'D7' => array(
                'input' => 'divider',
            ),
            'empty_line' => array(
                'title' => $this->langAdmin('Удалять пустые строки'),
                'input' => 'checkbox',
                'default' => true,
            ),
            'D8' => array(
                'input' => 'divider',
            ),
            'filter_description' => array(
                'title' => $this->langAdmin('Применять все настройки также и к описанию объявления'),
                'input' => 'checkbox',
                'default' => false,
            ),
        ), array(
            'titleRow' => 200,
        ));

        # Таб тестирования:
        $this->settingsTab($this->langAdmin('Тестирование'), 'tpl/test', function(){});
    }

    protected function start()
    {
        # Фильтр заголовка объявления
        bff::hookAdd('bbs.form.title.validate',array(
            $this, 'filterText'
        ));

        # Фильтр описания объявления
        if ($this->config('filter_description')) {
            bff::hookAdd('bbs.form.descr.validate',array(
                $this, 'filterText'
            ));
        }
    }

    /**
     * Обработка заголовка/описания
     * @param $text необработанный заголовок/описание
     * @return mixed|string обработанный заголовок/описание
     */
    public function filterText($text)
    {
        # Удаление emoji
        if ($this->config('remove_emoji')) {
            $text = $this->removeEmoji($text);
        }

        # Приведение к нижнему регистру слов длиннее заданного
        if ($this->config('lower_case')) {
            $text = $this->lowerCase($text, $this->config('lower_case_length'));
        }

        # Приведение заданных повторяющихся символов к одному
        if ($this->config('single_repeated_symbols')) {
            $text = $this->singleRepeatedSymbols($text,
                $this->config('repeated_symbols'),
                $this->config('case_repeated_symbols')
            );
        }

        # Добавление пробела после запятой
        if ($this->config('space_comma')) {
            $text = $this->spaceComma($text);
        }

        $text = trim($text);

        # Удаление заданных символов в начале текста
        $remove_symbols_begin = $this->config('remove_symbols_begin');
        $symbols_begin = $this->config('symbols_begin');
        if ($remove_symbols_begin && $symbols_begin != '') {
            $symbols_begin = $symbols_begin.' ';
            $text = ltrim($text, $symbols_begin);
        }

        # Удаление заданных символов в конце текста
        $remove_symbols_end = $this->config('remove_symbols_end');
        $symbols_end = $this->config('symbols_end');
        if ($remove_symbols_end && $symbols_end != '') {
            $symbols_end = $symbols_end.' ';
            $text = rtrim($text, $symbols_end);
        }

        # Капитализация первой буквы текста
        if ($this->config('capitalize_first_letter')) {
            $text = $this->mb_ucfirst($text);
        }

        # Удаление пустых строк
        if ($this->config('empty_line')) {
            $text = preg_replace('/\n{2,}/', "\n", $text);
        }

        $text = trim($text);

        return $text;
    }

    # multibyte версия капитализации первого символа в строке
    public function mb_ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
            mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }

    /**
     * Удаление emoji
     * @param $text
     * @return mixed|string
     */
    public function removeEmoji($text)
    {
        # Блоки символов emoji
        $regex_block1 = '\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23EF}\x{23F0}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}';

        $regex_block2 = '\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}';

        $regex_block3 = '\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F43F}';

        $regex_block4 = '\x{1F440}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}';

        $regex_block5 = '\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F6C5}\x{1F645}-\x{1F64F}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F8}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F94C}\x{1F950}-\x{1F96B}\x{1F980}-\x{1F997}\x{1F9C0}\x{1F9D0}-\x{1F9E6}';

        $new_text = $this->mb_str_replace('️', '', $text);

        $regex_line = '/\n['.$regex_block1.$regex_block2.$regex_block3.$regex_block4.$regex_block5.']+\n/u'; # В строке только emoji

        while (preg_match($regex_line, $new_text)) {
            $new_text = preg_replace($regex_line, "\n", $new_text); # Удаляем emoji с переводом строки
        }

        $regex = '/['.$regex_block1.$regex_block2.$regex_block3.$regex_block4.$regex_block5.']/u'; # Все emoji

        $new_text = preg_replace($regex, '', $new_text); # Удаляем emoji

        return $new_text;
    }

    /**
     * Приведение заданных повторяющихся символов к одному
     * @param $text входная строка
     * @param $symbols символы для приведения
     * @param $case учитывать ли регистр при приведении
     * @return mixed
     */
    public function singleRepeatedSymbols($text, $symbols, $case)
    {
//        $decoded_symbols = json_decode($symbols); # преобразуем коды обратно в символы
        $decoded_symbols = $symbols;

        $repeated_symbols_array = preg_split('//u',$decoded_symbols,-1,PREG_SPLIT_NO_EMPTY); # преобразуем строку с набором заменяемых символов в массив

        $previous_symbol = ''; # для объединения символов первых двух диапазонов emoji

        foreach ($repeated_symbols_array as $repeated_symbol) {

            # объединеняем символы первых двух диапазонов emoji
            if ($repeated_symbol == "️") {
                $repeated_symbol = $previous_symbol.$repeated_symbol;
            }

            $pattern2 = ''; # для втрого регистра
            $repeated_symbol = preg_quote($repeated_symbol, '/'); # экранируем спецсимволы

            if ($case) { # если учитываем регистр символа
                $pattern1 = '/('.$repeated_symbol.'){2,}/'; # символ только в заданном регистре
            } else { # если приводим оба регистра
                $repeated_symbol_low = mb_strtolower($repeated_symbol);
                $repeated_symbol_up = mb_strtoupper($repeated_symbol);

                if ($repeated_symbol_low != $repeated_symbol_up) {
                    $pattern1 = '/('.$repeated_symbol_low.'){2,}/';
                    $pattern2 = '/('.$repeated_symbol_up.'){2,}/';
                } else {
                    $pattern1 = '/('.$repeated_symbol.'){2,}/';
                }
            }

            $text = preg_replace($pattern1, '$1', $text); # заменяем повторяющиеся символы на один

            if ($pattern2 != '') { # если приводим оба регистра
                $text = preg_replace($pattern2, '$1', $text); # заменяем повторяющиеся символы на один во втором регистре
            }

            $previous_symbol = $repeated_symbol;
        }

        return $text;
    }

    /**
     * Приведение к нижнему регистру слов длиннее заданного
     * @param $text входная строка
     * @param $length минимальная длина приводимых слов
     * @return string
     */
    public function lowerCase($text, $length)
    {
        $words = explode(" ", $text); # делим строку на слова по пробелу

        $new_text = '';

        foreach ($words as $word) {
            if (mb_strlen($word) >= $length) { # если слово подходит по длине
                if ($length > 1 || mb_strlen($word) > 1) {
                    $first_letter = mb_substr ($word, 0, 1); # отделяем первую букву, ее не будем приводить к нижнему регистру
                    $cutted_word = mb_substr ($word, 1); # остаток слова
                } else { # когда длина = 1 и слово из 1 буквы, то первую букву не отделяем т.к. всего одна
                    $first_letter = '';
                    $cutted_word = $word;
                }
                $lower_cutted_word = mb_strtolower($cutted_word); # приводим к нижнему регистру слово кроме первой буквы
            } else { # короткие слова не трогаем
                $first_letter = '';
                $lower_cutted_word = $word;
            }

            # собираем строку обратно
            if ($new_text == '') { # если первое слово, то ведущий пробел не нужен
                $new_text = $first_letter.$lower_cutted_word;
            } else {
                $new_text = $new_text.' '.$first_letter.$lower_cutted_word;
            }
        }

        return $new_text;
    }

    /**
     * Добавление пробела после запятой
     * @param $text
     * @return string
     */
    public function spaceComma($text)
    {
        $new_text = $this->mb_str_replace(',', ', ', $text);

        return $this->mb_str_replace(',  ', ', ', $new_text);
    }

    /**
     * multibyte версия str_replace
     * @param $needle что меняем
     * @param $replace_text на что меняем
     * @param $haystack строка в которой меняем
     * @return string
     */
    public function mb_str_replace($needle, $replace_text, $haystack)
    {
        return implode($replace_text, mb_split($needle, $haystack));
    }

    /**
     * Получение данных из шаблона и передача обратно обработанного заголовка
     */
    public function testData()
    {
        $input_data = $this->input->post('input_data');

        $output_data = $this->filterText($input_data);

        $this->ajaxResponseForm(array('output_data'=>nl2br($output_data)));
    }
}