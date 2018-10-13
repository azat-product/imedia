<?php
    tpl::includeJS(array('datepicker','autocomplete'), true);
    tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>_t('banners', '+ добавить баннер'), 'href'=>$this->adminLink('add')),
        'fordev'=>array(
            'reset-cache' => array('title'=> _t('banners', 'сбросить кеш'), 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('ajax&act=dev-reset-cache')."'})", 'icon'=>'icon-refresh'),
        ),
    ));
    $locales = bff::locale()->getLanguages(false);
    $localeFilter = (Banners::FILTER_LOCALE && sizeof($locales) > 1);
    $rotate = $order_by == 'num' && ! empty($f['pos']) && empty($f['region']) && empty($f['locale']) && empty($f['show_start']) && empty($f['show_finish']);
    if($rotate){
        tpl::includeJS('tablednd', true);
    }
?>
<div class="actionBar relative">
    <form action="<?= $this->adminLink(NULL) ?>" method="get" name="bannersForm" id="j-banners-form" class="form-inline">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="order" value="<?= $order_by.tpl::ORDER_SEPARATOR.$order_dir ?>" />
        <div class="controls controls-row">
            <div class="left">
            <select name="pos" class="input-medium" style="width: 130px;" onchange="jBannersList.submit();">
                 <option value=""><?= _t('banners', 'Все позиции'); ?></option>
                 <?php foreach($positions as $k=>$v) { ?>
                    <option value="<?= $v['id'] ?>" <?php if($f['pos'] == $v['id']){ ?>selected="selected"<?php } ?>><?= $v['title'] ?>&nbsp;(<?= $v['sizes'] ?>)</option>
                 <?php } ?>
            </select>&nbsp;
            <? if($localeFilter) { ?>
            <select name="locale" onchange="jBannersList.submit();" style="width: 120px;">
                <option value=""<? if(empty($f['locale'])){ ?> selected="selected"<? } ?>><?= _t('banners', 'Локализация'); ?></option>
                <option value="<?= Banners::LOCALE_ALL ?>"<? if($f['locale'] == Banners::LOCALE_ALL){ ?> selected="selected"<? } ?>><?= _t('banners', 'Все локализации'); ?></option>
                <? foreach ($locales as $k=>$v) { ?>
                    <option value="<?= $k ?>"<? if($f['locale'] == $k){ ?> selected="selected"<? } ?>><?= $v['title'] ?></option>
                <? } ?>
            </select>&nbsp;
            <? } ?>
            </div>
            <div class="left">
                <?= Geo::i()->regionSelect($f['region'], 'region', array(
                    'placeholder' => Geo::coveringType(Geo::COVERING_COUNTRIES) ? _t('', 'Страна / Регион') : _t('', 'Регион'), 'width' => '130px',
                )); ?>
            </div>
            <div class="left" style="margin-left: 4px;">
            &nbsp;<?= _t('banners', 'Показ:'); ?> <input type="text" name="show_start" value="<?= HTML::escape($f['show_start']) ?>" placeholder="<?= _te('date', 'с'); ?>" style="width:65px;" />
            <input type="text" name="show_finish" value="<?= HTML::escape($f['show_finish']) ?>" placeholder="<?= _te('date', 'по'); ?>" style="width:65px;" />
            &nbsp;<select name="status" onchange="jBannersList.submit();" style="width:100px;"><?= HTML::selectOptions(array(0=>_t('', 'все'),1=>_t('', 'выключенные'),2=>_t('', 'включенные')), $f['status']) ?></select>
            &nbsp;<input class="btn btn-small button submit" type="submit" value="<?= _te('', 'найти'); ?>" />
            <a class="cancel" onclick="jBannersList.reset(); return false;"><?= _t('', 'сбросить'); ?></a>
            </div>
            <div class="clear"></div>
        </div>
    </form>
    <div style="position: absolute; right: 0; top:-8px;">
        <span id="j-banners-progress" style="display:none;" class="progress"></span>
    </div>
</div>
<table class="table table-condensed table-hover admtbl tblhover" id="j-banners-list">
<thead>
    <tr class="header<?= $rotate ? ' nodrag nodrop ' : '' ?>">
        <?
            $aCols = array(
                'id'          => array('t'=>_t('', 'ID'),              'w'=>40,   'order'=>'desc'),
                'title'       => array('t'=>_t('banners', 'Баннер'),   'w'=>false,'order'=>false),
                'limit'       => array('t'=>_t('banners', 'Лимит'),    'w'=>60,   'order'=>false),
                'show_start'  => array('t'=>_t('banners', 'Начало показа'), 'w'=>75, 'order'=>'desc'),
                'show_finish' => array('t'=>_t('banners', 'Конец показа'),  'w'=>75, 'order'=>'desc'),
                'shows'       => array('t'=>_t('banners', 'Показов'),  'w'=>65,   'order'=>'desc'),
                'clicks'      => array('t'=>_t('banners', 'Кликов'),   'w'=>55,   'order'=>'desc'),
                'ctr'         => array('t'=>_t('banners', 'CTR(%)'),   'w'=>53,   'order'=>'desc'),
                'action'      => array('t'=>_t('banners', 'Действие'), 'w'=>104,  'order'=>false),
            );
            foreach($aCols as $k=>$v) {
                if( empty($v['order']) ) {
                    ?><th<?php if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>><?= $v['t'] ?></th><?
                } else {
                    ?><th<?php if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>>
                     <?php if( $order_by == $k ) { ?>
                        <a href="javascript:void(0);" onclick="jBannersList.order('<?= $k ?>-<?= $order_dir_needed ?>');"><?= $v['t'] ?>
                        <div class="order-<?= $order_dir ?>"></div></a>
                     <?php } else { ?>
                        <a href="javascript:void(0);" onclick="jBannersList.order('<?= $k ?>-<?= $v['order'] ?>');"><?= $v['t'] ?></a>
                     <?php } ?>
                     </th><?
                }
            }
        ?>
    </tr>
