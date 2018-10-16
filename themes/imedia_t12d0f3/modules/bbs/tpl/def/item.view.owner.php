<?php

/**
 * Просмотр объявления: блок автора объявления
 * @var $this BBS
 * @var $user array данные пользователя (автора)
 * @var $owner boolean авторизованный пользователь = автор просматриваемого объявления
 * @var $contacts array контактные данные
 * @var $shop_id integer ID магазина
 * @var $shop array данные о магазине
 */

?>

<div class="ad-author">

  <?php // Shop
  if ($shop_id && $shop) { ?>
    <div class="ad-author-in ad-author-shop">
      <?php if ($shop['logo']) { ?>
      <div class="ad-author-shop-logo">
        <a href="<?= $shop['link'] ?>">
          <img src="<?= $shop['logo'] ?>" alt="" />
        </a>
      </div>
      <?php } ?>
      <div class="ad-author-shop-name">
        <a href="<?= $shop['link'] ?>" title="<?= $shop['title'] ?>"><?= $shop['title'] ?></a>
      </div>
      <div class="ad-author-shop-descr">
        <?php if (($descr_limit = 100) && mb_strlen($shop['descr']) > $descr_limit) { ?>
          <div><?= tpl::truncate($shop['descr'], $descr_limit ,'', true) ?><a href="#" class="link-ajax" id="j-view-owner-shop-descr-ex"><span>...</span></a></div>
          <div class="hide"><?= mb_substr($shop['descr'], $descr_limit); ?></div>
          <script type="text/javascript">
            <?php js::start() ?>
            $(function(){
              $('#j-view-owner-shop-descr-ex').on('click', function(e){ nothing(e);
                var $content = $(this).parent(); $(this).remove();
                $content.html($content.text() + $content.next().text());
              });
            });
            <?php js::stop() ?>
          </script>
        <?php } else { ?>
          <?= $shop['descr']; ?>
        <?php } ?>
      </div>
      <?php if ( ! empty($shop['site'])) { ?>
      <div class="ad-author-shop-website">
        <a href="<?= bff::urlAway($shop['site']) ?>" target="_blank" rel="nofollow" class="link-ico j-away"><i class="fa fa-globe"></i> <span><?= $shop['site'] ?></span></a>
      </div>
      <?php } ?>

      <?php if ( ! empty($shop['addr_addr'])) { ?>
      <div class="ad-author-shop-location">
        <?php
          $addr_map = ( floatval($shop['addr_lat']) && floatval($shop['addr_lon']) );
          if ($addr_map) {
            Geo::mapsAPI(false);
          }
        ?>
        <div class="ad-author-shop-location-info"><?= $shop['region_title'].', '.$shop['addr_addr'] ?></div>
        <?php if ($addr_map){ ?>
        <a href="#" class="link-ajax" id="j-view-owner-shop-map-toggler"><i class="fa fa-map-marker"></i> <span><?= _t('view', 'Показать на карте') ?></span></a>
        <div id="j-view-owner-shop-map-popup" class="ad-author-map" style="display: none;">
          <div id="j-view-owner-shop-map-container" class="ad-author-map-container"></div>
          <script type="text/javascript">
            <?php js::start() ?>
            $(function(){
              var jViewShopMap = (function(){
                var map = false;
                app.popup('view-shop-map', '#j-view-owner-shop-map-popup', '#j-view-owner-shop-map-toggler', {
                  onShow: function($p){
                    $p.fadeIn(100, function(){
                      if (map) {
                        map.panTo([<?= HTML::escape($shop['addr_lat'].','.$shop['addr_lon'], 'js') ?>], {delay: 10, duration: 200});
                      } else {
                        map = app.map('j-view-owner-shop-map-container', '<?= HTML::escape($shop['addr_lat'].','.$shop['addr_lon'], 'js') ?>', false, {
                          marker: true,
                          zoom: 12,
                          controls: 'view'
                        });
                      }
                    });
                  }
                });
              }());
            });
            <?php js::stop() ?>
          </script>
        </div>
        <?php } ?>
      </div>
      <?php } ?>
    </div><!-- /.ad-author-shop -->
  <?php } # Shop
  // User
  else { ?>
    <div class="ad-author-in ad-author-user">
      <a href="<?= $user['link'] ?>" class="ad-author-user-avatar">
        <img src="<?= $user['avatar'] ?>" class="img-circle" alt="" />
      </a>
      <div class="ad-author-user-info">
        <div class="ad-author-user-name"><?= $name ?></div>
        <?php if ($owner_type == BBS::OWNER_PRIVATE) { ?>
          <div class="ad-author-user-type"><?= _t('view', 'частное лицо') ?></div>
        <?php } ?>
        <?php if ($user['created']!=='0000-00-00 00:00:00') { ?>
          <div class="ad-author-user-created"><?= _t('view', 'на сайте с [date]', array('date'=>tpl::date_format2($user['created']))) ?></div>
        <?php } ?>
        <div class="ad-author-user-all">
          <a href="<?= $user['link'] ?>"><?= _t('view', 'Все объявления автора') ?></a>
        </div>
      </div>
    </div>
  <?php } ?>

  <?php if($contacts['has'] || ! empty($shop['social'])) { ?>
  <div class="ad-author-in ad-author-contact">
    <div class="ad-author-contact-row">
      <div class="ad-author-contact-row-label">
        <?= _t('view', 'Контакты:') ?>
      </div>
      <div class="ad-author-contact-row-content">
        <a href="#" class="link-ajax j-v-contacts-expand-link"><span><?= _t('view', 'показать контакты') ?></span></a>
      </div>
    </div>
    <div class="j-v-contacts-expand-block">
      <?php if ( ! empty($contacts['phones']) ) { ?>
      <div class="ad-author-contact-row">
        <div class="ad-author-contact-row-label">
          <?= _t('view', 'Тел.') ?>:
        </div>
        <div class="ad-author-contact-row-content j-c-phones">
          <?php foreach($contacts['phones'] as $v) { ?><div><?= $v ?></div><?php } ?>
        </div>
      </div>
      <?php } # phones ?>
      <?php if(!empty($contacts['contacts'])): ?>
        <?php foreach (Users::contactsFields($contacts['contacts']) as $contact): ?>
          <div class="ad-author-contact-row">
            <div class="ad-author-contact-row-label">
              <?= $contact['title'] ?>:
            </div>
            <div class="ad-author-contact-row-content j-c-<?= $contact['key'] ?>">
              <?= tpl::contactMask($contact['value']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php if ($shop_id && $shop && ! empty($shop['social'])) { ?>
    <?php $social = Shops::socialLinksTypes(); ?>
    <div class="ad-author-contact-social">
      <?php foreach($shop['social'] as $v):
        if ($v && isset($social[$v['t']])) {
      ?>
        <a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow noreferrer noopener" target="_blank"><?= $social[$v['t']]['icon'] ?></a><?php
      } endforeach; ?>
    </div>
    <?php } ?>
  </div><!-- /.ad-author-contact -->
  <?php } # $contacts['has'] ?>

    <? // TODO: clazion IK-6 IK-7 IK-8 Возможно стоит сделать чтоб было ровно (звезды Ваша и Срелняя на одно уровне)?>
    <? // TODO: clazion IK-6 Сделать модальное окно если пользователь не авторизирован и нажимает на звезды в "Ваша")?>
    <? // TODO: clazion IK-8 Разместить полученные оценки согласно макапам в задаче?>

    <div class="ad-author-in">
        <? $aCurrentUserRatingData = ['value' => $current_user_item_rating]; ?>
        <span><?= _t('view', 'Ваша оценка') ?></span><br><?=$this->viewPHP($aCurrentUserRatingData, 'item.rating.current.user'); ?>
    </div>


        <div class="ad-author-in">
            <? $aAvarageItemRatingData = ['value' => $avarage_item_rating, 'allow_edit' => true, 'class_prefix' => 'edit_']; ?>
            <span><?= _t('view', 'Средняя объявления') ?></span><br><?=$this->viewPHP($aAvarageItemRatingData, 'item.rating.avarage'); ?>
        </div>

    <div class="ad-author-in">
        <? $aAvarageAuthorRatingData = ['value' => $avarage_author_rating, 'class_prefix' => 'edit_']; ?>
        <span>
            <?= _t('view', 'Средняя оценка [user_title]', ['user_title'=> empty($shop_id) ? 'автора' : 'компании']) ?>
        </span><br><?=$this->viewPHP($aAvarageAuthorRatingData, 'item.rating.author.avarage'); ?>
    </div>

    <? if (!empty($avarage_author_categories_rating)): ?>
        <div class="ad-author-in">
            <? foreach ($avarage_author_categories_rating as $category_rating): ?>
            <? $aAvarageAuthorCategoryData = ['value' => $category_rating['value']]; ?>
            <span><?= _t('view', $category_rating['title']) ?></span><br><?=$this->viewPHP($aAvarageAuthorCategoryData, 'item.rating.author.cat.avarage'); ?><br>
            <? endforeach; ?>
        </div>
    <? endif; ?>

    <?php if ( ! $owner) { ?>
  <div class="ad-author-in">
    <a class="btn btn-block btn-info" href="#contact-form"><i class="fa fa-envelope"></i> <?= _t('view', 'Написать автору') ?></a>
  </div>
  <?php } ?>
</div><!-- /.ad-author -->

<script type="text/javascript">
    <?php js::start() ?>
        var star_rating_user = $('.star-rating-user .fa');
        var star_rating_avarage = $('.edit_star-rating-item-avarage .fa');
        var star_rating_author_avarage = $('.edit_star-rating-author-avarage .fa');
        var itemId = <?=$item_id?>;
        var userId = <?=User::id()?>;
        var authorId = <?=$user['id']?>;
        var isShop = <?= (int)!empty($shop_id)?>;

        var SetRatingStar = function() {
            star_rating_user.each(function() {
                if (Math.round(star_rating_user.siblings('input.rating-value').val()) >= parseInt($(this).data('rating'))) {
                    return $(this).removeClass('fa-star-o').addClass('fa-star');
                } else {
                    return $(this).removeClass('fa-star').addClass('fa-star-o');
                }
            });

            star_rating_avarage.each(function() {
                if (Math.round($('#star-rating-item-avarage').html()) >= parseInt($(this).data('rating'))) {
                    return $(this).removeClass('fa-star-o').addClass('fa-star');
                } else {
                    return $(this).removeClass('fa-star').addClass('fa-star-o');
                }
            });

            star_rating_author_avarage.each(function() {
                if (Math.round($('#star-rating-author-avarage').html()) >= parseInt($(this).data('rating'))) {
                    return $(this).removeClass('fa-star-o').addClass('fa-star');
                } else {
                    return $(this).removeClass('fa-star').addClass('fa-star-o');
                }
            });

            return;
        };

        star_rating_user.on('click', function() {

            if (!userId) {
                <? // TODO::IK-6 Сделать модальное окно если пользователь не авторизирован и нажимает на звезды в "Ваша")?>
                return;
            }

            bff.ajax(bff.ajaxURL('bbs&ev=raitings', ''),
                {
                    item_id: itemId,
                    user_id: userId,
                    author_id: authorId,
                    is_shop: isShop,
                    value: $(this).data('rating')
                },
                function(data,errors) {
                if(data && data.success) {
                    $('#star-rating-item-avarage').html(data.avarage_item_rating);
                    $('#star-rating-author-avarage').html(data.avarage_author_rating);
                    star_rating_user.siblings('input.rating-value').val(data.new_rating);
                    SetRatingStar();
                }
            });

            return;
        });

        $(document).ready(function() {
        });
    <?php js::stop() ?>
</script>
