<?php namespace bff\console;

/**
 * Консоль: класс приложения
 * @version 0.22
 * @modified 17.dec.2017
 */

use Symfony\Component\Console\Application;

class App extends Application
{
    public function __construct($version = null)
    {
        if (is_null($version)) {
            $version = \config::version();
        }
        parent::__construct('Tamaranga '.mb_strtoupper(BFF_PRODUCT).' - https://tamaranga.com.', $version);

        $this->addCommands(\bff::filter('app.console.commands.list', array(
            new commands\Maintenance(),
            new commands\migrations\Create(),
            new commands\migrations\Migrate(),
            new commands\migrations\Rollback(),
        )));
    }
}