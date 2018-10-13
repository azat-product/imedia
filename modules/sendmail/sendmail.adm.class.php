<?php

/**
 * Права доступа группы:
 *  - sendmail: Работа с почтой
 *      - massend: Массовая рассылка
 */
class Sendmail_ extends SendmailBase
{
    //---------------------------------------------------------------
    // рассылка писем

    public function massend_form()
    {
        if (!$this->haveAccessTo('massend')) {
            return $this->showAccessDenied();
        }

        $aData = array();
        $aData['noreply'] = config::sysAdmin('mail.noreply');
        $aData['fromname'] = config::sysAdmin('mail.fromname');
        $aData['wrappers'] = $this->model->wrappersOptions(0, _t('sendmail', '- Без шаблона -'));
        return $this->viewPHP($aData, 'admin.massend');
    }

    public function massend_listing()
    {
        if (!$this->haveAccessTo('massend')) {
            return $this->showAccessDenied();
        }

        $aData['items'] = $this->db->select('SELECT * FROM ' . TABLE_MASSEND . ' ORDER BY id DESC');

        $aData['list'] = $this->viewPHP($aData, 'admin.massend.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array('list' => $aData['list']));
        }

        return $this->viewPHP($aData, 'admin.massend.listing');
    }

    public function ajax()
    {
        if (!$this->haveAccessTo('massend')) {
            return $this->showAccessDenied();
        }

        $aResponse = array();
        $action = $this->input->getpost('act', TYPE_STR);
        switch ($action) {
            case 'massend-init': # инициализация рассылки
            {

                $this->input->postm(array(
                        'test'      => TYPE_BOOL,
                        'from_cron' => TYPE_BOOL,
                        'from'      => TYPE_STR,
                        'is_html'   => TYPE_BOOL,
                        'wrapper_id' => TYPE_UINT,
                        'shop_only' => TYPE_BOOL,
                ), $p);
                $this->input->postm_lang(array(
                    'fromname'  => TYPE_STR,
                    'subject'   => TYPE_STR,
                    'body'      => TYPE_STR,
                ), $p);
                extract($p, EXTR_REFS);

                foreach ($body as & $v) {
                    if (!$is_html) {
                        $v = nl2br($v);
                    }
                    $v = preg_replace("@<script[^>]*?>.*?</script>@si", '', $v);
                } unset($v);

                // set_time_limit(0);
                ignore_user_abort(true);

                $lngDef = $this->locale->getDefaultLanguage();

                if (!(!empty($from) && !empty($subject[$lngDef]) && !empty($body[$lngDef]))) {
                    $this->errors->impossible();
                    break;
                }

                if ($test) {
                    $aReceiversTest = $this->input->post('receivers_test', TYPE_STR);
                    $aReceiversTest = explode(',', $aReceiversTest);
                    if (!empty($aReceiversTest)) {
                        $aReceivers = array_map('trim', $aReceiversTest);
                    }

                    $nSendSuccess = 0;
                    $timeStart = microtime(true); # start
                    $nReceiversTotal = sizeof($aReceivers);
                    $aReceiversSended = array();

                    # формируем текст письма на основе шаблона "massend"
                    $aTemplate = $this->getMailTemplate('sendmail_massend', array('msg' => $body[$lngDef]), $lngDef, $is_html, $wrapper_id);

                    if ($from_cron) {
                        bff::cronManager()->executeOnce('sendmail', 'cronMassendTest', array(
                            'receivers' => $aReceivers,
                            'subject'   => $subject[$lngDef],
                            'body'      => $aTemplate['body'],
                            'from'      => $from,
                            'fromname'  => $fromname[$lngDef],
                        ));
                        $this->ajaxResponse(array('success' => 1, 'resultHTML' => '<div class="alert alert-info">Крон задача запланирована</div>'));
                    }

                    for ($i = 0; $i < $nReceiversTotal; $i++) {
                        if (BFF_LOCALHOST) {
                            bff::log(array('to'=>$aReceivers[$i], 'from'=>$from, 'fromName'=>$fromname[$lngDef], 'subject'=>$subject[$lngDef], 'body'=>$aTemplate['body']));
                            $res = true;
                        } else {
                            $res = $this->sendMail($aReceivers[$i], $subject[$lngDef], $aTemplate['body'], $from, $fromname[$lngDef]);
                        }
                        if ($res) {
                            $nSendSuccess++;
                        }
                    }

                    $timeTotal = (microtime(true) - $timeStart); # stop

                    $this->ajaxResponse(array(
                        'total'      => $nReceiversTotal,
                        'success'    => $nSendSuccess,
                        'failed'     => ($nReceiversTotal - $nSendSuccess),
                        'sended'     => $aReceiversSended,
                        'time_total' => sprintf('%0.2f', $timeTotal),
                        'time_avg'   => sprintf('%0.2f', (!empty($nReceiversTotal) ? ($timeTotal / $nReceiversTotal) : 0)),
                        'res'        => $this->errors->no(),
                    ));
                } else {
                    # формируем список получателей, исключая заблокированных/неактивированных/не подписавшихся на рассылку
                    $massendID = $this->model->massendStart(array(
                        'from'       => $from,
                        'fromname'   => $fromname,
                        'subject'    => $subject,
                        'body'       => $body,
                        'is_html'    => $is_html,
                        'wrapper_id' => $wrapper_id,
                        'shop_only'  => $shop_only,
                    ), Users::ENOTIFY_NEWS);
                    if( ! $massendID){
                        $this->errors->set(_t('sendmail', 'Ошибка инициализации рассылки'));
                        break;
                    }
                    bff::cronManager()->executeOnce('sendmail', 'cronMassendOnce', array('id' => $massendID), $massendID);
                }
            }
            break;
            case 'massend-delete': # удаление рассылки
            {

                $massendID = $this->input->post('rec', TYPE_UINT);
                if (!$massendID) {
                    $this->errors->impossible();
                    break;
                }

                $data = $this->model->massendData($massendID, array('pid', 'started'));
                if ($data['pid']) {
                    if (posix_kill($data['pid'], 0)) {
                        $this->model->massendSave($massendID, array('status' => static::STATUS_CANCEL));
                    } else {
                        $tm = time() - strtotime($data['started']);
                        if ($tm > 0 && $tm < 3600) {
                            $this->model->massendSave($massendID, array('status' => static::STATUS_CANCEL));
                        } else {
                            $this->model->massendDelete($massendID);
                        }
                    }
                } else {
                    $this->model->massendDelete($massendID);
                }
            }
            break;
            case 'massend-pause': # остановка рассылки
            {

                $massendID = $this->input->post('id', TYPE_UINT);
                if (!$massendID) {
                    $this->errors->impossible();
                    break;
                }

                $data = $this->model->massendData($massendID, array('status'));
                if (in_array($data['status'], array(static::STATUS_CANCEL, static::STATUS_FINISHED))) {
                    $this->errors->reloadPage();
                    break;
                }
                $this->model->massendSave($massendID, array('status' => static::STATUS_PAUSE_BEGIN));
            }
            break;
            case 'massend-continue': # возобновление рассылки
            {

                $massendID = $this->input->post('id', TYPE_UINT);
                if (!$massendID) {
                    $this->errors->impossible();
                    break;
                }

                $data = $this->model->massendData($massendID, array('status'));
                if ( ! in_array($data['status'], array(static::STATUS_PAUSED, static::STATUS_SCHEDULED, static::STATUS_PAUSE_BEGIN))) {
                    $this->errors->reloadPage();
                    break;
                }
                $this->model->massendSave($massendID, array(
                    'status'    => static::STATUS_SCHEDULED,
                    'started'   => date('Y-m-d H:i:s', strtotime('+ '.static::delay().' minutes')),
                ));
                bff::cronManager()->executeOnce('sendmail', 'cronMassendOnce', array('id' => $massendID), $massendID);
            }
            break;
            case 'massend-info': # получение сведений о рассылке
            {
                $nMassendID = $this->input->get('id', TYPE_UINT);
                if (!$nMassendID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->massendData($nMassendID);
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aSettings = func::unserialize($aData['settings']);
                foreach (array('from','subject','body','time_total','time_avg','wrapper_id', 'shop_only') as $k) {
                    if (isset($aSettings[$k])) $aData[$k] = $aSettings[$k];
                }
                if (in_array($aData['status'], array(static::STATUS_PROCESSING, static::STATUS_FINISHED, static::STATUS_PAUSED))) {
                    if (empty($aData['time_total'])) {
                        $finished = $aData['finished'] != '0000-00-00 00:00:00' ? strtotime($aData['finished']) : time();
                        $started = strtotime($aData['started']);
                        $aData['time_total'] = $finished - $started;
                    }
                    if (empty($aData['time_avg'])) {
                        $total = $aData['success'] + $aData['fail'];
                        $aData['time_avg'] = $total > 0 ? $aData['time_total'] / $total : 0;
                    }
                }
                $lngDef = $this->locale->getDefaultLanguage();
                if (is_array($aData['subject'])) {
                    $aData['subject'] = $aData['subject'][$lngDef];
                }
                if (is_array($aData['body'])) {
                    $aData['body'] = $aData['body'][$lngDef];
                }

                echo $this->viewPHP($aData, 'admin.massend.info');
                exit;
            }
            break;
            default:
            {
                bff::hook('sendmail.admin.ajax.default.action', $action, $this);
                $this->errors->impossible();
            }
        }

        $this->ajaxResponseForm($aResponse);
    }

}