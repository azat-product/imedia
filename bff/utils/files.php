<?php namespace bff\utils;

/**
 * Класс для работы с директориями / файлами
 * @abstract
 * @version 0.791
 * @modified 25.aug.2018
 * @copyright Tamaranga
 */

abstract class Files_
{
    /**
     * Формирование списка директорий относительно корневой директории
     * @param string $sPath путь к корневой директории
     * @return array
     */
    public static function getDirs($sPath)
    {
        $aResult = array();
        foreach (new \DirectoryIterator($sPath) as $file) {
            if ($file->isDir() && !$file->isDot()) {
                $aResult[] = $file->getFilename();
            }
        }

        return $aResult;
    }

    /**
     * Подсчет размера файлов(поддиректорий) в директории
     * @param mixed $sPath путь к директории
     * @return integer
     */
    public static function getDirSize($sPath)
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sPath)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Рекурсивный обход директорий
     * @param string $directory корневая директория
     * @param string $base путь относительно корневой директории
     * @param boolean $recursive рекурсивный обход
     * @param boolean $fullPath true - возвращать полный путь, false - только имя файла/директории
     * @param array $fileTypes типы файлов
     * @param array $exclude список исключений
     * @return array|boolean
     */
    public static function getFiles($directory, $base = '', $recursive = true, $fullPath = true, array $fileTypes = array(), array $exclude = array())
    {
        if (is_file($directory) || ! is_dir($directory)) {
            return false;
        }

        $directory = rtrim($directory, '\\/');
        if ( ! is_dir($directory)) {
            return false;
        }

        # Открываем директорию
        if ($dir = opendir($directory)) {
            # Формируем список найденных файлов
            $tmp = array();
            $sep = DIRECTORY_SEPARATOR;
            # Добавляем файлы
            while (($file = readdir($dir)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $isFile = is_file($directory . $sep . $file);
                if ( ! static::validatePath($base, $file, $isFile, $fileTypes, $exclude)) {
                    continue;
                }

                if ($isFile) {
                    array_push($tmp, ($fullPath ? $directory . $sep . $file : $file));
                } else {
                    # Если директория -> ищем в ней
                    if ($recursive) {
                        $tmpSub = static::getFiles($directory . $sep . $file, $base . $sep . $file, $recursive, $fullPath, $fileTypes, $exclude);
                        if ( ! empty($tmpSub)) {
                            $tmp = array_merge($tmp, $tmpSub);
                        }
                    }
                }
            }
            closedir($dir);

            return $tmp;
        }

        return array();
    }

    /**
     * Копирование директориий (файлов) с вложенностью
     * @param string $source
     * @param string $dest
     * @param int $permissions
     * @return bool
     */
    public static function copyRecursive($source, $dest, $permissions = 0755)
    {
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }
        if (is_file($source)) {
            return copy($source, $dest);
        }
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            static::copyRecursive($source.DIRECTORY_SEPARATOR.$entry, $dest.DIRECTORY_SEPARATOR.$entry, $permissions);
        }
        $dir->close();
        return true;
    }

    /**
     * Удаление директории с вложенностью
     * @param string $dir
     * @param bool $deleteSelf
     * @param bool $traverseSymlinks false - удаляем только симлинки, без удаления реального файла
     */
    public static function deleteRecursive($dir, $deleteSelf = true, $traverseSymlinks = false)
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $realPath = $file->getRealPath();
            if ($file->isDir() && !$file->isLink()) {
                rmdir($realPath);
            } else {
                if ($file->isLink()) {
                    if ($traverseSymlinks && $realPath !== false) {
                        unlink($realPath);
                    } else {
                        unlink($file->getPathname());
                    }
                } else {
                    unlink($realPath);
                }
            }
        }
        if ($deleteSelf) {
            rmdir($dir);
        }
    }

    /**
     * Валидация пути файла / директории
     * @param string $base путь относительно корневой директории
     * @param string $file имя файла / директории
     * @param boolean $bIsFile
     * @param array $aFileTypes список суфиксов типов файлов (без точки). Проходят только файлы с указанными суфиксами.
     * @param array $aExclude список исключений
     * @return boolean true - файл / директория валидны
     */
    protected static function validatePath($base, $file, $bIsFile, array $aFileTypes, array $aExclude)
    {
        foreach ($aExclude as $e) {
            if ($file === $e || strpos($base . '/' . $file, $e) === 0) {
                return false;
            }
        }
        if (!$bIsFile || empty($aFileTypes)) {
            return true;
        }
        if (($pos = strrpos($file, '.')) !== false) {
            $type = substr($file, $pos + 1);

            return in_array($type, $aFileTypes);
        } else {
            return false;
        }
    }

    /**
     * Получение содержимого файла в виде строки
     * @param string $sFilePath путь к файлу
     * @return string
     */
    public static function getFileContent($sFilePath)
    {
        return file_get_contents($sFilePath);
    }

    /**
     * Запись строки в файл
     * @param string $sFilePath путь к файлу
     * @param string $sContent данные
     * @return boolean
     */
    public static function putFileContent($sFilePath, $sContent)
    {
        $res = file_put_contents($sFilePath, $sContent);

        return ($res !== false);
    }

    /**
     * Достаточно ли прав на запись
     * @param string $sPath путь к файлу / директории
     * @param boolean $bTriggerError вызывать пользовательскую ошибку
     * @return boolean
     */
    public static function haveWriteAccess($sPath, $bTriggerError = false)
    {
        if (!is_writable($sPath) && !chmod($sPath, 775)) {
            if ($bTriggerError) {
                trigger_error(sprintf('Unable to write to "%s"', realpath((is_dir($sPath) ? $sPath : dirname($sPath)))));
            }

            return false;
        }

        return true;
    }

    /**
     * Чистим имя файла от запрещенных символов
     * @param string $sFileName имя файла
     * @param boolean $bRelativePath относительный путь (true)
     * @return string очищенное имя файла
     */
    public static function cleanFilename($sFileName, $bRelativePath = false)
    {
        $bad = array(
            '../',
            '<!--',
            '-->',
            '<',
            '>',
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            "%20",
            "%22",
            "%3c", // <
            "%253c", // <
            "%3e", // >
            "%0e", // >
            "%28", // (
            "%29", // )
            "%2528", // (
            "%26", // &
            "%24", // $
            "%3f", // ?
            "%3b", // ;
            "%3d", // =
        );

        if (!$bRelativePath) {
            $bad[] = './';
            $bad[] = '/';
        }
        $bad = \bff::filter('utils.files.cleanFilename.blacklist', $bad, $bRelativePath, $sFileName);

        return stripslashes(str_replace($bad, '', $sFileName));
    }

    /**
     * Создание директории
     * @param string $sDirectoryName имя директории
     * @param int $nPermissions права (@see chmod)
     * @param bool $bRecursive рекурсивное создание директории
     * @return bool
     **/
    public static function makeDir($sDirectoryName, $nPermissions = 0775, $bRecursive = false)
    {
        $parent = dirname($sDirectoryName);
        if (!$bRecursive || ($bRecursive && file_exists($parent))) {
            if (!static::haveWriteAccess($parent, true)) {
                return false;
            }
        }

        $umask = umask(0);
        $res = mkdir($sDirectoryName, $nPermissions, $bRecursive);
        umask($umask);
        return $res;
    }

    /**
     * Получение расширения файла (без точки)
     * @param string $path путь к файлу
     * @param boolea $isUrl путь является URL
     * @return string расширение без точки
     */
    public static function getExtension($path, $isUrl = false)
    {
        if ($isUrl) {
            $url = parse_url($path);
            if (!empty($url['path'])) {
                $path = $url['path'];
            }
        }
        $res = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return ($res == 'jpeg' ? 'jpg' : $res);
    }

    /**
     * Проверка, является ли файл изображением
     * @param mixed $filePath путь к файлу
     * @param boolean $checkExtension проверять расширение файла
     * @return boolean
     */
    public static function isImageFile($filePath, $checkExtension = false)
    {
        if ($checkExtension && !in_array(static::getExtension($filePath), array('gif','jpg','png'))) {
            return false;
        }

        $imageSize = getimagesize($filePath);
        if (empty($imageSize)) {
            return false;
        }
        if (in_array($imageSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
            return true;
        }

        return false;
    }

    /**
     * Загрузка файла по URL
     * @param string $url URL файла
     * @param string|bool $path полный путь для сохранения файла, false - вернуть содержимое
     * @param array|boolean $options
     *      setErrors - фиксировать ошибки
     *      timeout - максимально допустимое время выполнения
     * @return boolean|mixed файл был успешно загружен, false - ошибка загрузки файла, содержимое файла
     */
    public static function downloadFile($url, $path, $options = true)
    {
        $timeout = \bff::filter('utils.files.downloadFile.timeout', 30);
        if (is_bool($options)) {
            $setErrors = $options;
        } elseif (is_array($options)) {
            $setErrors = (isset($options['setErrors']) ? !empty($options['setErrors']) : true);
            if (isset($options['timeout'])) { $timeout = $options['timeout']; }
        }

        if (empty($url)) {
            if ($setErrors) {
                \bff::errors()->set(_t('system', 'URL указан некорректно'));
            }

            return false;
        }

        $returnContent = ($path === false);
        if ( ! $returnContent) {
            $dir = $path;
            if (!is_dir($dir)) {
                $dir = pathinfo($dir, PATHINFO_DIRNAME);
            }
            if (!is_writable($dir)) {
                if ($setErrors) {
                    \bff::errors()->set(_t('system', 'Укажите путь к директории, доступной для записи'));
                }

                return false;
            }
        }

        if (extension_loaded('curl')) {
            $max_redirects = 5;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            if (ini_get('open_basedir') !== '')  # fix CURLOPT_FOLLOWLOCATION + open_basedir
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                $url2 = $url_original = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $ch2 = curl_copy_handle($ch);
                curl_setopt($ch2, CURLOPT_HEADER, 1);
                curl_setopt($ch2, CURLOPT_NOBODY, 1);
                curl_setopt($ch2, CURLOPT_FORBID_REUSE, 0);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
                do
                {
                    curl_setopt($ch2, CURLOPT_URL, $url2);
                    $header = curl_exec($ch2);
                    if (curl_errno($ch2)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/i', $header, $matches);
                            $url2 = trim(array_pop($matches));

                            // if no scheme is present then the new url is a
                            // relative path and thus needs some extra care
                            if (!preg_match("/^https?:/i", $url2)) {
                                $url2 = $url_original . $url2;
                            }
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$max_redirects);
                curl_close($ch2);

                curl_setopt($ch, CURLOPT_URL, $url2);
            } else {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            if ($returnContent) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            } else {
                $file = fopen($path, 'w+');
                curl_setopt($ch, CURLOPT_FILE, $file);
            }

            $res = curl_exec($ch);
            if ($res === false) {
                \bff::log(sprintf('Files::downloadFile curl error (code %s): %s', curl_errno($ch), curl_error($ch)));
            }
            if (is_array($options) && !empty($options['returnCode'])) {
                $options['returnCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }
            curl_close($ch);
            if ($returnContent) {
                return $res;
            }

            fclose($file);
        } elseif (ini_get('allow_url_fopen')) {
            if ($returnContent) {
                return file_get_contents($url);
            } else {
                $res = file_put_contents($path, fopen($url, 'r'));
            }
        } else {
            $res = false;
        }

        if (empty($res)) {
            if ($setErrors) {
                \bff::errors()->set(_t('system', 'Ошибка загрузки файла'));
            }

            return false;
        }

        return true;
    }

    /**
     * Создание временного файла доступного на запись
     * @param bool $dir директория хранения или false (директория по-умолчанию)
     * @param bool $prefix дополнительный префикс в названии файла
     * @return string|bool путь к файлу или false
     */
    public static function tempFile($dir = false, $prefix = false)
    {
        if ($dir === false) {
            $dir = PATH_BASE.'files'.DS.'cache';
        } else if (!(is_string($dir) && is_dir($dir))) {
            $dir = sys_get_temp_dir();
        }
        if (empty($prefix)) {
            $prefix = strval(mt_rand(100,999));
        }
        $file = tempnam($dir, $prefix);
        if ($file !== false) {
            return $file;
        }
        $file = tmpfile();
        if ($file !== false) {
            return stream_get_meta_data($file)['uri'];
        }
        error_log('\bff\utils\Files::tempFile: unable to create temporary file');
        return false;
    }

    /**
     * Проверка прав записи в директорию/файл
     * @param array $files список директорий/файлов для проверки наличия прав записи
     * @param bool $onlyFordev выполнять проверку только при включенном режиме разработчика
     */
    public static function writableCheck(array $files, $onlyFordev = true)
    {
        if ($onlyFordev && !FORDEV) {
            return;
        }
        if (empty($files)) {
            return;
        }
        foreach ($files as $v) {
            if (empty($v)) continue;
            if (!file_exists($v)) {
                \bff::errors()->set('Проверьте наличие директории/файла "'.str_replace(PATH_BASE, DIRECTORY_SEPARATOR, $v).'"')->autohide(false);
            } else {
                if (!is_writable($v)) {
                    \bff::errors()->set('Недостаточно прав на запись в '.(is_dir($v) ? 'директорию' : 'файл').' "'.str_replace(PATH_BASE, DIRECTORY_SEPARATOR, $v).'"')->autohide(false);
                }
            }
        }
    }

}