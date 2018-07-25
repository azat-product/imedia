<?php
/**
 * Помощь: форма поиска
 * @var $this Help
 * @var $f array параметры фильтра
 */

extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

?>

<div class="container">
  <div class="l-filter">
    <noindex>
      <form id="j-f-form" action="<?= Help::url('search') ?>" method="get">
        <?php if(bff::$event == 'search') { ?><input type="hidden" name="page" value="<?= $f['page'] ?>" /><?php } ?>
        <div class="l-filter-form">
          <!-- input -->
          <div class="l-filter-form-input dropdown">
            <input type="text" name="q" id="j-f-query" class="form-control" placeholder="<?= _te('help','Поиск вопросов...') ?>" autocomplete="off" style="width: 100%" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
            <div id="j-search-quick-dd" class="l-filter-qsearch dropdown-menu">
              <div class="f-qsearch__results j-search-quick-dd-list"></div>
            </div>
          </div>
          
          <div class="l-filter-form-submit">
            <button type="submit" class="btn btn-default"><?= _t('help','Найти') ?></button>
          </div>
        </div>
      </form>
    </noindex>
  </div><!-- /.l-filter -->
</div><!-- /.container -->