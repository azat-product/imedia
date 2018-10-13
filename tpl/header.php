<?php
/**
 * Шапка сайта
 */
    $url = array(
        'item.add'      => BBS::url('item.add'),
        'user.login'    => Users::url('login'),
        'user.register' => Users::url('register'),
        'user.logout'   => Users::url('logout'),
    );
?>
<!-- BEGIN header -->
<div id="header">
    <? if( DEVICE_DESKTOP_OR_TABLET && ($bannerTop = Banners::view('site_top')) ) { ?>
    <div class="l-banner l-banner_top hidden-phone">
        <div class="l-banner__content">
            <?= $bannerTop; ?>
        </div>
    </div>
    <? } ?>
    <div class="content">
        <div class="container-fluid">
            <div class="l-top row-fluid">
                <div class="l-top__logo span6">
                    <div class="l-top__logo_desktop l-top__logo_desktop__v2 pull-left">
                    <a class="logo" href="<?= bff::urlBase() ?>"><img src="<?= Site::logoURL('header') ?>" alt="<?= HTML::escape(Site::titleHeader()) ?>" /></a>

                    <div class="logo-text">
                    <span class="logo-title"><?= Site::titleHeader() ?></span>
<?

?>
                    </div>
                    </div>
                </div>
                <div class="l-top__navbar span6">
                    <? if( ! User::id() ) { $favsCounter = BBS::i()->getFavorites(0, true); ?>
                    <!-- for: guest -->
                    <div class="l-top__navbar_guest" id="j-header-guest-menu">
                        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
                        <!-- for: desktop & tablet -->
                        <div class="l-top__navbar_guest_desktop hidden-phone">
                            <div class="user-menu">
                                <span class="link-block block nowrap"><?= _t('header', '<a [login_link]>Войдите</a> или <a [reg_link]>Зарегистрируйтесь</a>', array('login_link'=>'href="'.$url['user.login'].'" class="pseudo-link"','reg_link'=>'href="'.$url['user.register'].'"')) ?></span>
                                <div class="btn-group">
                                    <? if($favsCounter){ ?><a href="<?= BBS::url('my.favs') ?>" class="btn"><i class="fa fa-star"></i> <span class="label label-success j-cnt-fav"><?= $favsCounter ?></span></a><? } ?>
                                    <a class="btn btn-success nowrap" href="<?= $url['item.add'] ?>"><i class="fa fa-plus white"></i> <?= _t('header', 'Добавить объявление') ?></a>
                                </div>
                            </div>
                        </div>
                        <? } if( DEVICE_PHONE ) { ?>
                        <!-- for: mobile -->
                        <div class="l-top__navbar_guest_mobile visible-phone">
                            <span class="link-block block nowrap"><?= _t('header', '<a [login_link]>Войдите</a> или <a [reg_link]>Зарегистрируйтесь</a>', array('login_link'=>'href="'.$url['user.login'].'" class="pseudo-link"','reg_link'=>'href="'.$url['user.register'].'"')) ?></span>
                            <div class="navbar rel">
                                <a class="btn btn-navbar" data-target=".l-top__navbar .nav-collapse" data-toggle="collapse">
                                    <span class="fa fa-bars"></span>
                                </a>
                            </div>
                            <div class="btn-group">
                                <? if($favsCounter){ ?><a href="<?= BBS::url('my.favs') ?>" class="btn"><i class="fa fa-star"></i> <span class="label label-success j-cnt-fav"><?= $favsCounter ?></span></a><? } ?>
                                <a class="btn btn-success nowrap" href="<?= $url['item.add'] ?>"><i class="fa fa-plus white"></i> <?= ( $favsCounter ? _t('header', 'Добавить') : _t('header', 'Добавить объявление') ) ?></a>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? } ?>
                    </div>
                    <? } else {
                        $userMenu = Users::i()->my_header_menu();
                    ?>
                    <!-- for: logined user -->
                    <div class="l-top__navbar_user" id="j-header-user-menu">
                        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
                        <!-- for: desktop & tablet -->
                        <div class="l-top__navbar_user_desktop hidden-phone">
                            <div class="user-menu pull-right">
                                <div class="btn-group nowrap">
                                    <!-- start: User Dropdown -->
                                    <a href="javascript:void(0);" data-toggle="dropdown" class="btn">
                                        <i class="fa fa-user"></i><span class="hidden-tablet"> <?= tpl::truncate($userMenu['user']['name'], 20) ?></span>
                                        <i class="fa fa-caret-down"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <? foreach($userMenu['menu'] as $k=>$v):
                                            if( $v == 'D' ) { ?><li class="divider"></li><? }
                                            else { ?><li><a href="<?= $v['url'] ?>" class="ico"><i class="<?= $v['i'] ?>"></i> <?= $v['t'] ?></a></li><? }
                                        endforeach; ?>
                                    </ul>
                                    <!-- end: User Dropdown -->
                                    <a href="<?= $userMenu['menu']['favs']['url'] ?>" class="btn"><i class="fa fa-star"></i> <span class="label label-success<?= ( ! $userMenu['user']['cnt_items_fav'] ? ' hide' : '' ) ?> j-cnt-fav"><?= $userMenu['user']['cnt_items_fav'] ?></span></a>
                                    <a href="<?= $userMenu['menu']['messages']['url'] ?>" class="btn"><i class="fa fa-comment"></i> <span class="label label-success<?= ( ! $userMenu['user']['cnt_internalmail_new'] ? ' hide' : '' ) ?> j-cnt-msg"><?= $userMenu['user']['cnt_internalmail_new'] ?></span></a>
                                    <a href="<?= $url['item.add'] ?>" class="btn btn-success"><i class="fa fa-plus white"></i><span> <?= _t('header', 'Добавить объявление') ?></span></a>
                                </div>
                            </div>
                        </div>
                        <? } if( DEVICE_PHONE ) { ?>
                        <!-- for: mobile -->
                        <div class="l-top__navbar_user_mobile visible-phone">
                            <div class="user-menu pull-left">
                                <ul class="btn-group">
                                    <li class="btn">
                                        <!-- start: User Dropdown -->
                                        <a href="javascript:void(0);" data-toggle="dropdown" class="dropdown-toggle">
                                            <i class="fa fa-user"></i>
                                            <i class="fa fa-caret-down"></i>
                                        </a>
                                        <ul class="dropdown-menu">
                                        <? foreach($userMenu['menu'] as $k=>$v):
                                               if( $v == 'D' ) { ?><li class="divider"></li><? }
                                               else { ?><li><a href="<?= $v['url'] ?>"><i class="<?= $v['i'] ?>"></i> <?= $v['t'] ?></a></li><? }
                                           endforeach; ?>
                                        </ul>
                                        <!-- end: User Dropdown -->
                                    </li>
                                    <li class="btn<? if($userMenu['user']['cnt_items_fav']){ ?> active-counter<? } ?>"><a href="<?= $userMenu['menu']['favs']['url'] ?>"><i class="fa fa-star"></i></a></li>
                                    <li class="btn<? if($userMenu['user']['cnt_internalmail_new']){ ?> active-counter<? } ?>"><a href="<?= $userMenu['menu']['messages']['url'] ?>"><i class="fa fa-comment"></i></a></li>
                                    <li class="btn btn-success"><a href="<?= $url['item.add'] ?>"><i class="fa fa-plus white"></i></a></li>
                                </ul>
                            </div>
                            <div class="navbar pull-right">
                                <a class="btn btn-navbar" data-target=".l-top__navbar .nav-collapse" data-toggle="collapse">
                                    <span class="fa fa-bars"></span>
                                </a>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? } ?>
                    </div>
                    <? } ?>
                    <? if( DEVICE_PHONE ) { ?>
                    <!-- for mobile: collapsed main menu (guest & logined)-->
                    <div class="l-top__mmenu nav-collapse collapse visible-phone">
                        <ul class="nav nav-list">
                            <li<? if(bff::isIndex()) { ?> class="active"<? } ?>><a href="<?= bff::urlBase() ?>"><?= _t('', 'Главная') ?></a></li>
                            <?  $aMainMenu = Sitemap::view('main');
                                foreach($aMainMenu as $k=>$v) {
                                    ?><li<? if($v['a']) { ?> class="active"<? } ?>><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li><?
                                }
                            ?>
                        </ul>
                    </div>
                    <? } ?>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- END header -->