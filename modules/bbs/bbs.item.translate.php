<?php

/**
 * Компонент автоматического перевода объявлений
 * @version 0.11
 * @modified 16.feb.2016
 *
 * Google Translate: https://cloud.google.com/translate/pricing
 */

class BBSTranslate_ extends Component
{
    public static function i()
    {
        static $i;
        if ( ! isset($i)) {
            $i = new BBSTranslate();
            $i->init();
        }
        return $i;
    }

    /**
     * Перевод массива строк
     * @param array $data данные для перевода array('field' => 'hello world');
     * @param string $lang исходный язык, например 'en'
     * @param array $languages языки на которые необходимо выполнить перевод (если не указано => оставшиеся кроме $lang)
     * @return array переведенные данные для оставшихся локалей  array('ru' => array('field' => 'переводенный текст'))
     *     или false - ошибка
     */
    public function translate($data, $lang, $languages = array())
    {
        if (empty($data)) return array();

        $provider = config::sysAdmin('bbs.translate', '', TYPE_NOTAGS);
        if (empty($provider) || ! method_exists($this, $provider)) {
            if (!empty($provider)) {
                $filter = bff::filter('bbs.items.translate', $data, $lang, $languages, $provider);
                if ($filter !== $data && !empty($filter) && is_array($filter)) {
                    return $filter;
                }
                $this->log('provider not found "'.$provider.'"');
            }
            return false;
        }

        return $this->$provider($data, $lang, $languages);
    }

    /**
     * Перевод средствами Google Translate
     * @param array $data данные для перевода
     * @param string $lang исходный язык
     * @param array $languages языки на которые необходимо выполнить перевод
     * @return array переведенные данные
     */
    protected function google($data, $lang, $languages = array())
    {

        $key = config::sysAdmin('bbs.translate.google.key', '');
        if (empty($key)) {
            $this->log('google.key "'.$key.'" not found');
            return false;
        }
        if (empty($languages)) {
            $languages = $this->locale->getLanguages();
        }
        $k = array_search($lang, $languages);
        if ($k !== false) {
            unset($languages[$k]);
        }
        if (empty($languages)) {
            $this->log('locale set error');
            return false;
        }

        $q = '';
        $fields = array();
        foreach ($data as $k => $v) {
            $q .= '&q=' . rawurlencode($v);
            $fields[] = $k;
        }
        $result = array();
        foreach ($languages as $v) {
            $post = 'key=' . $key . '&source=' . $lang . '&target=' . $v . $q;
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, 'https://www.googleapis.com/language/translate/v2');
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post);
            curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
            $response = curl_exec($handle);
            if (empty($response)) {
                $this->log('google empty response #1');
                return $result;
            }
            $decoded = json_decode($response, true);
            if (empty($decoded)) {
                $this->log('google empty response #2');
                return $result;
            }
            if (isset($decoded['error'])) {
                $this->log('google translate error - "'.print_r($decoded['error'], true).'"');
                return $result;
            }
            if (isset($decoded['data']['translations'])) {
                foreach ($decoded['data']['translations'] as $kk => $vv) {
                    if (!isset($fields[$kk])) {
                        continue;
                    }
                    if (!isset($vv['translatedText'])) continue;
                    $result[$v][$fields[$kk]] = str_replace('&#39;', '\'', $vv['translatedText']);
                }
            } else {
                $this->log('google data translations not found');
                return $result;
            }
        }

        return $result;
    }

    public function log($message)
    {
        bff::log('BBSTranslate: '.(is_array($message) ? print_r($message, true) : strval($message)));
    }

}