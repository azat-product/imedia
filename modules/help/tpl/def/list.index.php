<?php
/**
 * Помощь: главная
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $titleh1 string заголовок H1 или пустая строка
 * @var $items array список
 * @var $favs array избранные вопросы
 * @var $seotext string SEO-текст
 */
?>
<div class="row-fluid">
    <div class="l-page span12">

        <?= tpl::getBreadcrumbs($breadCrumbs); ?>

        <h1><?= (!empty($titleh1) ? $titleh1 : _t('help', 'Помощь по проекту')) ?></h1>

        <div class="faq-list txt-content">
            <? foreach($items as &$v){ ?>
                <? if ( ! empty($v['subcats'])) { ?>
                    <div class="faq-category">
                        <h2><?= $v['title'] ?></h2>
                        <ul>
                        <? foreach($v['subcats'] as $c) { ?>
                            <li><a href="<?= Help::url('cat', array('keyword'=>$c['keyword'])) ?>"><?= $c['title'] ?></a></li>
                        <? } ?>
                        </ul>
                    </div>
                <? } else if($v['questions']) { ?>
                    <div class="faq-category">
                        <h2><?= $v['title'] ?></h2>
                        <ul>
                        <? foreach($v['questions_list'] as $q) { ?>
                            <li><a href="<?= Help::urlDynamic($q['link']) ?>"><?= $q['title'] ?></a></li>
                        <? } ?>
                        </ul>
                    </div>
                <? } ?>
            <? } unset($v); ?>

            <? if( ! empty($favs)) { ?>
            <div class="faq-category">
                <h2><?= _t('help', 'Частые вопросы') ?></h2>
                <ul>
                <? foreach($favs as $v) { ?>
                    <li><a href="<?= Help::urlDynamic($v['link']) ?>"><?= $v['title'] ?></a></li>
                <? } ?>
                </ul>
            </div>
            <? } ?>

        </div>

        <? if(!empty($seotext)) { ?>
        <div class="l-info">
            <?= $seotext ?>
        </div>
        <? } ?>
    </div>
</div>