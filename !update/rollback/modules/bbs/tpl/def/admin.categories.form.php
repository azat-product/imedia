<?php
    /**
     * @var $this BBS
     */
    tpl::includeJS(array('tablednd'));
    $aData = HTML::escape($aData, 'html', array('keyword_edit'));
    $edit = $aData['edit'] = ! empty($id);
    $isVirtual = ($edit && !empty($virtual_ptr));

    if( ! $edit ) {
        $price_sett['ranges'] = array();
        $price_sett['ex'] = 0;
        $photos = BBS::itemsImagesLimit(false);
    }

    $aTabs = array(
        'info' => _t('bbs', 'Основные'),
        'tpl' => _t('bbs', 'Шаблоны'),
        'seo' => _t('', 'SEO'),
    );
    if( ! $edit) {
        unset($aTabs['tpl']);
    }
    $aTabs = bff::filter('bbs.admin.category.form.tabs', $aTabs, array('edit'=>$edit,'data'=>&$aData));

    $copyRow = function($name = ''){
        $html = '<td width="25" class="j-cat-copy-row alert" style="vertical-align: top; text-align: center; display: none;">';
        if ($name !== '') {
            $html.= '<input type="checkbox" name="copy_to_subs_data[]" value="'.$name.'" class="j-cat-copy-checkbox" style="margin: 0 5px 0 0;" />';
        }
        $html.= '</td>';
        return $html;
    };

echo tplAdmin::blockStart( _t('bbs', 'Объявления / Категории / '). ( $edit ? _t('', 'Редактирование'): _t('', 'Добавление')), false);
?>
<form method="post" action="" name="bbsCategoryForm" id="bbsCategoryForm" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $id ?>" />
<input type="hidden" name="seek" value="<?= $seek ?>" id="bbs-cat-seek" />
<input type="hidden" name="copy_to_subs" value="0" id="bbs-cat-copy-to-subs" />
<? if ($edit) { ?>
<input type="hidden" name="structure_modified" value="<?= $structure_modified ?>" />
<? } ?>
<div class="tabsBar" id="bbsCategoryFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"<? if($k === 'tpl' && $isVirtual) { ?> style="display: none;" <?php } ?>><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
    <span class="progress pull-right" id="progress-category-form" style="display:none; margin: 8px 5px 0 0;"></span>
