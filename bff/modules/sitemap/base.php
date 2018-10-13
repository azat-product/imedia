<?php

use bff\utils\Files;

abstract class SitemapModuleBase extends Module
{
    /** @var SitemapModelBase */
    public $model = null;
    protected $securityKey = 'a4940a20fa52044c33174595811706c3';
    const ROOT_ID = 1;
    const XML_EXPORT = false;

    const MACROS_SITEHOST = '{sitehost}';
    const MACROS_SITEURL = '{siteurl}';

    /**
     * Корректировать относительные ссылки
     *  true - добавлять базовый URL (SITEURL), если ссылка начинается с символа "/" (относительная ссылка)
     *  false - не добавлять, оставлять ссылку относительной
     * @var bool
     */
    protected $_correctRelativeLinks = true;

    /**
     * Задействовать настройки мета данных меню и пунктов меню
     * @var bool
     */
    protected $_useMetaSettings = true;

    /**
     * Выполнять подмену макросов перед кешированием
     * @var bool
     */
    protected $_replaceMacrosBeforeCache = true;

    const typeMenu = 1;
    const typePage = 2;
    const typeLink = 3;
    const typeLinkModuleMethod = 4;

    public function init()
    {
        parent::init();
        $this->module_title = _t('sitemap','Карта сайта');
    }

    /**
     * @return Sitemap
     */
    public static function i()
    {
        return bff::module('sitemap');
    }

    /**
     * @return SitemapModel
     */
    public static function model()
    {
        return bff::model('sitemap');
    }

    /**
     * Формируем варианты "Открывать ссылку в..."(targets)
     * @param bool $bOptions
     * @param string $sCurrentTarget текущий(выбранный) target
     * @return array
     */
    protected function getTargets($bOptions = true, $sCurrentTarget = '_self')
    {
        $aTargets = array(
            '_self'  => _t('sitemap','в текущем окне'),
            '_blank' => _t('sitemap','в новом окне'),
        );

        return ($bOptions ? HTML::selectOptions($aTargets, $sCurrentTarget) : $aTargets);
    }

    /**
     * Проверка target на корректность
     * @param string $sTarget @ref
     */
    protected function checkTarget(&$sTarget)
    {
        if (empty($sTarget) || !array_key_exists($sTarget, $this->getTargets(false))) {
            $sTarget = '_self';
        }
    }

    /**
     * Проверка типа раздела на корректность
     * @param int $nType @ref тип раздела
     * @param bool $bAdd при добавлении
     * @return bool
     */
    protected function checkType(&$nType, $bAdd = true)
    {
        if (empty($nType) || !in_array($nType, array(
                    self::typeMenu,
                    self::typePage,
                    self::typeLink,
                    self::typeLinkModuleMethod
                )
            )
        ) {
            $nType = self::typeLink;

            return false;
        }
        if ($bAdd && !FORDEV && ($nType === self::typeLinkModuleMethod)) {
            $nType = self::typeLink;

            return false;
        }

        return true;
    }

    /**
     * Проверяем корректность ссылки указанной для пункта меню
     * @param string $sLink ссылка
     * @param boolean $bRequired ссылка обязательна
     * @return string
     */
    protected function checkLink($sLink, $bRequired = true)
    {
        if (empty($sLink) || $sLink == '#') {
            if ($bRequired) {
                $this->errors->set(_t('sitemap','Укажите корректную ссылку'));
            }
        } else {
            if (($pos = stripos($sLink, self::MACROS_SITEURL)) !== false ||
                ($pos = stripos($sLink, '{siteurl:')) !== false) {
                if ($pos !== 0) {
                    $sLink = substr($sLink, $pos);
                }
            } else if (($pos = stripos($sLink, '{route:')) !== false) {
                // no changes
            } else {
                if (preg_match('/^(http|https|ftp):\/\//xisu', $sLink) !== 1) {
                    if (strpos($sLink, 'www.') === 0) {
                        $sLink = 'http'.(\bff::httpsOnly() ? 's' : '').'://' . $sLink;
                    } else {
                        if ($sLink{0} !== '/') {
                            $sLink = '/' . $sLink;
                        }
                    }
                }
            }
        }

        return $sLink;
    }

    /**
     * Формируем cache-объект для кеширования разделов меню
     * @return Cache
     */
    protected function initCache()
    {
        return Cache::singleton('sitemap', 'file');
    }

    /**
     * Сбрасываем кеш разделов меню
     * @return Cache
     */
    protected function resetCache()
    {
        $this->initCache()->flush('sitemap');
    }

    /**
     * Подготавливаем разделы/пункты меню
     * @param array $aMenu разделы/пункты меню
     * @param string $languageKey ключ языка, по-умолчанию LNG
     * @return array
     */
    protected function prepareMenu($aMenu, $languageKey = LNG)
    {
        $aRes = array();
        $languagePrefix = $this->locale->getLanguageUrlPrefix($languageKey, false);
        foreach ($aMenu as $v) {
            if ($v['type'] == self::typeMenu) {
                if (!empty($v['sub'])) {
                    $v['sub'] = $this->prepareMenu($v['sub'], $languageKey);
                    # добавляем ссылку первого вложенного пункта меню к самому меню
                    if (!empty($v['sub']) && empty($v['link'])) {
                        $firstSub = reset($v['sub']);
                        $v['link'] = $firstSub['link'];
                    }
                }
            } else {
                # подставляем макросы
                if ($this->_replaceMacrosBeforeCache) {
                    $v['link'] = $this->replaceMacros($v['link'], $languageKey);
                }
                # корректируем относительные ссылки (если необходимо)
                if (!empty($v['link']) && $v['link']{0} === '/' && strpos($v['link'], '//') !== 0) {
                    # "/page.html" => "{siteurl}/page.html"
                    $v['link'] = ($this->_correctRelativeLinks ?
                            ($this->_replaceMacrosBeforeCache ? $this->getMacrosReplacement(self::MACROS_SITEURL, $languageKey) : self::MACROS_SITEURL)
                            : $languagePrefix) . '/' . ltrim($v['link'], '/\\ ');
                }
            }
            $key = (!empty($v['keyword']) && !isset($aRes[$v['keyword']]) ? $v['keyword'] : $v['id']);
            $aRes[$key] = $v;
        }

        return $aRes;
    }

