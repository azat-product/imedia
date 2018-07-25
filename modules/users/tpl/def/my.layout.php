<?php
    /**
     * Кабинет пользователя (Основное меню)
     * @var $this Users
     * @var $user array данные пользователя
     * @var $tabs array табы
     * @var $shop_open array ссылка на форму открытия магазина или FALSE (магазин уже был открыт)
     * @var $content string содержание текущего раздела кабинета (HTML)
     */
?>
<div class="row-fluid">
    <div class="l-page u-page span12">
        <div class="u-cabinet">
            <? if ($user){ ?>
            <h2><?= _t('users', 'Кабинет пользователя') ?><span class="hidden-phone">: <small class="inlblk"><?= $user['name'] ?></small></span></h2>
            <div class="u-cabinet__main-navigation">
                <? if( DEVICE_DESKTOP_OR_TABLET ): ?>
                <div class="u-cabinet__main-navigation_desktop hidden-phone">
                    <ul class="nav nav-tabs">
                        <? foreach($tabs as $k=>$v) { if(empty($v['t'])) continue; ?>
                        <li<? if( ! empty($v['active']) ){ ?> class="active"<? } ?>>
                            <a href="<?= $v['url'] ?>">
                                <?= $v['t'] ?>
                                <? if( ! empty($v['counter']) ) {
                                    if ($k === 'messages') {
                                        ?><span class="u-cabinet__main-navigation__new-message"><?= $v['counter'] ?></span><?
                                    } else if ($k === 'bill') {
                                        ?><span class="u-cabinet__main-navigation__money"><?= $v['counter'] ?></span><?
                                    } else {
                                        echo $v['counter'];
                                    }
                                } ?>
                            </a>
                        </li>
                        <? } ?>
                        <? if($shop_open) { ?><li class="pull-right<? if($shop_open['active']){ ?> active<? } ?>"><a href="<?= $shop_open['url'] ?>"><span class="visible-desktop"><i class="fa fa-plus u-cabinet__main-navigation__shop-open"></i> <?= _t('users', 'Открыть магазин') ?></span><span class="visible-tablet"><i class="fa fa-plus u-cabinet__main-navigation__shop-open"></i> <?= _t('users', 'Магазин') ?></span></a></li><? } ?>
                    </ul>
                </div>
                <? endif; # DEVICE_DESKTOP_OR_TABLET ?>
                <? if( DEVICE_PHONE ): ?>
                <div class="u-cabinet__main-navigation_mobile visible-phone">
                    <div class="btn-group<? if($shop_open) { ?> pull-left<? } elseif (!$shop_open) { ?> fullWidth<? } ?>">
                        <button data-toggle="dropdown" class="btn dropdown-toggle" class="btn dropdown-toggle"> <?= $tabs[$tab]['t'] ?> <? if( ! empty($tabs[$tab]['counter']) ) echo $tabs[$tab]['counter']; ?> <i class="fa <?= (empty($tabs[$tab]['t']) ? 'fa-bars' : 'fa-caret-down') ?>"></i></button>
                        <ul class="dropdown-menu">
                            <? foreach($tabs as $k=>$v) { if(empty($v['t'])) continue; ?>
                            <li<? if( ! empty($v['active']) ){ ?> class="active"<? } ?>>
                                <a href="<?= $v['url'] ?>">
                                    <?= $v['t'] ?>
                                    <? if( ! empty($v['counter']) ) {
                                        if ($k === 'messages') {
                                            ?><span class="u-cabinet__main-navigation__new-message">+ <?= $v['counter'] ?></span><?
                                        } else if ($k === 'bill') {
                                            ?><span class="u-cabinet__main-navigation__money">(<?= $v['counter'] ?>)</span><?
                                        } else {
                                            echo $v['counter'];
                                        }
                                    } ?>
                                </a>
                            </li>
                            <? } ?>
                        </ul>
                    </div>
                    <? if($shop_open) { ?>
                    <div class="pull-right">
                        <ul class="nav nav-pills">
                            <li<? if($shop_open['active']){ ?> class="active"<? } ?>><a href="<?= $shop_open['url'] ?>"><i class="fa fa-plus u-cabinet__main-navigation__shop-open"></i> <?= _t('users', 'Магазин') ?></a></li>
                        </ul>
                    </div>
                    <div class="clearfix"></div>
                    <? } ?>
                </div>
                <? if($shop_open) { ?><div class="l-page__spacer visible-phone"></div><? } ?>
                <? endif; # DEVICE_PHONE ?>
            </div>
            <? } ?>
            <?= $content ?>
        </div>
    </div>
</div>