<?php

abstract class SEOModuleBase extends Module
{
    /** @var SEOModelBase */
    public $model = null;
    protected $securityKey = '4c7ce37bb93111f217b7ae2x940f5b32';

    /** @var bool индексирование страницы роботом */
    protected $_robotsIndex = true;
    /** @var bool переход на страницу роботом */
    protected $_robotsFollow = true;
    /** @var array мета данные для вывода */
    protected $_metaData = array(
        'mtitle'       => '',
        'mkeywords'    => '',
        'mdescription' => '',
    );
    protected $_metaKeys = array(
        'mtitle',
        'mkeywords',
        'mdescription',
    );

    public function init()
    {
        parent::init();
        $this->module_title = 'SEO';
    }

    /**
     * @return SEO
     */
    public static function i()
    {
        return bff::module('seo');
    }

    /**
     * @return SEOModel
     */
    public static function model()
    {
        return bff::model('seo');
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        $templates = array(
            'pages'  => array(),
            'macros' => array(),
        );

        if (static::landingPagesEnabled()) {
            $templates['pages']['landing-page'] = array(
                't'       => _t('seo','Посадочная страница'),
                'macros'  => array(),
                'fields'  => static::landingPagesFields(),
            );
        }

        return $templates;
    }

    /**
     * Индексирование страницы роботом
     * @param boolean $index true - разрешить, false - запретить, null - текущее значение
     * @return boolean
     */
    public function robotsIndex($index = true)
    {
        if (is_null($index)) {
            return $this->_robotsIndex;
        }
        return ($this->_robotsIndex = !empty($index));
    }

    /**
     * Переход на страницу роботом
     * @param boolean $follow true - разрешить, false - запретить, null - текущее значение
     */
    public function robotsFollow($follow = true)
    {
        if (is_null($follow)) {
            return $this->_robotsFollow;
        }
        $this->_robotsFollow = !empty($follow);
    }

    /**
     * Установка канонического URL
     * @param string $url URL
     * @param array $query параметры URL
     * @param array $options дополнительные настройки
     * @return string
     */
    public function canonicalUrl($url, array $query = array(), array $options = array())
    {
        $languages = $this->locale->getLanguages();
        $landing = static::landingPage();
        if ($landing !== false) {
            $url = $landing['landing_uri'];
            if ($this->locale->getLanguageUrlPrefix() !== '/') {
                $url = preg_replace('/'.preg_quote('{sitehost}').'\/(' . join('|', $languages) . ')\/.*/U', '{sitehost}/', $url);
            }
            if (empty($landing['joined'])) {
                $this->robotsIndex(true);
            }
        }
        if (!$this->_robotsIndex) {
            return $url;
        }

        $urlOriginal = $url = strval($url);

        # добавляем доп. параметры URL
        if (!empty($query)) {
            foreach ($query as $k => &$v) {
                if (empty($v) || ($k == 'page' && $v < 2)) {
                    unset($query[$k]);
                }
            }
            unset($v);
            if (!empty($query)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
            }
        }

        $canonical = $url;

        # формируем полную ссылку
        if (strpos($url, '{site') !== false) {
            $scheme = (config::sysAdmin('https.canonical', false) ? 'https' : false);
            if (sizeof($languages) > 1) {
                foreach ($languages as $lng) {
                    $this->_metaData['link-alternate-' . $lng] = '<link rel="alternate" hreflang="' . $lng . '" href="' . static::urlDynamic($url, array(), $lng, $scheme) . '" />';
                }
            }
            $canonical = static::urlDynamic($url, array(), LNG, $scheme);
            $urlOriginal = static::urlDynamic($urlOriginal, array(), LNG, $scheme);
        }

        # каноническая ссылка
        $this->_metaData['link-canonical'] = '<link rel="canonical" href="' . $canonical . '" />';

        # prev-next
        if (isset($options['page-current']) && isset($options['page-last'])) {
            if (isset($query['page'])) {
                $query2 = $query;
                if ($query['page'] == 2) {
                    unset($query2['page']);
                } else {
                    $query2['page']--;
                }
                $this->_metaData['link-prev'] = '<link rel="prev" href="' . $urlOriginal . (!empty($query2) ? (strpos($urlOriginal, '?') === false ? '?' : '&') . http_build_query($query2) : '') . '" />';
            }
            if ($options['page-current'] < $options['page-last']) {
                $query2 = $query; $query2['page'] = $options['page-current'] + 1;
                $this->_metaData['link-next'] = '<link rel="next" href="' . $urlOriginal . (!empty($query2) ? (strpos($urlOriginal, '?') === false ? '?' : '&') . http_build_query($query2) : '') . '" />';
            }
        }

        return $canonical;
    }

