<?php namespace bff\external;

require_once modification(PATH_CORE . 'external/phpmailer/class.phpmailer.php');

class Mail extends \PHPMailer
{
    function __construct($exceptions = false)
    {
        //parent::__construct($exceptions);

        $config = \config::sys(array(), array(), 'mail', true);
        $config = \bff::filter('mail.config', $config);

        $this->From = $config['noreply'];
        $this->FromName = $config['fromname'];
        $this->CharSet = 'UTF-8';
        $this->XMailer = ' ';
        $this->Host = '';
        $this->Hostname = SITEHOST;

        $this->isHTML(true);

        $this->isMail();

        switch ($config['method']) {
            case 'sendmail':
            {
                $this->isSendmail();
            }
            break;
            case 'smtp':
            {
                if (empty($config['smtp'])) {
                    break;
                }

                require_once modification(PATH_CORE . 'external' . DS . 'phpmailer' . DS . 'class.smtp.php');
                $this->isSMTP();
                $this->SMTPKeepAlive = true;
                $config = array_merge(array(
                        'host' => 'localhost',
                        'port' => 25,
                        'user' => '',
                        'pass' => '',
                        'secure' => '',
                        'debug' => false,
                    ), $config['smtp']
                );

                if ( ! empty($config['secure'])) {
                    $this->SMTPSecure = strval($config['secure']);
                }

                $this->Host = $config['host'] . ':' . intval($config['port']);
                $this->SMTPAuth = !empty($config['user']);
                if ($this->SMTPAuth) {
                    $this->Username = $config['user'];
                    $this->Password = $config['pass'];
                }
                if (!empty($config['debug'])) {
                    $this->SMTPDebug = 2;
                    $this->Debugoutput = (is_string($config['debug']) ? $config['debug'] : 'error_log');
                }

            }
            break;
        }
    }

    function send()
    {
        $timeStart = microtime(true);
        $result = parent::send();
        $timeFinish = microtime(true) - $timeStart;
        if (\bff::hooksAdded('mail.sended')) {
            \bff::hook('mail.sended', array(
                'to' => array_keys($this->all_recipients),
                'subject' => $this->Subject,
                'body' => $this->Body,
                'from' => $this->From,
                'fromName' => $this->FromName,
                'result' => $result,
                'time' => $timeFinish,
            ), $this);
        }
        return $result;
    }
}