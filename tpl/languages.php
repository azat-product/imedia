<?php
/**
 * Переключатель языка
 * @var $lang string ключ текущего языка
 * @var $languages array список языков
 * @var $prefix string уникальный префикс
 */
 if (sizeof($languages) > 1) { ?>
    <div class="l-footer__lang rel">
        <?= _t('', 'Язык:') ?>
        <a class="dropdown-toggle ajax ajax-ico" id="j-<?= $prefix ?>-dd-link" data-current="<?= HTML::escape($lang) ?>" href="javascript:void(0);">
            <span class="lnk"><?= $languages[$lang]['title'] ?></span> <i class="fa fa-caret-down"></i>
        </a>
        <div class="dropdown-menu dropdown-block box-shadow" id="j-<?= $prefix ?>-dd">
            <ul>
                <?php foreach ($languages as $k=>$v): ?>
                <li>
                    <a href="<?= ($v['active'] ? 'javascript:void(0);' : $v['url']) ?>" class="country-icon-element <?php if($v['active']) { ?> active<?php } ?>">
                        <span class="country-icon country-icon-<?= $v['country'] ?>"></span>
                        <span><?= $v['title'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <script type="text/javascript">
        <?php js::start(); ?>
        $(function(){
            app.popup('<?= $prefix ?>', '#j-<?= $prefix ?>-dd', '#j-<?= $prefix ?>-dd-link');
        });
        <?php js::stop(); ?>
    </script>
<?php }