</div>
<!-- таб: Основные -->
<div class="j-tab j-tab-info">
<table class="admtbl tbledit">
<tr>
    <td class="row1 field-title" width="120"><?= _t('bbs', 'Основная категория:'); ?></td>
    <td class="row2" id="pid-select-container" colspan="2">
        <div class="left">
        <? if( ! $edit || ! empty($pid_editable)) { ?>
            <select name="pid" id="pid-select"><?= $pid_options ?></select>
        <? } else { ?>
            <?
                $pid_title = array();
                if( ! empty($pid_options) ) foreach($pid_options as $v) $pid_title[] = $v['title'];
            ?>
            <p class="bold"><?= join('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $pid_title); ?></p>
            <input type="hidden" name="pid" value="<?= $pid ?>" />
        <? } ?>
        </div>
        <div class="right">
            <a href="javascript:void(0);" class="but chain" style="margin-right: 10px; <?php if(!$isVirtual) { ?>display: none;<?php } ?>"></a><span class="label <?= ($isVirtual ? 'label-info' : '') ?>"><label class="checkbox"><input type="checkbox" name="is_virtual" class="j-virtual-toggler" <? if($isVirtual){ ?> checked="checked"<? } ?> onclick="$(this).parent().parent().toggleClass('label-info').prev().toggle();"> <?= _t('bbs', 'виртуальная категория') ?></label></span>
        </div>
    </td>
</tr>
<tr class="j-virtual-settings <?php if(!$isVirtual){ ?> hidden<?php } ?>">
    <td class="row1 field-title"><?= _t('bbs', 'Ссылается на:'); ?></td>
    <td class="row2">
        <select name="virtual_ptr" class="j-virtual-select"></select>
    </td>
</tr>
<?= $this->locale->buildForm($aData, 'bbs-category', ''.'
<tr class="required">
    <td class="row1 field-title">'._t('bbs', 'Название:').'</td>
    <td class="row2"><input class="stretch lang-field" type="text" name="title[<?= $key ?>]" id="bbs-cat-title-<?= $key ?>" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" maxlength="200" /></td>
    '.$copyRow().'
</tr>
<tr class="<? if(!$aData[\'edit\'] || $aData[\'numlevel\'] < BBS::catsFilterLevel()): ?>displaynone<? endif ?> j-virtual-hidden">
    <td class="row1 field-title">'._t('bbs', 'Заголовок подкатегорий:').'</td>
    <td class="row2">
        <input class="stretch lang-field" type="text" name="subs_filter_title[<?= $key ?>]" value="<?= HTML::escape($aData[\'subs_filter_title\'][$key]); ?>" />
        <span class="desc">'._t('bbs', 'заголовок для группы подкатегорий в фильтре').'</span>
    </td>
    '.$copyRow().'
</tr>
<tr class="j-virtual-hidden<?= BBS::CATS_TYPES_EX ? " hidden" : "" ?>">
    <td class="row1 field-title">'._t('bbs', 'Тип размещения:').'</td>
    <td class="row2">
        <div class="well well-small">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1" width="75"><span class="">'._t('bbs', 'Предлагаю').'</span>:</td>
                    <td class="row2">
                        <input class="input-medium lang-field" type="text" placeholder="'._t('bbs', 'Предлагаю').'" title="'._t('bbs', 'Название в форме').'" name="type_offer_form[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_offer_form\'][$key]); ?>" />
                        <input class="input-medium lang-field" type="text" placeholder="'._t('bbs', 'Объявления').'" title="'._t('bbs', 'Название в списке').'" name="type_offer_search[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_offer_search\'][$key]); ?>" /><span class="help-inline">'._t('bbs', 'примеры: Предлагаю, Продам, Сдам, Предложение, Предлагаю работу, ...').'</span>
                    </td>
                </tr>
                <tr class="j-bbs-cat-seek-param<? if( ! $aData[\'seek\']) { ?> disabled<? } ?>">
                    <td class="row1"><span class="field-title">'._t('bbs', 'Ищу').'</span>:</td>
                    <td class="row2">
                        <input class="input-medium lang-field" type="text" placeholder="'._t('bbs', 'Ищу').'" title="'._t('bbs', 'Название в форме').'" name="type_seek_form[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_seek_form\'][$key]); ?>" />
                        <input class="input-medium lang-field" type="text" placeholder="'._t('bbs', 'Объявления').'" title="'._t('bbs', 'Название в списке').'" name="type_seek_search[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_seek_search\'][$key]); ?>" />
                        <label class="checkbox inline desc" style="margin-left:7px;"><input type="checkbox" class="j-bbs-cat-seek-toggler" <? if( $aData[\'seek\']) { ?>checked="checked"<? } ?> onclick="jCategory.onSeek(this);" />'._t('bbs', 'задействовать').'</label>
                        <span class="help-inline">'._t('bbs', 'примеры: Ищу, Куплю, Сниму, Ищу работу, ...').'</span>
                    </td>
                </tr>
            </table>
        </div>
    </td>
    '.$copyRow('seek').'
</tr>
'); ?>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Цена:'); ?></td>
    <td class="row2">
        <label class="radio inline"><input type="radio" name="price" value="1" onclick="$('#j-price-sett').show();" <? if($price) { ?> checked="checked" <? } ?> /><?= _t('', 'есть'); ?></label>
        <label class="radio inline"><input type="radio" name="price" value="0" onclick="$('#j-price-sett').hide();" <? if( ! $price) { ?> checked="checked" <? } ?> /><?= _t('', 'нет'); ?></label>
        <div id="j-price-sett" style="margin-top: 8px;<? if( ! $price) { ?> display:none;<? } ?>">
            <?
                $price_curr = ( ! empty($price_sett['curr']) ? $price_sett['curr'] : Site::currencyDefault('id') );
                $price_curr_title = Site::currencyData($price_curr, 'title_short');
            ?>
            <div class="well well-small">
                <table class="admtbl tbledit">
                    <tr>
                        <td class="row1" width="140"><?= _t('bbs', 'Заголовок цены:'); ?></td>
                        <td id="j-price-title">
                            <?= $this->locale->formField('price_sett[title]', $price_sett['title'], 'text', array('placeholder'=>_t('bbs','Цена'))); ?>
                            <span class="help-inline"><?= _t('bbs', 'примеры: Цена, Стоимость, Зарплата от, ...'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="row1"><?= _t('bbs', 'Валюта по-умолчанию:'); ?></td>
                        <td id="j-price-curr">
                            <select name="price_sett[curr]" style="width:70px;" onchange="jCategoryPrice.onCurr(this);"><?= Site::currencyOptions($price_curr); ?></select>
                        </td>
                    </tr>
                    <tr>
                        <td class="row1"><?= _t('bbs', 'Диапазоны цен:[span](для поиска)</span>', array('span' => '<br /><span class="desc">')); ?></td>
                        <td>
                            <table id="j-price-ranges">
                                <?
                                $i = 1;
                                if( ! empty($price_sett['ranges']) ) {
                                    foreach($price_sett['ranges'] as $v) {
                                        ?><tr class="range-<?= $i; ?>"><td><?= _t('', 'от'); ?> <input name="price_sett[ranges][<?= $i ?>][from]" value="<?= ($v['from'] > 0 ? $v['from'] : '' ); ?>" type="text" class="input-mini" />&nbsp;&nbsp; <?= _t('', 'до'); ?> <input name="price_sett[ranges][<?= $i ?>][to]" type="text" value="<?= ($v['to'] > 0 ? $v['to'] : ''); ?>" class="input-mini" /><span class="help-inline j-price-ranges-curr-help"><?= $price_curr_title ?></span><a class="but cross j-price-ranges-del" href="#" style="margin-left:7px;"></a></td></tr><?
                                        $i++;
                                    }
                                } ?>
                            </table>
                            <a href="#" class="ajax" id="j-price-ranges-add"><?= _t('bbs', 'добавить диапазон цен'); ?></a><span class="desc">&nbsp;&nbsp;&uarr;&darr;</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="row1"><?= _t('bbs', 'Модификатор:'); ?></td>
                        <td>
                            <label class="checkbox inline"><input type="checkbox" name="price_sett[ex][0]" value="<?= BBS::PRICE_EX_MOD ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_MOD) { ?> checked="checked" <? } ?> onclick="$('#j-price-sett-mod-title').toggle()" /></label>
                            <span id="j-price-sett-mod-title"<? if( ! ($price_sett['ex'] & BBS::PRICE_EX_MOD) ) { ?> style="display: none;" <? } ?>>
                                <?= $this->locale->formField('price_sett[mod_title]', $price_sett['mod_title'], 'text', array('placeholder'=>_t('bbs', 'Торг возможен'))); ?>
                                <span class="help-inline"><?= _t('bbs', 'примеры: Торг возможен, По результатам собеседования, ...'); ?></span>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="row1"><?= _t('bbs', 'Обмен:'); ?></td>
                        <td><label class="checkbox inline"><input type="checkbox" name="price_sett[ex][1]" value="<?= BBS::PRICE_EX_EXCHANGE ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_EXCHANGE) { ?> checked="checked" <? } ?> /></label></td>
                    </tr>
                    <tr>
                        <td class="row1"><?= _t('bbs', 'Бесплатно:'); ?></td>
                        <td><label class="checkbox inline"><input type="checkbox" name="price_sett[ex][2]" value="<?= BBS::PRICE_EX_FREE ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_FREE) { ?> checked="checked" <? } ?> /></label></td>
                    </tr>
                    <tr>
                        <td class="row1"><?= _t('bbs', 'Договорная:'); ?></td>
                        <td><label class="checkbox inline"><input type="checkbox" name="price_sett[ex][3]" value="<?= BBS::PRICE_EX_AGREED ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_AGREED) { ?> checked="checked" <? } ?> /></label></td>
                    </tr>
                </table>
            </div>
        </div>
    </td>
    <?= $copyRow('price') ?>
</tr>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Фотографии:'); ?></td>
    <td class="row2">
        <label><input class="input-mini" type="number" min="<?= BBS::itemsImagesLimit(false) ?>" max="<?= BBS::itemsImagesLimit() ?>" maxlength="2" name="photos" value="<?= $photos ?>" /><span class="help-inline"> &mdash; <?= _t('bbs', 'максимально доступное кол-во фотографий в объявлении'); ?> (<?= BBS::itemsImagesLimit(false) ?> - <?= BBS::itemsImagesLimit() ?>)</span></label>
    </td>
    <?= $copyRow('photos') ?>
</tr>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Представитель:'); ?></td>
    <td class="row2">
        <label class="radio inline"><input type="radio" name="owner_business" value="1" onclick="$('#j-ownertype-sett').show();" <? if($owner_business) { ?> checked="checked" <? } ?> /><?= _t('', 'есть'); ?></label><label class="radio inline"><input type="radio" name="owner_business" value="0" onclick="$('#j-ownertype-sett').hide();" <? if( ! $owner_business) { ?> checked="checked" <? } ?> /><?= _t('', 'нет'); ?></label>
        <div class="well well-small" id="j-ownertype-sett" style="margin-top:5px;<? if( ! $owner_business) { ?> display:none;<? } ?>">
            <input type="hidden" name="owner_search[1]" class="j-ownertype-search-val-private" value="<?= ($owner_search & BBS::OWNER_PRIVATE ? BBS::OWNER_PRIVATE : 0) ?>" />
            <input type="hidden" name="owner_search[2]" class="j-ownertype-search-val-business" value="<?= ($owner_search & BBS::OWNER_BUSINESS ? BBS::OWNER_BUSINESS : 0) ?>" />
            <? $i=0; foreach($this->locale->getLanguages() as $lng) { ?>
            <table class="admtbl tbledit j-lang-form j-lang-form-<?= $lng ?><?= ($i++ ? ' displaynone':'') ?>">
                <tr>
                    <td class="row1" width="95"><span class=""><?= _t('bbs', 'Частное лицо'); ?></span>:</td>
                    <td class="row2">
                        <input class="input-large lang-field" type="text" placeholder="<?= _te('bbs', 'Частное лицо'); ?>" title="<?= _te('bbs', 'Название в форме'); ?>" name="owner_private_form[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_private_form'][$lng]); ?>" maxlength="50" />
                        <input class="input-large lang-field" type="text" placeholder="<?= _te('bbs', 'От частных лиц'); ?>" title="<?= _te('bbs', 'Название в списке'); ?>" name="owner_private_search[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_private_search'][$lng]); ?>" maxlength="50" />
                        <label class="checkbox inline desc" style="margin-left:7px;"><input type="checkbox" class="j-ownertype-search-toggler-private" data="{id:<?= BBS::OWNER_PRIVATE ?>, key:'private'}" <? if($owner_search & BBS::OWNER_PRIVATE) { ?>checked="checked"<? } ?> onclick="jCategory.onOwnertypeSearch(this);" /><?= _t('', 'поиск'); ?></label>
                    </td>
                </tr>
                <tr>
                    <td class="row1"><span class="field-title"><?= _t('bbs', 'Бизнес'); ?></span>:</td>
                    <td class="row2">
                        <input class="input-large lang-field" type="text" placeholder="<?= _te('bbs', 'Бизнес'); ?>" title="<?= _te('bbs', 'Название в форме'); ?>" name="owner_business_form[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_business_form'][$lng]); ?>" maxlength="50" />
                        <input class="input-large lang-field" type="text" placeholder="<?= _te('bbs', 'Только бизнес объявления'); ?>" title="<?= _te('bbs', 'Название в списке'); ?>" name="owner_business_search[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_business_search'][$lng]); ?>" maxlength="50" />
                        <label class="checkbox inline desc" style="margin-left:7px;"><input type="checkbox" class="j-ownertype-search-toggler-business" data="{id:<?= BBS::OWNER_BUSINESS ?>, key:'business'}" <? if($owner_search & BBS::OWNER_BUSINESS) { ?>checked="checked"<? } ?> onclick="jCategory.onOwnertypeSearch(this);" /><?= _t('', 'поиск'); ?></label>
                    </td>
                </tr>
            </table>
            <? } ?>
        </div>
    </td>
    <?= $copyRow('owner') ?>
</tr>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Адрес:'); ?></td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" name="addr" <? if($addr) { ?> checked="checked"<? } ?> /><?= _t('bbs', 'подробный адрес и карта'); ?></label>
    </td>
    <?= $copyRow('addr') ?>
</tr>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Метро:'); ?></td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" name="addr_metro" <? if($addr_metro) { ?> checked="checked"<? } ?> /><?= _t('bbs', 'поиск по станции метро'); ?></label>
    </td>
    <?= $copyRow('addr_metro') ?>
</tr>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Доставка в регионы:'); ?></td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" name="regions_delivery" <? if($regions_delivery) { ?> checked="checked"<? } ?> /><?= _t('bbs', 'доступна возможность указать доставку в регионы'); ?></label>
    </td>
    <?= $copyRow('regions_delivery') ?>
</tr>
<tr class="j-virtual-hidden">
    <td class="row1 field-title"><?= _t('bbs', 'Вид списка<br /> по-умолчанию:'); ?></td>
    <td class="row2">
        <select name="list_type" style="width: auto;">
            <?= HTML::selectOptions(array(
                0 => _t('bbs', 'Не указан'),
                BBS::LIST_TYPE_LIST    => _t('bbs', 'Список'),
                BBS::LIST_TYPE_GALLERY => _t('bbs', 'Галерея'),
                BBS::LIST_TYPE_MAP     => _t('bbs', 'Карта'),
            ), $list_type); ?>
        </select>
    </td>
    <?= $copyRow('list_type') ?>
</tr>
<tr>
    <td class="row1">
        <span class="field-title"><?= _t('', 'URL Keyword'); ?></span>:<br />
        <a href="#" onclick="return bff.generateKeyword('#bbs-cat-title-<?= LNG ?>', '#bbs-cat-keyword');" class="ajax desc small"><?= _t('', 'сгенерировать'); ?></a>
    </td>
    <td class="row2">
        <input class="stretch" type="text" maxlength="100" name="keyword_edit" id="bbs-cat-keyword" value="<?= $keyword_edit ?>" />
    </td>
    <?= $copyRow() ?>
</tr>
<? bff::hook('bbs.admin.category.form', array('edit'=>$edit,'data'=>&$aData,'copyRow'=>$copyRow)); ?>
<? if($edit && $this->model->catIsMain($id, $pid))
{
    $oIcon = BBS::categoryIcon($id);
    foreach($oIcon->getVariants() as $iconField=>$v) {
        $oIcon->setVariant($iconField);
        $icon = $v;
        $icon['uploaded'] = ! empty($aData[$iconField]);
    ?>
    <tr>
        <td class="row1">
            <span class="field-title"><?= $icon['title'] ?></span>:<? if(sizeof($v['sizes']) == 1) { $sz = current($v['sizes']); ?><br /><span class="desc"><?= ($sz['width'].'x'.$sz['height']) ?></span><? } ?>
        </td>
        <td class="row2">
            <input type="file" name="<?= $iconField ?>" <? if($icon['uploaded']){ ?>style="display:none;" <? } ?> />
            <? if($icon['uploaded']) { ?>
                <div style="margin:5px 0;">
                    <input type="hidden" name="<?= $iconField ?>_del" class="del-icon" value="0" />
                    <img src="<?= $oIcon->url($id, $aData[$iconField], $icon['key']) ?>" alt="" /><br />
                    <a href="#" class="ajax desc cross but-text" onclick="return jCategory.iconDelete(this);"><?= _t('', 'удалить'); ?></a>
                </div>
            <? } ?>
        </td>
        <?= $copyRow() ?>
    </tr>
    <? }
} ?>
</table>
</div>
<!-- таб: TPL -->
<div class="j-tab j-tab-tpl hidden">
    <table class="admtbl tbledit">
        <tr>
            <td style="vertical-align:top;">
                <table class="admtbl tbledit">
                    <tr>
                        <td class="row1 field-title" width="150"> <?= _t('bbs', 'Автоматическая генерация заголовка:') ?></td>
                        <td class="row2">
                            <label class="checkbox"><input type="checkbox" name="tpl_title_enabled" <?= ! empty($tpl_title_enabled) ? 'checked="checked"' : '' ?> value="1"> <?= _t('', 'Включено') ?></label>
                            <span class="desc"><?= _t('bbs', 'В случае если включено и шаблоны заголовка не указаны, будут применяться настройки из категорий выше, первый найденный непустой шаблон отображаемый под полями ввода.') ?><span>
                        </td>
                    </tr>
                    <?= $this->locale->buildForm($aData, 'bbs-category-tpl', ''.'
                        <tr class="<?= empty($aData[\'tpl_title_enabled\']) ? \'disabled\' : \'\' ?> j-title-enabled">
                            <td class="row1 field-title">'._t('bbs', 'Шаблон для просмотра объявления:').'</td>
                            <td class="row2">
                                <input type="text" class="stretch j-focus-input" name="tpl_title_view[<?= $key ?>]" value="<?= HTML::escape($aData[\'tpl_title_view\'][$key]); ?>" />
                                <? if ( ! empty($aData[\'tpl_parent\'][\'tpl_title_view\'])): ?>
                                <div class="well well-small desc" style="border:none;"><?= $aData[\'tpl_parent\'][\'tpl_title_view\'] ?></div>
                                <? endif; ?>                                
                            </td>
                        </tr>
                        <tr class="<?= empty($aData[\'tpl_title_enabled\']) ? \'disabled\' : \'\' ?> j-title-enabled">
                            <td class="row1 field-title">'._t('bbs', 'Шаблон для списка объявлений:').'</td>
                            <td class="row2">
                                <input type="text" class="stretch j-focus-input" name="tpl_title_list[<?= $key ?>]" value="<?= HTML::escape($aData[\'tpl_title_list\'][$key]); ?>" />
                                <? if ( ! empty($aData[\'tpl_parent\'][\'tpl_title_list\'])): ?>
                                <div class="well well-small desc" style="border:none;"><?= $aData[\'tpl_parent\'][\'tpl_title_list\'] ?></div>
                                <? endif; ?>                                
                            </td>
                        </tr>
                        <tr>
                            <td class="row1 field-title">'._t('bbs', 'Шаблон для описания объявления (список):').'</td>
                            <td class="row2">
                                <textarea class="j-focus-input" name="tpl_descr_list[<?= $key ?>]" rows="8"><?= HTML::escape($aData[\'tpl_descr_list\'][$key]); ?></textarea>
                                <? if ( ! empty($aData[\'tpl_parent\'][\'tpl_descr_list\'])): ?>
                                <div class="well well-small desc" style="border:none;"><?= $aData[\'tpl_parent\'][\'tpl_descr_list\'] ?></div>
                                <? endif; ?>                                
                            </td>
                        </tr>
                    '); ?>
                </table>
            </td>
            <td style="vertical-align:top; width: 220px; min-width: 200px; padding-left: 20px;">
                <div style="height: 330px;overflow-x: hidden;overflow-y: scroll;">
                <? if ( ! empty($aData['dp'])): foreach($aData['dp'] as $k => $v): ?>
                    <div style="margin-bottom: 4px;">
                        <? if ( ! empty($v['cache_key'])): ?>
                        <a href="#" class="j-tpl-macros" data-key="{<?= $v['cache_key'] ?>}">{<?= $v['cache_key'] ?>}</a>
                        <? endif; ?>
                        <a href="#" class="j-tpl-macros <?= ! empty($v['cache_key']) ? 'desc' : '' ?>" data-key="{<?= $v['id'] ?>}">{<?= $v['id'] ?>}</a>
                        <?= $v['req'] ? ' <span class="required-mark">*</span>' : '' ?><br />
                        <?= $v['title'] ?>
                    </div>
                <? endforeach; endif; ?>
                <? $tplSpecialFields = array(
                    'price'         => array('title' => _t('filter', 'Цена')),
                    'category'      => array('title' => _t('filter', 'Категория объявления')),
                    'geo.city'      => array('title' => _t('geo', 'Город')),
                    'geo.city.in'   => array('title' => _t('geo', 'Город со склонением')),
                    'geo.metro'     => array('title' => _t('geo', 'Станция метро')),
                    'geo.district'  => array('title' => _t('geo', 'Район города')),
                ); ?>
                <? foreach ($tplSpecialFields as $k => $v): ?>
                    <div style="margin-bottom: 4px;">
                        <a href="#" class="j-tpl-macros" data-key="{<?= $k ?>}">{<?= $k ?>}</a><br />
                        <?= $v['title'] ?>
                    </div>
                <? endforeach; ?>
                </div>
                <hr size="1" style="color:#ccc" />
                <div style="margin-top: 10px;">
                    <a href="#" class="j-tpl-macros" data-key="|"><?= _t('bbs', '+ добавить разделитель'); ?></a>
                </div>
            </td>
        </tr>
    </table>
</div>
<!-- таб: SEO -->
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'search-category'); ?>
</div>
<? bff::hook('bbs.admin.category.form.tabs.content', array('edit'=>$edit,'data'=>&$aData,'tabs'=>$aTabs)); ?>
<div>
    <div class="left" style="margin-top: 10px;">
        <input type="hidden" name="back" class="j-back" value="0" />
        <div class="btn-group">
            <input type="submit" class="btn btn-success button submit j-submit" value="<?= ($edit ? _t('', 'Save') : _t('', 'Создать')) ?>" data-loading-text="<?= _te('', 'Сохраняем...'); ?>" />
            <? if($edit) { ?>
                <input type="submit" class="btn btn-success button submit j-submit" value="<?= _te('', 'и вернуться'); ?>" data-loading-text="<?= _te('', 'и вернуться'); ?>" onclick="$('#bbsCategoryForm').find('.j-back').val(1);" />
            <? } ?>
        </div>
        <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="bff.redirect('<?= $this->adminLink('categories_listing') ?>')" />
    </div>
    <? if(FORDEV){ ?>
    <div class="right" style="background-color:; padding: 7px;" data-bg="#fcf8e3">
        <input type="submit" class="btn btn-small btn-success button j-cat-copy-step2 j-submit" style="display: none;" value="<?= _te('', 'Продолжить'); ?>" data-loading-text="<?= _te('', 'Продолжить'); ?>" onclick="return jCategory.copySettingsToSubs(2, this);" />
        <input type="button" class="btn btn-small button j-cat-copy-step2" style="display: none;" value="<?= _te('', 'Отметить все'); ?>" onclick="return jCategory.copySettingsToSubs(4, this);" />
        <input type="button" class="btn btn-small button j-cat-copy-step2" style="display: none;" value="<?= _te('', 'Cancel') ?>" onclick="return jCategory.copySettingsToSubs(3, this);" />
        <input type="button" class="btn btn-small button j-cat-copy-step1" value="<?= _te('bbs', 'Скопировать настройки в подкатегории'); ?>" onclick="return jCategory.copySettingsToSubs(1, this);" />
    </div>
    <? } ?>
    <div class="clearfix"></div>
</div>
</form>

<script type="text/javascript">
var jCategory = (function(){
    var $seekData, $form;
    var virtualToggler, virtualSettings, virtualSelect, virtualCache = null;

    $(function(){
        $form = $('#bbsCategoryForm');

        bff.iframeSubmit($form, function(data){
            if(data && data.success) {
                if (data.hasOwnProperty('redirect') || data.back) {
                    bff.redirect('<?= tpl::adminLink('categories_listing'); ?>');
                } else if(data.reload) {
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    bff.success('<?= _t('', 'Данные успешно сохранены'); ?>');
                    if (data.hasOwnProperty('landing_id')) {
                        $form.find('[name="landing_id"]').val(data.landing_id);
                    }
                    if (data.hasOwnProperty('landing_url')) {
                        $form.find('[name="landing_url"]').val(data.landing_url);
                    }
                }
            }
        },{
            beforeSubmit: function(){
                return true;
            },
            button: '.j-submit'
        });
        new bff.formChecker( document.forms.bbsCategoryForm );
        $seekData = $('#bbs-cat-seek');

        // tabs
        $form.find('#bbsCategoryFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
            $form.find('.j-cat-copy-step1').toggle((key === 'info'));
        });

        $form.find('[name="addr"]').change(function(){
            var ch = $(this).is(':checked');
            var $lt = $form.find('[name="list_type"]');
            var $lt_map = $lt.find('[value="<?= BBS::LIST_TYPE_MAP ?>"]');
            if(! ch && intval($lt.val()) == <?= BBS::LIST_TYPE_MAP ?>){
                $lt.val(0);
            }
            $lt_map.attr('disabled', ! ch);
        });

        $form.find('[name="tpl_title_enabled"]').change(function(){
            $form.find('.j-title-enabled').toggleClass('disabled', ! $(this).is(':checked'));
        });

        $form.on('click', '.j-cat-copy-checkbox', function(){
            var isChecked = $(this).is(':checked');
            var hiddenCheck = $form.find('.j-cat-copy-checkbox[value="'+$(this).val()+'"]:hidden');
            if (hiddenCheck.length) {
                hiddenCheck.prop('checked', isChecked);
                hiddenCheck.parent().parent().toggleClass('alert', isChecked);
            }
            $(this).parent().parent().toggleClass('alert', isChecked);
        });

        var focusedInput = null;
        $form.on('focus', '.j-focus-input', function(){
            focusedInput = this;
        });
        $form.on('click', '.j-tpl-macros', function(e){ nothing(e);
            var key = $(this).data('key');
            bff.textInsert(focusedInput, (key !== '|' ? key+' |' : key));
        });

        // virtual
        virtualToggler  = $form.find('.j-virtual-toggler');
        virtualSettings = $form.find('.j-virtual-settings');
        virtualSelect   = virtualSettings.find('.j-virtual-select');
        virtualToggler.on('click', function(){
            onVirtualToggle($(this).prop('checked'));
        });
        if (virtualToggler.prop('checked')) {
            onVirtualToggle(true);
        }
    });

    function onVirtualToggle(isVirtual)
    {
        // form controls
        jCategory.toggleTab('tpl', !isVirtual);
        $form.find('.j-virtual-hidden').toggle(!isVirtual);
        $form.find('.j-cat-copy-step1').toggle(!isVirtual);

        // virtual settings
        if (isVirtual) {
            if (virtualCache === null) {
                bff.ajax('<?= $this->adminLink('ajax&act=category-options') ?>',
                    {type: 'adm-category-add-virtual', selected: <?= $isVirtual ? $virtual_ptr : 0 ?>},
                    function (resp, error) {
                        if (resp && resp.success) {
                            virtualSelect.html((virtualCache = resp.options));
                            virtualSettings.show();
                        }
                    }, '#progress-category-form'
                );
            } else {
                virtualSettings.show();
            }
        } else {
            virtualSettings.hide();
        }
    }

    return {
        iconDelete: function(link){
            var $block = $(link).parent();
            $block.hide().find('input.del-icon').val(1);
            $block.prev().show();
            return false;
        },
        onSeek: function(check){
            var enabled = $(check).is(':checked');
            $seekData.val( ( enabled ? 1 : 0 ) );
            $('.j-bbs-cat-seek-param').toggleClass('disabled', !enabled);
            $('.j-bbs-cat-seek-toggler').not(check).prop('checked', enabled);
        },
        onOwnertypeSearch: function(check){
            var checked = $(check).is(':checked');
            var meta = $(check).metadata();
            $('.j-ownertype-search-val-'+meta.key).not(check).val((checked ? meta.id : 0));
            $('.j-ownertype-search-toggler-'+meta.key).not(check).prop({checked:checked});
        },
        copySettingsToSubs: function(step, btn){
            var btnParent = $(btn).parent();
            var chAll = $form.find('.j-cat-copy-checkbox');
            switch (step) {
                case 1: /* start */ {
                    $form.find('.j-cat-copy-step1').hide();
                    $form.find('.j-cat-copy-step2').show();
                    btnParent.css('background-color', btnParent.data('bg'));
                    $form.find('.j-cat-copy-row').show();
                } break;
                case 2: /* submit */ {
                    var settingsSelected = chAll.filter(':checked').length;
                    if (settingsSelected < 1) {
                        bff.error('<?= _t('bbs', 'Отметьте как минимум одно свойство для копирования его настроек'); ?>');
                        return false;
                    }
                    if ( ! bff.confirm('sure') ) return false;
                    $('#bbs-cat-copy-to-subs').val(1);
                } break;
                case 4: /* select all */ {
                    var chState = ! chAll.filter(':checked').length;
                    chAll.each(function(){
                        var ch = $(this); ch.prop('checked', chState);
                        ch.parent().parent().toggleClass('alert', chState);
                    });
                } break;
                case 3: /* cancel */ {
                    if (chAll.filter(':checked').length > 0) {
                        jCategory.copySettingsToSubs(4);
                    }
                    $form.find('.j-cat-copy-step1').show();
                    $form.find('.j-cat-copy-step2').hide();
                    $form.find('.j-cat-copy-row').hide();
                    btnParent.css('background-color', '');
                } break;
            }
            return true;
        },
        toggleTab: function(tabKey, show) {
            $form.find('#bbsCategoryFormTabs .j-tab-toggler[data-key="'+tabKey+'"]').parent().toggle(show);
        }
    };
}());
var jCategoryPrice = (function(){

    function getCurrTitle() {
        var sel = $('#j-price-curr select').get(0);
        return sel.options[sel.selectedIndex].text;
    }

    var ranges = (function(){
        var $block, iterator = <?= ( ! empty($price_sett) ? count($price_sett['ranges']) : 0 ); ?>;
        $(function(){
            $block = $('#j-price-ranges');

            $('#j-price-ranges-add').on('click', function(e){ nothing(e);
                addRange(++iterator);
                initRotate(true);
            });
            $block.on('click', '.j-price-ranges-del', function(e){ nothing(e);
                $(this).parent().remove();
            });
            initRotate(false);
        });

        function initRotate(update)
        {
            if(update === true) {
                $block.tableDnDUpdate();
            } else {
                $block.tableDnD({onDragClass: 'rotate'});
            }
        }

        function addRange(i)
        {
            $block.append('<tr class="range-'+i+'"><td><?= _t('', 'от') ?> <input name="price_sett[ranges]['+i+'][from]" type="text" class="input-mini" />&nbsp;&nbsp; <?= _t('', 'до') ?> <input name="price_sett[ranges]['+i+'][to]" type="text" class="input-mini" /><span class="help-inline j-price-ranges-curr-help">'+getCurrTitle()+'</span><a class="but cross j-price-ranges-del" href="#" style="margin-left:7px;"></a></td></tr>');
            $('.range-'+i+' > td > input:first', $block).focus();
        }
    }());
    return {
        onCurr: function() {
            $('.j-price-ranges-curr-help').text( getCurrTitle() );
        }
    };
}());
</script>
<?= tplAdmin::blockStop(); ?>
<? if(BBS::CATS_TYPES_EX && $edit) {
    echo $this->types_listing($id);
} ?>