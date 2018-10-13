<?php namespace bff;

use bff, func, Module, SEO, Site, Request;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Компонент работы с роутами
 * @version 0.77
 * @modified 5.sep.2018
 * @copyright Tamaranga
 */
class Router
{
    const REGEX_DELIMITER = '#';

    /**
     * Регулярное выражение поиска подставляемых данных
     * /search/{cat_key}-{id}.html
     * /search/{catKeyword?}
     */
    const REGEX_PLACEHOLDER = '#\{/?([a-zA-Z][a-zA-Z0-9_]*)/?\\\\?\??\}#u'; # (?::\s*([^{}]*(?:\{(?-1)\}[^{}]*)*))

    /**
     * Аннотации в doc-комментариях
     */
    const REGEX_ANNOTATION = '/@%s(?:[\s\t]*(.*?))?[\s\t]*\r?$/m';

    /**
     * Объект запроса
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Исходный URI запроса
     * @var string|bool
     */
    protected $uri = false;

    /**
     * Финальный URI запроса
     * Отличный от исходного при задействовании посадочных страниц
     * @var string|bool
     */
    protected $uri_final = false;

    /**
     * Параметры поиска
     * @var array
     */
    protected $searchOptions = [];

    /**
     * Список инициализированных роутов
     * @var array
     */
    public $routes = [];

    /**
     * Список замен по умолчанию
     * @var array
     */
    protected $wheres = [
        'id'   => ['r'=>'([0-9]+)', 'ro'=>'([0-9]*)', 't'=>TYPE_UINT],
        'page' => ['r'=>'([0-9]+)', 'ro'=>'([0-9]*)', 't'=>TYPE_UINT],
        'any'  => ['r'=>'(.*)',     'ro'=>'(.*)',     't'=>TYPE_NOTAGS],
    ];

    /**
     * Ключ текущего роута
     * @var string|bool
     */
    protected $current_id = false;

    /**
     * Текущий префикс роутов
     * @var string
     */
    protected $group_prefix = '';

    /**
     * Инициализация
     * @param array $opts
     */
    public function init(array $opts = [])
    {
        # Устанавливаем объект запроса
        if ($this->request === null) {
            $this->setRequest(bff::request());
        }

        # Сбрасываем префикс группы
        $this->group_prefix = '';
    }

    /**
     * Устанавливаем объект запроса
     * @param ServerRequestInterface $request объект запроса
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->uri = false;
    }

    /**
     * Добавляем группу роутов с общим префиксом
     * @param string $prefix префикс группы
     * @param callable $callback
     */
    public function group($prefix, callable $callback)
    {
        $groupPrefixBefore = $this->group_prefix;
        $this->group_prefix = $groupPrefixBefore . $prefix;
        $callback($this);
        $this->group_prefix = $groupPrefixBefore;
    }

    /**
     * Добавление нескольких роутов
     * @param array $routes
     */
    public function addMany(array $routes)
    {
        foreach ($routes as $id => $route) {
            $this->add($id, $route);
        }
    }

