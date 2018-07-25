<?php
/**
 * Поиск магазинов: форма поиска
 * @var $this Shops
 * @var $f array параметры фильтра
 * @var $catID integer ID текущей категории
 * @var $catData array данные о текущей категории
 * @var $catACTIVE boolean является ли текущая категория выбранной
 * @var $catACTIVE_STEP integer текущий уровень выбора категории: 1,2
 */

tpl::includeJS('shops.search', false, 4);

extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f'); # параметры фильтра => переменные с префиксом f_

?>
<div class="container">
  <div class="l-filter">
    <noindex>
      <form id="j-f-form" action="<?= Shops::url('search', $catData) ?>" method="get">
        <input type="hidden" name="c" value="<?= $f_c ?>"<?php if( ! $f_c ) { ?> disabled="disabled"<?php } ?> />
        <input type="hidden" name="lt" value="<?= $f_lt ?>" />
        <input type="hidden" name="page" value="<?= $f_page ?>" />
        <div class="l-filter-form">
          <!-- Region -->
          <?= View::template('filter.region'); ?>

          <!-- Categories -->
          <div class="l-filter-form-btn dropdown">
            <a class="btn btn-default" href="#" id="j-f-cat-desktop-link">
              <?= ( $catACTIVE ? $catData['title'] : _t('shops','Все категории') ) ?>
              <b class="caret"></b>
            </a>
            <div id="j-f-cat-desktop-popup" class="l-categories-dropdown dropdown-menu">
              <div id="j-f-cat-desktop-step1"<?php if($catACTIVE_STEP != 1) { ?> class="hide"<?php } ?>>
                <?= $this->catsList('search', bff::DEVICE_DESKTOP, 0); ?>
              </div>
              <div id="j-f-cat-desktop-step2"<?php if($catACTIVE_STEP != 2) { ?> class="hide"<?php } ?>>
                <?php if($catACTIVE_STEP == 2) { echo $this->catsList('search', bff::DEVICE_DESKTOP, $catID); } ?>
              </div>
            </div>
          </div>
          
          <!-- input -->
          <div class="l-filter-form-input dropdown">
            <input type="text" name="q" id="j-f-query" class="form-control" onkeyup="$(this).next().val($(this).val());" placeholder="<?= _te('shops','Поиск магазинов...') ?>" autocomplete="off" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
            <input type="text" name="qm" autocomplete="off" value="<?= HTML::escape($f_q) ?>" maxlength="80" style="display: none;">
            <div id="j-search-quick-dd" class="l-filter-qsearch dropdown-menu">
              <div class="f-qsearch__results j-search-quick-dd-list"></div>
            </div>
          </div>

          <div class="l-filter-form-submit">
            <button type="submit" class="btn btn-default"><?= _t('shops','Найти') ?></button>
          </div>

        </div>
      </form>
    </noindex>
  </div><!-- /.l-filter -->
</div><!-- /.container -->