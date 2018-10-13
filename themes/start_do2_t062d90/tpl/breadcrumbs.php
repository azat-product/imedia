<?php
/**
 * Хлебные крошки
 * @var $crumbs array хлебные крошки
 * @var $active_is_link boolean является ли активный пункт ссылкой
 * @var $title_key string ключ заголовка
 */
 $i = 1;
?>
<div class="l-page__breadcrumb_shadow l-page__breadcrumb-wrap" id="j-breadcrumbs">
    <div class="l-page__breadcrumb_v2 j-breadcrumbs-in">
        <ul class="breadcrumb j-breadcrumbs-full" vocab="http://schema.org/" typeof="BreadcrumbList">
            <li><a href="<?= Geo::url(Geo::filterUrl()) ?>"><i class="fa fa-home"></i></a></li>
            <?php foreach($crumbs as $v) {
                if ($v['active']) {
                    if ($active_is_link) { ?><li property="itemListElement" typeof="ListItem"><a href="<?= $v['link'] ?>" title="<?= (!empty($v['link_title']) ? $v['link_title'] : $v[$title_key]) ?>" property="item" typeof="WebPage"><span property="name"><?= $v[$title_key] ?></span></a><meta itemprop="position" content="<?= $i++; ?>" /></li><?php }
                    else { ?><li property="itemListElement" typeof="ListItem"><span class="active" property="name"><?= $v[$title_key] ?></span><meta itemprop="position" content="<?= $i++; ?>" /></li><?php }
                } else {
                    ?><li property="itemListElement" typeof="ListItem"><a href="<?= $v['link'] ?>" title="<?= (!empty($v['link_title']) ? $v['link_title'] : $v[$title_key]) ?>" property="item" typeof="WebPage"><span property="name"><?= $v[$title_key] ?></span></a><meta itemprop="position" content="<?= $i++; ?>" /></li><?php
                }
            } ?>
        </ul>
    </div>
</div>
<script type="text/javascript">
<?php js::start(); ?>
$(function(){
    var container = $('#j-breadcrumbs');
    function scrollbarWidth() {
        var parent = $('<div style="width:50px;height:50px;overflow:auto"><div/></div>').appendTo('body');
        var child = parent.children();
        var width = child.innerWidth() - child.height(99).innerWidth();
        parent.remove();
        return width + 'px';
    }
    container.find('.j-breadcrumbs-in').scrollLeft(container.find('.j-breadcrumbs-full').outerWidth()).css({bottom:-scrollbarWidth()});
});
<?php js::stop(); ?>
</script>