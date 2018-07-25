<?php namespace bff\extend;

/**
 * Плагинизация: хук
 * @version 0.1
 * @modified 6.jul.2017
 */

class Hook extends \Component
{
    protected $callback = null;

    public function __construct($callback = null)
    {
        if (is_callable($callback)) {
            $this->setCallback($callback);
        }
    }

    /**
     * Устанавливаем callback
     * @param callable $callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke()
    {
        $args = func_get_args();
        if ( ! is_null($this->callback)) {
            return call_user_func_array($this->callback, $args);
        }
        return (isset($args[0]) ? $args[0] : '');
    }
}