    /**
     * Формируем Sitemap.xml (http://ru.wikipedia.org/wiki/Sitemaps)
     * @param string $sMenuKeyword ключ меню
     * @param string $sSitemapPath путь к xml файлу, по-умолчанию: PATH_PUBLIC.'sitemap.xml'
     * @return mixed
     */
    public function generateSitemapXML($sMenuKeyword, $sSitemapPath = '')
    {
        if (empty($sMenuKeyword)) {
            return false;
        }
        if (empty($sSitemapPath)) {
            $sSitemapPath = PATH_PUBLIC . 'sitemap.xml';
        }

        $aMainMenu = $this->model->itemDataByFilter(array('keyword' => $sMenuKeyword));
        if (empty($aMainMenu)) {
            return false;
        }

        $aItems = $this->db->select('SELECT id, link, type, changefreq, priority FROM ' . TABLE_SITEMAP . '
                      WHERE type != :type AND pid != 0
                        AND numleft > :nl AND numright < :nr
                      ORDER BY numleft', array(
                ':type' => self::typeMenu,
                ':nl'   => $aMainMenu['numleft'],
                ':nr'   => $aMainMenu['numright']
            )
        );

        $sContent = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($aItems as $v) {
            $v = $this->prepareMenu($v);
            $sContent .= '<url><loc>' . $v['link'] . '</loc><lastmod>?</lastmod><changefreq>' . $v['changefreq'] . '</changefreq><priority>' . $v['priority'] . '</priority></url>';
        }
        $sContent .= '</urlset>';

        Files::putFileContent($sSitemapPath, $sContent);
    }

    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        $this->resetCache();
    }

    /**
     * Получаем замену макроса по его ключу
     * @param string $key ключ макроса
     * @param string $languageKey ключ языка
     * @return string
     */
    protected function getMacrosReplacement($key, $languageKey = LNG)
    {
        switch ($key) {
            case self::MACROS_SITEURL:
                return SITEURL . bff::locale()->getLanguageUrlPrefix($languageKey, false);
                break;
            case self::MACROS_SITEHOST:
                return SITEHOST . bff::locale()->getLanguageUrlPrefix($languageKey, false);
                break;
        }
        return $key;
    }

    /**
     * Выполняем замену макросов в тексте
     * @param string $text текст
     * @param string $languageKey ключ языка
     * @return string
     */
    protected function replaceMacros($text, $languageKey)
    {
        if (mb_stripos($text, '{') === false) {
            return $text;
        }
        $replace = array(
            self::MACROS_SITEURL  => $this->getMacrosReplacement(self::MACROS_SITEURL, $languageKey),
            self::MACROS_SITEHOST => $this->getMacrosReplacement(self::MACROS_SITEHOST, $languageKey),
        );
        # макрос вида {siteurl:module}
        if (preg_match('/\{siteurl\:([a-z0-9\-]+)\}/iu', $text, $matches) && ! empty($matches[1])) {
            $module = $matches[1];
            if (method_exists($module, 'urlBase')) {
                $replace[$matches[0]] = $module::urlBase($languageKey);
            } else {
                $replace[$matches[0]] = bff::urlBase(false, $languageKey);
            }
        }
        # макрос вида {sitehost:key}
        else if (preg_match('/\{sitehost\:([a-zA-Z0-9\-]+)\}/iu', $text, $matches) && ! empty($matches[1])) {
            switch ($matches[1]) {
                case 'nolang': {
                    $replace[$matches[0]] = SITEHOST;
                } break;
                default: {
                    $replace[$matches[0]] = SITEHOST . bff::locale()->getLanguageUrlPrefix($languageKey, false);
                }
            }
        }
        # макрос вида {route:key}
        else if (mb_stripos(ltrim($text), '{route:') === 0 && preg_match('/\{route\:([a-zA-Z0-9\-\.]+)(\:[a-zA-Z0-9\-]+)?\}/iu', $text, $matches) && ! empty($matches[1])) {
            if ( ! empty($matches[2]) && $matches[2] === ':rel') {
                $replace[$matches[0]] = bff::router()->url($matches[1], [], false);
            } else {
                $replace[$matches[0]] = bff::router()->url($matches[1], [], ['lang' => $languageKey]);
            }
        }
        return strtr($text, $replace);
    }

    /**
     * Выполняем замену макросов во всех пунктах меню (рекурсивно)
     * @param array $menu @ref пункты меню
     * @param string $languageKey ключ языка
     */
    protected function replaceMacrosRecursive(array & $menu, $languageKey)
    {
        foreach ($menu as &$v) {
            if ($v['type'] == self::typeMenu) {
                if (!empty($v['sub'])) {
                    $this->replaceMacrosRecursive($v['sub'], $languageKey);
                }
            }

            # подставляем макросы
            $v['link'] = $this->replaceMacros($v['link'], $languageKey);
            if (mb_strpos($v['link'], '//') === 0) {
                $v['link'] = Request::scheme() . '://' . mb_substr($v['link'], 2);
            }

        }
        unset($v);
    }
}