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

<div class="l-pageHeading">
  <h1 class="l-pageHeading-title"><?= (!empty($titleh1) ? $titleh1 : _t('svc', 'Как продать быстрее?')) ?></h1>
</div>

<ul class="nav nav-pills mrgb15" role="tablist">
  <li class="active"><a href="#tab-ads" aria-controls="tab-ads" role="tab" data-toggle="tab"><?= _t('svc', 'Объявления') ?></a></li>
  <li><a href="#tab-shops" aria-controls="tab-shops" role="tab" data-toggle="tab"><?= _t('svc', 'Магазин') ?></a></li>
</ul>

<div class="tab-content">
  
  <!-- Ads tab -->
  <div role="tabpanel" class="tab-pane active" id="tab-ads">
    
    <?php foreach($svc_bbs as $v) { ?>
    <div class="l-serviceItem">
      <div class="l-serviceItem-img">
        <img src="<?= $v['icon_b'] ?>" class="hidden-xs" alt="" />
        <img src="<?= $v['icon_s'] ?>" class="visible-xs" alt="" />
      </div>
      <div class="l-serviceItem-content">
        <h3 class="l-serviceItem-title"><?= $v['title_view'] ?></h3>
        <?= nl2br($v['description_full']) ?>
      </div>
    </div>
    <?php } ?>

    <div class="l-serviceAds">
      <div class="l-serviceAds-item">
        <div class="l-serviceAds-item-l">
          <?= _t('svc', 'Подайте новое объявление и сделайте его заметным') ?>
        </div>
        <div class="l-serviceAds-item-r">
          <a class="btn btn-success btn-block" href="<?= BBS::url('item.add') ?>"> <i class="fa fa-plus"></i> <?= _t('svc', 'Добавить объявление') ?></a>
        </div>
      </div>
      <?php if($user_logined) { ?>
      <div class="l-serviceAds-item">
        <div class="l-serviceAds-item-l">
          <?= _t('svc', 'Рекламируйте уже существующие объявления') ?>
        </div>
        <div class="l-serviceAds-item-r">
          <a class="btn btn-default btn-block" href="<?= BBS::url('my.items') ?>"><i class="fa fa-user"></i> <?= _t('svc', 'Мои объявления') ?></a>
        </div>
      </div>
      <?php } ?>
    </div>

  </div><!-- /#tab-ads -->

  <!-- Shops tab -->
  <div role="tabpanel" class="tab-pane" id="tab-shops">
    
    <?php foreach($svc_shops as $v) { ?>
    <div class="l-serviceItem">
      <div class="l-serviceItem-img">
        <img src="<?= $v['icon_b'] ?>" class="hidden-xs" alt="" />
        <img src="<?= $v['icon_s'] ?>" class="visible-xs" alt="" />
      </div>
      <div class="l-serviceItem-content">
        <h3 class="l-serviceItem-title"><?= $v['title_view'] ?></h3>
        <?= nl2br($v['description_full']) ?>
      </div>
    </div>
    <?php } ?>

    <div class="l-serviceAds">
      <?php if ($shop_opened) { ?>
      <div class="l-serviceAds-item">
        <div class="l-serviceAds-item-l">
          <?= _t('shops', 'Рекламируйте свой магазин') ?>
        </div>
        <div class="l-serviceAds-item-r">
          <a class="btn btn-success btn-block" href="<?= $shop_promote_url ?>"><i class="fa fa-arrow-up"></i> <?= _t('shops', 'Рекламировать') ?></a>
        </div>
      </div>
      <?php } else { ?>
      <div class="l-serviceAds-item">
        <div class="l-serviceAds-item-l">
          <?= _t('shops', 'Откройте свой магазин') ?>
        </div>
        <div class="l-serviceAds-item-r">
          <a class="btn btn-success btn-block" href="<?= $shop_open_url ?>"><i class="fa  fa-plus white"></i> <?= _t('shops', 'Открыть магазин') ?></a>
        </div>
      </div>
      <?php } ?>
    </div>

  </div><!-- /#tab-shops -->

</div>

<?php if (!empty($seotext)) { ?>
<div class="l-seoText">
  <?= $seotext ?>
</div>
<?php } ?>