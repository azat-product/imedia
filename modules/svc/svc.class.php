<?php

class Svc_ extends SvcBase
{
    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'cron' => array('period' => '0 0 * * *'),
        );
    }

}