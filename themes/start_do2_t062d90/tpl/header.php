<?php
/**
 * Website Header
 */
$url = array(
  'item.add'      => BBS::url('item.add'),
  'user.login'    => Users::url('login'),
  'user.register' => Users::url('register'),
  'user.logout'   => Users::url('logout'),
  );
?>

<div class="l-header">
  <div class="container">
    <div class="l-header-in">
      <!-- Logo -->
      <div class="l-header-logo">
        <a href="<?= bff::urlBase() ?>"><img src="<?= Site::logoURL('header') ?>" alt="<?= HTML::escape(Site::titleHeader()) ?>" /></a>
        <span><?= Site::titleHeader() ?></span>
      </div>
      <!-- User Navigation -->
      <div class="l-header-user">

        <!-- Guest User -->
        <?php if( ! User::id() ) { $favsCounter = BBS::i()->getFavorites(0, true); ?>

          <div class="l-header-user-links">
            <?= _t('header', '<a [login_link]>Войдите</a> или <a [reg_link]>Зарегистрируйтесь</a>', array('login_link'=>'href="'.$url['user.login'].'"','reg_link'=>'href="'.$url['user.register'].'"')) ?>
          </div>
          <div class="l-header-user-buttons">
            <?php if($favsCounter){ ?>
              <a href="<?= BBS::url('my.favs') ?>" class="btn btn-default">
                <i class="fa fa-star"></i>
                <span class="label label-success j-cnt-fav"><?= $favsCounter ?></span>
              </a>
            <?php } ?>
            <a class="btn btn-success" href="<?= $url['item.add'] ?>"><i class="fa fa-plus"></i> <?= _t('header', 'Добавить объявление') ?></a>
          </div>
        
        <!-- Logged User -->
        <?php } else { $userMenu = Users::i()->my_header_menu(); ?>
          <div class="l-header-user-buttons">
            <div class="l-header-user-buttons-dropdown dropdown">
              <a href="#" class="btn btn-default" data-toggle="dropdown">
                <i class="fa fa-user"></i>
                <span class="hidden-xs"><?= tpl::truncate($userMenu['user']['name'], 20) ?></span>
                <b class="caret"></b>
              </a>
              <ul class="dropdown-menu">
                <?php foreach($userMenu['menu'] as $k=>$v): if($v == 'D') { ?>
                  <li class="divider"></li>
                  <?php } else { ?>
                  <li><a href="<?= $v['url'] ?>" class="ico"><i class="<?= $v['i'] ?>"></i> <?= $v['t'] ?></a></li>
                <?php } endforeach; ?>
              </ul>
            </div>
            <a href="<?= $userMenu['menu']['favs']['url'] ?>" class="btn btn-default"><i class="fa fa-star"></i> <span class="label label-success<?= ( ! $userMenu['user']['cnt_items_fav'] ? ' hide' : '' ) ?> j-cnt-fav"><?= $userMenu['user']['cnt_items_fav'] ?></span></a>
            <a href="<?= $userMenu['menu']['messages']['url'] ?>" class="btn btn-default"><i class="fa fa-comment"></i> <span class="label label-success<?= ( ! $userMenu['user']['cnt_internalmail_new'] ? ' hide' : '' ) ?> j-cnt-msg"><?= $userMenu['user']['cnt_internalmail_new'] ?></span></a>
            <a href="<?= $url['item.add'] ?>" class="btn btn-success"><i class="fa fa-plus white"></i> <span class="hidden-xs"><?= _t('header', 'Добавить объявление') ?></span></a>
          </div>
        <?php } ?>

      </div>
    </div><!-- /.l-header-in -->
  </div><!-- /.container -->

  <div class="l-header-nav">
    <div class="container">
      <ul class="l-header-nav-menu">
        <?php $mainMenu = Sitemap::view('main'); foreach($mainMenu as $k=>$v) { ?>
        <li<?php if($v['a']) { ?> class="active"<?php } ?>>
          <a href="<?= $v['link'] ?>"<?= ($v['target'] === '_blank' ? ' target="_blank"' : '') ?>><?= $v['title'] ?></a>
        </li>
        <?php } ?>
      </ul>
    </div><!-- /.container -->
  </div><!-- /.l-header-nav -->

</div><!-- /.l-header -->