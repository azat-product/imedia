<?php
/**
 * Помощь: форма поиска
 * @var $this Help
 * @var $f array параметры фильтра
 */

extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

?>
<div class="row-fluid">
    <div class="f-msearch rel span12">
    <?
        if( DEVICE_DESKTOP_OR_TABLET ) {
            echo Geo::i()->filterForm(bff::DEVICE_DESKTOP);
        }
    ?>
    <noindex>
    <form id="j-f-form" action="<?= Help::url('search') ?>" method="get" class="form-inline rel">
        <? if(bff::$event == 'search') { ?><input type="hidden" name="page" value="<?= $f['page'] ?>" /><? } ?>
        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
        <div class="f-msearch_desktop hidden-phone">
            <table width="100%">
                <tr>
                    <td class="input">
                        <input type="text" name="q" id="j-f-query" placeholder="<?= _te('help','Поиск вопросов...') ?>" autocomplete="off" style="width: 100%" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
                    </td>
                    <td width="70">
                        <button type="submit" class="btn pull-left"><?= _t('help','Найти') ?></button>
                    </td>
                </tr>
            </table>
        </div>
        <? } if( DEVICE_PHONE ) { ?>
        <div class="f-msearch_mobile visible-phone">
            <div class="input-append span12">
                <input type="text" name="q_m" placeholder="<?= _te('help','Поиск вопросов...') ?>" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
                <button type="submit" class="btn"><i class="fa fa-search"></i></button>
            </div>
        </div>
        <? } ?>
    </form>
    </noindex>

    </div>
</div>