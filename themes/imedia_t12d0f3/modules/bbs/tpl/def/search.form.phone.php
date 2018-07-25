<?php use bff\db\Dynprops;
/**
 * Поиск объявлений: фильтр дин. свойств категорий (phone)
 * @var $this Dynprops
 * @var $dynprops array дин. свойства
 * @var $extra array доп. параметры
 */

extract($extra['f'], EXTR_REFS | EXTR_PREFIX_ALL, 'filter');

$lng = array(
    'btn_reset' => _t('filter','Не важно'),
);

/**
 * Отрисовка select-фильтра
 * @param string $name input-name
 * @param string $title select-название
 * @param array $meta meta-данные (id - ID дин.свойства или 0, key - ID блока, type - тип дин. свойства, parent - является ли фильтр parent-фильтром)
 * @param array $values значения
 * @param integer $selected ID выбранного значения
 * @param bool $visible false - скрыть кнопку
 */
$drawSelect = function($name, $title, array $meta, array $values = array(), $selected = 0, $visible = true) use ($lng, $filter_seek) {
  ?><div class="l-filter-m-item j-select<?php if($selected>0) { ?> selected<?php } if( ! $visible || ( $filter_seek && ! $meta['seek'])) { ?> hide<?php } ?>" id="j-f-phone-<?= $meta['key'] ?>">
        <input type="hidden" name="<?= $name ?>" value="<?= $selected ?>" <?php if( ! $selected ) { ?> disabled="disabled"<?php } ?> />
        <select autocomplete="off" class="form-control" data="{id:<?= $meta['id'] ?>,title:'<?= HTML::entities($title) ?>',key:'<?= $meta['key'] ?>',parent:<?= $meta['parent'] ?>,seek:<?= ($meta['seek']?'true':'false') ?>}">
            <?= HTML::selectOptions(array(-1 => $title.': '.( isset($values[$selected]) ? $values[$selected] : $lng['btn_reset']), -2=>$lng['btn_reset']) + $values) ?>
        </select>
    </div><?php
};

/**
 * Отрисовка checkbox-фильтра
 * @param string $name input-name
 * @param string $title checkbox-название
 * @param integer $selected ID выбранного значения
 * @param integer $value базовое значение, подменяющее "on"
 * @param bool $inSeek отбражать при фильтрации по типу "ищу"
 */
$drawCheckbox = function($name, $title = '', $selected = 0, $value = 1, $inSeek = true) use ($lng, $filter_seek) {
    static $html = '';
    if( $name !== NULL ) {
        $html .= '
        <div class="checkbox j-checkbox'.( $inSeek ? ' j-seek' : '' ).'"'.($filter_seek && ! $inSeek ? ' style="display:none;"' : '' ).'>
            <label><input type="checkbox" name="'.$name.'" value="'.$value.'" '.($selected ? ' checked="checked"' : '').' />&nbsp;'.$title.'</label>
        </div>';
    } else {
        if($html!='') echo $html;
    }
};

/**
 * Отрисовка дин. свойств
 * @param Dynprops $self
 * @param boolean $numFirst дин. св-ва помеченные вне очереди (num_first = 1)
 */
