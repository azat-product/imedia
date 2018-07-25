<?php
/**
 * Фильтр по региону: layout
 * @var $this Geo
 * @var $coveringType integer тип покрытия
 * @var $regionID integer ID текущего региона
 * @var $regionData array данные о текущем регионе
 * @var $regionLevel integer тип текущего региона
 * @var $country array|false данные о текущей стране или FALSE
 * @var $noregions boolean выполнять пропуск шага выбора области (true)
 * @var $device string текущее устройство
 */

# при покрытии города: скрываем фильтр по региону
if ($coveringType == Geo::COVERING_CITY) {
    return;
}

/* Фильтр по региону (desktop): popup */
if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) { ?>
<?php if($coveringType == Geo::COVERING_COUNTRIES) {
  ?>
  <div id="j-f-country-desktop-popup" class="dropdown-menu l-regions">
    <!--for: desktop-->
    <div id="j-f-country-desktop-st0"<?php if($regionID > 0) { ?>  class="hide"<?php } ?>>
      <div class="dropdown-menu-heading">
        <div class="dropdown-menu-heading-title"><?= _t('filter', 'Выберите страну'); ?></div>
        <?= _t('filter', 'Искать объявления по <a[link]>всем странам</a>', array('link' => ' id="j-f-region-desktop-all" href="'.bff::urlBase().'" data="{id:0,pid:0,title:\''.HTML::escape(_t('filter', 'Все страны'), 'js').'\'}"')) ?>
      </div>
      <?= $this->filterData('desktop-countries-step0', 0); ?>
    </div>
    <div id="j-f-region-desktop-st1"<?php if(( ! $regionID || $regionLevel != Geo::lvlCountry) && ! $noregions) { ?> class="hide"<?php } ?>>
      <div class="f-navigation__region_change_desktop">
        <div class="l-regions-heading">
          <div class="l-regions-heading-left">
            <h3 class="l-regions-heading-title" id="j-f-region-desktop-country-title"><?= ! empty($country) ? $country['title'] : '' ?></h3>
          </div>
          <div class="l-regions-heading-right">
            <span class="l-regions-heading-right-item">
              <?php  $attr = ' id="j-f-country-desktop-all" ';
              if( ! empty($country)){
                $attr .= 'href="'.Geo::url(array('country' => $country['keyword'])).'" ';
                $attr .= 'data="{id:'.$country['id'].',pid:0,key:\''.$country['keyword'].'\'}" ';
              }else{
                $attr .= 'href="#"';
              }
              echo(_t('filter', 'Искать объявления по <a[attr]>всей стране</a>', array('attr' => $attr))); ?>
            </span>
            <span class="l-regions-heading-right-item">
              <a href="#" class="link-ajax change j-f-region-desktop-back"><span><?= _t('filter', 'Изменить страну'); ?></span></a>
            </span>
          </div>
        </div>
        <div class="l-regions-heading">
          <div class="l-regions-heading-left">
            <div class="<?= ($noregions ? ' hide' : '') ?>">
                <span class="hide"><?= _t('filter', 'Выберите регион:') ?></span>
                <input type="text" id="j-f-region-desktop-st1-q" class="form-control" placeholder="<?= _te('filter', 'Введите первые буквы...') ?>" />
            </div>
          </div>
        </div>
        
        <div id="j-f-region-desktop-st1-v" class="f-navigation__region_change_sub hidden-xs<?= $noregions ? ' hide' : '' ?>">
          <?php if( ! empty($country)) { echo $this->filterData('desktop-country-step1', $country['id']); } ?>
        </div>
        <div class="clearfix"></div>
      </div>
    </div>
    <div id="j-f-region-desktop-st2"<?php if($regionLevel < Geo::lvlRegion && ! $noregions) { ?> class="hide"<?php } ?>>
      <?php if($noregions){ if( ! empty($country['id'])){ echo $this->filterData('desktop-country-city-noregions', $regionID ? $regionID : $country['id']); } }else{
       if($regionLevel > Geo::lvlCountry) { echo $this->filterData('desktop-country-step2', $regionID); } }?>
     </div>
   </div>
   <?php } else if($coveringType == Geo::COVERING_COUNTRY) {
    if(empty($country)){ $country = Geo::regionData(Geo::coveringRegion());
      $noregions = ! empty($country['filter_noregions']); }
      ?>
      <div id="j-f-region-desktop-popup" class="l-regions dropdown-menu">
        <div id="j-f-region-desktop-st1"<?php if($regionID > 0 && ! $noregions) { ?>  class="hide"<?php } ?>>
          <div class="l-regions-heading">
            <div class="l-regions-heading-left">
              <div class="<?= ($noregions ? 'hide' : '') ?>">
                  <input type="text" id="j-f-region-desktop-st1-q" class="form-control" placeholder="<?= _te('filter', 'Введите первые буквы...') ?>" />
              </div>
            </div>
            <div class="l-regions-heading-right">
              <?= _t('filter', 'Искать объявления по') ?> <a href="<?= bff::urlBase() ?>" id="j-f-region-desktop-all" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter', 'Все регионы'), 'js') ?>'}"><?= _t('filter', 'всей стране') ?></a>
            </div>
          </div>
          
          <!-- Step 1 -->
          <div id="j-f-region-desktop-st1-v"<?= $noregions ? ' class="hide"' : '' ?>>
            <?= $this->filterData('desktop-country-step1', 0); ?>
          </div>
        </div>
        <div id="j-f-region-desktop-st2"<?php if( ! $regionID && ! $noregions ) { ?> class="hide"<?php } ?>>
          <?php if($noregions){ echo $this->filterData('desktop-country-city-noregions', $regionID ? $regionID : $country['id']); }
          else if($regionID > 0) { echo $this->filterData('desktop-country-step2', $regionID); } ?>
        </div>
      </div>
      <?php } else if ($coveringType == Geo::COVERING_REGION) { ?>
      <?= $this->filterData('desktop-region'); ?>
      <?php } else if ($coveringType == Geo::COVERING_CITIES) { ?>
      <?= $this->filterData('desktop-cities'); ?>
      <?php } ?>
      <?php }

      /* Фильтр по региону (phone) */
      if($device == bff::DEVICE_PHONE) { ?>
      <!--STAR select rerion-->
      <div class="select-ext">
        <div class="select-ext-container" style="width:100%">
          <a class="select-ext-bnt" href="#" id="j-f-region-phone-link">
            <span><?= ( $regionID > 0 ? $regionData['title'] : _t('filter', 'Все регионы') ) ?></span>
            <i class="fa fa-caret-down"></i>
          </a>
          <div id="j-f-region-phone-popup" class="select-ext-drop hide" style="width:99%;">
            <div class="select-ext-search">
              <input type="text" autocomplete="off" style="min-width: 183px;" id="j-f-region-phone-q" />
              <a href="#"><i class="fa fa-search"></i></a>
            </div>
            <ul class="select-ext-results" id="j-f-region-phone-q-list">
              <?= $this->filterData('phone-presuggest', ! empty($country['id']) ? $country['id'] : 0) ?>
            </ul>
            <div class="select-ext-no-results hide">
              <span><?= _t('filter', 'Не найдено - "[word]"', array('word'=>'<span class="word"></span>')) ?></span>
            </div>
          </div>
        </div>
      </div>
      <!--END select rerion-->
      <?php }