    /**
     * Проверка URL текущего запроса на обязательное наличие/отсутствие завершающего слеша
     * @param bool $required true - URL должен обязательно завершаться слешем,
     *                       false - URL должен обязательно быть без завершающего слеша
     * @param int $redirectStatus статус редиректа
     */
    public function urlCorrectionEndSlash($required = true, $redirectStatus = 301)
    {
        $url = parse_url(Request::uri());
        if ( ! empty($url['path'])) {
            $path = $url['path'];
            $last = mb_substr($path, -1);
            if ($required) {
                # URL должен обязательно завершаться слешем
                if ($last !== '/') {
                    $path .= '/';
                } else {
                    # исправляем множественные завершающие слешы
                    $path = rtrim($path, '/').'/';
                }
            } else {
                # URL должен обязательно быть без завершающего слеша
                if ($last === '/') {
                    $path = rtrim($path, '/');
                }
            }
            if ($path !== $url['path']) {
                $url = SITEURL.$path.( isset($url['query']) ? '?'.$url['query'] : '' );
                $this->redirect($url, $redirectStatus);
            }
        }
    }

    /**
     * Вывод мета данных
     * @param array $options дополнительные настройки
     * @return string
     */
    public function metaRender(array $options = array())
    {
        # дозаполняем мета-данные
        foreach ($this->_metaKeys as $k) {
            if (isset($options[$k])) {
                $this->metaSet($k, $options[$k]);
            }
        }
        $view =& $this->_metaData;
        # чистим незаполненные мета-данные
        foreach ($this->_metaKeys as $k) {
            if (empty($view[$k])) {
                unset($view[$k]);
            }
        }
        $view['language'] = '<meta http-equiv="Content-Language" content="' . LNG . '" />';
        $view['robots'] = '<meta name="robots" content="' . ($this->_robotsIndex ? 'index' : 'noindex') . ', ' . ($this->_robotsFollow ? 'follow' : 'nofollow') . '" />';
        if (!empty($options['csrf-token']) && User::id() && !isset($view['csrf-token'])) {
            $view['csrf-token'] = '<meta name="csrf_token" content="'.HTML::escape((is_string($options['csrf-token']) ? $options['csrf-token'] : $this->security->getToken())).'" />';
        }

        if (!empty($options['content-type']) && !isset($view['content-type'])) {
            $view = array('content-type'=>'<meta http-equiv="Content-Type" content="text/html; charset='.(is_string($options['content-type']) ? $options['content-type'] : 'utf-8' ).'" />') + $view;
        }

        $view = bff::filter('seo.meta.render', $view, $options);

        return join("\r\n", $view) . "\r\n";
    }

    /**
     * Сброс автоматических мета данных
     * @param array $keys ключи
     * @return string
     */
    public function metaReset(array $keys = array())
    {
        if (empty($keys)) {
            $keys[] = join('_', array('site','meta','main'));
        }
        foreach ($keys as $key) {
            foreach (config::getWithPrefix($key) as $k=>$v) {
                if (mb_stripos($k, '-') === 0) config::save($key.$k, '');
            }
        }
    }

    /**
     * Установка мета данных
     * @param string $type тип данных
     * @param string|mixed $data данные
     */
    public function metaSet($type, $data)
    {
        $data = trim(strval($data));
        $limit = config::sysAdmin('seo.meta.limit.'.$type, 0, TYPE_UINT);

        switch ($type) {
            case 'mtitle':
            {
                $this->_metaData[$type] = '<title>' . mb_substr($data, 0, ($limit > 0 ? $limit : 1000)) . '</title>';
            }
            break;
            case 'mkeywords':
            {
                $this->_metaData[$type] = '<meta name="keywords" lang="' . LNG . '" content="' . mb_substr(htmlspecialchars($data, ENT_QUOTES, 'UTF-8', false), 0, ($limit > 0 ? $limit : 250)) . '" />';
            }
            break;
            case 'mdescription':
            {
                $this->_metaData[$type] = '<meta name="description" lang="' . LNG . '" content="' . mb_substr(htmlspecialchars($data, ENT_QUOTES, 'UTF-8', false), 0, ($limit > 0 ? $limit : 300)) . '" />';
            }
            break;
        }
    }

