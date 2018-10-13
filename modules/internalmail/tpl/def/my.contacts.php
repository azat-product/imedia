<?php
/**
 * Кабинет пользователя: Сообщения
 * @var $this InternalMail
 * @var $list string список контактов (HTML)
 * @var $total integer общее кол-во контактов
 * @var $f array параметры фильтра списка
 * @var $folders array папки группировки контактов
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 */
    tpl::includeJS(array('history'), true);
    tpl::includeJS(array('internalmail.my'), false, 2);
?>

<form action="" class="form-search" id="j-my-messages-form">
<input type="hidden" name="f" value="<?= $f['f'] ?>" id="j-my-messages-folder-value" />
<input type="hidden" name="page" value="<?= $f['page'] ?>" />
<input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-messages-pp-value" />

<? # Фильтр сообщений ?>
<div class="u-cabinet__sub-navigation">
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="u-cabinet__sub-navigation_desktop hidden-phone">
        <ul class="nav nav-pills">
            <? foreach($folders as $k=>$v) {
                   ?><li class="u-cabinet__sub-navigation__sort <? if($f['f'] == $k) { ?> active<? } ?> j-folder-options"><a href="javascript:void(0);" class="j-folder-option" data-value="<?= $k ?>"><span><?= $v['title'] ?></span></a></li><?
               } ?>
            <li class="u-cabinet__sub-navigation__search pull-right">
                <div class="input-append">
                    <input type="text" name="qq" value="<?= HTML::escape($f['qq']) ?>" class="input-medium search-query j-q" maxlength="50" />
                    <button type="submit" class="btn j-q-submit"><i class="fa fa-search"></i></button>
                </div>
            </li>
        </ul>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-cabinet__sub-navigation_mobile visible-phone">
        <div class="u-cabinet__sub-navigation__type" id="j-my-messages-folder-arrows">
            <table>
                <tr>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_left j-left"><a href="javascript:void(0);"><i class="fa fa-chevron-left"></i></a></div></td>
                    <td class="u-cabinet__sub-navigation__type__title j-title"><?= $folders[$f['f']]['title'] ?></td>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_right j-right"><a href="javascript:void(0);"><i class="fa fa-chevron-right"></i></a></div></td>
                </tr>
            </table>
        </div>
    </div>
    <? } ?>
</div>

<? # Список сообщений ?>
<div class="u-mail__list l-table" id="j-my-messages-list">
    <?= $list ?>
</div>

<? # Постраничная навигация ?>
<div class="u-cabinet__pagination">
    <div class="pull-left" id="j-my-messages-pgn">
        <?= $pgn ?>
    </div>
    <ul id="j-my-messages-pp" class="u-cabinet__list__pagination__howmany nav nav-pills pull-right hidden-phone<?= ( ! $total ? ' hide' : '' ) ?>">
        <li class="dropdown">
            <a class="dropdown-toggle j-pp-dropdown" data-toggle="dropdown" href="javascript:void(0);">
                <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($pgn_pp as $k=>$v): ?>
                    <li><a href="javascript:void(0);" class="<? if($k == $f['pp']) { ?>active <? } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
                <? endforeach; ?>
            </ul>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
</form>

<script type="text/javascript">
<? js::start() ?>
    $(function(){
        jMyMessages.init(<?= func::php2js(array(
            'lang' => array(),
            'folders' => $folders,
            'ajax' => true,
        )) ?>);
    });
<? js::stop() ?>
</script>