<?php
/**
 * Поиск объявлений: форма поиска (layout)
 * @var $this BBS
 * @var $f array параметры фильтра
 * @var $catID integer ID текущей категории
 * @var $catData array данные о текущей категории
 * @var $catACTIVE boolean является ли текущая категория выбранной
 * @var $catACTIVE_STEP integer текущий уровень выбора категории: 1,2
 * @var $filterDesktopBlock string блок фильтров desktop/tablet версии (HTML)
 * @var $filterPhoneBlock string блок фильтров phone версии (HTML)
 * @var $filterVertical boolean включен вертикальный фильтр
 */

tpl::includeJS(array('bbs.search'), false, 7);
extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
?>

<div class="container">
  <div class="l-filter">
  <noindex>
      <form id="j-f-form" action="<?= BBS::url('items.search', $catData) ?>" method="get">
        <input type="hidden" name="c" value="<?= $f_c ?>"<?php if( ! $f_c ) { ?> disabled="disabled"<?php } ?> />
        <input type="hidden" name="ct" value="<?= $f_ct ?>" />
        <input type="hidden" name="lt" value="<?= $f_lt ?>" />
        <input type="hidden" name="sort" value="<?= $f_sort ?>" />
        <input type="hidden" name="page" value="<?= $f_page ?>" />

        <div class="l-filter-form">
          <!-- Region -->
          <?= View::template('filter.region'); ?>

          <!-- Categories -->
          <div class="l-filter-form-btn dropdown">
              <a class="btn btn-default" href="#" id="j-f-cat-desktop-link">
                <?= ( $catACTIVE ? (DEVICE_DESKTOP_OR_TABLET ? $catData['dropdown']['title'] : $catData['title']) : _t('bbs','Все категории') ) ?>
                <b class="caret"></b>
              </a>
              <div id="j-f-cat-desktop-popup" class="l-categories-dropdown dropdown-menu">
                <div id="j-f-cat-desktop-step1"<?php if($catACTIVE_STEP != 1) { ?> class="hide"<?php } ?>>
                  <?= BBS::i()->catsList('search', bff::DEVICE_DESKTOP, 0); ?>
                </div>
                <div id="j-f-cat-desktop-step2"<?php if($catACTIVE_STEP != 2) { ?> class="hide"<?php } ?>>
                  <?php if($catACTIVE_STEP == 2) { echo BBS::i()->catsList('search', bff::DEVICE_DESKTOP, $catData['dropdown']['id']); } ?>
                </div>
              </div>
          </div>

          <!-- Input -->
          <div class="l-filter-form-input dropdown">
            <input type="text" name="q" id="j-f-query" class="form-control" onkeyup="$(this).next().val($(this).val());" placeholder="<?= _te('bbs','Поиск объявлений...') ?>" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
            <input type="text" name="mq" autocomplete="off" value="<?= HTML::escape($f_q) ?>" maxlength="80" style="display: none;" />
            <div id="j-search-quick-dd" class="l-filter-qsearch dropdown-menu">
              <div class="f-qsearch__results j-search-quick-dd-list"></div>
            </div>
          </div>
          
          <div class="l-filter-form-submit">
            <button type="submit" class="btn btn-default j-submit"><?= _t('bbs','Найти') ?></button>
          </div>
        </div>

        <!-- Filter -->
        <?php if($catACTIVE && !$filterVertical) { ?>
        
          <?php if (DEVICE_DESKTOP_OR_TABLET) { ?>
          <!-- Desktop & Tablet Filter -->
          <div class="l-filter-options" id="j-f-desktop">
            <?= $filterDesktopBlock; ?>
          </div>
          <?php } ?>

          <?php if (DEVICE_PHONE) { ?>
          <!-- Mobile Filter -->
          <div class="l-filter-m">
            <span class="f-catfiltermob__content__title">
              <a class="btn btn-default btn-block" href="javascript:void(0);"><?= ( $f_filter_active ? _t('bbs', 'Изменить настройки фильтра') : _t('bbs', 'Фильтровать результаты') ) ?></a>
            </span>
            <div class="l-filter-m-content hide" id="j-f-phone">
              <?= $filterPhoneBlock; ?>
              <button type="button" class="btn btn-small btn-info j-submit"><?= _t('bbs', 'Отфильтровать') ?></button>
              <button type="button" class="btn btn-small btn-default j-cancel"><?= _t('bbs', 'Отменить') ?></button>
            </div>
          </div>
          <?php } ?>

        <?php } ?>

      </form>
    </noindex>
  </div><!-- /.l-filter -->
</div><!-- /.container -->
