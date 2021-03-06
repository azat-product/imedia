<?php
/**
 * Блог: список постов - тег
 * @var $this Blog
 * @var $breadCrumbs array хлебные крошки
 * @var $tag string название тега
 * @var $list string список (HTML)
 * @var $pgn string постраничная навигация (HTML)
 * @var $rightBlock string правый блок (HTML)
 */
?>
<div class="row-fluid">
    <div class="l-page l-page_right span12">

        <?= tpl::getBreadcrumbs($breadCrumbs) ?>

        <div class="l-table">
            <div class="l-table-row">
                <div class="l-main l-table-cell">
                    <div class="l-main__content">

                        <h1><?= $tag ?></h1>

                        <?= $list ?>

                        <br />
                        <?= $pgn ?>
                    </div>
                </div>

                <?= $rightBlock ?>

            </div>
        </div>
    </div>
</div>