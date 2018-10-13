<?php
/**
 * Блог: просмотр поста
 * @var $this Blog
 * @var $breadCrumbs array хлебные крошки
 * @var $created string дана создания поста
 * @var $title string заголовок
 * @var $content string содержание (HTML)
 * @var $tags array теги
 * @var $share_code string код шаринга в соц. сетях
 * @var $next array ссылка на следущий пост
 */
?>
<div class="row-fluid">
    <div class="l-page l-page_right span12">

        <?= tpl::getBreadcrumbs($breadCrumbs) ?>

        <div class="l-table">
            <div class="l-table-row">
                <div class="l-main l-table-cell">
                    <div class="l-main__content">
                        <div class="b-view">
                                <div class="b-article_date"><?= tpl::dateFormat($created, '%d.%m.%Y в %H:%M') ?></div>
                                <h1 class="b-title"><?= $title ?></h1>

                                <?= $content ?>

                                <? if( ! empty($tags) ) { ?>
                                <div class="b-tags">
                                    <? foreach($tags as $v) { ?>
                                        <a href="<?= $v['link'] ?>" class="b-tags-item"><?= $v['tag'] ?></a>
                                    <? } ?>
                                </div>
                                <? } ?>
                                <? if( ! empty($share_code) ) { ?>
                                    <div class="b-tags">
                                        <?= $share_code ?>
                                    </div>
                                <? } ?>
                                <div class="spacer"></div>
                                <a href="<?= Blog::url('index') ?>" class="b-goback ico pull-left">&lsaquo; <span><?= _t('blog', 'Назад в блог') ?></span></a>
                                <? if ( ! empty($next)) { ?><a href="<?= $next['link'] ?>" class="b-goback ico pull-right"><span><?= _t('blog', 'Следующая запись') ?></span> &rsaquo;</a><? } ?>
                        </div>
                    </div>
                </div>
                <? # Баннер (справа): ?>
                <? if(DEVICE_DESKTOP && ($bannerRight = Banners::view('blog_view_right'))) { ?>
                <div class="l-right l-table-cell visible-desktop">
                    <div class="l-right__content">
                        <div class="l-banner banner-right">
                            <div class="l-banner__content">
                                <?= $bannerRight ?>
                            </div>
                        </div>
                    </div>
                </div>
                <? } ?>
            </div>
        </div>
    </div>
</div>