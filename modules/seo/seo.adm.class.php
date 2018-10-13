<?php

class SEO_ extends SEOBase
{
    /**
     * Настройки
     * @return string
     */
    public function settings()
    {
        if ( ! $this->haveAccessTo('seo')) {
            return $this->showAccessDenied();
        }
        $alerts = array();
        $robotsEnabled = $this->robotsEnabled(true, $alerts);

        if ($this->isPOST()) {
            $act = $this->input->postget('act', TYPE_STR);
            $response = array();
            switch($act) {
                case 'delete-robot':

                    $path = PATH_PUBLIC.static::ROBOTS_FILE;
                    @unlink($path);
                    if (file_exists($path)) {
                        if ($this->errors->no()) {
                            $this->errors->set(_t('seo', 'Не удалось удалить файл [file]', ['file'=>static::ROBOTS_FILE]));
                        }
                    } else {
                        $response['msg'] = _t('seo', 'Файл [name] был успешно удален', ['name'=>static::ROBOTS_FILE]);
                    }
                    break;
                case 'delete-sitemap':

                    $path = PATH_PUBLIC.static::SITEMAP_FILE;
                    @unlink($path);
                    if (file_exists($path)) {
                        if ($this->errors->no()) {
                            $this->errors->set(_t('seo', 'Не удалось удалить файл [name]', ['name'=>static::SITEMAP_FILE]));
                        }
                    } else {
                        $response['msg'] = _t('seo', 'Файл [name] был успешно удален', ['name'=>static::SITEMAP_FILE]);
                    }
                    break;
                case 'refresh-sitemap':

                    if (static::sitemapRefresh()) {
                        $response['msg'] = _t('seo', 'Обновление файла будет выполнено в течение нескольких минут');
                    } else {
                        $this->errors->set(_t('dev', 'Проверьте выполняется ли запуск Cron-менеджера и повторите попытку'));
                    }
                    break;
                default:
                    if ( ! $this->input->post('save', TYPE_BOOL)) break;
                    if ( ! $robotsEnabled) {
                        $this->errors->impossible();
                        break;
                    }

                    $data = $this->input->postm(array(
                        'robots_template' => TYPE_STR,
                    ));
                    bff::hook('seo.admin.settings.submit', array('data' => &$data));

                    $this->configSave($data);

                    $this->adminRedirect(Errors::SUCCESS, 'settings');
                    $response = false;
                    break;
            }
            if ($response !== false) {
                $this->ajaxResponseForm($response);
            }
        }

        $data = $this->configLoad();

        $sitemap = 'sitemap.xml';
        $sitemapPath = bff::path('').$sitemap;
        $data['sitemapUrl'] = bff::url('').$sitemap;

        if ($robotsEnabled) {
            $sitemapPath = static::pathSEO().$sitemap;
            $data['sitemapUrl'] = static::urlSEO().$sitemap;
        }

        $data['sitemapExists'] = file_exists($sitemapPath);
        if ($data['sitemapExists']) {
            $data['sitemapModified'] = filemtime($sitemapPath);
        }

        $data['robotsEnabled'] = $robotsEnabled;
        $data['alerts'] = $alerts;

        return $this->viewPHP($data, 'admin.settings');
    }


}