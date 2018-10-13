<?php
/**
 * Помощь: поиск вопроса
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $questions array список вопросов
 * @var $pgn string постраничная навигация (HTML)
 * @var $f array фильтр: q - строка поиска
 */
 $lang_more = _t('help', 'Подробнее');
?>
<div class="row-fluid">
    <div class="l-page span12">

            <?= tpl::getBreadcrumbs($breadCrumbs, true) ?>

            <div class="faq-list txt-content">
                <div class="faq-category">
                    <? if ( ! empty($questions)) { ?>
                    <h1><?= _t('help', 'Результаты поиска по запросу "[query]":', array('query'=>HTML::escape($f['q']))) ?></h1>
                    <ul class="faq-search-results">
                        <? foreach($questions as &$v) { ?>
                        <li><div class="faq-num"><?= $num++ ?>.</div>
                            <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
                            <div class="faq-search__short__text">
                                <div><?= $v['textshort'] ?></div>
                                <? if(!$v['content_no']){ ?><a href="<?= $v['link'] ?>"><span><?= $lang_more ?></span> <i class="fa fa-angle-right c-link-icon"></i></a><? } ?>
                            </div>
                        </li>
                        <? } unset($v); ?>
                    </ul>
                    <? } else { ?>
                    <h1><?= _t('help', 'По запросу "[query]" ничего не найдено', array('query'=>HTML::escape($f['q']))) ?></h1>
                    <? } ?>
                </div>
                <div class="mrgl20"><?= $pgn ?></div>
            </div>

    </div>
</div>