    /**
     * Добавление роута
     * @param string $id уникальный ключ роута
     * @param array|string $route параметры роута
     * @param string|array $method метод запроса
     * @return boolean
     */
    public function add($id, $route, $method = ['GET','POST'])
    {
        if ( ! is_array($route)) {
            $route = array(
                'pattern'  => $id,
                'callback' => $route,
            );
        } else if (isset($route[0])) {
            $route['pattern']  = $route[0]; unset($route[0]);
            $route['callback'] = $route[1]; unset($route[1]);
        }

        $route['id'] = $id;

        # method:
        if ( ! isset($route['method']) ) {
            $route['method'] = $method;
        }
        if (is_string($route['method'])) {
            $route['method'] = explode(',', $route['method']);
        }

        # before callback:
        if ( ! isset($route['before']) || ! is_callable($route['before'], true)) {
            $route['before'] = false;
        }

        # url callback:
        if (empty($route['url']) || ! is_callable($route['url'], true)) {
            $route['url'] = false;
        }

        # route alias:
        if (isset($route['alias'])) {
            if ($route['before'] === false) {
                return false;
            }
            $this->routes[$id] = $route;
            return true;
        }

        # pattern should be string:
        if ( ! is_string($route['pattern'])) {
            return false;
        }
        $route['pattern'] = ltrim($this->group_prefix, '/') . ltrim($route['pattern'], '/');

        # pattern + placeholders => regex
        $route['placeholders'] = [];
        if (preg_match(static::REGEX_PLACEHOLDER, $route['pattern']) > 0) {
            if ( ! isset($route['where']) || ! is_array($route['where'])) {
                $route['where'] = array();
            }
            $i = 1;
            $route['regex'] = preg_replace_callback(static::REGEX_PLACEHOLDER, function($m) use (&$route, &$i) {
                $name = $m[1];
                $optional = (strpos($m[0], '?') !== false);
                if (array_key_exists($name, $route['where'])) {
                    if (is_string($route['where'][$name])) {
                        $regex = ['t'=>TYPE_STR];
                        $regex['r'] = $regex['ro'] = $route['where'][$name];
                    } else {
                        $regex = $route['where'];
                    }
                } else if (array_key_exists($name, $this->wheres)) {
                    $regex = $this->wheres[$name];
                } else {
                    $regex = ['r'=>'(.*)','ro'=>'(.*)','t'=>TYPE_STR];
                }
                $route['placeholders'][$name] = [
                    'index' => $i++,
                    'search' => str_replace('\\', '', $m[0]),
                    'replace' => str_replace($name, '{v}', trim($m[0], '{}\\?')),
                    'optional' => $optional,
                    'type' => (isset($regex['t']) ? $regex['t'] : TYPE_STR),
                ];
                return $regex[($optional ? 'ro' : 'r')];
            }, strtr(preg_quote($route['pattern'], static::REGEX_DELIMITER), ['\\{'=>'{','\\}'=>'}']));
        } else {
            # pattern = regex
            $route['regex'] = $route['pattern'];
        }

        # wrap regex
        if ( ! (isset($route['regex-wrap']) && $route['regex-wrap'] === false)) {
            $route['regex'] = static::REGEX_DELIMITER.'^' . $route['regex'] . '$'.static::REGEX_DELIMITER.'i';
        }

        $this->routes[$id] = $route;

        return true;
    }

    /**
     * Поиск роутов в doc-комментариях к методам класса
     * @param string $className название класса
     * @return array
     */
    public function parseClass($className)
    {
        $routes = array();
        $extract = function($param, $comment, $default = false) {
            if (preg_match(sprintf(static::REGEX_ANNOTATION, $param), $comment, $matches) > 0 && !empty($matches[1])) {
                return trim($matches[1]);
            }
            return $default;
        };
        $methods = (new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC);
        $extra = false;
        foreach ($methods as $method) {
            $name = $method->getName();
            $comment = $method->getDocComment();
            if (($pattern = $extract('route', $comment)) !== false) {
                $routes[$name] = array(
                    'pattern' => $pattern,
                    'priority' => intval($extract('route-priority', $comment, 0)),
                    'method' => explode(',',$extract('route-method', $comment, 'GET,POST')),
                );
            }
            if ($method->isStatic() && $name === 'routes') {
                $extra = $method;
            }
        }
        if ($extra !== false) {
            $routesArr = $className::routes();
            if ( ! empty($routesArr) && is_array($routesArr)) {
                foreach ($routesArr as $k=>$v) {
                    if (isset($routes[$k])) {
                        $routes[$k] = array_merge($routes[$k], $v);
                    } else {
                        $routes[$k] = $v;
                    }
                }
            }
        }
        return $routes;
    }

    /**
     * Формирование URL роута
     * @param string $id ключ роута
     * @param array $params параметры подставляемые в строку роута
     * @param array|boolean $opts доп. параметры для формирования полного URL или true (параметры по умолчанию)
     * @return string
     */
    public function url($id, array $params = [], $opts = true)
    {
        $uri = '/';

        if (isset($this->routes[$id]))
        {
            $route = &$this->routes[$id];

            if ( ! empty($route['alias'])) {
                return $this->url($route['alias'],
                    call_user_func($route['before'], $params, $opts), $opts);
            }

            $ignore = array();
            if ( ! empty($route['pattern'])) {
                if ($route['before'] !== false) {
                    $params = call_user_func($route['before'], $params, $opts);
                }
                $replace = array();
                foreach ($route['placeholders'] as $k=>$v) {
                    if (array_key_exists($k, $params) && ($params[$k] !== '')) {
                        $replace[$v['search']] = str_replace('{v}', $params[$k], $v['replace']);
                        $ignore[] = $k;
                    } else {
                        if ($v['optional']) {
                            $replace[$v['search']] = str_replace('{v}', '', rtrim($v['replace'], '/'));
                            $ignore[] = $k;
                        }
                    }
                }
                $uri .= strtr($route['pattern'], $replace);
            }
            $uri .= Module::urlQuery($params, $ignore);
        } else {
            return $uri;
        }

        # // => /
        $uri = preg_replace(static::REGEX_DELIMITER.'\/+'.static::REGEX_DELIMITER.'i', '/', $uri);

        # Full URL
        if ( ! empty($opts)) {
            $o = array_merge([
                'scheme' => Request::scheme(),
                'host' => SITEHOST,
                'subdomains' => [],
                'lang' => bff::locale()->getCurrentLanguage(),
                'dynamic' => false,
            ], (is_array($opts) ? $opts : []));
            if ( ! empty($route['host'])) {
                # route host
                $o['host'] = $route['host'];
            }
            if ($route['url'] === false) {
                $o['subdomains'] = (!empty($o['subdomains']) ? join('.', $o['subdomains']) : '');
                if (!empty($o['dynamic'])) {
                    $uri = '//' . $o['subdomains'] . '{sitehost}' . $uri;
                } else {
                    $o['lang'] = bff::locale()->getLanguageUrlPrefix($o['lang'], false);
                    $uri = $o['scheme'] . '://' . $o['subdomains'] . $o['host'] . $o['lang'] . $uri;
                }
            } else {
                $o['uri'] = $uri;
                $uri = call_user_func($route['url'], $params, $o);
            }
            # Hooks::moduleUrl
            if ( ! empty($opts['module'])) {
                $uri = bff::filter($opts['module'].'.url', $uri, array('key'=>$id, 'opts'=>$params, 'dynamic'=>!empty($o['dynamic']), 'base'=>\bff::urlBase(LNG, $o['dynamic'])));
            }
        }

        return $uri;
    }

