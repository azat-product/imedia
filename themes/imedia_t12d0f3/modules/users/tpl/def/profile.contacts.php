<?php
/**
 * Профиль пользователя: контакты
 * @var $this Users
 * @var $user array данные пользователя
 * @var $is_owner boolean профиль просматривает его владелец
 */
?>
<div class="ad-author">
  <div class="ad-author-in ad-author-user">
      <a href="<?= $user['profile_link'] ?>" class="ad-author-user-avatar">
          <img src="<?= $user['avatar'] ?>" alt=""/>
      </a>
      <div class="ad-author-user-info">
          <div class="ad-author-user-name"><?= $user['name'] ?></div>
          <?php if ($user['region_id']) { ?>
              <div class="ad-author-user-subtext"><?= $user['region_title'] ?></div>
          <?php } ?>
          <div class="ad-author-user-subtext">
              <?= _t('users', 'на сайте с [date]', array('date' => tpl::date_format2($user['created']))) ?>
          </div>
      </div>
  </div>
  <div class="ad-author-in">
      <? $aAvarageAuthorRatingData = ['value' => $avarage_author_rating]; ?>
      <span>
          <?= _t('view', 'Средняя оценка [user_title]', ['user_title' => empty($shop_id) ? 'автора:' : 'компании:']) ?>
      </span>
      <?= BBS::i()->viewPHP($aAvarageAuthorRatingData, 'item.rating.author.avarage'); ?>
  </div>
  <? if (!empty($avarage_author_categories_rating)): ?>
      <? foreach ($avarage_author_categories_rating as $category_rating): ?>
          <div class="ad-author-in">
              <? $aAvarageAuthorCategoryData = ['value' => $category_rating['value']]; ?>
              <span><?= _t('view', $category_rating['title']) ?></span>
              <?= BBS::i()->viewPHP($aAvarageAuthorCategoryData, 'item.rating.author.cat.avarage'); ?>
          </div>
      <? endforeach; ?>
  <? endif; ?>
  <?php if(!empty($user['phones']) || !empty($user['contacts'])) { ?>
    <div class="ad-author-in ad-author-contact">
      <div class="ad-author-contact-row">
        <div class="ad-author-contact-row-label">
          <?= _t('users', 'Контакты') ?>:
        </div>
        <div class="ad-author-contact-row-content">
          <a href="#"
             class="link-ajax j-user-profile-c-toggler"><span><?= _t('users', 'показать контакты') ?></span></a>
        </div>
      </div>

      <?php if (!empty($user['phones'])) { ?>
        <div class="ad-author-contact-row">
          <div class="ad-author-contact-row-label">
            <?= _t('users', 'Тел.') ?>:
          </div>
          <div class="ad-author-contact-row-content j-user-profile-c-phones">
            <?php foreach ($user['phones'] as $v) { ?>
              <div><?= $v['m'] ?></div><?php } ?>
          </div>
        </div>
      <?php } # phones ?>
      <?php if (!empty($user['contacts'])): ?>
        <?php foreach (Users::contactsFields($user['contacts']) as $contact): ?>
          <div class="ad-author-contact-row">
            <div class="ad-author-contact-row-label">
              <?= $contact['title'] ?>:
            </div>
            <div class="ad-author-contact-row-content j-user-profile-c-<?= $contact['key'] ?>">
              <?= tpl::contactMask($contact['value']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; # contacts ?>

    </div><!-- /.ad-author-contact -->
  <?php } ?>
  <?php if ($is_owner) { ?>
    <div class="ad-author-in">
      <a href="<?= Users::url('my.settings', array('t' => 'contacts')) ?>" class="btn btn-default btn-block btn-sm"><i
          class="fa fa-edit"></i> <?= _t('users', 'Редактировать') ?></a>
    </div>
  <?php } ?>
</div>