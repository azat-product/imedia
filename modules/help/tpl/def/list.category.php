<?php
/**
 * Помощь: вопросы категории
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $title string заголовок
 * @var $subcats boolean есть подкатегории
 * @var $subcats_list array список подкатегории
 * @var $questions_list array список вопросов
 */
 $lang_more = _t('help', 'Подробнее');
?>
<div class="row-fluid">
    <div class="l-page span12">

            <?= tpl::getBreadcrumbs($breadCrumbs); ?>

            <div class="faq-list txt-content">

                <div class="faq-category faq-category-inside">
                    <h1><?= $title ?></h1>
                    <? if($subcats) { ?>
                        <ul>
                            <? foreach($subcats_list as &$v) { ?>
                                <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
                            <? } unset($v); ?>
                        </ul>
                    <? } else { ?>
                        <ul>
                            <? foreach($questions_list as &$v) { ?>
                                <? if ( ! empty($v['textshort'])) { ?>
                                    <li><a href="javascript:void(0);" class="ajax j-help-cat-question-ex"><?= $v['title'] ?></a>
                                        <div class="hide faq-question-short">
                                            <div><?= $v['textshort'] ?></div>
                                            <? if(!$v['content_no']){ ?><a href="<?= $v['link'] ?>"><span><?= $lang_more ?></span> <i class="fa fa-angle-right c-link-icon"></i></a><? } ?>
                                        </div>
                                    </li>
                                <? } else { ?>
                                    <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
                                <? } ?>
                            <? } unset($v); ?>
                        </ul>
                        <? if (empty($questions_list)) { ?>
                            <?= _t('help', 'В данной рубрике пусто') ?>
                        <? } ?>
                    <? } ?>
                </div>

            </div>
    </div>
</div>
<script type="text/javascript">
<? js::start() ?>
$(function(){
    $('.j-help-cat-question-ex').on('click', function(e){
        nothing(e);
        $(this).next().slideToggle()
    });
});
<? js::stop() ?>
</script>