<?php
/**
 * Блог: список последних постов на главной
 * @var $this Blog
 * @var $list array список постов
 * @var $preview integer отображать превью
 */
?>
<div class="index-blog">
    <div class="index__heading">
        <h2><?= ! empty($title) ? $title : _t('blog', 'Последнее в блоге') ?></h2>
    </div>
    <div class="index-blog-list">
        <div class="row-fluid">
            <? foreach($list as $v): $v['link'] = Blog::urlDynamic($v['link']); ?>
            <div class="span4">
                <div class="b-list-item">
                    <? if($preview && $v['preview']):?>
                        <div class="b-list-item-img">
                            <a href="<?= $v['link'] ?>">
                                <img src="<?= BlogPostPreview::url($v['id'], $v['preview'], BlogPostPreview::szIndex) ?>" alt=""/>
                            </a>
                        </div>
                    <? endif; ?>
                    <div class="b-article_date"><?= tpl::dateFormat($v['created'], '%d.%m.%Y в %H:%M') ?></div>
                    <h3>
                        <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
                    </h3>
                    <p><?= $v['textshort'] ?></p>
                </div>
            </div>
            <? endforeach; ?>
        </div>
    </div>
</div>