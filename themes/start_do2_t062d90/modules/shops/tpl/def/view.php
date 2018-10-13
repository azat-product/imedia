<?php
/**
 * Страница магазина: layout
 * @var $this Shops
 * @var $shop array данные о магазине
 * @var $has_owner boolean у магазина есть владелец
 * @var $is_owner boolean является ли текущий пользователь владельцем просматриваемого магазина
 * @var $share_code string код шаринга в соц. сетях
 * @var $url_promote string URL на страницу продвижения магазина
 * @var $url_promote_visible boolean отображать ссылку продвижения магазина
 * @var $request_form_visible boolean отображать форму подачи заявки на представительство
 */
tpl::includeJS('shops.view', false, 5);
?>

<?= tpl::getBreadcrumbs($breadcrumbs); ?>

<div class="l-mainLayout" id="j-shops-v-container">

  <!-- Content -->
  <div class="l-mainLayout-content has-sidebar">
    
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= $shop['title'] ?></h1>
    </div>

    <?php if (DEVICE_PHONE) {
      // Shop owner block (phone)
      echo $this->viewPHP($aData, 'view.owner');
    } ?>

    <div class="l-pageDescr">
      <?= $shop['descr']; ?>
    </div>

    <?php if ($request_form_visible) { ?>
    <div class="l-center">
      <div class="l-center__content v-page__content_center">
        <div class="l-page__spacer hidden-xs mrgt30 mrgb20"></div>
        <div class="sh-need-owner">
          <p><?= _t('shops', 'Если вы являетесь представителем этого магазины вы можете получить доступ к управлению информацией о магазине и размещению объявлений от его имени <a [request_form_link]>подав заявку</a>.', array(
            'request_form_link' => ' href="#" class="ajax" id="j-shop-view-request-form-toggler"',
            )) ?></p>
          <div class="v-descr_contact" id="j-shop-view-request-form-block" style="display: none;">
            <div class="v-descr_contact_title"><?= _t('shops', 'Укажите ваши контактные данные и мы с вами свяжемся') ?></div>
            <div class="v-descr_contact__form">
              <form action="" class="j-form">
                <?php if ( ! User::id()) { ?>
                <input type="text" name="name" class="j-required" placeholder="<?= _te('shops', 'Ваше имя') ?>" maxlength="50" />
                <input type="tel" name="phone" class="j-required" placeholder="<?= _te('shops', 'Ваш телефон') ?>" maxlength="50" />
                <input type="email" name="email" class="j-required" placeholder="<?= _te('shops', 'Ваш email-адрес') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                <?php } ?>
                <textarea name="description" class="j-required j-description" placeholder="<?= _te('shops', 'Расскажите как вы связаны с данным магазином') ?>"></textarea>
                <small class="help-block grey j-description-maxlength pull-left hidden-xs"></small>
                <button type="submit" class="btn pull-right"><i class="fa fa-envelope"></i> <?= _t('shops', 'Отправить заявку') ?></button>
                <div class="clearfix"></div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="clearfix"></div>
    <?php } ?>

    <?php if($has_owner) { ?>
    <ul class="nav nav-tabs">
      <?php foreach($tabs as $v) { ?>
      <li<?php if($v['a']){ ?> class="active"<?php } ?>><a href="<?= $v['url'] ?>"><?= $v['t'] ?></a></li>
      <?php } ?>
    </ul>
    <?php } # has_owner ?>

    <div class="c-tab-content">
      <?php // bbs/shop.items.php
      echo $content ?>
    </div>

  </div><!-- /.l-mainLayout-content -->
  
  <?php if (DEVICE_DESKTOP_OR_TABLET) { ?>
  <!-- Sidebar -->
  <div class="l-mainLayout-sidebar">

    <!-- Shop owner block -->
    <?= $this->viewPHP($aData, 'view.owner') ?>

    <div class="ad-actions">

      <!-- Share -->
      <div class="ad-actions-item dropdown">
        <a href="#" class="link-ico" id="j-shop-view-send4friend-desktop-link"><i class="fa fa-user"></i> <span><?= _t('shops', 'Поделиться с другом') ?></span></a>
        <!-- Share Dropdown -->
        <div class="dropdown-menu ad-actions-item-dropdown" id="j-shop-view-send4friend-desktop-popup">
          <div class="dropdown-menu-in">
            <form action="">
              <div class="input-group">
                <input type="text" name="email" class="form-control j-required" placeholder="<?= _te('', 'E-mail') ?>" />
                <span class="input-group-btn">
                  <button type="submit" class="btn btn-default j-submit"><?= _t('', 'Отправить') ?></button>
                </span>
              </div>
            </form>
          </div>
        </div>
      </div><!-- /.ad-actions-item -->

      <?php if( ! $is_owner) { ?>
        <!-- Complain -->
        <div class="ad-actions-item dropdown">
          <a href="#" class="link-ico" id="j-shops-v-claim-desktop-link"><i class="fa fa-fire"></i> <span><?= _t('shops', 'Пожаловаться') ?></span></a>
          <!-- Cimplain Dropdown -->
          <div class="dropdown-menu ad-actions-item-dropdown" id="j-shops-v-claim-desktop-popup">
            <div class="dropdown-menu-heading">
              <div class="dropdown-menu-heading-title">
                <?= _t('shops', 'Укажите причины, по которым вы считаете этот магазин некорректным') ?>:
              </div>
            </div>
            <div class="dropdown-menu-in">
              <form action="">
                <?php foreach($this->getShopClaimReasons() as $k=>$v): ?>
                <div class="checkbox">
                  <label><input type="checkbox" class="j-claim-check" name="reason[]" value="<?= $k ?>" /> <?= $v ?> </label>
                </div>
                <?php endforeach; ?>
                <div class="form-group j-claim-other" style="display: none;">
                  <label for="actions-complaint-other"><?= _t('shops', 'Оставьте ваш комментарий') ?></label>
                    <textarea name="comment" rows="3" class="form-control" id="actions-complaint-other" autocapitalize="off"></textarea>
                </div>

                <?php if( ! User::id() ): ?>
                  <?php if (Site::captchaCustom('shops-view')): ?>
                    <?php bff::hook('captcha.custom.view', 'shops-view', __FILE__); ?>
                  <?php else: ?>
                    <div class="form-group">
                      <label for="actions-complaint-captcha"><?= _t('shops', 'Введите результат с картинки') ?></label>
                      <div class="row">
                        <div class="col-xs-6">
                          <input type="text" name="captcha" class="form-control required" id="actions-complaint-captcha" value="" pattern="[0-9]*" />
                        </div>
                        <div class="col-xs-6">
                          <img src="" alt="" class="j-captcha" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&rnd='+Math.random())" />
                        </div>
                      </div>
                    </div>
                <?php endif; endif; ?>

                <button type="submit" class="btn btn-danger j-submit"><?= _t('shops', 'Отправить жалобу') ?></button>
              </form>
            </div>
          </div>
        </div><!-- /.ad-actions-item -->
      <?php } # ! $is_owner ?>

      <?php if ($url_promote_visible) { ?>
        <!-- Promotion -->
        <div class="ad-actions-item">
          <a href="<?= $url_promote ?>" class="link-ico"><i class="fa fa-hand-o-up"></i> <span><?= _t('shops', 'Продвинуть магазин') ?></span></a> <br />
        </div><!-- /.ad-actions-item -->
      <?php } ?>
      <?php if ( ! empty($share_code)) { ?>
        <!-- Social Share -->
        <div class="ad-actions-social">
          <?= $share_code ?>
        </div>
      <?php } ?>

    </div><!-- /.ad-actions -->

    <?php if ($bannerRight = Banners::view('shops_view_right', array('region'=>$shop['reg3_city']))) { ?>
      <div class="l-banner-v">
        <?= $bannerRight ?>
      </div>
    <?php } ?>

  </div><!-- /.l-mainLayout-sidebar -->
  <?php } # DEVICE_DESKTOP_OR_TABLET ?>

</div><!-- /.l-mainLayout -->

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jShopsView.init(<?= func::php2js(array(
      'lang'=>array(
        'request' => array(
          'success' => _t('shops', 'Ваша заявка была успешно отправлена'),
          'maxlength_symbols_left' => _t('', '[symbols] осталось'),
          'maxlength_symbols' => _t('', 'знак;знака;знаков'),
          ),
        'sendfriend'=>array(
          'email' => _t('','E-mail адрес указан некорректно'),
          'success' => _t('','Сообщение было успешно отправлено'),
          ),
        'claim' => array(
          'reason_checks' => _t('shops','Укажите причину жалобы'),
          'reason_other' => _t('shops','Опишите причину подробнее'),
          'captcha' => _t('','Введите результат с картинки'),
          'success' => _t('shops','Жалоба успешно принята'),
          ),
        ),
      'id'=>$shop['id'], 'ex'=>$shop['id_ex'].'-'.$shop['id'],
      'claim_other_id'=>Shops::CLAIM_OTHER,
      'addr_map' => ($shop['addr_map']),
      'addr_lat' => $shop['addr_lat'],
      'addr_lon' => $shop['addr_lon'],
      'request_url' => Shops::url('request'),
      )) ?>);
  });
  <?php js::stop(); ?>
</script>