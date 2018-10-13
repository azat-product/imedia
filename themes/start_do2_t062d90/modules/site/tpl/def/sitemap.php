<?php
/**
 * Карта сайта
 * @var $this Site
 * @var $breadcrumb string название хлебной крошки или пустая строка
 * @var $titleh1 string заголовок H1 или пустая строка
 * @var $cats array категории
 * @var $seotext string SEO-текст
 */
?>

<?php
  echo tpl::getBreadcrumbs(array(array('title'=>(!empty($breadcrumb) ? $breadcrumb : _t('', 'Карта сайта')), 'link'=>'#', 'active'=>true)));
?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= (!empty($titleh1) ? $titleh1 : _t('', 'Карта сайта')) ?></h1>
    </div>
    <div class="row">
      <?php foreach($cats as &$row) { ?>
      <div class="col-md-4">
        <?php foreach($row as &$c) { ?>
        <div class="l-sitemap-item">
          <div class="l-sitemap-item-img">
            <img src="<?= $c['icon'] ?>" alt="<?= $c['title'] ?>" />
          </div>
          <div class="l-sitemap-item-content">
            <div class="l-sitemap-item-title">
              <?php if ($c['items'] > 0) { ?>
              <a href="<?= $c['link'] ?>"><?= $c['title'] ?></a>
              <?php } else { ?>
              <span class="hidden-link" data-link="<?= $c['link'] ?>"><?= $c['title'] ?></span>
              <?php } ?>
            </div>
            <ul class="l-sitemap-item-subcats">
              <?php foreach($c['subs'] as &$cc) { ?>
              <li>
                <?php if ($cc['items'] > 0) { ?>
                <a href="<?= $cc['link'] ?>"><?= $cc['title'] ?></a>
                <?php } else { ?>
                <span class="hidden-link" data-link="<?= $cc['link'] ?>"><?= $cc['title'] ?></span>
                <?php } ?>
              </li>
              <?php } unset($cc); ?>
            </ul>
          </div>
        </div>
        <?php } unset($c); ?>
      </div>
      <?php } unset($row); ?>
    </div>

    <div class="l-seoText"><?= $seotext; ?></div>

  </div>

</div>