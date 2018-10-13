<?php

/**
 * Просмотр объявления: layout
 * @var $this BBS
 * @var $id integer ID объявления
 * @var $cats array категории объявления
 * @var $owner bool просматривает владелец объявления
 * @var $share_code string код шаринга в соц. сетях
 * @var $moderation boolean объявление находится на модерации и просматривается модератором
 * Статус объявления:
 * @var $is_publicated boolean объявление публикуется
 * @var $is_publicated_out boolean объявление снято с публикации
 * @var $is_blocked boolean объявление заблокировано
 * @var $is_soon_left boolean объявление публикуется и скоро истекает его срок публикации
 * @var $is_business boolean тип владельца - бизнес
 * @var $is_map boolean выводить карту
 * @var $contacts array контактные данные
 * @var $lang array текстовые фразы
 */

tpl::includeJS('bbs.view', false, 7);

tpl::includeJS('fancybox/jquery.fancybox.pack', false);
tpl::includeJS('fancybox/helpers/jquery.fancybox-thumbs', false);
tpl::includeCSS('/js/fancybox/jquery.fancybox', false);
tpl::includeCSS('/js/fancybox/helpers/jquery.fancybox-thumbs', false);

tpl::includeJS('fotorama/fotorama', false, '4.6.4.1');
tpl::includeCSS('/js/fotorama/fotorama', false);

if ($is_map) {
  Geo::mapsAPI(false);
}
?>

<?= tpl::getBreadcrumbs($cats, true, 'breadcrumb'); ?>

