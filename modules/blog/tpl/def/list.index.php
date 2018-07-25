<?php
/**
 * Блог: список постов - главная
 * @var $this Blog
 * @var $breadCrumbs array хлебные крошки
 * @var $titleh1 string заголовок H1
 * @var $list string список (HTML)
 * @var $pgn string постраничная навигация (HTML)
 * @var $rightBlock string правый блок (HTML)
 * @var $page integer номер текущей страницы
 * @var $seotext string SEO-текст
 */
?>
<div class="row-fluid">
    <div class="l-page l-page_right span12">

        <?= tpl::getBreadcrumbs($breadCrumbs) ?>

        <div class="l-table">
            <div class="l-table-row">
                <div class="l-main l-table-cell">
                    <div class="l-main__content">

                        <h1><?= (!empty($titleh1) ? $titleh1 : _t('blog', 'Блог проекта')) ?></h1>

                        <?= $list ?>

                        <br />
                        <?= $pgn ?>
                    </div>
                </div>

                <?= $rightBlock ?>

            </div>
        </div>
        <? if($page <= 1 && !empty($seotext)) { ?>
        <div class="l-info">
            <?= $seotext ?>
        </div>
        <? } ?>
    </div>
</div>