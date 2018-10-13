<?php
/**
 * Форма для обвертки блока с кодом Recaptcha
 * @var $this Plugin_Captcha_recaptcha_p00d266
 * @var $page string страница на которой будет выводиться блок
 * @var $captchaHTML string HTML код Recaptcha
 */
    switch(BFF_PRODUCT):
        case 'do':
            switch($page):
                case 'contacts-write':
                case 'users-auth-register':
                    ?><div class="controls"><?= $captchaHTML ?></div><?php
                    break;
                default:
                    ?><?= $captchaHTML ?><?php
                    break;
            endswitch;
        break;
        default:
            ?><?= $captchaHTML ?><?php
            break;
    endswitch;
