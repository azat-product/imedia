<?php

/**
 * Компонент автоматического обновления курса валют
 */
class SiteCurrencyRate_ extends Component
{
    protected $userErrors = true;
    protected $providers = array();

    public function __construct()
    {
        $this->init();

        $this->providers = bff::filter('currency.rate.auto.providers', array(
            /* ключ валюты => array('method'=>'метод источника', 'title'=>'название источника') */
            'uah' => array('method'=>'bank_gov_ua', 'title'=>'bank.gov.ua'),
            'rub' => array('method'=>'cbr_ru', 'title'=>'cbr.ru'),
            'byn' => array('method'=>'nbrb_by', 'title'=>'nbrb.by'),
            'kzt' => array('method'=>'nationalbank_kz', 'title'=>'nationalbank.kz'),
        ));
    }

    /**
     * Выполняем процесс обновления курса валют
     */
    public function process()
    {
        $provider = $this->provider();
        if (!empty($provider) && method_exists($this, $provider)) {
            $this->$provider();
        }
    }

    /**
     * Получение метода провайдера
     * @return array|boolean
     */
    public function provider()
    {
        $provider = config::sysAdmin('currency.rate.auto.provider', '', TYPE_STR);
        if (empty($provider) || !method_exists($this, $provider)) {
            $default = trim(strtolower(Site::currencyDefault('keyword')));
            $provider = isset($this->providers[$default]) ? $this->providers[$default]['method'] : false;
        }
        return $provider;
    }

    /**
     * Получение списка доступных провайдеров
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Данные о настройках валют
     * @return array
     */
    protected function currencies()
    {
        $data = Site::model()->currencyData(false);
        $default = Site::currencyDefault('id');
        unset($data[$default]);

        $result = array();
        foreach ($data as $v) {
            $code = strtoupper($v['keyword']);
            $result[$code] = array(
                'id'   => $v['id'],
                'code' => $code,
                'rate' => $v['rate'],
            );
        } unset($v);
        return $result;
    }

