<?php namespace bff\external;

if ( ! class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once modification(PATH_CORE . 'external' . DS . 'phpmailer' . DS . 'src' . DS . 'Exception.php');
    require_once modification(PATH_CORE . 'external' . DS . 'phpmailer' . DS . 'src' . DS . 'PHPMailer.php');
    require_once modification(PATH_CORE . 'external' . DS . 'phpmailer' . DS . 'src' . DS . 'SMTP.php');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail extends PHPMailer
{
    public function __construct($exceptions = null)
    {
        parent::__construct($exceptions);

        # Mail settings:
        $config = \config::sys(array(), array(), 'mail', true);
        $config['noreply']  = \config::sysAdmin('mail.noreply', 'noreply@'.SITEHOST, TYPE_NOTAGS);
        $config['admin']    = \config::sysAdmin('mail.admin', 'admin@'.SITEHOST, TYPE_NOTAGS);
        $config['fromname'] = \config::sysAdmin('mail.fromname', SITEHOST, TYPE_NOTAGS);
        $config['method']   = \config::sysAdmin('mail.method', 'mail', TYPE_NOTAGS);
        $smtp = array_merge(array(
            'host' => 'localhost',
            'port' => 25,
            'user' => '',
            'pass' => '',
            'secure' => '',
            'debug' => false,
        ), ( ! empty($config['smtp']) ? $config['smtp'] : array()));
        $smtp['host'] = \config::sysAdmin('mail.smtp.host', $smtp['host'], TYPE_NOTAGS);
        $smtp['port'] = \config::sysAdmin('mail.smtp.port', $smtp['port'], TYPE_UINT);
        $smtp['user'] = \config::sysAdmin('mail.smtp.user', $smtp['user'], TYPE_NOTAGS);
        $smtp['pass'] = \config::sysAdmin('mail.smtp.pass', $smtp['pass'], TYPE_PASS);
        $smtp['secure'] = \config::sysAdmin('mail.smtp.secure', $smtp['secure'], TYPE_NOTAGS);
        $config['smtp'] = $smtp;

        $config = \bff::filter('mail.config', $config);

        $this->From = $config['noreply'];
        $this->FromName = $config['fromname'];
        $this->CharSet = 'UTF-8';
        $this->XMailer = ' ';
        $this->Host = '';
        $this->Hostname = SITEHOST;

        # HTML message
        $this->isHTML(true);

        # Default method: mail
        $this->isMail();

        # Errors: EN => RU
        if (\bff::locale()->getCurrentLanguage() === 'ru') {
            $this->setLanguage('ru');
        }

        switch ($config['method']) {
            case 'sendmail':
            {
                $this->isSendmail();
            } break;
            case 'smtp':
            {
                $this->isSMTP();
                $this->SMTPKeepAlive = true;
                $this->SMTPAutoTLS = false;

                if ( ! empty($smtp['secure'])) {
                    $this->SMTPSecure = strval($smtp['secure']);
                }

                $this->Host = $smtp['host'] . ':' . intval($smtp['port']);
                $this->SMTPAuth = !empty($smtp['user']);
                if ($this->SMTPAuth) {
                    $this->Username = $smtp['user'];
                    $this->Password = $smtp['pass'];
                }
                if ( ! empty($smtp['debug'])) {
                    $this->SMTPDebug = 2;
                    $this->Debugoutput = (is_string($smtp['debug']) ? $smtp['debug'] : 'error_log');
                }
            } break;
        }
    }

    /**
     * Отправка сообщения
     * @return boolean
     */
    public function send()
    {
        # Send
        $timeStart = microtime(true);
        try {
            $result = parent::send();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
        $timeFinish = microtime(true) - $timeStart;

        # Hooks
        if (\bff::hooksAdded('mail.sended')) {
            \bff::hook('mail.sended', array(
                'to' => array_keys($this->getAllRecipientAddresses()),
                'subject' => $this->Subject,
                'body' => $this->Body,
                'from' => $this->From,
                'fromName' => $this->FromName,
                'result' => $result,
                'time' => $timeFinish,
            ), $this);
        }

        # Result
        return $result;
    }
}