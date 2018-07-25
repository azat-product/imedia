<?php
/**
 * Страница перенаправления на внешний ресурс
 * @var $this Site
 * @var $url string URL перенаправления
 * @var $timeout integer таймаут (в секундах)
 */
?>

<p class="text-center">
    <?= _t('site', 'Вы будете перенаправлены по внешней ссылке через [sec] секунд.', array('sec' => '<span id="j-away-countdown">'.$timeout.'</span>')); ?>
</p>
<p class="text-center"><a href="<?= $url ?>" rel="nofollow noopener"><?= _t('site', 'Перейти по ссылке прямо сейчас'); ?></a></p>
<script type="text/javascript">
<? js::start(); ?>
$(function(){
    var $secs = $('#j-away-countdown');
    var timeout = <?= $timeout ?>;

    function showTimeout() {
        if (timeout >= 0) {
            $secs.text(timeout);
        }
    }

    setInterval(function(){
        timeout--;
        if(timeout <= 0){
            document.location = '<?= $url ?>';
        }
        showTimeout();
    }, 1000);
    showTimeout();
});
<? js::stop(); ?>
</script>