    /**
     * Поиск роута исходя из текущего запроса
     * @param array|boolean $opts настройки:
     *   'init-class-event' - инициализировать bff::$class, bff::$event
     *   'seo-landing-pages' - использовать ли SEO Посадочные страницы
     *   'seo-redirects' - использовать ли SEO Редиректы
     * @return array|mixed: array('class'=>'','event'=>'','params'=>[])
     */
    public function search(array $opts = [])
    {
        # default options:
        func::array_defaults($opts, array(
            'init-class-event'  => false,
            'seo-landing-pages' => false,
            'seo-redirects'     => false,
        ));

        # request uri:
        $req = (isset($opts['uri']) ? $opts['uri'] : $this->getUri(true));

        # landing pages:
        if ( ! empty($opts['seo-landing-pages'])) {
            $reqNew = SEO::landingPage($req);
            if ($reqNew !== false && $req !== $reqNew) {
                $this->uri_final = $req; $req = ltrim($reqNew, '/ ');
                $query = mb_stripos($req, '?');
                if ($query !== false) {
                    list($query, $req) = array(mb_substr($req, $query+1), mb_substr($req, 0, $query));
                    if (!empty($query)) {
                        parse_str($query, $query);
                        if (!empty($query)) {
                            foreach ($query as $k=>&$v) {
                                if (!isset($_GET[$k])) $_GET[$k] = $v;
                                if (!isset($_POST[$k])) $_POST[$k] = $v;
                            }
                        } unset($v);
                    }
                }
            }
        }

        # search:
        if (bff::hooksAdded('routes.search.before')) {
            $this->routes = bff::filter('routes.search.before', $this->routes, $opts);
        }
        $this->reorder();
        $routeID = false;
        $hostFilter = false;
        foreach ($this->routes as &$v) {
            if ( ! empty($v['host'])) {
                $hostFilter = true;
                $host = $this->request->getHeaderLine('Host');
                break;
            }
        } unset($v);
        foreach ($this->routes as $k => &$v) {
            # skip alias:
            if (isset($v['alias'])) {
                continue;
            }
            # host filter:
            if ($hostFilter) {
                if ($host == SITEHOST) {
                    # main domain: route host is set and != current domain
                    if ( ! empty($v['host']) && $v['host'] != $host) {
                        continue;
                    }
                } else {
                    # sub domain: route host is empty or != current sub domain
                    if (empty($v['host']) || $v['host'] != $host) {
                        continue;
                    }
                }
            }
            # method filter:
            if ( ! empty($v['method']) && ! $this->isRequestMethod($v['method'])) {
                continue;
            }
            # compare:
            if ( ! empty($v['placeholders'])) {
                if (preg_match($v['regex'], $req, $matches)) {
                    $v['params'] = $matches;
                    $callback = '';
                    if (is_string($v['callback'])) {
                        $callback = preg_replace($v['regex'], $v['callback'], $req);
                    }
                    $routeID = $k;
                    break;
                }
            } else {
                if (is_string($v['callback'])) {
                    $callback = preg_replace($v['regex'], $v['callback'], $req, 1, $found);
                    if ($found && $callback !== '') {
                        $routeID = $k;
                        break;
                    }
                } else {
                    if (preg_match($v['regex'], $req, $matches)) {
                        $v['params'] = $matches;
                        $callback = '';
                        $routeID = $k;
                        break;
                    }
                }
            }
        } unset($v);

        $this->current_id = $routeID;

        # init class-event-params
        $result = array('class'=>false, 'event'=>false, 'params'=>[], 'route'=>false);
        if ($routeID !== false)
        {
            $result['route'] = $this->routes[$routeID];
            $callback = explode('/', $callback, 3);
            if (isset($callback[0])) $result['class'] = $callback[0];
            if (isset($callback[1])) $result['event'] = $callback[1];
            if ( ! empty($result['route']['placeholders'])) {
                foreach ($result['route']['placeholders'] as $k=>&$v) {
                    if (isset($result['route']['params'][$v['index']]) && $k !== 'any') {
                        $result['params'][$k] = bff::input()->clean($result['route']['params'][$v['index']], $v['type']);
                    }
                } unset($v);
                unset ($result['route']['params']);
            }
            if (isset($callback[2]) && $callback[2] !== '') {
                parse_str($callback[2], $callbackParams);
                if ( ! empty($callbackParams) && is_array($callbackParams)) {
                    $result['params'] = array_merge($result['params'], $callbackParams);
                }
            }
            if ( ! empty($result['params'])) {
                $_GET = array_merge($_GET, $result['params']);
            }
            if ($opts['init-class-event']) {
                if ( ! empty($result['class'])) bff::$class = $result['class'];
                if ( ! empty($result['event'])) bff::$event = $result['event'];
            }
        }

        # redirects:
        if ( ! empty($opts['seo-redirects']) && empty($reqNew) && $this->isRequestMethod('GET')) {
            SEO::redirectsProcess($req);
        }

        return $result;
    }

