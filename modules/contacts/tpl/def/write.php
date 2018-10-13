<?php
    /**
     * Форма контактов
     * @var $this Contacts
     * @var $breadcrumb string хлебная крошка или пустая строка
     * @var $captcha_on boolean включена ли капча
     * @var $user array данные о текущем пользователе: name, email
     * @var $types array варианты темы сообщения
     */
    tpl::includeJS('contacts.form', false, 3);
    $user = HTML::escape($user);
    $captcha_url = ( $captcha_on ? tpl::captchaURL('math', array('bg'=>'FFFFFF')) : '' );
?>
<div class="row-fluid">
    <div class="l-page span12">
        <div class="l-view">

            <?= tpl::getBreadcrumbs(array( array('title'=>(!empty($breadcrumb) ? $breadcrumb : config::get('contacts_form_title_'.LNG, _t('contacts', 'Контакты'))), 'active'=>true) )); ?>

            <div class="txt-content ">
                <h1><?= config::get('contacts_form_title_'.LNG, _t('contacts', 'Контакты')) ?></h1>
                <?= config::get('contacts_form_text_'.LNG) ?>
                <form id="j-contacts-form" action="" class="contacts-form mrgt40 form-horizontal">
                    <div class="l-content__title mrgb20"><?= config::get('contacts_form_title2_'.LNG) ?></div>
                    <div class="control-group">
                        <label for="j-contacts-form-name" class="control-label"><?= _t('contacts', 'Ваше имя') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="text" class="j-required" name="name" id="j-contacts-form-name" value="<?= $user['name'] ?>" maxlength="70" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="j-contacts-form-email" class="control-label"><?= _t('contacts', 'Ваш e-mail адрес') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="email" class="j-required" name="email" id="j-contacts-form-email" value="<?= $user['email'] ?>" maxlength="70" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="j-contacts-form-subject" class="control-label"><?= _t('contacts', 'Выберите тему') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <select name="ctype" class="j-required" id="j-contacts-form-subject"><?= $types ?></select>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="j-contacts-form-message" class="control-label"><?= _t('contacts', 'Сообщение') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <textarea name="message" class="input-block-level j-required" id="j-contacts-form-message" rows="6" autocapitalize="off"></textarea>
                        </div>
                    </div>

                    <? if( ! User::id() && $captcha_on ) { ?>
                        <?php if (Site::captchaCustom('contacts-write')) { ?>
                            <div class="control-group">
                                <?php bff::hook('captcha.custom.view', 'contacts-write', __FILE__, ['wrap'=>'<div class="controls">{form}</div>']); ?>
                            </div>
                        <?php } else { ?>
                            <div class="control-group">
                                <label for="j-contacts-form-captcha" class="control-label"><?= _t('contacts', 'Введите результат') ?></label>
                                <div class="controls">
                                    <input type="text" name="captcha" class="j-required j-captcha" id="j-contacts-form-captcha" pattern="[0-9]*" />
                                    <img src="<?= $captcha_url ?>" style="cursor: pointer;" onclick="jContactsForm.refreshCaptha();" id="j-contacts-form-captcha-code" alt="" />
                                </div>
                            </div>
                        <?php } ?>
                    <? } ?>

                    <div class="control-group">
                        <div class="controls"><button type="submit" name="" class="btn btn-info"><i class="fa fa-envelope white"></i> <?= _t('contacts', 'Отправить сообщение') ?></button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jContactsForm.init(<?= func::php2js(array(
            'captcha' => !empty($captcha_on),
            'captcha_url' => $captcha_url,
            'submit_url' => Contacts::url('form'),
            'lang' => array(
                'email' => _t('', 'E-mail адрес указан некорректно'),
                'message' => _t('contacts', 'Текст сообщения слишком короткий'),
                'captcha' => _t('', 'Введите результат с картинки'),
                'success' => _t('contacts', 'Ваше сообщение успешно отправлено.<br/>Мы постараемся ответить на него как можно скорее.'),
            ),
        )) ?>);
    });
<? js::stop(); ?>
</script>