$drawDynprops = function($self, $numFirst = false) use (&$dynprops, &$extra, $drawSelect, $drawCheckbox, $lng) {
    $prefix = 'md';
    $prefix_child = 'mdc';
    extract($extra['f'], EXTR_REFS | EXTR_PREFIX_ALL, 'filter');

    foreach($dynprops as $d)
    {
        if ( ( $numFirst && ! $d['num_first'] ) ||
             ( ! $numFirst && $d['num_first'] ) ) continue;

        $name = $prefix.'['.$d['data_field'].']';
        $ID = $d['id'];
        $btn_name = $name;
        $btn_values = array();
        $btn_selected = ( ! empty($filter_md[ $d['data_field'] ]) ? intval($filter_md[ $d['data_field'] ]) : 0);
        $btn_meta = array('id'=>$ID,'key'=>'dp-'.$ID,'type'=>$d['type'],'parent'=>$d['parent'],'seek'=>$d['in_seek']);

        switch($d['type'])
        {
            case Dynprops::typeSelect: # Выпадающий список
            case Dynprops::typeSelectMulti: # Список с мультивыбором
            case Dynprops::typeRadioGroup: # Группа св-в с единичным выбором
            case Dynprops::typeCheckboxGroup: # Группа св-в с множественным выбором
            {
                if( empty($d['multi']) ) continue;
                foreach($d['multi'] as $m) {
                    if( empty($m['value']) ) continue; # пропускаем вариант "-- выберите --"
                    $btn_values[$m['value']] = $m['name'];
                }

                # {select}
                $drawSelect($btn_name, $d['title'], $btn_meta, $btn_values, $btn_selected);

                if( $d['parent'] )
                {
                    $btn_name = '';
                    $parent_value = $btn_selected;
                    $btn_selected = 0;
                    $btn_values = array();
                    if( $parent_value ) # есть выбранный элемент в PARENT-свойстве
                    {
                        $aChildren = $self->getByParentIDValuePairs(array(array('parent_id'=>$ID,'parent_value'=>$parent_value)), true);
                        if( ! empty($aChildren[$ID][$parent_value]) ) {
                            $dd = $aChildren[$ID][$parent_value];
                            $btn_selected = ( ! empty($filter_mdc[$dd['data_field']]) ? $filter_mdc[$dd['data_field']] : 0 );
                            $btn_name = $prefix_child.'['.$dd['data_field'].']';
                            if( ! empty($dd['multi']) ) {
                                foreach($dd['multi'] as $m) $btn_values[$m['value']] = $m['name'];
                            }
                        }
                    }

                    # CHILD: {select}
                    $drawSelect($btn_name, $d['child_title'], array('id'=>$ID,'key'=>'dp-'.$ID.'-child', 'type'=>Dynprops::typeSelect, 'parent'=>0, 'child'=>true, 'seek'=>$d['in_seek']),
                                $btn_values, $btn_selected, $parent_value);
                }

            } break;
            case Dynprops::typeNumber: # Число
            case Dynprops::typeRange: # Диапазон
            {
                if( ! $self->searchRanges || empty($d['search_ranges']) ) continue;

                $btn_values = array();
                foreach($d['search_ranges'] as $k=>$i){
                    $btn_values[$k] = ($i['from'] && $i['to'] ? $i['from'].'...'.$i['to'] : ($i['from'] ? '> '.$i['from'] : '< '.$i['to']));
                }

                # {select}
                $drawSelect($btn_name, $d['title'], $btn_meta, $btn_values, $btn_selected);
            } break;
            case Dynprops::typeRadioYesNo: # Выбор Да/Нет
            {
                # {checkbox}
                $drawCheckbox($btn_name, $d['title'], $btn_selected, 2, $d['in_seek']);
            } break;
            case Dynprops::typeCheckbox: # Флаг
            {
                # {checkbox}
                $drawCheckbox($btn_name, $d['title'], $btn_selected, 1, $d['in_seek']);
            } break;
            default: continue;
        }
    }
};

# ---------------------------------------------------------------------------------------
# Дин. свойства (вне очереди):
$drawDynprops($this, true);

# ---------------------------------------------------------------------------------------
# Цена:
if( ! empty($extra['price']['enabled']) ) {
    extract($extra['price'], EXTR_PREFIX_ALL, 'price');
    # PRICE: строим варианты (ranges)
    if( ! empty($price_sett['ranges']) && is_array($price_sett['ranges']) ) {
        $btn_values = array();
        $btn_selected = $filter_mp;
        $price_curr = ' '.Site::currencyData($price_sett['curr'], 'title_short');
        foreach($price_sett['ranges'] as $k=>$v) {
            $btn_values[$k] = ($v['from'] && $v['to'] ? $v['from'].'...'.$v['to'] : ($v['from'] ? '> '.$v['from'] : '< '.$v['to'])).$price_curr;
        }
        # PRICE: выводим {button}
        $drawSelect('mp', ( ! empty($price_sett['title'][LNG]) ? $price_sett['title'][LNG] : _t('filter','Цена') ),
                    array('id'=>0,'key'=>'price','type'=>'price','parent'=>0,'seek'=>true), $btn_values, $btn_selected);
    }
}

# ---------------------------------------------------------------------------------------
# Район города:
if (Geo::districtsEnabled()) {
    $nCityID = 0;
    $regionData = Geo::filter();
    if (!empty($regionData['id'])) {
        if (Geo::isCity($regionData['id'])) {
            $nCityID = $regionData['id'];
        }
    }
    $aDistricts = array();
    if ($nCityID) {
        $aDistricts = Geo::districtList($nCityID);
    }
    if (!empty($aDistricts)) {
        $btn_values = array();
        $btn_selected = $filter_mrd;
        foreach ($aDistricts as $v) {
            $btn_values[ $v['id'] ] = $v['t'];
        }
        # Район: выводим {button}
        $drawSelect('mrd', _t('filter','Район'),
            array('id'=>0,'key'=>'district','type'=>'price','parent'=>0,'seek'=>true), $btn_values, $btn_selected);
    }
}

# ---------------------------------------------------------------------------------------
# Дин. свойства (по порядку):
$drawDynprops($this, false);

# {checkbox}: c фото
if($extra['photos']) $drawCheckbox('mph', _t('filter', 'С фото'), $filter_mph);
# {checkbox}: тип владельца
if($extra['owner_business'] && $extra['owner_search']) {
    $i = 0;
    foreach(array(BBS::OWNER_PRIVATE, BBS::OWNER_BUSINESS) as $owner_type) {
        if( $extra['owner_search'] & $owner_type ) {
            $drawCheckbox('mow['.($i++).']', $extra['owner_business_title'][$owner_type], in_array($owner_type, $filter_mow), $owner_type);
        }
    }
}

# отрисовуем все чекбоксы
$drawCheckbox(NULL);