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
        <div class="b-index-blog-heading-title"><?= ! empty($title) ? $title : _t('blog', 'Последнее в блоге') ?></div>
    </div>

    <div class="index-blog-list">
        <div class="row">
            <? foreach($list as $v): $v['link'] = Blog::urlDynamic($v['link']); ?>
            <div class="col-sm-4">
                <div class="b-list-item">
                    <? if($preview && $v['preview']):?>
                    <div class="b-list-item-img">
                        <a href="<?= $v['link'] ?>">
                            <img src="<?= BlogPostPreview::url($v['id'], $v['preview'], BlogPostPreview::szIndex) ?>" alt="">
                        </a>
                    </div>
                    <? endif; ?>
                    <div class="bl-date"><?= tpl::dateFormat($v['created'], _t('blog','%d.%m.%Y в %H:%M')) ?></div>
                    <div class="bl-list-item-title">
                        <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
                    </div>
                    <p><?= $v['textshort'] ?></p>
                </div>
            </div>
            <? endforeach; ?>
        </div>
    </div>
</div>