    /**
     * Сохранение настроек шаблона мета данных страницы
     * @param string $moduleName название модуля страницы
     * @param string $pageKey ключ страницы
     * @param array $settings настройки
     * @return boolean
     */
    protected function metaTemplateSave($moduleName, $pageKey, array $settings)
    {
        if (empty($moduleName) || empty($pageKey)) {
            return false;
        }
        config::save($moduleName . '_meta_' . $pageKey, serialize($settings));
    }

    /**
     * Загрузка настроек шаблона мета данных страницы
     * @param string $moduleName название модуля страницы
     * @param string $pageKey ключ страницы
     * @return array
     */
    protected function metaTemplateLoad($moduleName, $pageKey)
    {
        # по-умолчанию
        $defaultData = array_fill_keys($this->locale->getLanguages(), '');
        $default = array('mtitle' => $defaultData, 'mkeywords' => $defaultData, 'mdescription' => $defaultData);
        if (empty($moduleName) || empty($pageKey)) {
            return $default;
        }

        # загружаем настройки
        $settings = config::get($moduleName . '_meta_' . $pageKey, '');
        $settings = func::unserialize($settings);
        if (empty($settings)) {
            $settings = $default;
        }

        return $settings;
    }

    /**
     * Подстановка макросов в мета-текст
     * @param string|array $text @ref текст
     * @param array $macrosData @ref данные для подстановки вместо макросов
     * @param boolean $isTemplate используется seo-шаблон
     * @return string|array
     */
    public function metaTextPrepare(&$text, array &$macrosData = array(), $isTemplate = false)
    {
        if (empty($text)) {
            return $text;
        }

        # подготавливаем макросы для замены
        $replace = array('{site.title}' => Site::title('seo.template.macros'));
        $replaceCallable = array();
        foreach ($macrosData as $k => $v) {
            if ($k == 'page' && is_numeric($v)) {
                $v = ($v > 1 ? _t('pgn', ' - страница [page]', array('page' => $v)) : '');
                $replace[' {' . $k . '}'] = $replace['{' . $k . '}'] = $v;
            } else {
                if ($v == '') {
                    foreach (array(' ', ' - ', ' | ', ', ', ': ') as $prefix) {
                        $replace[$prefix.'{' . $k . '}'] = $v;
                    }
                } else if ($v instanceof \Closure) {
                    $replaceCallable['{' . $k . '}'] = $v;
                    continue;
                }
                $replace['{' . $k . '}'] = $v;
            }
        }
        if (\bff::hooksAdded('seo.meta.text.prepare')) {
            $text = \bff::filter('seo.meta.text.prepare', $text, array('replace'=>&$replace, 'macrosData'=>&$macrosData));
        }
        if (is_string($text)) {
            $text = strtr($text, $replace);
            foreach ($replaceCallable as $k=>$v) {
                $text = call_user_func($v, 0, $isTemplate, $text, $k); // index, isTemplate, text, macros
            }
        } else {
            foreach ($text as $k=>&$v) {
                $v = strtr($v, $replace);
                foreach ($replaceCallable as $kk=>$vv) {
                    $v = call_user_func($vv, $k, $isTemplate, $v, $kk); // index, isTemplate, text, macros
                }
            }
        }

        return $text;
    }

    /**
     * Формирование посадочной страницы
     * @param string|boolean $request:
     *  string - URI текущего запроса
     *  false  - вернуть данные о текущей посадочной странице
     * @return mixed
     */
    public static function landingPage($request = false)
    {
        # Посадочные страницы не используются (выключены)
        if ( ! static::landingPagesEnabled()) {
            return false;
        }

        static $page;
        if (is_string($request)) {
            # URL decode
            if (mb_strpos($request, '%') !== false && ($request2 = urldecode($request)) !== $request) {
                $request = $request2;
            }
            # Выполняем поиск посадочной страницы по URI текущего запроса
            $extra = Site::urlExtra();
            $requestVariations = Request::urlVariations($request, $extra);
            $page = static::model()->landingpageDataByRequest($requestVariations);
            if (empty($page) && !empty($request) && mb_stripos($request, '?') === false) {
                # Дополняем / убираем завершающий "/"
                $request = (mb_substr($request, -1) === '/' ? mb_substr($request, 0, -1) : $request.'/');
                $requestVariations = Request::urlVariations($request, $extra);
                $page = static::model()->landingpageDataByRequest($requestVariations);
            }
            if (!empty($page)) {
                $landingURL = $page['landing_uri'];
                if ($page['is_relative']) {
                     # отрезаем {extra}
                     if (!empty($extra)) {
                        foreach ($extra as $v) {
                            $v = '/'.$v.'/';
                            if (strpos($landingURL, $v) === 0) {
                                $landingURL = mb_substr($landingURL, mb_strlen($v));
                                $extraCut = true;
                            }
                        }
                        if (isset($extraCut)) {
                            $landingURL = '/'.$landingURL;
                        }
                    }
                    $landingURL = Request::scheme().'://'.strtr(Request::host(), array(SITEHOST=>'{sitehost}')).(!empty($extra) ? '/'.join('/', $extra) : '').$landingURL;
                    $page['is_relative'] = 0;
                } else {
                    if (mb_stripos($landingURL, '//') === 0) {
                        $landingURL = Request::scheme().':'.$landingURL;
                    }
                }
                # отрезаем {query}
                if (($queryPosition = strpos($landingURL, '?')) !== false) {
                    $landingURL = mb_substr($landingURL, 0, $queryPosition);
                }
                $page['landing_uri_original'] = $page['landing_uri'];
                $page['landing_uri'] = $landingURL;
                return $page['original_uri'];
            } else {
                # Сбрасываем объявленную ранее посадочную страницу
                $page = false;
                return false;
            }
        }

        # Посадочная страница не была объявлена
        if (empty($page)) {
            return false;
        }

        return $page;
    }

