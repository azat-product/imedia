<?php
/**
 * Переключатель языка
 * @var $lang string ключ текущего языка
 * @var $languages array список языков
 */
 if (sizeof($languages) > 1) { ?>
 <div class="l-footer-lang dropdown">
  <?= _t('', 'Язык:') ?>
  <a data-current="<?= HTML::escape($lang) ?>" href="#" data-toggle="dropdown">
    <?= $languages[$lang]['title'] ?> <b class="caret"></b>
  </a>
  <ul class="dropdown-menu">
    <?php foreach ($languages as $k => $v): ?>
      <li<?php if ($v['active']) { ?> class="active"<?php } ?>>
        <a href="<?= ($v['active'] ? 'javascript:void(0);' : $v['url']) ?>">
          <span class="country-icon-element">
            <span class="country-icon country-icon-<?= $v['country'] ?>"></span>
            <?= $v['title'] ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php }