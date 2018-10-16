<div class="ad-author">
  <div class="ad-author-in ad-author-shop">
    <?php if ($shop['logo']) { ?>
      <div class="ad-author-shop-logo">
        <a href="<?= $shop['link'] ?>">
          <img src="<?= $shop['logo'] ?>" alt=""/>
        </a>
      </div>
    <?php } ?>
    <?php if (!empty($shop['site'])) { ?>
      <div class="ad-author-shop-website">
        <a href="<?= bff::urlAway($shop['site']) ?>" target="_blank" rel="nofollow" class="link-ico j-away"><i
            class="fa fa-globe"></i> <span><?= $shop['site'] ?></span></a>
      </div>
    <?php } ?>
    <div class="ad-author-shop-location">
      <?php if (!empty($shop['addr_addr'])) {
        if ($shop['addr_map']) {
          Geo::mapsAPI(false);
        } ?>
        <div class="ad-author-shop-location-info"><?= $shop['region_title'] . ', ' . $shop['addr_addr'] ?></div>
        <?php if ($shop['addr_map']) { ?>
          <a href="#" class="link-ajax" id="j-shop-view-map-toggler"><i class="fa fa-map-marker"></i>
            <span><?= _t('shops', 'Показать на карте') ?></span></a>
        <?php } ?>
        <?php if ($shop['addr_map']) { ?>
          <div id="j-shop-view-map-popup" class="ad-author-map" style="display: none;">
            <div id="j-shop-view-map-container" class="ad-author-map-container"></div>
          </div>
        <?php } ?>

      <?php } ?>
    </div>
  </div><!-- /.ad-author-shop -->

  <?php if ($shop['has_contacts']) { ?>
    <div class="ad-author-in ad-author-contact">
      <div class="ad-author-contact-row">
        <div class="ad-author-contact-row-label">
          <?= _t('shops', 'Контакты') ?>:
        </div>
        <div class="ad-author-contact-row-content">
          <a href="#" class="link-ajax j-shop-view-c-toggler"><span><?= _t('shops', 'показать контакты') ?></span></a>
        </div>
      </div>
      <?php if (!empty($shop['phones'])) { ?>
        <div class="ad-author-contact-row">
          <div class="ad-author-contact-row-label">
            <?= _t('users', 'Тел.') ?>:
          </div>
          <div class="ad-author-contact-row-content j-shop-view-c-phones">
            <?php foreach ($shop['phones'] as $v) { ?>
              <div><?= $v['m'] ?></div><?php } ?>
          </div>
        </div>
      <?php } ?>
      <?php if (!empty($shop['contacts'])): ?>
        <?php foreach (Users::contactsFields($shop['contacts']) as $contact): ?>
          <div class="ad-author-contact-row">
            <div class="ad-author-contact-row-label">
              <?= $contact['title'] ?>:
            </div>
            <div class="ad-author-contact-row-content j-shop-view-c-<?= $contact['key'] ?>">
              <?= tpl::contactMask($contact['value']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!empty($shop['social']) && $social) { ?>
        <div class="ad-author-contact-social">
          <?php foreach ($shop['social'] as $v) {
            if ($v && isset($social[$v['t']])) { ?>
              <a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow noopener" target="_blank"
                 class="c-social"><?= $social[$v['t']]['icon'] ?></a><?php
            }
          } ?>
          <div class="clearfix"></div>
        </div>
      <?php } ?>
    </div><!-- /.ad-author-in -->
    <?php if (!$is_owner && $has_owner) { ?>
      <div class="ad-author-in">
        <a class="btn btn-block btn-info" href="<?= Shops::urlContact($shop['link']) ?>"><i
            class="fa fa-envelope"></i> <?= _t('users', 'Написать сообщение') ?></a>
      </div>
    <?php } ?>
  <?php } # $shop['has_contacts'] ?>
    <div class="ad-author-in">
        <? $aAvarageAuthorRatingData = ['value' => $avarage_author_rating]; ?>
        <span>
          <?= _t('view', 'Средняя оценка компании') ?>
      </span><br><?= BBS::i()->viewPHP($aAvarageAuthorRatingData, 'item.rating.author.avarage'); ?>
    </div>
    <? if (!empty($avarage_author_categories_rating)): ?>
        <div class="ad-author-in">
            <? foreach ($avarage_author_categories_rating as $category_rating): ?>
                <? $aAvarageAuthorCategoryData = ['value' => $category_rating['value']]; ?>
                <span><?= _t('view', $category_rating['title']) ?></span>
                <br><?= BBS::i()->viewPHP($aAvarageAuthorCategoryData, 'item.rating.author.cat.avarage'); ?><br>
            <? endforeach; ?>
        </div>
    <? endif; ?>

</div><!-- /.ad-author -->