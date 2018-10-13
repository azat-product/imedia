<?php
/**
 * Блог: список постов - список
 * @var $this Blog
 * @var $list array список постов
 */
 $lang_more = _t('blog', 'Читать дальше');
?>

<div class="b-list">

    <? foreach($list as $v) { $v['link'] = Blog::urlDynamic($v['link']); ?>
    <div class="b-list-item">
        <div class="b-article_date"><?= tpl::dateFormat($v['created'], '%d.%m.%Y в %H:%M') ?></div>
        <? if($v['preview']):?>
            <a href="<?= $v['link'] ?>" class="sr-page__list__item__img">
                <img src="<?= BlogPostPreview::url($v['id'], $v['preview'], BlogPostPreview::szList) ?>">
            </a>
        <? endif; ?>
        <h3>
            <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
        </h3>
        <p><?= $v['textshort'] ?></p>
        <a href="<?= $v['link'] ?>"><?= $lang_more ?></a> &rarr;
    </div>
    <div class="spacer"></div>
    <? } ?>

</div>