    /**
     * Сортировка роутов в порядке приоритета
     */
    protected function reorder()
    {
        func::sortByPriority($this->routes, 'priority');
    }

    /**
     * Проверка соответствует ли текущий запрос указанному роуту
     * @param string|array $id ключ роута
     * @return boolean
     */
    public function isCurrent($id)
    {
        if ($this->current_id !== false) {
            if (is_string($id)) {
                return ($this->current_id === $id);
            } else if (is_array($id)) {
                return in_array($this->current_id, $id, true);
            }
        }

        return false;
    }

    /**
     * Проверка обрабатывается ли текущий запрос указанным контроллером
     * @param string $name название контроллера: модуля/плагина/темы
     * @param array $opts доп. параметры:
     *    boolean|string|array 'method' проверяем соответствие метода текущего запроса: GET, POST, ...
     * @return boolean
     */
    public function isCurrentController($name, array $opts = [])
    {
        # method check
        if ( ! empty($opts['method']) && ! $this->isRequestMethod($opts['method'])) {
            return false;
        }
        # current route controller check
        if ($this->current_id !== false && array_key_exists($this->current_id, $this->routes)) {
            $route = & $this->routes[$this->current_id];
            if (isset($route['callback'])) {
                if (is_string($route['callback']) && mb_stripos($route['callback'], $name.'/') === 0) {
                    return true;
                }
            }
        } else if (bff::$class === $name) {
            return true;
        }
        return false;
    }

    /**
     * Получаем текущий URI запроса
     * @param boolean $originalOnly только исходный URI
     * @return string
     */
    public function getUri($originalOnly = false)
    {
        if ($this->uri === false) {
            # // => /
            $uri = preg_replace('/\/+/', '/', $this->request->getRequestTarget());
            # remove first / (left)
            $uri = ltrim($uri, '/');
            # remove query "?xxx"
            $uri = preg_replace("/^(.*)\?.*$/U", '$1', $uri);
            # uri extra parts
            $extra = Site::urlExtra();
            if ( ! empty($extra)) {
                $extra = join('/', $extra).'/';
                if (mb_stripos($uri, $extra) === 0) {
                    $uri = mb_substr($uri, mb_strlen($extra));
                }
            }
            # extra deprecated
            if (method_exists('bff', 'routeEx')) {
                $uri = bff::routeEx($uri);
            }
            $this->uri = $this->uri_final = $uri;
        }

        if ($originalOnly) {
            return $this->uri;
        }
        return $this->uri_final;
    }

    /**
     * Сверяем с текущим методом запроса
     * @param string|array $method метод запроса или несколько методов
     * @return boolean
     */
    public function isRequestMethod($method)
    {
        if (is_string($method)) {
            return $this->request->getMethod() === $method;
        } else if (is_array($method)) {
            foreach ($method as $m) {
                if (is_string($m) && $this->request->getMethod() === $m) {
                    return true;
                }
            }
        }
        return false;
    }
}