    /**
     * Включено ли использование посадочных страниц
     * @return bool
     */
    public static function landingPagesEnabled()
    {
        return config::sysAdmin('seo.landing.pages.enabled', false, TYPE_BOOL);
    }

    /**
     * Доп. поля для посадочных страниц
     * @return array
     */
    public static function landingPagesFields()
    {
        $fields = config::sys('seo.landing.pages.fields', array());
        if (!empty($fields) && is_array($fields)) {
            return $fields;
        } else {
            return array();
        }
    }

    /**
     * Включено ли использование редиректов
     * @return bool
     */
    public static function redirectsEnabled()
    {
        return config::sysAdmin('seo.redirects', false, TYPE_BOOL);
    }

    /**
     * Выполняем редирект
     * @param string $request URI текущего запроса (без extra данных)
     * @param boolean $return вернуть данные о редиректе не выполняя его
     * @return mixed
     */
    public static function redirectsProcess($request, $return = false)
    {
        if (!static::redirectsEnabled()) {
            return false;
        }

        # Формируем варианты URL
        $scheme = Request::scheme();
        $host   = Request::host();
        $extra  = Site::urlExtra();
        $query  = Request::getSERVER('QUERY_STRING');
        $set    = Request::urlVariations($request, $extra);

        # Выполняем поиск
        $redirect = static::model()->redirectsByRequest($set);
        if (empty($redirect)) return false;

        # Подготавливаем URL редиректа
        $extra = join('/', $extra);
        $to = $redirect['to_uri'];
        if (empty($to)) return false;
        if ($redirect['is_relative']) {
            if ($to[0] === '/') {
                if (isset($to[1])) {
                    if ($to[1] !== '/') {
                        # /{to} => http{s}://{host}/{extra}/{to}
                        $to = $scheme.'://'.$host.($redirect['add_extra'] && !empty($extra) ? '/'.$extra : '').$to;
                    } else {
                        # //{to} => http{s}://{to}
                        $to = $scheme.':'.$to;
                    }
                } else {
                    # / => http{s}://{host}/{extra}/
                    $to = $scheme.'://'.$host.($redirect['add_extra'] && !empty($extra) ? '/'.$extra.'/' : '');
                }
            } else if (mb_stripos($to, 'www.') === 0) {
                # www.{to} => http{s}://www.{to}
                $to = $scheme.'://'.$to;
            }
        }
        # Макросы
        if (mb_stripos($to, '{') !== false) {
            $to = strtr($to, array(
                '{sitehost}' => SITEHOST,
                '/{extra}' => (!empty($extra) ? '/'.$extra : ''),
                '{extra}' => $extra,
            ));
        }
        # Подставляем Query
        if ($redirect['add_query'] && !empty($query)) {
            $toQuery = mb_stripos($to, '?');
            if ($toQuery === false) {
                $to = $to.'?'.$query;
            } else {
                $to = mb_substr($to, 0, $toQuery).'?'.$query;
            }
        }
        # Статус редиректа
        if ($redirect['status']!=301 && $redirect['status']!=302) {
            $redirect['status'] = 301;
        }

        # Выполняем редирект
        if (!$return) {
            Request::redirect($to, intval($redirect['status']));
        } else {
            $redirect['to'] = $to;
            return $redirect;
        }
    }
}