<?php
    /**
     * Форма отправки сообщения
     * @var $this Users
     * @var $form_id string ID формы
     * @var $captcha boolean включена ли капча
     */
    tpl::includeJS('users.write.form', false, 2);
?>
<? if( ! User::id() ) { ?><input type="email" name="email" class="j-required j-email" value="" placeholder="<?= _te('users', 'Ваш email-адрес') ?>" maxlength="100" /><? } ?>
<textarea name="message" class="j-required j-message" placeholder="<?= _te('users', 'Текст вашего сообщения') ?>" autocapitalize="off"></textarea>
<? if ($captcha) { ?>
    <?php if (Site::captchaCustom('users-write-form')) { ?>
        <div class="v-descr_contact__form_captcha pull-left">
            <?php bff::hook('captcha.custom.view', 'users-write-form', __FILE__); ?>
        </div>
    <?php } else { ?>
        <div class="v-descr_contact__form_captcha pull-left">
            <input type="text" name="captcha" class="j-required" pattern="[0-9]*" />
            <img src="" style="cursor: pointer;" data-url="<?= tpl::captchaURL('math', array('key'=>'c2wf', 'bg'=>'F5F5F5', 'rnd'=>'')) ?>" class="j-captcha" alt="" />
        </div>
    <?php } ?>
<? } else if (InternalMail::attachmentsEnabled()) { ?>
<div class="v-descr_contact__form_file attach-file j-attach-block pull-left">
    <div class="upload-btn j-upload">
        <span class="upload-mask">
            <input type="file" name="attach" class="j-upload-file" />
        </span>
        <a href="#" onclick="return false;" class="ajax"><?= _t('users', 'Прикрепить файл') ?></a>
    </div>
    <div class="j-cancel hide">
        <span class="j-cancel-filename"></span>
        <a href="#" class="ajax pseudo-link-ajax ajax-ico j-cancel-link"><i class="fa fa-times"></i> <?= _t('users', 'Удалить') ?></a>
    </div>
</div>
<? } ?>
<button type="submit" class="btn btn-info j-submit"><i class="fa fa-envelope white"></i> <?= _t('users', 'Отправить') ?></button>
<div class="clearfix"></div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jUsersWriteForm.init(<?= func::php2js(array(
            'lang'=>array(
                'email' => _t('','E-mail адрес указан некорректно'),
                'message' => _t('users','Сообщение слишком короткое'),
                'success' => _t('users','Сообщение было успешно отправлено'),
            ),
            'form_id' => '#'.$form_id,
            'captcha' => $captcha,
        )) ?>);
    });
<? js::stop(); ?>
</script>