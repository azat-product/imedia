<?php
# http://www.minifier.org/
use MatthiasMullie\Minify;
require_once modification(PATH_CORE . 'external/minifier/src/Minify.php');
require_once modification(PATH_CORE . 'external/minifier/src/Exception.php');
require_once modification(PATH_CORE . 'external/minifier/src/Exceptions/BasicException.php');
require_once modification(PATH_CORE . 'external/minifier/src/Exceptions/FileImportException.php');
require_once modification(PATH_CORE . 'external/minifier/src/Exceptions/IOException.php');

class Minifier_
{
    const CONFIG_KEY = 'minifier';

    /**
     * Минимизация файлов статики: js, css
     * @param array $data @ref список url файлов
     * @return void
     */
    public static function process(array &$data)
    {
        if ( ! \config::sysAdmin('site.static.minify', false, TYPE_BOOL)) {
            return;
        }
        $host = SITEHOST;
        if (empty($host)) {
            return;
        }

        static $settings = false;
        if( ! $settings){
            $settings = \config::get(static::CONFIG_KEY, '');
            $settings = \func::unserialize($settings);
        }
        $save = false;

        $minPath = \bff::path('min');
        $minUrl = \bff::url('min');
        foreach ($data as &$v)
        {
            # пропускаем URL на сторонние сервера
            if (mb_stripos($v, $host) === false) {
                continue;
            }
            $path = str_replace($host, '', $v);
            # пропускаем предположительно упакованные файлы
            if (mb_stripos($v,'.min') ||
                mb_stripos($v,'.pack')) {
                continue;
            }
            # отрезаем query (?x=1)
            if ($pos = mb_stripos($path, '?')) {
                $path = mb_substr($path, 0, $pos);
            }
            # отрезаем протокол
            foreach (array('//','http://','https://') as $proto) {
                if (mb_stripos($path, $proto) === 0) {
                    $path = mb_substr($path, mb_strlen($proto)); break;
                }
            }
            # проверяем наличие файла по указанному пути
            $path = PATH_PUBLIC.trim($path, DIRECTORY_SEPARATOR);
            if ( ! file_exists($path)) {
                continue;
            }
            # проверка допустимых расширений
            $ext = \bff\utils\Files::getExtension($path);
            if ( ! in_array($ext, array('js', 'css'))) {
                continue;
            }
            $key = pathinfo($path, PATHINFO_FILENAME).'.'.md5($path.$minUrl); # md5 пути к файлу
            $modified = filemtime($path); # дата и время последнего редактирования файла
            $minPathFile = $minPath.$key.'.'.$ext;
            if (file_exists($minPathFile) && ! empty($settings[$key]['mod']) && $settings[$key]['mod'] == $modified) {
                $v = $settings[$key]['url'];
            } else {
                if ($ext == 'js') {
                    $res = static::js($path, $minPathFile);
                } else {
                    $res = static::css($path, $minPathFile);
                }
                if ($res) {
                    $hash = substr(md5_file($minPathFile), 0, 6);
                    $settings[$key] = array(
                        'mod' => $modified,
                        'url' => $minUrl.$key.'.'.$ext.'?v='.$hash,
                    );
                    $v = $settings[$key]['url'];
                    $save = true;
                }
            }
        } unset($v);

        if ($save) {
            \config::save(static::CONFIG_KEY, serialize($settings));
            $settings = false;
        }
    }

    /**
     * Минимизировать JS файл
     * @param string $source путь к исходному файлу
     * @param string $destination путь для сохранения результата
     * @return bool
     */
    public static function js($source, $destination)
    {
        require_once modification(PATH_CORE . 'external/minifier/src/JS.php');
        try {
            $minifier = new Minify\JS($source);
            $minifier->minify($destination);
        } catch (\Exception $e) {
            \bff::log($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Минимизировать CSS файл
     * @param string $source путь к исходному файлу
     * @param string $destination путь для сохранения результата
     * @return bool
     */
    public static function css($source, $destination)
    {
        require_once modification(PATH_CORE . 'external/minifier/src/Converter.php');
        require_once modification(PATH_CORE . 'external/minifier/src/CSS.php');
        try {
            $minifier = new Minify\CSS($source);
            $minifier->minify($destination);
        } catch (\Exception $e) {
            \bff::log($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Сбросить кеш
     */
    public static function reset()
    {
        $path = \bff::path('min');
        $files = \bff\utils\Files::getFiles($path);
        foreach($files as $v) {
            @unlink($v);
        }
        \config::save(static::CONFIG_KEY, '');
    }
}