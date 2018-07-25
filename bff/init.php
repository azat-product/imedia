<?php

# Время запроса
define('BFF_NOW', $_SERVER['REQUEST_TIME']);
define('BFF_SUPPORT', 'support@tamaranga.com');

/**
 * Модификация файлов
 * @param string $filename путь к файлу
 * @param bool $modsAllow разрешать модификацию файла /custom/mods
 * @param bool $customAllow разрешать кастомизацию файла /custom
 * @return string путь к файлу с учетом модификаций
 */
function modification($filename, $modsAllow = true, $customAllow = true)
{
    # Custom
    static $customDir, $modsDir;
    if (!isset($customDir)) {
        $customDir = PATH_BASE . 'custom' . DIRECTORY_SEPARATOR;
        if (!is_dir($customDir)) $customDir = false;
        $modsDir = $customDir . 'mods' . DIRECTORY_SEPARATOR;
        if (!empty($_COOKIE['bff_testmode_ext'])) {
            $modsDir .= 'testmode' . DIRECTORY_SEPARATOR;
        }
        if (!is_dir($modsDir)) $modsDir = false;
    }
    if ($customAllow && $customDir !== false) {
        # Custom
        $file = $customDir . substr($filename, strlen(PATH_BASE));
        if (is_file($file)) {
            $filename = $file;
            $isCustom = true;
        }
        # Original
        else if (mb_stripos($filename, $customDir) === 0) {
            $filename = PATH_BASE . substr($filename, strlen($customDir));
        }
    }
    # Mods
    if ($modsAllow && $modsDir !== false && !isset($isCustom)) {
        $file = $modsDir . substr($filename, strlen(PATH_BASE));
        if (is_file($file)) {
            $filename = $file;
        }
    }
    return $filename;
}

# Основные компоненты
require modification(PATH_CORE . 'common.php',    false);
require modification(PATH_CORE . 'singleton.php', false);
require modification(PATH_CORE . 'base' . DIRECTORY_SEPARATOR . 'app.php',  false);

# Autoload
if (file_exists(PATH_BASE . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
   require_once PATH_BASE . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
} else {
    if (mb_stripos(PHP_SAPI, 'cli') !== 0) {
        echo 'You need to set up the project dependencies using Composer: <br />';
        echo '<strong>composer install</strong>';
        exit;
    } else {
        fwrite(
            STDERR,
            "\033[0;31m".'You need to set up the project dependencies using Composer:'."\033[0m" . PHP_EOL .
            "\033[1m".'    composer install'."\033[0m" . PHP_EOL . PHP_EOL
        );
        die(1);
    }
}
spl_autoload_register(array('bff\base\app', 'autoload'));

# Загрузка системных настроек
config::sys(false);

# Общие константы
define('BFF_DEBUG', config::sys('debug'));
define('BFF_LOCALHOST', config::sys('localhost', false));
error_reporting(config::sys('php.errors.reporting'));
ini_set('display_errors', config::sys('php.errors.display'));
header('Content-type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set(config::sys('date.timezone'));
define('SITEHOST', config::sys('site.host'));
define('SITEURL', Request::scheme() . '://' . SITEHOST);
define('SITEURL_STATIC', rtrim(config::sys('site.static'), '/ '));
define('DB_PREFIX', config::sys('db.prefix'));
if (!defined('BFF_SESSION_START')) {
    define('BFF_SESSION_START', 1);
}

# users
define('USERS_GROUPS_SUPERADMIN', 'x71');
define('USERS_GROUPS_MODERATOR', 'c60');
define('USERS_GROUPS_MEMBER', 'z24');

# Константы таблиц базы данных
config::file('db.tables');

if (BFF_DEBUG) {
    ini_set('display_startup_errors', 1);
    include modification(PATH_CORE . 'utils' . DIRECTORY_SEPARATOR . 'vardump.php', false);
    Errors::whoops();
} else {
    if (!function_exists('debug')) {
        function debug()
        {
        }
    }
}