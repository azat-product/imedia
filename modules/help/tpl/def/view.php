<?php
/**
 * Помощь: просмотр вопроса
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $title string заголовок
 * @var $content string содержание (HTML)
 * @var $questions_other array список похожих вопросов
 */
?>
<div class="row-fluid">
    <div class="l-page span12">
            <?= tpl::getBreadcrumbs($breadCrumbs); ?>
            <div class="txt-content">
                <h1><?= $title ?></h1>
                <div class="faq-question">
                    <?= $content ?>
                </div>

                <? if ( ! empty($questions_other)) { ?>
                <span class="l-spacer mrgb20"></span>
                <div class="faq-category">
                    <h2><?= _t('help', 'Другие вопросы из этого раздела') ?></h2>
                    <ul>
                        <? foreach($questions_other as $v) { ?>
                            <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
                        <? } ?>
                    </ul>
                </div>
                <? } ?>

            </div>
    </div>
</div>