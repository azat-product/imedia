<?php

  define('BFF_SESSION_START', 0);
  require (__DIR__ . '/../bff.php');
  require PATH_CORE.'captcha/captcha.math.php';

/*
    <img src="<?= tpl::captchaURL() ?>" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&r='+Math.random(1));" />
    
    для проверки:
    if ( ! CCaptchaProtection::isCorrect($this->input->get('captcha'))) {
        $this->errors->set( _t('captcha', 'Результат с картинки указан некорректно') );
    }

*/