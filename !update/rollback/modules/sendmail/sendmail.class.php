<?php

class Sendmail_ extends SendmailBase
{
    /**
     * CronOnce: Массовая рассылка писем
     * @param $params $params['id'] ID рассылки
     */
    public function cronMassendOnce($params)
    {
        if (!bff::cron()) {
            return;
        }
        if (empty($params) || empty($params['id'])) return;
        $massendID = $params['id'];

        do {
            # получаем информацию о рассылке
            $massend = $this->model->massendData($massendID);
            if (empty($massend)) {
                break;
            }

            switch ($massend['status']) {
                case static::STATUS_PROCESSING:
                case static::STATUS_FINISHED:
                case static::STATUS_PAUSED:
                    break 2;
                case static::STATUS_SCHEDULED:
                    # задержка старта рассылки
                    if (strtotime($massend['started']) > time()) {
                        bff::cronManager()->executeOnce('sendmail', 'cronMassendOnce', array('id' => $massendID), $massendID);
                        break 2;
                    }

                    $this->model->massendSave($massendID, array(
                        'status'    => static::STATUS_PROCESSING,
                        'pid'       => getmypid(),
                        'started'   => date('Y-m-d H:i:s')));
                    break;
                case static::STATUS_PAUSE_BEGIN:
                    $this->model->massendSave($massendID, array(
                        'status'    => static::STATUS_PAUSED,
                        'pid'       => 0,
                        'started'   => date('Y-m-d H:i:s'),
                        'finished'  => date('Y-m-d H:i:s'),
                        ));
                    break 2;
                case static::STATUS_CANCEL:
                    $this->model->massendDelete($massendID);
                    break 2;
                default:
                    bff::log('incorrect status: '.$massend['status'] , Logger::INFO, 'cron.log');
                    break 2;
            }

            $settings = func::unserialize($massend['settings']);
            if ($settings === false || empty($settings)) {
                bff::log('corrupted massend-settings data (id=' . $massendID . ')');
                $this->model->massendSave($massendID, array('status' => static::STATUS_FINISHED, 'pid' => 0, 'finished'  => date('Y-m-d H:i:s')));
                break;
            }

            # формируем текст письма для всех локалей
            $isHTML = (is_null($settings['is_html']) ? NULL : !empty($settings['is_html']));
            $wrapperID = (!empty($settings['wrapper_id']) ? intval($settings['wrapper_id']) : 0);

            $lngDef = $this->locale->getDefaultLanguage();
            $templates = array();
            foreach ($this->locale->getLanguages() as $l) {
                $templates[$l] = $this->getMailTemplate('sendmail_massend', array(
                    'msg' => ! empty($settings['body'][$l]) ? $settings['body'][$l] : $settings['body'][$lngDef]
                ), $l, $isHTML, $wrapperID);
            }

            $success = $massend['success'];
            $fail = $massend['fail'];
            $tm = 0;
            $status = static::STATUS_FINISHED;
            # проходимя итеративно по всем подписавшимся пользователям
            $this->model->massendReceiversIterator($massendID, function($row) use(& $settings, & $templates, & $tm, & $success, & $fail, & $status, $massendID, $lngDef) {
                if ($status == static::STATUS_PAUSED) return false;

                # раз в 10 секунд обновляем в БД статистику и анализируем состояние
                if (time() - $tm > 10) {
                    $tm = time();
                    $data = $this->model->massendData($massendID, array('status'));
                    switch ($data['status']) {
                        case static::STATUS_PAUSE_BEGIN:
                            $status = static::STATUS_PAUSED;
                            return false;
                        case static::STATUS_CANCEL:
                            $status = false;
                            $this->model->massendDelete($massendID);
                            return false;
                    }
                    $this->model->massendSave($massendID, array(
                        'success'   => $success,
                        'fail'      => $fail,
                    ));
                }

                # макрос ФИО
                $replace = array('{fio}' => $row['name']);

                # макрос отписки
                $hash = Users::userHashGenerate($row['user_id'], $massendID);
                $replace['{unsubscribe}'] = Users::url('unsubscribe', array('h'=>$hash));

                # берем шаблон для языка указаного пользователем
                $l = ! empty($row['lang']) ? $row['lang'] : $lngDef;
                $subject = strtr( ! empty($settings['subject'][ $l ]) ? $settings['subject'][ $l ] : $settings['subject'][ $lngDef ], $replace);
                $body = strtr( ! empty($templates[ $l ]) ? $templates[ $l ]['body'] : $templates[ $l ]['body'], $replace);
                $from = (!empty($settings['from']) ? $settings['from'] : '');
                $fromName = (!empty($settings['fromname'][ $l ]) ? $settings['fromname'][ $l ] : '');
                if (empty($fromName)) {
                    $fromName = (!empty($settings['fromname'][ $lngDef ]) ? $settings['fromname'][ $lngDef ] : '');
                }
                $customHeaders = array(
                    'Precedence' => 'bulk', # индикатор массовой рассылки для Google
                );

                # ставим отметку, получатель обработан
                $update = array('processed' => 1);
                try {
                    $result = $this->sendMail($row['email'], $subject, $body, $from, $fromName, $customHeaders);
                } catch (\Exception $e) {
                    bff::log(__FUNCTION__.' Exception: '.$e->getMessage());
                    $result = false;
                }

                if ($result) {
                    $success++;
                    # отметка успешно
                    $update['success'] = 1;
                } else {
                    $fail++;
                }
                $this->model->massendReceiverUpdate($massendID, $row['user_id'], $update);
                usleep(250000);
                return true;
            });

            if ($status) {
                $this->model->massendSave($massendID, array(
                    'status'    => $status,
                    'pid'       => 0,
                    'success'   => $success,
                    'fail'      => $fail,
                    'finished'  => date('Y-m-d H:i:s'),
                ));
            }

        } while(false);
    }

    /**
     * CronOnce: Тестовая рассылка
     * @param $params
     */
    public function cronMassendTest($params)
    {
        if (empty($params['receivers'])) return;
        extract($params, EXTR_REFS);
        foreach ($receivers as $v) {
            $this->sendMail($v, ! empty($subject) ? $subject : 'subject', ! empty($body) ? $body : 'body', ! empty($from) ? $from : 'from', ! empty($fromname) ? $fromname : 'fromname');
        }
    }

}