<div id="j-view-container" itemscope itemtype="http://schema.org/Product">
  <?php if ($is_publicated_out): ?>
    <div class="alert alert-info">
      <?= _t('bbs', 'Объявление снято с публикации') ?>
      <?= tpl::date_format2($status_changed) ?>
      <?php if ($owner) { ?>
        <div class="alert-controls">
          <a href="#" class="btn btn-info j-item-publicate"><i
              class="fa fa-refresh white"></i> <?= _t('bbs', 'Опубликовать снова') ?></a>
        </div>
      <?php } ?>
    </div>
  <?php endif; ?>
  <?php if ($owner && $is_publicated && $is_soon_left): ?>
    <div class="alert alert-info">
      <?= _t('bbs', 'Объявление публикуется') ?>
      <?= _t('bbs', 'до [date]', array('date' => tpl::date_format2($publicated_to))) ?>
      <div class="alert-controls">
        <a href="#" class="btn btn-info j-item-refresh"><i class="fa fa-refresh white"></i> <?= _t('bbs', 'Продлить') ?>
        </a>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($is_blocked): ?>
    <div class="alert alert-danger">
      <?= _t('bbs', 'Объявление было заблокировано модератором.') ?><br/>
      <?= _t('bbs', 'Причина блокировки:') ?>&nbsp;<strong><?= $blocked_reason ?></strong>
    </div>
  <?php endif; ?>
  <?php if ($is_publicated && BBS::premoderation() && !$moderated && !$moderation): ?>
    <div class="alert alert-warning">
      <?= _t('bbs', 'Данное объявление находится на модерации.') ?><br/>
      <?= _t('bbs', 'После проверки оно будет опубликовано') ?>
    </div>
  <?php endif; ?>

  <div class="l-mainLayout">

    <!-- Content -->
    <div class="l-mainLayout-content has-sidebar">

      <div class="l-pageHeading">
        <?php if (!$is_publicated_out): ?>
          <?php if ($fav): ?>
            <a href="#" class="l-pageHeading-item c-fav active j-i-fav" data="{id:<?= $id ?>}"
               title="<?= $lang['fav_out'] ?>"><span class="item-fav__star"><i
                  class="fa fa-star j-i-fav-icon"></i></span></a>
          <?php else: ?>
            <a href="#" class="l-pageHeading-item c-fav j-i-fav" data="{id:<?= $id ?>}"
               title="<?= $lang['fav_in'] ?>"><span class="item-fav__star"><i
                  class="fa fa-star-o j-i-fav-icon"></i></span></a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($svc_quick) { ?>
          <span class="l-pageHeading-item label label-warning quickly"><?= _t('bbs', 'срочно') ?></span>&nbsp;
        <?php } ?>
        <h1 class="l-pageHeading-title" itemprop="name">
          <?= $title ?>
        </h1>
        <div class="l-pageHeading-subtext">
          <i class="fa fa-map-marker"></i> <?= $city_title_delivery ?>
          | <?= _t('view', 'Добавлено: [date], номер: [id]', array('date' => tpl::date_format2($created), 'id' => $id)) ?>
        </div>
      </div>
    </div><!-- /.l-mainLayout-content -->
    <div class="l-mainLayout-sidebar">
      <?php if (!$is_publicated_out) { ?>
      <?php if ($price_on) { ?>
        <!-- Price -->
        <div class="ad-price c-price"<?php if($price) { ?> itemprop="offers" itemscope itemtype="http://schema.org/Offer"<?php } ?>>
          <?php if ($price) { ?>
            <?= $price ?>
            <?php if($price_value > 0) { ?>
                <span itemprop="priceCurrency" content="<?= mb_strtoupper(Site::currencyData($price_curr, 'keyword')) ?>"></span>
                <span itemprop="price" content="<?= number_format($price_value, 2, '.', '') ?>"></span>
            <?php } ?>
          <?php } ?>
          <?php if ($price_mod) { ?>
            <div class="ad-price-mod"><?= $price_mod ?></div>
          <?php } ?>
        </div>
      <?php } # $price_on ?>
    </div><!-- /.l-mainLayout-sidebar -->

    <div class="l-mainLayout-content has-sidebar">
      <div class="ad-images fotorama" id="j-view-images" data-auto="false" data-controlsonstart="false">
        <?php
        $i = 0;
        foreach ($images as $v) { ?>
          <div data-img="<?= $v['url_view'] ?>" data-thumb="<?= $v['url_small'] ?>" data-alt="<?= $v['t'] ?>"
               class="j-view-images-frame">
            <a href="javascript:;" class="ad-images-zoom j-zoom" data-zoom="<?= $v['url_zoom'] ?>" data-index="<?= $i++; ?>"><i
                class="fa fa-search"></i></a>
          </div>
        <?php }
        echo $this->itemVideo()->viewFotorama($video_embed);
        if ($is_map) { ?>
        <div data-thumb="<?= bff::url('/img/map_marker.gif') ?>" class="j-view-images-frame j-map">
          <div id="j-view-map" style="height:<?= (DEVICE_DESKTOP ? '450' : '350') ?>px; width: 100%;"></div>
          </div><?php } ?>
      </div>

      <?php if ($is_map) { ?>
        <div class="ad-address">
          <span class="ad-address-attr"><?= _t('view', 'Адрес') ?>:</span>
          <span class="ad-address-val"><?= $city_title ?>, <?php
            if ($district_id && !empty($district_data['title'])) {
              echo _t('view', 'район [district]', array('district' => $district_data['title'])) . ', ';
            } ?><?php
            if ($metro_id && !empty($metro_data['title'])) {
              echo _t('view', 'метро [station]', array('station' => $metro_data['title'])) . ', ';
            } ?><?= $addr_addr ?>,
            <a href="#" class="ajax"
               onclick="return jView.showMap(event);"><span><?= _t('view', 'показать на карте') ?></span></a>
          </span>
        </div>
      <?php } elseif ($cat_addr && $metro_id && !empty($metro_data['title'])) { ?>
        <div class="ad-address">
          <span class="ad-address-attr"><?= _t('view', 'Адрес') ?>:</span>
          <span class="ad-address-val"><?= $city_title ?>, <?php
            if ($district_id && !empty($district_data['title'])) {
              echo _t('view', 'район [district]', array('district' => $district_data['title'])) . ', ';
            }
            ?>
            <?= _t('view', 'метро [station]', array('station' => $metro_data['title'])); ?>
          </span>
        </div>
      <?php } # is_map  ?>

      <div class="ad-dynprops"><?= $dynprops ?></div>

      <div class="ad-descr" itemprop="description"><?= nl2br($descr) ?></div>

      <?php if (!$is_publicated_out && !$owner && (!$moderation || ($moderation && !$moderated))) {
        /* Contact Form */
        ?>
        <div class="ad-contact">
          <a name="contact-form"></a>
          <div class="l-blockHeading">
            <div class="l-blockHeading-title"><?= _t('view', 'Свяжитесь с автором объявления') ?></div>
          </div>
          <div class="ad-contact-block">
            <div class="ad-contact-name">
              <?php if ($is_shop): ?>
                <a href="<?= $shop['link'] ?>"><?= $shop['title'] ?></a>
              <?php else: ?>
                <?= $name ?>
              <?php endif; ?>
            </div>
            <div class="ad-contact-phones j-v-contacts-expand-block">
              <?php $contactsExpand = false;
              if (!empty($contacts['phones'])) { ?>
                <div class="j-c-phones">
                <?php foreach ($contacts['phones'] as $v): ?>
                  <div class="ad-contact-phones-item">
                    <span><?= $v ?></span>
                    <?php if (!$contactsExpand) { $contactsExpand = true ?>
                      <a href="javascript:void(0);" class="link-ajax j-v-contacts-expand-link">
                        <span><?= _t('view', 'Показать контакты') ?></span>
                      </a>
                    <?php } ?><br/>
                  </div>
                <?php endforeach; ?>
                </div>
              <?php } ?>
              <?php if (!empty($contacts['contacts'])): ?>
                <?php foreach (Users::contactsFields($contacts['contacts']) as $contact): ?>
                  <div class="ad-contact-item">
                    <span class="ad-contact-item-title"><?= $contact['title'] ?></span>
                    <span class="ad-contact-item-content j-c-<?= $contact['key'] ?>">
                        <span><?= tpl::contactMask($contact['value']) ?></span>
                        <?php if (!$contactsExpand) { $contactsExpand = true ?>
                          <a href="#" class="link-ajax j-v-contacts-expand-link">
                            <span><?= _t('view', 'Показать контакты') ?></span>
                          </a>
                        <?php } ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <form action="?act=contact-form" id="j-view-contact-form">
              <?= Users::i()->writeForm('j-view-contact-form') ?>
            </form>

          </div><!-- /.ad-contact-block -->
        </div><!-- /.ad-contact -->
      <?php } ?>

      <div class="l-comments" id="j-comments-block">
        <?php
        /* Comments */
        echo $comments;
        ?>
      </div>

        <?php if (!DEVICE_PHONE) { ?>
            <div class="ad-similar">
                <?php
                /* Similar Listings */
                if ($is_publicated && !$moderation) {
                    echo $similar;
                }
                ?>
            </div>
        <? } ?>

      <?php if ($bannerSimilar = Banners::view('bbs_view_similar_bottom', array('cat' => $cat_id, 'region' => $city_id))) { ?>
        <div class="l-banner">
          <div class="l-banner__content">
            <?= $bannerSimilar; ?>
          </div>
        </div>
      <?php } ?>

    </div><!-- /.l-mainLayout-content -->

    <!-- Sidebar -->
    <div class="l-mainLayout-sidebar">

      <?php /* Author */
      echo $this->viewPHP($aData, 'item.view.owner');
      ?>

      <div class="ad-actions">

        <!-- Share -->
        <div class="ad-actions-item dropdown">
          <a href="#" class="link-ico" id="j-v-send4friend-desktop-link"><i class="fa fa-user"></i>
            <span><?= _t('view', 'Поделиться с другом') ?></span></a>
          <!-- Share Dropdown -->
          <div class="dropdown-menu ad-actions-item-dropdown" id="j-v-send4friend-desktop-popup">
            <div class="dropdown-menu-in">
              <form action="">
                <div class="input-group">
                  <input type="text" name="email" class="form-control j-required"
                         placeholder="<?= _te('', 'E-mail') ?>"/>
                  <input type="hidden" name="item_id" value="<?= $id ?>"/>
                  <span class="input-group-btn">
                      <button type="submit" class="btn btn-default j-submit"><?= _t('', 'Отправить') ?></button>
                    </span>
                </div>
              </form>
            </div>
          </div>
        </div><!-- /.ad-actions-item -->

        <?php if (!$owner) { ?>
          <!-- Complain -->
          <div class="ad-actions-item dropdown">
            <a href="#" class="link-ico" id="j-v-claim-desktop-link"><i class="fa fa-fire"></i>
              <span><?= _t('view', 'Пожаловаться') ?></span></a>
            <!-- Cimplain Dropdown -->
            <div class="dropdown-menu ad-actions-item-dropdown" id="j-v-claim-desktop-popup">
              <div class="dropdown-menu-heading">
                <div class="dropdown-menu-heading-title">
                  <?= _t('item-claim', 'Укажите причины, по которым вы считаете это объявление некорректным') ?>:
                </div>
              </div>
              <div class="dropdown-menu-in">
                <form action="">
                  <?php foreach ($this->getItemClaimReasons() as $k => $v): ?>
                    <div class="checkbox">
                      <label><input type="checkbox" class="j-claim-check" name="reason[]" value="<?= $k ?>"/> <?= $v ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                  <div class="form-group j-claim-other" style="display: none;">
                    <label for="actions-complaint-other"><?= _t('item-claim', 'Оставьте ваш комментарий') ?></label>
                    <textarea name="comment" rows="3" class="form-control" id="actions-complaint-other"
                              autocapitalize="off"></textarea>
                  </div>
                  <?php if (!User::id()): ?>
                    <?php if (Site::captchaCustom('bbs-item-view')): ?>
                      <?php bff::hook('captcha.custom.view', 'bbs-item-view', __FILE__); ?>
                    <?php else: ?>
                      <label
                        for="actions-complaint-captcha"><?= _t('item-claim', 'Введите результат с картинки') ?></label>
                      <div class="row">
                        <div class="col-xs-6">
                          <input type="text" name="captcha" class="form-control required" id="actions-complaint-captcha"
                                 value="" pattern="[0-9]*"/>
                        </div>
                        <div class="col-xs-6">
                          <img src="" alt="" class="j-captcha"
                               onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&rnd='+Math.random())"/>
                        </div>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                  <button type="submit"
                          class="btn btn-danger j-submit"><?= _t('item-claim', 'Отправить жалобу') ?></button>
                </form>
              </div>
            </div>
          </div><!-- /.ad-actions-item -->
        <?php } ?>

        <!-- Print -->
        <div class="ad-actions-item">
          <a href="?print=1" class="link-ico"><i class="fa fa-print"></i> <span><?= _t('view', 'Распечатать') ?></span></a>
        </div>

        <?php if (!empty($share_code)) { ?>
          <!-- Social Share -->
          <div class="ad-actions-social">
            <?= $share_code ?>
          </div>
        <?php } ?>

        <?php if (bff::servicesEnabled() && BBS::itemViewPromoteAvailable($owner)) { ?>
          <!-- Promotion -->
          <div class="ad-actions-item">
            <a href="<?= $promote_url ?>" class="link-ico"><i class="fa fa-hand-o-up"></i>
              <span><?= _t('view', 'Продвинуть объявление') ?></span></a> <br/>
          </div>
        <?php } ?>

        <div class="ad-actions-stats">
          <div class="ad-actions-stats-item">
            <?= _t('view', '[views_total] объявления', array('views_total' => tpl::declension($views_total, _t('view', 'просмотр;просмотра;просмотров')))) ?>
          </div>
          <div class="ad-actions-stats-item">
            <?= _t('view', '[views_today] из них сегодня', array('views_today' => $views_today)) ?>
          </div>
          <div class="ad-actions-stats-item">
            <?php if ($views_total) { ?><a href="#" class="link-ajax" id="j-v-viewstat-desktop-link-2">
              <span><?= _t('view', 'Посмотреть статистику') ?></span></a><?php } ?>
          </div>
          <div id="j-v-viewstat-desktop-popup-container"></div>
        </div>

      </div><!-- /.ad-actions -->

      <?php if ($bannerRight = Banners::view('bbs_view_right', array('cat' => $cat_id, 'region' => $city_id))) { ?>
        <div class="l-banner-v">
          <?= $bannerRight ?>
        </div>
      <?php } # $bannerRight ?>

      <?php if (DEVICE_PHONE) { ?>
          <div class="ad-similar">
                <?php
                /* Similar Listings */
                if ($is_publicated && !$moderation) {
                    echo $similar;
                }
                ?>
            </div>
      <? } ?>

    </div>
    <?php } # ! $is_publicated_out ?>

  </div><!-- /.l-mainLayout -->

