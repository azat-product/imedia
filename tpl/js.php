<?php
bff::hook('js.extra');
echo tpl::includesJS(); ?>
<script type="text/javascript">
<? js::start(); ?>
$(function(){
    app.init({adm: false, host:'<?= SITEHOST ?>', hostSearch: '<?= Geo::url() ?>', rootStatic: '<?= SITEURL_STATIC ?>',
              cookiePrefix: 'bff_', regionPreSuggest: <?= Geo::regionPreSuggest() ?>, lng: '<?= LNG ?>',
    lang: <?= func::php2js(bff::filter('js.app.lang', array(
                'fav_in'=>_t('bbs', 'Добавить в избранное'),
                'fav_out'=>_t('bbs', 'Удалить из избранного'),
                'fav_added_msg'=>_t('bbs', 'Весь список ваших избранных объявлений можно посмотреть <a [fav_link]>тут</a>', array('fav_link'=>'href="'.BBS::url('my.favs').'" class="green-link"')),
                'fav_added_title'=>_t('bbs', 'Объявление добавленно в избранные'),
                'fav_limit'=>_t('bbs', 'Авторизуйтесь для возможности добавления большего количества объявлений в избранные'),
                'form_btn_loading'=>_t('', 'Подождите...'),
                'form_alert_errors'=>_t('', 'При заполнении формы возникли следующие ошибки:'),
                'form_alert_required'=>_t('', 'Заполните все отмеченные поля'),
            ))); ?>,
    mapType: '<?= Geo::mapsType() ?>',
    logined: <?= User::id() > 0 ? 'true' : 'false'; ?>,
    device: '<?= bff::device() ?>',
    deviceNoResponsive: <?= (bff::deviceNoResponsive() ? 'true' : 'false') ?>,
    catsFilterLevel: <?= BBS::catsFilterLevel(); ?>
    });
 });
<? js::stop(true); ?>
</script>
<?= js::renderInline(js::POS_HEAD); ?>
