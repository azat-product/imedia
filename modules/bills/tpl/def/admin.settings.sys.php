<?php
/**
 * Системные настройки: Счета
 * @var $this Bills
 * @var $options array
 * @var $settings array
 */
    $extend = array('settings'=>&$settings, 'options'=>&$options);

    # платные услуги
    $settings['services.enabled'] = array(
        'title' => _t('bills','Платные услуги'),
        'type' => TYPE_BOOL,
        'input' => 'select',
        'default' => true,
        'options' => array(
            true => array(
                'title' => _t('','включено'),
                'description' => _t('bills','пользователям сайта доступна возможность оплаты и активации платных услуг'),
            ),
            false => array(
                'title' => _t('','выключено'),
            ),
        ),
    );

    $settings['users.register.money.gift'] = array(
        'title' => _t('users','Подарок на счет'),
        'description' => _t('users','Сумма зачисляемая на счет пользователя в момент регистрации'),
        'group' => _t('users','Регистрация'),
        'type' => TYPE_UINT,
        'input' => 'number',
        'default' => 0,
        'options' => array(
            'min' => array('value' => 0),
            'tip' => Site::currencyDefault(),
        ),
    );

    # Настройки способов оплаты:
    //$this->settingsSystemPaySystemsUser($extend);

    # Настройки встроенных систем оплаты:
    $this->settingsSystemPaySystems($extend);

    $options['priority'] = 4;
    echo Site::i()->settingsSystemForm($this, $settings, $options);