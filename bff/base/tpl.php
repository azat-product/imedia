<?php namespace bff\base;

/**
 * Вспомогательные методы в шаблонах
 * @abstract
 * @version 0.35
 * @modified 23.jan.2018
 */

abstract class tpl
{
    const ORDER_SEPARATOR = '-';

    static public $includesJS = array();
    static public $includesCSS = array();

    /**
     * Подключаем javascript файл
     * @param array|string $mInclude название скрипта(без расширения ".js") или полный URL
     * @param boolean|null $fromCore true - "/js/bff/", false - "/js/", null - подключаем из ядра если bff::adminPanel()
     * @param integer|boolean $nVersion версия подключаемого файла (для скриптов приложения) или FALSE
     * @param boolean
     */
    public static function includeJS($mInclude, $fromCore = null, $nVersion = false)
    {
        if (empty($mInclude)) {
            return false;
        }

        if (is_null($fromCore)) {
            $fromCore = \bff::adminPanel();
        }

        if ($fromCore) {
            static $paths;
            if (!isset($paths)) {
            $paths = \bff::filter('js.includes.core', array(
                # key => array(
                #   0 => директория скрипта относительно /js/bff/,
                #   1 => название js-скрипта(true - совпадает с ключем|string|array(js.name,js.name,...)),
                #   2 => название css-скрипта(true - style|string),
                #   3 => зависимости(array(key,key,...)))
                # admin
                'fancybox'        => array('admin/fancybox', true, true),
                'datepicker'      => array('admin/datepicker', true, true),
                'tablednd'        => array('admin', true),
                'comments'        => array('admin/comments', true, true),
                # common
                'autocomplete'    => array('autocomplete', true),
                'autocomplete.fb' => array('autocomplete.fb', true, true),
                'cloudzoom'       => array('cloudzoom', 'cloudzoom.min', 'cloudzoom'),
                'dynprops'        => array('dynprops', 'dynprops.min'),
                'fancybox2'       => array('fancybox2', 'jquery.fancybox', 'jquery.fancybox'),
                'history'         => array('history', 'history.min'),
                'jcrop'           => array('jcrop', 'jquery.Jcrop.min', 'jquery.Jcrop'),
                'jquery'          => array('jquery', 'jquery.min'),
                'maps.editor'     => array('maps', 'editor'),
                'publicator'      => array('publicator', 'publicator.min', true),
                'publicator.frontend' => array('publicator', 'frontend.min', 'frontend'),
                'qquploader'      => array('qquploader', 'fileuploader', true),
                'swfupload'       => array('swfupload', array('swfupload', 'handlers'), true),
                'swfobject'       => array('swfobject', true),
                'ui.sortable'     => array('jquery.ui', array('core', 'sortable')),
                'wysiwyg'         => array('wysiwyg', 'wysiwyg.min', true),
            ));
            }

            if (!is_array($mInclude)) $mInclude = array($mInclude);
            $mIncludeCopy = $mInclude;
            $mInclude = array();
            foreach ($mIncludeCopy as $k) {
                if (empty($paths[$k])) {
                    $mInclude[] = $k;
                    continue;
                }

                $j = $paths[$k];
                $j_dir = $j[0] . '/';

                # js
                if (!empty($j[1])) {
                    //js.name === key
                    if ($j[1] === true) {
                        $mInclude[] = $j_dir . $k;
                    } //js.name
                    else if (is_string($j[1])) {
                        $mInclude[] = $j_dir . $j[1];
                    } //array(js.name,js.name,...)
                    else {
                        foreach ($j[1] as $jj) {
                            $mInclude[] = $j_dir . $jj;
                        }
                    }
                }

                # css
                if (!empty($j[2])) {
                    $css = $j[2];
                    if ($css === true) {
                        $css = 'style';
                    } elseif (is_string($css)) {
                        // $css = $css;
                    }
                    // подключаем
                    static::includeCSS(\bff::url('/js/bff/'.$j_dir.$css.'.css'), false);
                }

                # js-dependencies
                if (!empty($j[3])) {
                    static::includeJS($j[3], true);
                }
            }
        }

        if (!is_array($mInclude)) $mInclude = array($mInclude);

        foreach ($mInclude as $j) {
            if (strpos($j, 'http://') === 0 ||
                strpos($j, 'https://') === 0 ||
                strpos($j, '//') === 0) {
                # указан полный url, например "http://example.com/jquery.js", просто подключаем
            } else {
                # /js/*.js
                # /js/bff/*.js
                $j = \bff::url('/js/'.($fromCore ? 'bff/' : '') . $j . '.js', $nVersion);
            }

            if (!in_array($j, static::$includesJS))
                static::$includesJS[] = $j;
        }

        return true;
    }