</div><!-- /#j-view-container -->

<?php if ($owner): ?>

  <div class="l-ownerBlock" id="j-v-owner-panel">
    <div class="container hidden-xs">
      <div class="l-ownerBlock-top">
        <div class="l-ownerBlock-content">
          <div class="l-ownerBlock-content-l">
            <a href="<?= ($from == 'my' ? 'javascript:history.back();' : BBS::url('my.items')) ?>" class="link-ico"><i
                class="fa fa-chevron-left"></i> <span><?= _t('view', 'Назад в Мои объявления') ?></span></a>
          </div>
          <div class="l-ownerBlock-content-r">
            <a href="#" class="j-panel-actions-toggler" data-state="hide">
            <span class="j-toggler-state"><span><?= _t('view', 'Закрыть') ?></span>
            <i class="fa fa-chevron-down"></i></span>
              <span class="j-toggler-state hide"><span><?= _t('view', 'Показать') ?></span><i
                  class="fa fa-chevron-up"></i></span>
            </a>
          </div>
        </div>
      </div>
    </div>
    <div class="l-ownerBlock-main j-panel-actions">
      <div class="container">
        <div class="l-ownerBlock-content">
          <div class="l-ownerBlock-content-l">
            <div class="l-ownerBlock-content-link">
              <a href="<?= BBS::url('item.edit', array('id' => $id, 'from' => 'view')) ?>" class="link-ico"><i
                  class="fa fa-edit"></i> <span><?= _t('view', 'Изменить информацию') ?></span></a>
            </div>
            <?php if ($is_publicated) { ?>
              <div class="l-ownerBlock-content-link">
                <a href="#" class="link-ico j-item-unpublicate"><i class="fa fa-times"></i>
                  <span><?= _t('view', 'Снять с публикации') ?></span></a>
              </div>
            <?php } ?>
            <?php if (!$is_publicated || $is_soon_left) { ?>
              <div class="l-ownerBlock-content-link">
                <a href="#" class="link-ico j-item-delete"><i
                    class="fa <?= $is_soon_left ? 'fa-trash-o' : 'fa-times' ?>"></i>
                  <span><?= _t('view', 'Удалить') ?></span></a>
              </div>
            <?php } ?>
          </div>
          <div class="l-ownerBlock-content-r">
            <a href="<?= InternalMail::url('item.messages', array('item' => $id)) ?>" class="btn btn-sm btn-default"><i
                class="fa fa-envelope"></i> <?= $messages_total ?><span
                class="hidden-xs"> <?= tpl::declension($messages_total, _t('view', 'Сообщение;Сообщения;Сообщений'), false) ?></span></a>
            <?php if ($is_publicated_out) { ?>
              <a href="#" class="btn btn-sm btn-info j-item-publicate"><i class="fa fa-arrow-up white"></i><span
                  class="hidden-xs"> <?= _t('view', 'Активировать') ?></span></a>
            <?php } ?>
            <?php if ($is_publicated && bff::servicesEnabled()) { ?>
              <a href="<?= $promote_url ?>" class="btn btn-sm btn-success"><?= _t('view', 'Рекламировать') ?></a>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php endif; # owner ?>
<script type="text/javascript">
  <?php js::start(); ?>
  $(function () {
    jView.init(<?= func::php2js(array(
      'lang' => array(
        'sendfriend' => array(
          'email' => _t('', 'E-mail адрес указан некорректно'),
          'success' => _t('', 'Сообщение было успешно отправлено'),
        ),
        'claim' => array(
          'reason_checks' => _t('item-claim', 'Укажите причину жалобы'),
          'reason_other' => _t('item-claim', 'Опишите причину подробнее'),
          'captcha' => _t('', 'Введите результат с картинки'),
          'success' => _t('item-claim', 'Жалоба успешно принята'),
        ),
      ),
      'item_id' => $id,
      'addr_lat' => $addr_lat,
      'addr_lon' => $addr_lon,
      'claim_other_id' => BBS::CLAIM_OTHER,
      'mod' => ($moderation ? BBS::moderationUrlKey($id) : ''),
      'msg_success' => !empty($msg_success) ? $msg_success : '',
      'msg_error' => !empty($msg_error) ? $msg_error : '',
    )) ?>);
  });

  // views stat popup
  $(function () {
    var statPopup = false, $container = $('#j-v-viewstat-desktop-popup-container');
    $('#j-v-viewstat-desktop-link-2').on('click', function (e) {
      nothing(e);
      if (statPopup === false) {
        bff.ajax('?act=views-stat', {}, function (data, errors) {
          if (data && data.success) {
            $container.html(data.popup);
            bff.st.includeJS('d3.v3.min.js', function () {
              jView.viewsChart('#j-v-viewstat-desktop-popup-chart', data.stat.data, data.lang);
              $('.j-popup', $container).modal();
              statPopup = true;
            });
          } else {
            app.alert.error(errors);
          }
        });
      } else {
        $('.j-popup', $container).modal();
      }
    });
  });
  <?php js::stop(); ?>
</script>