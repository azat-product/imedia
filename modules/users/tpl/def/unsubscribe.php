<?php
/**
 * Страница отписаться от рассылки
 * @var $step string текущий шаг
 */
?>
<? switch($step) { case 'error': { ?>
    <div class="align-center">
        <strong><?= _t('users', 'Ошибка') ?></strong>
        <p><?= _t('users', 'Что-то пошло не так. Возможно ссылка, по которой вы перешли, некорректна.') ?></p>
    </div>
<? } break; case 'subscribe': { ?>
    <div class="align-center" id="j-unsubscribe-block">
        <strong class="j-title"><?= _t('users', 'Вы успешно отписались от нашей рассылки') ?></strong>
        <div class="j-message">
            <p><?= _t('users', 'Извините, если успели вам надоесть.') ?></p>
            <p><?= _t('users', 'Если вы перешли по ссылке “отписаться от рассылки” по ошибке, нажмите на кнопку ниже.') ?></p>
            <p><input type="button" class="btn btn-success j-subscribe" value="<?= _te('users', 'Подписаться на рассылку') ?>" /></p>
        </div>
    </div>
    <script type="text/javascript">
        <? js::start(); ?>
        $(function(){
            var $block = $('#j-unsubscribe-block');
            $block.on('click', '.j-subscribe', function(e){ nothing(e);
                var $btn = $(this); $btn.prop('disabled', 'disabled');
                bff.ajax(window.location.href, {fp:bff.fp()}, function(r,err) {
                    if (r.success) {
                        $block.find('.j-title').html(r.title);
                        $block.find('.j-message').html(r.message);
                    } else {
                        app.alert.error(err, {});
                        $btn.removeProp('disabled');
                    }
                });
            });
        });
        <? js::stop(); ?>
    </script>
<? } break;
}