    /**
     * Подключаем CSS файл
     * @param string|array $mInclude название css файла(без расширения ".css") или полный URL
     * @param bool $bAddUrl false - если в $mInclude был указан полный URL
     * @param integer|boolean $nVersion версия подключаемого файла или FALSE
     */
    public static function includeCSS($mInclude, $bAddUrl = true, $nVersion = false)
    {
        if (empty($mInclude)) return false;

        if (!is_array($mInclude)) {
            $mInclude = array($mInclude);
        }
        foreach ($mInclude as $c) {
            if (isset(static::$includesCSS[$c])) continue;
            $ext = (mb_substr($c, -4) !== '.css' ? '.css' : '');
            static::$includesCSS[$c] = ($bAddUrl ? \bff::url('/css/' . $c . $ext, $nVersion) : $c . $ext.(!empty($nVersion) ? '?v=' . $nVersion : ''));
        }
    }

    /**
     * Обрезаем строку до нужного кол-ва символом
     * @param string $sString строка
     * @param int $nLength необходимая длина текста
     * @param string $sEtc окончание обрезанной строки
     * @param bool $bBreakWords разрывать ли слова
     * @param bool $bCalcEtcLength учитывать ли длину текста $sEtc перед обрезанием
     */
    public static function truncate($sString, $nLength = 80, $sEtc = '...', $bBreakWords = false, $bCalcEtcLength = true)
    {
        if ($nLength == 0)
            return '';

        if (mb_strlen($sString) > $nLength) {
            $nLength -= ($bCalcEtcLength === true ? mb_strlen($sEtc) : $bCalcEtcLength);
            if (!$bBreakWords)
                $sString = preg_replace('/\s+?(\S+)?$/', '', mb_substr($sString, 0, $nLength + 1));

            return mb_substr($sString, 0, $nLength) . $sEtc;
        } else
            return $sString;
    }

    /**
     * Инициализация CWysiwyg компонента (FCKEditor, ...)
     * @param string $sContent редактируемый контент
     * @param string $sFieldName имя поля
     * @param string|int $mWidth ширина
     * @param string|int $mHeight высота
     * @param string $sToolbarMode режим панели: average, ...
     * @param string $sTheme тема: sd
     * @return string
     */
    public static function wysiwyg($sContent, $sFieldName, $mWidth = '575px', $mHeight = '300px', $sToolbarMode = 'average', $sTheme = 'sd')
    {
        static $oWysiwyg;
        if (!isset($oWysiwyg)) {
            $oWysiwyg = new \CWysiwyg();
        }

        return $oWysiwyg->init($sFieldName, $sContent, $mWidth, $mHeight, $sToolbarMode, $sTheme);
    }

