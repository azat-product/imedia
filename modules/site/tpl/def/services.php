<?php
/**
 * Страница "Услуги"
 * @var $this Site
 * @var $titleh1 string заголовок H1
 * @var $svc_bbs array данные об услугах для объявлений
 * @var $svc_shops array данные об услугах для магазинов
 * @var $shop_opened boolean открыт ли у текущего пользователя магазин
 * @var $shop_promote_url string URL на страницу продвижения магазина
 * @var $shop_open_url string URL на страницу открытия магазина
 * @var $user_logined boolean авторизован ли пользователь
 * @var $seotext string SEO-текст
 */
?>
<div class="row-fluid">
    <div class="l-page u-page span12">
        <div class="i-services">
            <h1 class="align-center"><?= (!empty($titleh1) ? $titleh1 : _t('svc', 'Как продать быстрее?')) ?></h1>
            <? if ( ! empty($svc_shops)) { ?>
            <div class="align-center">
                <div class="btn-group j-services-list-togglers" data-toggle="buttons-radio">
                    <button type="button" class="btn j-toggler active" data-type="bbs"><?= _t('svc', 'Объявления') ?></button>
                    <button type="button" class="btn j-toggler" data-type="shops"><?= _t('svc', 'Магазин') ?></button>
                </div>
            </div>
            <script type="text/javascript">
                <? js::start() ?>
                $(function(){
                    var $togglers = $('.j-services-list-togglers').on('click touchstart', '.j-toggler', function(){
                        var type = $(this).data('type');
                        $('.j-services-list').addClass('hide').filter('.j-services-list-'+type).removeClass('hide');
                    });
                });
                <? js::stop() ?>
            </script>
            <? } ?>
            <div class="j-services-list j-services-list-bbs">
                <div class="i-services__list">
                    <? foreach($svc_bbs as $v) { ?>
                   <div class="i-services__list__item l-table">
                        <div class="l-table-row">
                            <div class="l-table-cell i-services__list__item__icon_big hidden-phone"><img src="<?= $v['icon_b'] ?>" alt="" /></div>
                            <div class="l-table-cell i-services__list__item__icon_small visible-phone"><img src="<?= $v['icon_s'] ?>" alt="" /></div>
                            <div class="l-table-cell i-services__list__item__descr">
                                <div class="i-services__list__item__title"><?= $v['title_view'] ?></div>
                                <?= nl2br($v['description_full']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="l-spacer"></div>
                    <? } ?>
                </div>
                <br />
                <div class="i-services__ads">
                    <div class="i-services__ads__item">
                        <span><?= _t('svc', 'Подайте новое объявление и сделайте его заметным') ?></span>
                        <a class="btn btn-success pull-right" href="<?= BBS::url('item.add') ?>"> <i class="fa fa-plus white"></i> <?= _t('svc', 'Добавить объявление') ?></a>
                        <div class="clearfix"></div>
                    </div>
                    <? if($user_logined) { ?>
                    <br />
                    <div class="i-services__ads__item">
                        <span><?= _t('svc', 'Рекламируйте уже существующие объявления') ?></span>
                        <a class="btn pull-right" href="<?= BBS::url('my.items') ?>"><i class="fa fa-user"></i> <?= _t('svc', 'Мои объявления') ?></a>
                        <div class="clearfix"></div>
                    </div>
                    <? } ?>
                </div>
            </div>
            <? if ( ! empty($svc_shops)) { # TODO: подставить иконки для услуг магазинов ?>
            <div class="j-services-list j-services-list-shops hide">
                <div class="i-services__list">
                    <? foreach($svc_shops as $v) { ?>
                   <div class="i-services__list__item l-table">
                        <div class="l-table-row">
                            <div class="l-table-cell i-services__list__item__icon_big hidden-phone"><img src="<?= $v['icon_b'] ?>" alt="" /></div>
                            <div class="l-table-cell i-services__list__item__icon_small visible-phone"><img src="<?= $v['icon_s'] ?>" alt="" /></div>
                            <div class="l-table-cell i-services__list__item__descr">
                                <div class="i-services__list__item__title"><?= $v['title_view'] ?></div>
                                <?= nl2br($v['description_full']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="l-spacer"></div>
                    <? } ?>
                </div>
                <br />
                <div class="i-services__ads">
                    <div class="i-services__ads__item">
                    <? if ($shop_opened) { ?>
                        <span><?= _t('shops', 'Рекламируйте свой магазин') ?></span>
                        <a class="btn pull-right" href="<?= $shop_promote_url ?>"><i class="fa fa-arrow-up"></i> <?= _t('shops', 'Рекламировать') ?></a>
                    <? } else { ?>
                        <span><?= _t('shops', 'Откройте свой магазин') ?></span>
                        <a class="btn btn-success pull-right" href="<?= $shop_open_url ?>"><i class="fa  fa-plus white"></i> <?= _t('shops', 'Открыть магазин') ?></a>
                    <? } ?>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
            <? } ?>
        </div>
        <? if (!empty($seotext)) { ?>
        <div class="l-info">
            <?= $seotext ?>
        </div>
        <? } ?>
    </div>
</div>