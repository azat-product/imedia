<?php
/**
 * Хлебные крошки
 * @var $crumbs array хлебные крошки
 * @var $active_is_link boolean является ли активный пункт ссылкой
 * @var $title_key string ключ заголовка
 */

if (DEVICE_DESKTOP_OR_TABLET) { $i = 1; ?>
<ul class="l-page__breadcrumb breadcrumb hidden-phone" vocab="http://schema.org/" typeof="BreadcrumbList">
    <li><a href="<?= Geo::url(Geo::filterUrl()) ?>"><i class="fa fa-home"></i></a> <span class="divider">/</span></li>
    <? foreach($crumbs as $v) {
          if($v['active']){
                if($active_is_link) { ?><li property="itemListElement" typeof="ListItem"><a href="<?= $v['link'] ?>" title="<?= (!empty($v['link_title']) ? $v['link_title'] : $v[$title_key]) ?>" property="item" typeof="WebPage"><span property="name"><?= $v[$title_key] ?></span></a><meta itemprop="position" content="<?= $i++; ?>" /></li><? }
                else { ?><li property="itemListElement" typeof="ListItem"><span class="active" property="name"><?= $v[$title_key] ?></span><meta itemprop="position" content="<?= $i++; ?>" /></li><? }
          } else {
                ?><li property="itemListElement" typeof="ListItem"><a href="<?= $v['link'] ?>" title="<?= (!empty($v['link_title']) ? $v['link_title'] : $v[$title_key]) ?>" property="item" typeof="WebPage"><span property="name"><?= $v[$title_key] ?></span></a><meta itemprop="position" content="<?= $i++; ?>" /> <span class="divider">/</span></li><?
          }
       } ?>
</ul>
<? } ?>