    /**
     * Обновление курса валют в БД
     * @param array $update
     * @param array $bind
     * @param array $id
     */
    protected function update($update, $bind, $id)
    {
        if (empty($update)) return;

        $bind[':now'] = $this->db->now();
        $this->db->exec('
            UPDATE '.TABLE_CURRENCIES.' 
            SET rate = CASE '.join(' ', $update).' ELSE rate END,
                modified = :now
            WHERE '.$this->db->prepareIN('id', $id), $bind);
    }

    /**
     * Обновление курса валют в БД
     * @param integer $id ID валюты
     * @param $rate $rate курс
     */
    protected function updateRate($id, $rate)
    {
        if (empty($id)) return;

        $updated = $this->db->update(TABLE_CURRENCIES, array(
            'rate' => $rate,
            'modified' => $this->db->now(),
        ), array(
            'id' => $id,
        ));
        if ($updated) {
            \bff::i()->callModules('onCurrencyRateChange', array($id, $rate, array(
                'context' => 'site-currency-rate-autoupdate',
            )));
        }
    }

    /**
     * Источник: bank.gov.ua
     * @return bool
     */
    public function bank_gov_ua()
    {
        $currencies = $this->currencies();
        if (empty($currencies)) {
            return false;
        }

        $dom = new \DOMDocument('1.0','Windows-1251');
        if ($dom->load('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange') !== true) {
            $this->errors->set(_t('site','Ошибка парсинга курсов валют с сайта: [site]', array('site'=>'bank.gov.ua')));
            return false;
        }

        foreach ($dom->getElementsByTagName('currency') as $v)
        {
            $code = false;
            $rate = false;
            foreach ($v->childNodes as $vv) {
                switch ($vv->nodeName) {
                    case 'cc':
                        $code = $vv->nodeValue;
                        if ( ! isset($currencies[$code]))
                            break 2;
                        break;
                    case 'rate':
                        $rate = floatval(str_replace(",", ".", $vv->nodeValue));
                        break;
                }
            }
            if ( ! isset($currencies[$code])) continue;

            $cur = $currencies[$code];
            $cur['rate'] = floatval($cur['rate']);
            if (empty($rate)) continue;
            if (round($cur['rate'], 8) == round($rate, 8)) continue;

            $this->updateRate($cur['id'], $rate);
        }

        return true;
    }

    /**
     * Источник: cbr.ru
     * @return bool
     */
    public function cbr_ru()
    {
        $currencies = $this->currencies();
        if (empty($currencies)) {
            return false;
        }
        $dom = new \DOMDocument('1.0','Windows-1251');
        if ($dom->load('http://www.cbr.ru/scripts/XML_daily.asp') !== true) {
            $this->errors->set(_t('site','Ошибка парсинга курсов валют с сайта: [site]', array('site'=>'cbr.ru')));
            return false;
        }

        foreach ($dom->getElementsByTagName('Valute') as $v)
        {
            $code = false;
            $value = false;
            $nominal = false;
            foreach ($v->childNodes as $vv)
            {
                switch ($vv->nodeName)
                {
                    case 'CharCode':
                        $code = $vv->nodeValue;
                        if ( ! isset($currencies[$code]))
                            break 2;
                        break;
                    case 'Nominal':
                        $nominal = floatval(str_replace(",", ".", $vv->nodeValue));
                        break;
                    case 'Value':
                        $value = floatval(str_replace(",", ".", $vv->nodeValue));
                        break;
                }
            }
            if ( ! isset($currencies[$code])) continue;

            $cur = $currencies[$code];
            $cur['rate'] = floatval($cur['rate']);
            if (empty($value)) continue;
            if (empty($nominal)) continue;

            $rate = $value / $nominal;
            if (round($cur['rate'], 8) == round($rate, 8)) continue;

            $this->updateRate($cur['id'], $rate);
        }

        return true;
    }

    /**
     * Источник: nbrb.by
     * @return bool
     */
    public function nbrb_by()
    {
        $currencies = $this->currencies();
        if (empty($currencies)) {
            return false;
        }
        $data = file_get_contents('http://www.nbrb.by/API/ExRates/Rates?onDate='.date('Y-n-j').'&Periodicity=0');
        $data = json_decode($data, true);
        if (empty($data)) {
            $this->errors->set(_t('site','Ошибка парсинга курсов валют с сайта: [site]', array('site'=>'nbrb.by')));
            return false;
        }

        foreach ($data as $v) {
            if (empty($v['Cur_Abbreviation'])) continue;
            if (empty($v['Cur_Scale'])) continue;
            if (empty($v['Cur_OfficialRate'])) continue;

            $code = $v['Cur_Abbreviation'];
            if ( ! isset($currencies[$code])) continue;

            $scale = floatval($v['Cur_Scale']);
            $val = floatval(str_replace(",", ".", $v['Cur_OfficialRate']));
            $rate = $val / $scale;
            $cur = $currencies[$code];
            $cur['rate'] = floatval($cur['rate']);
            if (empty($rate)) continue;
            if (round($cur['rate'], 8) == round($rate, 8)) continue;

            $this->updateRate($cur['id'], $rate);
        }

        return true;
    }

    /**
     * Источник: nationalbank.kz
     * @return bool
     */
    public function nationalbank_kz()
    {
        $currencies = $this->currencies();
        if (empty($currencies)) {
            return false;
        }
        $dom = new \DOMDocument('1.0','Windows-1251');
        if ($dom->load('http://www.nationalbank.kz/rss/rates_all.xml') !== true) {
            $this->errors->set(_t('site','Ошибка парсинга курсов валют с сайта: [site]', array('site'=>'nationalbank.kz')));
            return false;
        }

        foreach ($dom->getElementsByTagName('item') as $v) {
            $code = false;
            $value = false;
            $quant = false;
            foreach ($v->childNodes as $vv) {
                switch ($vv->nodeName) {
                    case 'title':
                        $code = $vv->nodeValue;
                        if ( ! isset($currencies[$code]))
                            break 2;
                        break;
                    case 'description':
                        $value = floatval(str_replace(",", ".", $vv->nodeValue));
                        break;
                    case 'quant':
                        $quant = floatval(str_replace(",", ".", $vv->nodeValue));
                        break;
                }
            }
            if ( ! isset($currencies[$code])) continue;
            if (empty($value)) continue;
            if (empty($quant)) continue;

            $cur = $currencies[$code];
            $cur['rate'] = floatval($cur['rate']);
            $rate = $value / $quant;
            if (round($cur['rate'], 8) == round($rate, 8)) continue;

            $this->updateRate($cur['id'], $rate);
        }

        return true;
    }

}