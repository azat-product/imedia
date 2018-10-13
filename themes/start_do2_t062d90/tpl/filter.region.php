<?php
/**
 * Фильтр по региону
 */
$coveringCity = Geo::coveringType(Geo::COVERING_CITY);
if ( ! $coveringCity)
{
    tpl::includeJS('filter', false, 6);
    $regionID = 0;
    $confirmView = false;
    if (Geo::ipLocationConfirm()) {
        Geo::filterUser(0);
        $regionData = Geo::regionFilterByIp();
        $confirmView = !empty($regionData['id']);
    } else {
        $regionData = Geo::filter(); # user
    }
    if ( ! empty($regionData['id'])) {
        $regionID = $regionData['id'];
    }
}
$titleAll = _t('filter', 'Все регионы');
if ( ! $coveringCity && Geo::coveringType(Geo::COVERING_COUNTRIES)) {
    $titleAll = _t('filter', 'Все страны');
}
?>

<?php if ( ! $coveringCity) { ?>
<div class="l-filter-form-btn l-filter-form-region">
  <a class="btn btn-default" href="#" id="j-f-region-desktop-link">
    <span><?= ( $regionID > 0 ? $regionData['title'] : $titleAll ) ?></span>
    <b class="caret"></b>
  </a>
    <?php // Confirm Region
      if ($confirmView) { ?>
      <div class="dropdown-menu show" id="j-f-region-desktop-confirm" style="padding: 10px;">
        <b><?= _t('filter', 'Ваш регион [region]?', array('region' => $regionData['title'])) ?></b>
        <hr />
        <button type="button" data-id="<?= $regionID ?>" data-redirect="<?= HTML::escape(bff::urlRegionChange($regionData['keyword'])); ?>" class="btn btn-success j-confirm-yes"><?= _t('filter','Да') ?></button>
        <a href="#" class="btn j-confirm-no" data-filter-text="<?= HTML::escape($titleAll) ?>"><?= _t('filter','Нет, выбрать другой') ?></a>
      </div>      
    <?php } ?>
    <?php // Region Select
      echo Geo::i()->filterForm(bff::DEVICE_DESKTOP);
    ?>
</div>
<?php } ?>