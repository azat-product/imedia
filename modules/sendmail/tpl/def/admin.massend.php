<?php
/** @var $this Sendmail */
    $aData = HTML::escape($aData, 'html', array('noreply'));
    $lngDef = $this->locale->getDefaultLanguage();
?>
<script type="text/javascript">
var sendmailMassend = (function(){
    var $form = false, $testReceivers, processing = false;
    var url = '<?= $this->adminLink('ajax&act=massend-init') ?>';

    $(function(){
        $form = $('#ms-form');
        $testReceivers = $form.find('#ms-receivers-test');
        $form.on('click', 'a.ms-receivers-test-example', function(e){ nothing(e);
            var cur = $testReceivers.val();
            if( cur.length > 0 ) cur += ', ';
            $testReceivers.val( cur + $(this).text() );
        });

        if (bff.bootstrapJS()) {
            <? $macroses = array(
                '{fio}'         => _t('', 'ФИО'),
                '{unsubscribe}' => _t('sendmail', 'Отписаться (URL)'),
            );
            foreach ($this->locale->getLanguages() as $l): ?>
            $('#j-massend-link-title-<?= $l ?>').popover({
                trigger: 'click',
                placement: 'bottom',
                container: '#j-massend-link-popover-<?= $l ?>',
                title: '<?= _t('', 'Доступные макросы:'); ?>',
                html: true,
                content: '<? foreach ($macroses as $k => $v){ echo('<div><a href="javascript:" class="ajax" onclick="return sendmailMassend.onLinkMacros(this);">'.$k.'</a> - '.$v.'</div>'); } ?>'
            });
            <? endforeach; ?>
        }
    });

    function init(btn)
    {
        if(processing)
            return false;

        var testing = $form.find('#ms-test').is(':checked');
        //есть ли получатели
        if(testing)
        {
            var res = $.trim( $testReceivers.val() );
            if( ! res.length) {
                bff.error('<?= _t('sendmail', 'Укажите получателей для тестирования'); ?>');
                $testReceivers.focus();
                return false;
            }
        }

        //указан ли отправитель
        var f = $form.find('[name="from"]');
        if(f.val()=='') {
            f.focus();
            return false;
        }

        //проверяем тему сообщения
        var s = $form.find('[name="subject[<?= $lngDef ?>]"]');
        if(s.val()=='') {
            if (s.is(':visible')) {
                s.focus();
            } else {
                bff.error('<?= _t('sendmail', 'Укажите тему для языка [lang]', array('lang'=>$lngDef)) ?>');
            }
            return false;
        }

        //указан текст сообщения
        var b = $form.find('[name="body[<?= $lngDef ?>]"]');
        if(b.val()=='') {
            if (b.is(':visible')) {
                b.focus();
            } else {
                bff.error('<?= _t('sendmail', 'Укажите текст сообщения для языка [lang]', array('lang'=>$lngDef)) ?>');
            }
            return false;
        }

        bff.ajax(url, $form.serialize(), function(data){
            if(data && data.success)
            {
                if(testing)
                {
                    if (data.resultHTML) {
                        $form.find('#ms-result').html(data.resultHTML);
                    } else {
                    $form.find('#ms-result').html('<div class="alert alert-info"><table class="admtbl tdbledit">\
                            <tr><td><b><?= _t('sendmail', 'Результат тестовой рассылки писем:'); ?></b></td><td width="150">&nbsp;</td></tr>\
                            <tr><td><span class="clr-success"><?= _t('sendmail', 'Отправлено:'); ?> </span></td><td><strong>'+data.success+'</strong></td></tr>\
                            <tr><td><span class="clr-error"><?= _t('sendmail', 'Не отправлено:'); ?></span></td><td><strong>'+data.failed+'</strong></td></tr>\
                            <tr><td colspan="2"><hr/></td></tr>\
                            <tr><td><?= _t('sendmail', 'Среднее время отправки письма:'); ?></td><td><strong>'+data.time_avg+'сек.</strong></td></tr>\
                            <tr><td><?= _t('sendmail', 'Общее время отправки:'); ?></td><td><strong>'+data.time_total+'сек.</strong></td></tr>\
                        </table></div>');
                    }
                } else {
                   bff.success('<?= _t('sendmail', 'Рассылка была успешно иницирована'); ?>');
                }
            } else {
                if( ! testing)
                    bff.error('<?= _t('sendmail', 'Возникла ошибка при формировании рассылки'); ?>');
            }
        }, function(p){ $(btn).button((p?'loading':'reset')); processing = p; });

        return true;
    }

    function testMode(inp)
    {
        if(inp.checked) {
            $('#ms-test-settings').show();
            $testReceivers.focus();
        } else {
            $('#ms-test-settings').hide();
        }
    }

    function onLinkMacros(el)
    {
        bff.textInsert($('.j-body:visible:first').get(0), $(el).text());
    }

    return {
        init:init,
        testMode:testMode,
        onLinkMacros:onLinkMacros
    };
}());
</script>