    /**
     * Инициализация bffWysiwyg компонента
     * @param string $sContent редактируемый контент
     * @param string $sFieldName имя поля или "id поля,имя поля"
     * @param int|string $mWidth ширина число или "100%" (0,false = 100%)
     * @param int|string $mHeight высота число или "100%" (0,false = 100%)
     * @param mixed $mParams параметры инициализации, варианты: FALSE; array(...); '{...}';
     * @param string $sJSObjectName имя js объекта, для дальнейшего управления компонентом
     * @return string
     */
    public static function jwysiwyg($sContent, $sFieldName, $mWidth = 575, $mHeight = 300, $mParams = false, $sJSObjectName = '')
    {
        # параметры редактора
        if (empty($mParams) && !is_array($mParams)) {
            if (\bff::adminPanel()) {
                $mParams = array(
                    'stretch'  => true,
                    'autogrow' => false,
                    'controls' => array('insertImageSimple' => array('visible' => false))
                );
            } else {
                $mParams = array(
                    'controls' => array(
                        'insertImageSimple' => array('visible' => false),
                        'fullscreen'        => array('visible' => false),
                        'html'              => array('visible' => false),
                        'title'             => array('visible' => false),
                    )
                );
            }
        }

        # name/id редактора
        if (strpos($sFieldName, ',') !== false) {
            list($sFieldID, $sFieldName) = explode(',', $sFieldName);
            if (empty($sFieldName)) $sFieldName = $sFieldID;
        } else {
            $sFieldID = $sFieldName;
            $sFieldID = str_replace(array('[', ']'), '', $sFieldID);
        }

        # размеры редактора (ширина/высота)
        if (empty($mWidth)) $mWidth = '100%';
        $WidthCSS = (strpos(strval($mWidth), '%') === false ? $mWidth . 'px' : $mWidth);
        if (empty($mHeight)) $mHeight = '100%';
        $HeightCSS = (strpos(strval($mHeight), '%') === false ? $mHeight . 'px' : $mHeight);

        # подключаем javascript
        static $js = array();
        if (empty($js)) {
            $js['wy'] = static::includeJS('wysiwyg', true);
        }
        if (!empty($mParams['reformator']) && !isset($js['ref'])) {
            $js['ref'] = static::includeJS('reformator/reformator', true);
        }

        # формируем HTML
        $htmlTextarea = '<textarea name="' . $sFieldName . '" id="' . $sFieldID . '" style="height:' . $HeightCSS . '; width:' . $WidthCSS . ';">' . $sContent . '</textarea>';
        $htmlJavascript = '$(function(){ ' . (!empty($sJSObjectName) ? $sJSObjectName . ' = ' : '') . ' $(\'#' . $sFieldID . '\').bffWysiwyg(
                    ' . (is_string($mParams) ? $mParams : \func::php2js($mParams)) . ', true); });';
        if (\bff::adminPanel()) {
            return $htmlTextarea.'<script type="text/javascript">'.$htmlJavascript.'</script>';
        } else {
            ?><script type="text/javascript"><?php \js::start(); ?><?= $htmlJavascript ?><?php \js::stop(); ?></script><?php
            return $htmlTextarea;
        }
    }

    /**
     * Формируем URL капчи
     * @param string $sType тип капчи, варианты: 'math' - математическая (5+2); 'simple' - обычная
     * @param array $aParams параметры, доступны: 'bg'=>'ffffff' (цвет фона)
     * @return string
     */
    public static function captchaURL($sType = 'math', array $aParams = array('bg' => 'ffffff'))
    {
        $aParams = http_build_query($aParams);
        switch ($sType) {
            case 'math':
                return SITEURL . '/captcha2.php?' . $aParams;
                break;
            case 'simple':
            default:
                return SITEURL . '/captcha.php?' . $aParams;
                break;
        }
    }

    /**
     * Формируем объем файла в текстовом виде, например "2 Мегабайта"
     * @param integer $nSize размер в байтах
     * @param boolean $bExtendedTitle true - "Мегабайт", false - "МБ"
     * @return string
     */
    public static function filesize($nSize, $bExtendedTitle = false)
    {
        $aUnits = ($bExtendedTitle ? explode(',', _t('', 'Байт,Килобайт,Мегабайт,Гигабайт,Терабайт')) : explode(',', _t('', 'Б,КБ,МБ,ГБ,ТБ')));
        for ($i = 0; $nSize > 1024; $i++) {
            $nSize /= 1024;
        }

        return round($nSize, 2) . ' ' . $aUnits[$i];
    }

    /**
     * Форматирование даты
     * @param string|integer $datetime дата в текстовом формате или unix-вариант
     * @param string $format требуемый формат @see: strftime
     * @return bool|string
     */
    public static function dateFormat($datetime, $format = '%d.%m.%Y')
    {
        if (empty($datetime)) return '';
        if (is_string($datetime)) {
            if ($datetime == '0000-00-00') return '';
            if ($datetime == '0000-00-00 00:00:00') return '';
            $datetime = strtotime($datetime);
        }

        return strftime($format, $datetime);
    }

    /**
     * Форматируем дату к виду: "1 января 2011[, 11:20]"
     * @param mixed $mDatetime дата: integer, string - 0000-00-00[ 00:00:00]
     * @param boolean $getTime добавлять время
     * @param boolean $bSkipYearIfCurrent опускать год, если текущий
     * @param string $glue1 склейка между названием месяца и годом (если не опускается)
     * @param string $glue2 склейка между датой и временем (если добавляется)
     * @param boolean $bSkipYear всегда опускать год
     * @return string
     */
    public static function date_format2($mDatetime, $getTime = false, $bSkipYearIfCurrent = false, $glue1 = ' ', $glue2 = ', ', $bSkipYear = false)
    {
        static $months;
        if (!isset($months)) $months = \bff::locale()->getMonthTitle();

        if (!$mDatetime) {
            if (is_string($bSkipYearIfCurrent)) return $bSkipYearIfCurrent;

            return false;
        }
        $res = \func::parse_datetime((is_int($mDatetime) ? date('Y-m-j H:i:s', $mDatetime) : $mDatetime));

        return intval($res['day']) . ' ' . $months[intval($res['month'])] . ($bSkipYear === true || ($bSkipYearIfCurrent === true && date('Y', time()) == $res['year']) ? '' : $glue1 . $res['year']) .
        ($getTime ? !(int)$res['hour'] && !(int)$res['min'] ? '' : $glue2 . $res['hour'] . ':' . $res['min'] : '');
    }

    public static function date_format3($sDatetime, $sFormat = false)
    {
        # get datetime
        if (!$sDatetime) return '';
        $date = \func::parse_datetime($sDatetime);

        if ($sFormat !== false) {
            return date($sFormat, mktime($date['hour'], $date['min'], 0, $date['month'], $date['day'], $date['year']));
        }

        # get now
        $now = array();
        list($now['year'], $now['month'], $now['day']) = explode(',', date('Y,m,d'));

        # дата позже текущей
        if ($now['year'] < $date['year'])
            return '';

        if ($now['year'] == $date['year'] && $now['month'] == $date['month']) {
            if ($now['day'] == $date['day']) {
                return _t('', 'сегодня') . " {$date['hour']}:{$date['min']}";
            } else if ($now['day'] == $date['day'] - 1) {
                return _t('', 'вчера') . " {$date['hour']}:{$date['min']}";
            }
        }

        return "{$date['day']}.{$date['month']}.{$date['year']} в {$date['hour']}:{$date['min']}";
    }

    /**
     * Формирование строки с описанием прошедшего времени от даты {$mDatetime}
     * @param string|integer $mDatetime дата
     * @param bool $getTime добавлять время
     * @param bool $addBack добавлять слово "назад"
     * @return string
     */
    public static function date_format_spent($mDatetime, $getTime = false, $addBack = true)
    {
        # локализация
        static $lng;
        if (!isset($lng)) {
            switch (LNG) {
                case 'en':
                    $lng = array(
                        's'     => explode(';', _t('','second;seconds;seconds')),
                        'min'   => explode(';', _t('','minute;minutes;minutes')),
                        'h'     => explode(';', _t('','hour;hours;hours')),
                        'd'     => explode(';', _t('','day;days;days')),
                        'mon'   => explode(';', _t('','month;months;months')),
                        'y'     => explode(';', _t('','year;years;years')),
                        'now'   => _t('','now'),
                        'today' => _t('','today'),
                        'yesterday' => _t('','yesterday'),
                        'back'  => _t('','ago'),
                    );
                    break;
                case 'ru':
                    $lng = array(
                        's' => 'секунда;секунды;секунд',
                        'min' => 'минута;минуты;минут',
                        'h' => 'час;часа;часов',
                        'd' => 'день;дня;дней',
                        'mon' => 'месяц;месяца;месяцев',
                        'y' => 'год;года;лет',
                        'now' => 'сейчас',
                        'today' => 'сегодня',
                        'yesterday' => 'вчера',
                        'back' => 'назад',
                    );
                    break;
                case 'ua':
                default:
                    $lng = array(
                        's' => 'секунда;секунди;секунд',
                        'min' => 'хвилина;хвилини;хвилин',
                        'h' => 'година;години;годин',
                        'd' => 'день;дні;днів',
                        'mon' => 'місяць;місяці;місяців',
                        'y' => 'рік;роки;років',
                        'now' => 'зараз',
                        'today' => 'сьогодні',
                        'yesterday' => 'вчора',
                        'back' => 'тому',
                    );
                    break;
            }
        }

        # проверяем дату
        if (!$mDatetime) return '';

        $dtFrom = date_create($mDatetime);
        $dtTo = date_create();
        # дата позже текущей
        if ($dtFrom > $dtTo)
            return '';

        # считаем разницу
        $interval = date_diff($dtFrom, $dtTo);
        if ($interval === false) return '';
        $since = array(
            'year'  => $interval->y,
            'month' => $interval->m,
            'day'   => $interval->d,
            'hour'  => $interval->h,
            'min'   => $interval->i,
            'sec'   => $interval->s,
        );

        $text = '';
        $allowBack = true;
        do {
            # разница в год и более (X лет [X месяцев])
            if ($since['year']) {
                $text .= $since['year'] . ' ' . static::declension($since['year'], $lng['y'], false);
                if ($since['month']) {
                    $text .= ' ' . $since['month'] . ' ' . static::declension($since['month'], $lng['mon'], false);
                }
                break;
            }
            # разница в месяц и более (X месяцев [X дней])
            if ($since['month']) {
                $text .= $since['month'] . ' ' . static::declension($since['month'], $lng['mon'], false);
                if ($since['day'])
                    $text .= ' ' . $since['day'] . ' ' . static::declension($since['day'], $lng['d'], false);
                break;
            }
            # разница в день и более  (X дней [X часов])
            if ($since['day']) {
                if ($getTime) {
                    $text .= $since['day'] . ' ' . static::declension($since['day'], $lng['d'], false);
                    if ($since['hour'] > 0) {
                        $text .= ' ' . $since['hour'] . ' ' . static::declension($since['hour'], $lng['h'], false);
                    }
                } else {
                    if ($since['day'] == 1) {
                        $text = $lng['yesterday'];
                        $allowBack = false;
                    } else {
                        $text .= $since['day'] . ' ' . static::declension($since['day'], $lng['d'], false);
                    }
                }
                break;
            }

            if ($getTime) {
                # разница в час и более  (X часов [X минут])
                if ($since['hour']) {
                    $text .= $since['hour'] . ' ' . static::declension($since['hour'], $lng['h'], false);
                    if ($since['min']) {
                        $text .= ' ' . $since['min'] . ' ' . static::declension($since['min'], $lng['min'], false);
                    }
                    break;
                }

                # разница более 3 минут (X минут)
                if ($since['min'] > 3) {
                    $text = $since['min'] . ' ' . static::declension($since['min'], $lng['min'], false);
                } else {
                    $text = $lng['now']; # сейчас
                    $allowBack = false;
                }
            } else {
                if (intval($dtTo->format('d')) > intval($dtFrom->format('d'))) {
                    $text = $lng['yesterday']; # сегодня
                } else {
                    $text = $lng['today']; # сегодня
                }
                $allowBack = false;
            }

        } while (false);

        return $text . ($addBack && $allowBack ? ' ' . $lng['back'] : '');
    }

    /**
     * Склонение
     * @param int $nCount число
     * @param array|string $mForms варианты
     * @param bool $bAddCount добавлять число к результату
     * @param string $sDelimeter разделитель вариантов (в случае если $mForms строка)
     * @return string
     */
    public static function declension($nCount, $mForms, $bAddCount = true, $sDelimeter = ';')
    {
        $n = abs($nCount);

        $sResult = '';
        if ($bAddCount)
            $sResult = $n . ' ';

        $aForms = (is_string($mForms) ? explode($sDelimeter, $mForms) : $mForms);
        if (empty($aForms)) {
            $aForms = array(0=>'',1=>'',2=>'');
        } else if (sizeof($aForms) == 1) {
            $aForms[1] = $aForms[2] = $aForms[0];
        } else if (sizeof($aForms) == 2) {
            # english
            return $sResult . $aForms[($n > 1 ? 1 : 0)];
        }

        $n = $n % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return $sResult . $aForms[2];
        }
        if ($n1 > 1 && $n1 < 5) {
            return $sResult . $aForms[1];
        }
        if ($n1 == 1) {
            return $sResult . $aForms[0];
        }

        return $sResult . $aForms[2];
    }

    public static function ucfirst($string)
    {
        $fc = mb_strtoupper(mb_substr($string, 0, 1));

        return $fc . mb_substr($string, 1);
    }

    /**
     * Формирование URL в админ-панели
     * @param string|NULL $sEvent название метода
     * @param string $sModule название модуля
     * @param boolean|string $escapeType выполнять квотирование, false - не выполнять, 'html', 'js'
     * @return string
     */
    public static function adminLink($sEvent, $sModule = '', $escapeType = false)
    {
        if (is_null($sEvent)) return \bff::adminPanel(true);
        if (empty($sModule)) $sModule = \bff::$class;

        return \HTML::escape(\bff::adminPanel(true).'?s=' . $sModule . '&ev=' . $sEvent, $escapeType);
    }

    /**
     * Помечаем настройки текущей страницы в admin панели
     * @param array $aSettings настройки ключ=>значение
     * @param bool $bRewrite перетереть уже указанные
     * @return mixed
     */
    public static function adminPageSettings(array $aSettings = array(), $bRewrite = true)
    {
        static $data = array(
            'title'  => '',      # заголовок страницы
            'custom' => false,   # обвертка для основного контента не требуется
            'attr'   => array(), # доп. атрибуты блока
            'link'   => array(), # ссылка в шапке блока, справа
            'icon'   => null,    # ключ иконки в шапке блока (false - список; true - форма; string - ключ)
            'fordev' => array(), # список доп. ссылок в режиме разработчика
        ), $set = array();
        if (!empty($aSettings)) {
            foreach ($aSettings as $k => $v) {
                if (!$bRewrite && in_array($k, $set)) continue;
                $data[$k] = $v;
                $set[] = $k;
            }
        }

        return $data;
    }

}