</thead>
<?php foreach($banners as $k=>$v) { ?>
<tr class="row<?= $k%2 ?><?php if( ! $v['enabled']) { ?> desc<?php } ?>"<?= $rotate ? ' id="dnd-'.$v['id'].'" ' : '' ?>>
        <td class="small"><?= $v['id'] ?></td>
        <td width="200">
            <a href="<?= HTML::escape($v['click_url']) ?>" class="but linkout" target="_blank" rel="noreferrer noopener"></a><a href="javascript:void(0)" onclick="return jBannersList.preview(<?= $v['id'] ?>);"><?= ! empty($v['description']) ? tpl::truncate($v['description'], 35, '...', true) : $v['pos']['title'] ?></a><br />
            <a href="#" onclick="jBannersList.region(<?= $v['region_id'] ?>); return false;" class="desc"><?= $v['region_title'] ?></a>
            <? if($localeFilter && ! empty($v['locale']) && ! in_array(Banners::LOCALE_ALL, $v['locale'])) { ?>
               <span class="desc">/ <? foreach ($v['locale'] as $l) { ?><a href="javascript:void(0);" class="but" style="margin-right: 3px;"><span class="lang-icon country-icon country-icon-<?= (isset($locales[$l]['country']) ? $locales[$l]['country'] : '') ?>"></span></a><? } ?></span>
            <? } ?>
        </td>
        <td class="small"><?= ( ! empty($v['show_limit']) ? $v['show_limit'] : '&mdash;') ?></td>
        <td><?= tpl::date_format3($v['show_start'], 'd.m.Y') ?></td>
        <td><?= tpl::date_format3($v['show_finish'], 'd.m.Y') ?></td>
        <td><?= intval($v['shows']) ?></td>
        <td><?= intval($v['clicks']) ?></td>
        <td><?= $v['ctr'] ?></td>
        <td>
            <a class="but sett" title="Статистика" href="<?= $this->adminLink('statistic&id='.$v['id']) ?>" ></a>
            <a class="but <?php if($v['enabled']){ ?>un<?php } ?>block" onclick="return jBannersList.toggle(<?= $v['id'] ?>, this);"></a>
            <a class="but edit" href="<?= $this->adminLink('edit&id='.$v['id']) ?>"></a>
            <a class="but del" href="#" onclick="bff.confirm('sure',{r: '<?= $this->adminLink('delete&id='.$v['id']) ?>'}); return false;"></a>
        </td>
</tr>
<?php } if(empty($banners)) { ?>
<tr class="norecords">
    <td colspan="9"><?= _t('', 'Nothing found') ?></td>
</tr>
<?php } ?>
</table>
<? if($rotate): ?>
<div>
    <div class="left"></div>
    <div class="right desc" style="width:60px; text-align:right;">
        &darr; &uarr;
    </div>
    <br />
</div>
<? endif; ?>
<script type="text/javascript">
var jBannersList = (function(){
    var $form;

    $(function(){
        $form = $('#j-banners-form');
        bff.datepicker('input[name^=show_]', {yearRange: '-5:+5'});
        <? if($rotate): ?>bff.rotateTable($('#j-banners-list'), '<?= $this->adminLink('ajax&act=rotate&pos='.$f['pos']); ?>', '#j-banners-progress');<? endif;  ?>
    });

    function formSubmit()
    {
        $form.submit();
    }

    return {
        toggle: function(id, link)
        {
            bff.ajaxToggle(id, '<?= $this->adminLink('ajax&act=banner-toggle') ?>', {link: link, complete: function(data){
                $(link).closest('tr').toggleClass('desc');
            }});
            return false;
        },
        order: function(order)
        {
           $('[name=order]', $form).val(order);
           formSubmit();
        },
        preview: function(id)
        {
            bff.ajax('<?= $this->adminLink('preview') ?>', {id:id}, function(data){
                if(data) { $.fancybox(data); }
            });
            return false;
        },
        region: function(regionID)
        {
            $('.j-geo-region-select-id', $form).val(regionID);
            formSubmit();
        },
        reset: function()
        {
            bff.redirect('<?= $this->adminLink(bff::$event) ?>');
        },
        submit: function()
        {
            formSubmit();
        }
    };
}());
</script>