<form method="post" action="" id="ms-form">
<table class="admtbl tbledit">
<tr>
    <td class="row1" width="80"><span class="field-title"><?= _t('', 'От'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="from" style="width:250px;" value="<?= $noreply ?>" placeholder="<?= _te('', 'Email'); ?>" tabindex="1" />
    </td>
</tr>
<?= $this->locale->buildForm($aData, 'massend-form','    
<tr>
    <td class="row1"><span class="field-title">'._t('sendmail', 'Тема').'</span>:</td>
	<td class="row2">
        <input type="text" name="fromname[<?= $key ?>]" style="width:250px; position: absolute; left: 360px; top:12px;" value="<?= $aData[\'fromname\'] ?>" placeholder="'._te('', 'Имя').'" tabindex="1" />
	    <input type="text" name="subject[<?= $key ?>]" class="stretch" value="" tabindex="2" />
	</td>
</tr>
<tr>
	<td class="row1 field-title">
        <a href="javascript:void(0);" class="ajax" id="j-massend-link-title-<?= $key ?>" data-original-title="" title="">'._t('sendmail', 'Сообщение:').'</a><div id="j-massend-link-popover-<?= $key ?>"></div>	
    </td>
	<td class="row2"><textarea name="body[<?= $key ?>]" class="stretch j-body" style="min-height: 150px; height:150px" tabindex="3"></textarea></td>
</tr>
'); ?>
<? bff::hook('sendmail.admin.massend.form') ?>
<tr>
    <td class="row1"></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" name="is_html" tabindex="4" /><?= _t('sendmail', 'Текст сообщения содержит HTML теги:'); ?> <span class="desc"><?= HTML::escape('<div>, <br>, <table>, <body>, <html>') ?></span></label>
    </td>
</tr>
<tr>
    <td class="row1"></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" name="shop_only" value="1"/><?= _t('sendmail', 'Только для пользователей магазинов'); ?></label>
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('sendmail', 'Шаблон:'); ?></td>
    <td class="row2">
        <select name="wrapper_id" style="width: auto; min-width: 150px;"><?= $wrappers ?></select>
    </td>
</tr>

<tr>
    <td class="row1"></td>
    <td class="row2">
        <hr class="cut" />
        <label class="checkbox"><input type="checkbox" name="test" value="1" tabindex="5" id="ms-test" onclick="sendmailMassend.testMode(this);" /><?= _t('sendmail', 'Тестовая рассылка'); ?></label>
        <div id="ms-test-settings" class="relative" style="display:none;">
            <? if (FORDEV): ?><label class="checkbox" style="position: absolute; right: 0; top: 0;"><input type="checkbox" name="from_cron" value="1" />From cron manager</label><? endif; ?>
            <label>
                <?= _t('sendmail', 'Укажите получателей для тестирования:'); ?><br />
                <input type="text" class="stretch" name="receivers_test" id="ms-receivers-test" />
            </label>
            <span class="desc"><?= _t('sendmail', 'например:'); ?> <a class="ajax desc ms-receivers-test-example" href="#">test@gmail.com</a>, <a class="ajax desc ms-receivers-test-example" href="#">123@yandex.ru</a></span>
        </div>
    </td>
</tr>
<tr>
    <td class="row1"></td>
    <td class="row2" id="ms-result"></td>
</tr>
<tr>
    <td class="row1"></td>
	<td class="row2">
        <input type="button" class="btn btn-success button submit" data-loading-text="<?= _te('', 'Подождите...'); ?>" value="<?= _te('', 'Отправить'); ?>" tabindex="5" onclick="return sendmailMassend.init(this);" />
	</td>                                                                                                             
</tr> 
</table>
</form>