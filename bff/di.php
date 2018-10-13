<?php namespace bff;

/**
 * Dependency Injection Container
 * @version 0.13
 * @modified 27.aug.2018
 */
class DI
{
    /**
     * Контейнер
     * @var \Pimple
     */
    private static $pimple;

    /**
     * Инициализация
     * @param array $container
     */
    public static function init(array $container = array())
    {
        static::$pimple = new \Pimple();

        $container = array_merge(array(
            'errors_class' => '\Errors',
            'errors' => function($c){
                return $c['errors_class']::i();
            },
            'security_class' => '\Security',
            'security' => function($c){
                return new $c['security_class']();
            },
            'locale_class' => '\bff\base\Locale',
            'locale' => function($c){
                return new $c['locale_class']();
            },
            'input_class' => '\bff\base\Input',
            'input' => function($c){
                return new $c['input_class']();
            },
            'view_class' => '\View',
            'view' => function($c){
                return new $c['view_class']();
            },
            'database_class' => '\bff\db\Database',
            'database_factory' => static::$pimple->factory(function($c){
                return new $c['database_class']();
            }),
            'database' => function($c){
                $db = new $c['database_class']();
                $db->connectionConfig('db');
                $db->connect();
                return $db;
            },
            'hooks_class' => '\Hooks',
            'hooks' => function($c){
                return new $c['hooks_class']();
            },
            'tags_class' => '\bff\extend\Tags',
            'tags' => function($c){
                return new $c['tags_class']();
            },
            'router_class' => '\Router',
            'router' => function($c){
                return new $c['router_class']();
            },
            'request' => function(){
                return \Request::fromGlobals();
            },
        ), $container);

        foreach ($container as $k=>$v) {
            static::$pimple->offsetSet($k, $v);
        }
    }

    /**
     * Объект контейнера
     * @return \Pimple
     */
    public static function container()
    {
        return static::$pimple;
    }

    /**
     * Устанавливаем
     * @param string|array $id ключ сервиса
     * @param mixed|callable $value
     */
    public static function set($id, $value)
    {
        if (is_array($id)) {
            foreach ($id as $k=>$v) {
                static::$pimple->offsetSet($k, $v);
            }
        } else {
            static::$pimple->offsetSet($id, $value);
        }
    }

    /**
     * Получаем
     * @param bool $id ключ требуемого "сервиса"
     */
    public static function get($id)
    {
        return static::$pimple[$id];
    }

    /**
     * Проверка наличия
     * @param $id
     * @return bool
     */
    public static function has($id)
    {
        return isset(static::$pimple[$id]);
    }
}