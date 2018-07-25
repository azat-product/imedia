<?php use bff\db\Dynprops;
/**
 * Поиск объявлений: фильтр дин. свойств категорий (desktop, tablet)
 * @var $this Dynprops
 * @var $dynprops array дин. свойства
 * @var $extra array доп. параметры
 */
extract($extra['f'], EXTR_REFS | EXTR_PREFIX_ALL, 'filter');

$lng = array(
  'range_from' => _t('filter','от'),
  'range_to'   => _t('filter','до'),
  'btn_submit' => _t('filter','Отфильтровать'),
  'btn_reset'  => _t('filter','Не важно'),
  );

# $btn_active - активна ли кнопка фильтра
# $btn_value - текст указанного/выбранного значения фильтра
# $btn_value_plus - задействовано ли в фильтра более одного параметра
# $btn_reset - отображать ли в фильтре кнопку(checkbox) "Не важно"

/**
 * Отрисовка кнопки фильтра
 * @param string $title название кнопки
 * @param array $meta meta-данные (id - ID дин.свойства или 0, key - ключ для инициализации app.popup, type - тип фильтра(checks,range), parent - является ли фильтр parent-фильтром)
 * @param bool $active активная кнопка
 * @param string $value текст выбранного значения
 * @param bool $value_plus выбрано ли несколько значений (true)
 * @param bool $visible false - скрыть кнопку
 */
$drawButton = function($title, array $meta, $active = false, $value = '', $value_plus = false, $visible = true) use ($lng, $filter_seek) {
  ?><a href="#" class="l-filter-options-btn j-button<?php if($active) { ?> active<?php } if( ! $visible || ( $filter_seek && ! $meta['seek']) ) { ?> hide<?php } ?>" data="{id:<?= $meta['id'] ?>,key:'<?= $meta['key'] ?>',type:'<?= $meta['type'] ?>',parent:<?= $meta['parent'] ?>,seek:<?= ($meta['seek']?'true':'false') ?>}">
  <span class="l-filter-options-btn-title">
    <?= $title ?>
  </span>
  <span class="l-filter-options-btn-value j-value">
    <?= ( ! empty($value) ? $value : $lng['btn_reset'] ) ?> <i class="fa fa-plus-square extra<?php if(!$value_plus){ ?> hide<?php } ?>"></i>
  </span>
  <span class="l-filter-options-btn-caret spacer">
    <i class="fa fa-caret-down j-button-caret"></i>
  </span>
</a>
<?php
};

/**
 * Отрисовка общей структуры выпадающего блока фильтра
 * @param bool $open true - открываем блок, false - закрываем блок
 * @param bool $reset true - отображать кнопку "сбросить фильтр"
 * @param integer $activeCounter кол-во выбранных элементов фильтра или 0
 * @param bool $submit true - отображать кнопку "фильтровать"
 * @param array $extra - доп. параметры
 */
$drawDropdown = function($open, $reset = false, $activeCounter = 0, $submit = true, $extra = array()) use ($lng) {
  if( $open ) {
    ?><div class="dropdown-menu<?= ! empty($extra['class']) ? ' '.$extra['class'] : '' ?>"<?= ! empty($extra['id']) ? ' id="'.$extra['id'].'"' : '' ?>><?php
  } else {
    ?>  <div class="clearfix"></div>
    <?php if($submit || $reset) { ?>
      <div class="dropdown-menu-footer">
        <?php if($submit) { ?>
          <button type="button" class="btn btn-small btn-info j-submit"><?= $lng['btn_submit'] ?></button>
        <?php } if($reset) { ?>
          <div class="checkbox-inline mrgl10">
            <label><input type="checkbox" class="j-reset"<?php if( ! $activeCounter){ ?> disabled="disabled" checked="checked"<?php } ?> /><?= $lng['btn_reset'] ?></label>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
  </div><?php
}
};

/**
 * Отрисовка checkbox-фильтра
 * @param integer $group группа чекбоксов
 * @param string $name input-name
 * @param string $title название кнопки
 * @param integer $selected ID выбранного значения
 * @param integer $value базовое значение, подменяющее "on"
 * @param bool $inSeek отбражать при фильтрации по типу "ищу"
 */
$drawCheckbox = function($group, $name, $title = '', $selected = 0, $value = 1, $inSeek = true) use ($lng, $filter_seek) {
  static $html = array(0=>'',1=>'');
  if( $name !== NULL ) {
    if( ! $group ) $html[$group] .= '<span class="l-filter-options-checkboxes'.( $inSeek ? ' j-seek' : '' ).'"'.($filter_seek && ! $inSeek ? ' style="display:none;"' : '' ).'>';
    $html[$group] .= '<div class="l-filter-options-checkbox checkbox"><label class="j-checkbox"><input type="checkbox" name="'.$name.'" value="'.$value.'" '.($selected ? ' checked="checked"' : '').' />'.$title.'</label></div>';
    if( ! $group ) $html[$group] .= '</span>';
  } else {
    if( $group ) { ?><span class="l-filter-options-checkboxes<?= ($inSeek ? ' j-seek' : '') ?>"><?php }
      if( ! empty($html[$group]) ) echo $html[$group];
    if( $group ) { ?></span><?php }
      $html[$group] = '';
  }
};

# фильтр подкатегорий:
if (!empty($extra['cat']['subs_filter']))
{
  $subs = & $extra['cat']['subs_filter'];
  $btn_active = false; $btn_value = '';
  foreach ($subs as &$v) {

        # открываем {dropdown}
    $drawDropdown(true);

    $btn_active = false;
    $btn_value = _t('filter','Все');
    echo HTML::renderList($v['subs'], array(), function($k,$i,$values) use (&$btn_active, &$btn_value) {
      $class = '';
      if($i['active']) {
        $btn_active = true;
        $btn_value = $i['title'];
        $class = ' active';
      }
      return '<li><a href="'. $i['link'] .'" data-id="'.$i['id'].'" class="j-catLink'. $class .'">'. $i['title'] .'</a></li>';
    }, array(1=>40), array('class'=>'dropdown-menu-list'));
    ?>
      <a href="<?= $v['link'] ?>" class="dropdown-menu-list-footLink j-catLink"><?= _t('filter','Все') ?></a>
    <?php

        # закрываем {dropdown}, выводим {button}
    $drawDropdown(false, false, 0, false);
    $drawButton(( !empty($v['subs_title']) ? $v['subs_title'] : _t('filter','Выберите категорию') ),
      array('id'=>0,'key'=>'subcats-'.$v['id'],'type'=>'subcats','parent'=>0,'seek'=>true),
      $btn_active, $btn_value, false);

  } unset($v, $btn_active, $btn_value);
}

/**
 * Отрисовка дин. свойств
 * @param Dynprops $self
 * @param boolean $numFirst дин. св-ва помеченные вне очереди (num_first = 1)
 */
$drawDynprops = function($self, $numFirst = false) use (&$dynprops, &$extra, $drawButton, $drawDropdown, $drawCheckbox, $lng) {
  $prefix = 'd';
  $prefix_child = 'dc';
  extract($extra['f'], EXTR_REFS | EXTR_PREFIX_ALL, 'filter');

  $i = 0;
  foreach($dynprops as $d)
  {
    if ( ( $numFirst && ! $d['num_first'] ) ||
     ( ! $numFirst && $d['num_first'] ) ) continue;

      $i++;
    $name = $prefix.'['.$d['data_field'].']';
    $ID = $d['id'];
    $d['value'] = ( isset($filter_d[ $d['data_field'] ]) ? $filter_d[ $d['data_field'] ] : '');
    $btn_meta = array('id'=>$ID,'key'=>'dp-'.$ID,'type'=>'','parent'=>$d['parent'],'seek'=>$d['in_seek']);

    switch($d['type'])
    {
            case Dynprops::typeSelect: # Выпадающий список
            case Dynprops::typeSelectMulti: # Список с мультивыбором
            case Dynprops::typeRadioGroup: # Группа св-в с единичным выбором
            case Dynprops::typeCheckboxGroup: # Группа св-в с множественным выбором
            {
                # открываем {dropdown}
              $drawDropdown(true);

              $values = (isset($d['value']) && $d['value'] ? $d['value'] : array());
              $self->input->clean($values, TYPE_ARRAY_UINT);

              $btn_active = sizeof($values); reset($values);
              $btn_value = FALSE;
              $btn_value_plus = $btn_active > 1;
              $btn_reset = true;
              $btn_meta['type'] = 'checks';
              if( ! empty($d['multi']) ) {
                if( isset($d['multi'][0]) && empty($d['multi'][0]['value']) ) {
                        unset($d['multi'][0]); # удаляем вариант "-- выберите --"
                      }
                      if( $btn_active ) {
                        $valFirst = current($values);
                        foreach($d['multi'] as $m) {
                          if($m['value'] == $valFirst) {
                            $btn_value = $m['name']; break;
                          }
                        }
                      }
                      ?><div class="l-filter-checkboxes"><?php
                      echo HTML::renderList($d['multi'], $values, function($k,$i,$values) use ($name) {
                        $v = &$i['value'];
                        return '<div class="checkbox"><label><input type="checkbox" name="'.$name.'[]"
                        '.(in_array($v, $values)?' checked="checked"':'').' value="'.$v.'" data-num="'.$i['num'].'" />'.$i['name'].'</label></div>';
                      }, array(2=>4,3=>15), array('class' => 'l-filter-checkboxes-item'), 'div');
                      ?></div><?php
                    }

                # закрываем {dropdown}, выводим {button}
                    $drawDropdown(false, $btn_reset, $btn_active);
                    $drawButton($d['title'], $btn_meta, $btn_active, $btn_value, $btn_value_plus);

                    if( $d['parent'] )
                    {
                    # CHILD: открываем {dropdown}
                      $drawDropdown(true);
                      ?><div class="j-children"><?php

                      $parent_active = $btn_active && ! empty($d['multi']);
                      $btn_active = 0;
                      $btn_value = FALSE;
                      $btn_value_plus = false;
                      $btn_reset = true;
                      $cnt_multi = 0;

                    if( $parent_active ) # есть выбранные элементы в PARENT-свойстве
                    {
                      $aPairs = array();
                      foreach($values as $v) $aPairs[] = array('parent_id'=>$ID,'parent_value'=>$v);
                      $aChildren = $self->getByParentIDValuePairs($aPairs, true);
                      $aChildren = ( ! empty($aChildren[$ID]) ? $aChildren[$ID] : array() );

                      foreach($d['multi'] as $k=>$m)
                      {
                        if( empty($aChildren[$m['value']]) ) continue;
                        $cnt_multi++;
                        $dd = $aChildren[$m['value']];
                            # CHILD: реализуем поддержку типов, формирующих вывод в виде checkbox-списков
                        if( ! in_array($dd['type'], array(Dynprops::typeSelect, Dynprops::typeSelectMulti, Dynprops::typeRadioGroup, Dynprops::typeCheckboxGroup)) )
                          continue;
                            # CHILD: находим отмеченные(active=1), формируем "текст отмеченных"($btn_value)
                        if( isset($filter_dc[ $dd['data_field'] ][ $dd['id'] ]) ) {
                          foreach($dd['multi'] as $kk=>$mm) {
                            if( in_array($mm['value'], $filter_dc[ $dd['data_field'] ][ $dd['id'] ]) ) {
                              $dd['multi'][$kk]['active'] = ++$btn_active;
                              if($btn_value === FALSE) $btn_value = $mm['name'];
                            }
                          }
                        }
                            # CHILD: выводим checkbox-списки с заголовками
                        $name_child = $prefix_child.'['.$dd['data_field'].']['.$dd['id'].']';
                        ?><div id="dp-<?= $ID ?>-child-<?= $m['value'] ?>" data-num="<?= $m['num'] ?>"><div class="f-catfilter__popup__subtitle rel"><span><?= $m['name'] ?></span> <hr/></div><?php
                        echo HTML::renderList($dd['multi'], array(), function($k,$i,$values) use ($name_child) {
                          $v = $i['value'];
                          return '<li><label class="checkbox"><input type="checkbox" name="'.$name_child.'[]"
                          '.( ! empty($i['active'])?' checked="checked"':'').' value="'.$v.'" />'.$i['name'].'</label></li>';
                        }, array(2=>4,3=>15));
                        ?></div><?php
                      }
                      $btn_value_plus = $btn_active > 1;
                    }

                    # CHILD: закрываем {dropdown}, выводим {button}
                    ?></div><?php
                    $drawDropdown(false, $btn_reset, $btn_active);
                    $drawButton($d['child_title'], array('id'=>$ID,'key'=>'dp-'.$ID.'-child', 'type'=>'checks-child', 'parent'=>0, 'seek'=>$d['in_seek']), $btn_active, $btn_value, $btn_value_plus, $parent_active && $cnt_multi > 0);
                  }

                } break;
            case Dynprops::typeRadioYesNo: # Выбор Да/Нет
            {
                # {checkbox}
              $drawCheckbox(0, $name, $d['title'], ! empty($d['value']), 2, $d['in_seek']);
            } break;
            case Dynprops::typeCheckbox: # Флаг
            {
                # {checkbox}
              $drawCheckbox(0, $name, $d['title'], ! empty($d['value']), 1, $d['in_seek']);
            } break;
            case Dynprops::typeNumber: # Число
            case Dynprops::typeRange: # Диапазон
            {
              $value = ( is_array($d['value']) ? $d['value'] : array() );
              $self->input->clean_array($value, array(
                    'f' => TYPE_UNUM, # от (пользовательский вариант)
                    't' => TYPE_UNUM, # до (пользовательский вариант)
                    'r' => TYPE_ARRAY_UINT, # отмеченные диапазоны (ranges)
                    )); $from = $value['f']; $to = $value['t']; if($from && $to && $from>$to) $from = $value['f'] = 0;

              $sel = FALSE;
              $custom = ! empty($d['search_range_user']);
              if( $self->searchRanges ) {
                foreach($d['search_ranges'] as $k=>$i){
                  $d['search_ranges'][$k]['title'] = $i['title'] = ($i['from'] && $i['to'] ? $i['from'].'...'.$i['to'] : ($i['from'] ? '> '.$i['from'] : '< '.$i['to']));
                  if($sel === FALSE && in_array($k, $value['r'])) {
                    $sel = $i['title'];
                  }
                }
              }
              $btn_active = intval( ($custom && ($from || $to) ? 1 : 0) + sizeof($value['r']) );
              $btn_value = ( ($custom && ($from || $to)) ? (($from && $to) ? $from.' - '.$to:(($from ? $lng['range_from'].'&nbsp;'.$from : $lng['range_to'].'&nbsp;'.$to))) : $sel );
              $btn_value_plus = ($btn_active > 1);
              $btn_reset = true;
              $btn_meta['parent'] = 0;
              $btn_meta['type'] = 'range';

                # открываем {dropdown}
              $drawDropdown(true);

              if( $custom )
              {
                ?>
                <div class="dropdown-menu-in">
                  <div class="form-inline l-filter-options-fromto">
                    <div class="form-group">
                      <label class="sr-only"><?= $lng['range_from'] ?></label>
                      <input type="text" class="form-control l-filter-options-fromto-input j-from" name="<?= $name ?>[f]" value="<?= ($from ?: '') ?>" placeholder="<?= $lng['range_from'] ?>" />
                    </div>
                    -
                    <div class="form-group">
                      <label class="sr-only"><?= $lng['range_to'] ?></label>
                      <input type="text" class="form-control l-filter-options-fromto-input j-to" name="<?= $name ?>[t]" value="<?= ($to ?: '') ?>" placeholder="<?= $lng['range_to'] ?>" />
                    </div>
                    <?php
                    if($d['parent'] && isset($aData['children'][$ID])) {
                      $childForm = $self->formChild($aData['children'][$ID], array('name'=>$prefix_child), true);
                      ?><span><?= $childForm; ?></span><?php
                    } else if( mb_strlen(strip_tags($d['description'])) <=5 ) {
                      ?><label><?= $d['description'] ?></label><?php
                    }
                    ?>
                  </div>
                </div>
                <?php
              }
              if($self->searchRanges && !empty($d['search_ranges']))
              {
                ?><div class="l-filter-checkboxes"><?php
                echo HTML::renderList($d['search_ranges'], $value['r'], function($k,$i,$values) use ($name) {
                  return '<div class="checkbox"><label><input type="checkbox" name="'.$name.'[r][]"
                  '.(in_array($k, $values)?' checked="checked"':'').' value="'.$k.'" />'.$i['title'].'</label></div>';
                }, array(2=>4,3=>15), array('class' => 'l-filter-checkboxes-item'), 'div');
                ?></div><?php
              }

                # закрываем {dropdown}
              $drawDropdown(false, $btn_reset, $btn_active);
                # выводим {button}
              $drawButton($d['title'], $btn_meta, $btn_active, $btn_value, $btn_value_plus);
            } break;
          }
        }

    # отрисовуем чекбоксы дин.свойств:
        if($i) $drawCheckbox(0, NULL);
      };

# ---------------------------------------------------------------------------------------
# Дин. свойства (вне очереди):
      $drawDynprops($this, true);

# ---------------------------------------------------------------------------------------
# Цена:
      if( ! empty($extra['price']['enabled']) ) {
    # PRICE: открываем {dropdown}
        $drawDropdown(true);

        extract($extra['price'], EXTR_PREFIX_ALL, 'price');
        $price_from = $filter_p['f'];
        $price_to = $filter_p['t'];
        $price_curr_fromto = ( ! empty($filter_p['c']) ? $filter_p['c'] : $price_sett['curr'] );
        $price_curr_fromto_text = ' '.Site::currencyData($price_curr_fromto, 'title_short');
        $price_curr_ranges_text = ' '.Site::currencyData($price_sett['curr'], 'title_short');
        ?>
        <div class="dropdown-menu-in">
          <div class="form-inline l-filter-options-fromto">
            <div class="form-group">
              <label for="filter-price-from" class="sr-only"><?= $lng['range_from'] ?></label>
              <input type="text" class="form-control l-filter-options-fromto-input j-from" id="filter-price-from" name="p[f]" value="<?= ($price_from ?: '') ?>" maxlength="15" placeholder="<?= $lng['range_from'] ?>" />
            </div>
            -
            <div class="form-group">
              <label for="filter-price-to" class="sr-only"><?= $lng['range_to'] ?></label>
              <input type="text" class="form-control l-filter-options-fromto-input j-to" name="p[t]" value="<?= ($price_to ?: '') ?>" maxlength="15" placeholder="<?= $lng['range_to'] ?>" />
            </div>
            <div class="form-group">
              <select name="p[c]" class="form-control l-filter-options-fromto-input j-curr-select"><?= Site::currencyOptions($price_curr_fromto) ?></select>
            </div>
            <input type="hidden" class="j-curr" value="<?= $price_curr_fromto_text ?>" />
          </div>
        </div>
        
        <?php

    # PRICE: строим варианты (ranges)
        $btn_active = (($price_from OR $price_to) ? 1 : 0);
        $btn_value = FALSE;
        if( ! empty($price_sett['ranges']) && is_array($price_sett['ranges']) ) {
          $price_ranges = $price_sett['ranges'];
          foreach($price_ranges as $k=>&$v) {
            $v['title'] = ($v['from'] && $v['to'] ? $v['from'].'...'.$v['to'] : ($v['from'] ? '> '.$v['from'] : '< '.$v['to'])).$price_curr_ranges_text;
            if(in_array($k, $filter_p['r'])) {
              $v['a'] = 1;
              $btn_active++;
              if($btn_value === FALSE) $btn_value = $v['title'];
            }
          } unset($v);
          ?><div class="dropdown-menu-in l-filter-checkboxes"><?php
          echo HTML::renderList($price_ranges, array(), function($k,$i,$values) {
            return '<div class="checkbox"><label><input type="checkbox" name="p[r][]"
            '.( ! empty($i['a'])?' checked="checked"':'').' value="'.$k.'" />'.$i['title'].'</label></div>';
          }, array(2=>4,3=>15), array('class' => 'l-filter-checkboxes-item'), 'div');
          ?></div><?php
        }
        $btn_value = ( ($price_from || $price_to) ? (($price_from && $price_to) ? $price_from.' - '.$price_to : ($price_from ? $lng['range_from'].'&nbsp;'.$price_from : $lng['range_to'].'&nbsp;'.$price_to)).$price_curr_fromto_text : $btn_value );
        $btn_value_plus = ($btn_active > 1);

    # PRICE: закрываем {dropdown}, выводим {button}
        $drawDropdown(false, true, $btn_active);
        $drawButton( ( ! empty($price_sett['title'][LNG]) ? $price_sett['title'][LNG] : _t('filter','Цена') ),
          array('id'=>0,'key'=>'price','type'=>'price','parent'=>0,'seek'=>true), $btn_active, $btn_value, $btn_value_plus);
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
        # Район: открываем {dropdown}
          $drawDropdown(true);

          $btn_active = 0;
          $btn_value = false;

        # Район: перечисляем список
          foreach ($aDistricts as &$v) {
            if (in_array($v['id'], $filter_rd)) {
              $v['a'] = 1;
              $btn_active++;
              if ($btn_value === FALSE) $btn_value = $v['t'];
            }
          } unset($v);

          ?><div class="l-filter-checkboxes"><?php
          echo HTML::renderList($aDistricts, array(), function($k,$i,$values) {
            return '<div class="checkbox"><label><input type="checkbox" name="rd[]"
            '.( ! empty($i['a'])?' checked="checked"':'').' value="'.$k.'" />'.$i['t'].'</label></div>';
          }, array(2=>4,3=>15), array('class' => 'l-filter-checkboxes-item'), 'div')
          ?></div><?php

          $btn_value_plus = ($btn_active > 1);

        # Район: закрываем {dropdown}, выводим {button}
          $drawDropdown(false, true, $btn_active);
          $drawButton( _t('filter','Район города'),
            array('id'=>0,'key'=>'district','type'=>'price','parent'=>0,'seek'=>true), $btn_active, $btn_value, $btn_value_plus);
        }
      }

# ---------------------------------------------------------------------------------------
# Станция метро:
      if (!empty($extra['cat']['addr_metro'])) {
        $nCityID = 0;
        $regionData = Geo::filter();
        if (!empty($regionData['id']) ) {
          if (Geo::isCity($regionData['id'])) {
            $nCityID = $regionData['id'];
          }
        }
        $aMetro = array();
        if ($nCityID && Geo::hasMetro($nCityID)) {
          $aMetro = Geo::cityMetro($nCityID);
        }
        if (!empty($aMetro)) {
        # Метро: открываем {dropdown}
          $drawDropdown(true, false, 0, true, array('id' => 'j-filterMetro', 'class' => 'accordion'));

          $btn_active = 0;
          $btn_value = false;

          $nBranches = count($aMetro['data']);
        # Метро: перечисляем список
          if ($nBranches == 1) {
            $aMetro = reset($aMetro['data']);
            ?>
            <div class="l-filter-metro-item"><span><div class="f-catfilter__popup__subtitle__color" style="background: <?= $aMetro['color'] ?>;"></div> <?= $aMetro['t'] ?></span> <hr /></div>
            <?php
            foreach($aMetro['st'] as & $v){
              if (in_array($v['id'], $filter_rm)) {
                $v['a'] = 1;
                $btn_active++;
                if ($btn_value === FALSE) $btn_value = $v['t'];
              }
            } unset($v);

            echo HTML::renderList($aMetro['st'], array(), function($k,$i,$values) {
              return '<li><label class="checkbox"><input type="checkbox" name="rm[]"
              '.( ! empty($i['a'])?' checked="checked"':'').' value="'.$k.'" />'.$i['t'].'</label></li>';
            }, 2, array('class' => 'f-catfilter__popup__metro'));
          } else {
            foreach ($aMetro['data'] as &$v) {
              $cnt = 0;
              foreach ($v['st'] as &$vv) {
                if (in_array($vv['id'], $filter_rm)) {
                  $vv['a'] = 1;
                  $cnt++;
                }
              } unset($vv);
              ?>
              <div class="panel l-filter-metro-item j-metro-branch">
                <a data-toggle="collapse" data-parent="#j-filterMetro" href="#j-filterMetro<?= $v['id'] ?>">
                  <span class="c-metro-ico" style="background: <?= $v['color'] ?>;">
                    <span class="c-metro-ico-cnt j-cnt"><?= $cnt ? $cnt : '' ?></span>
                  </span>
                  <?= $v['t'] ?>
                </a>
                <div id="j-filterMetro<?= $v['id'] ?>" class="collapse">
                  <?php
                  echo HTML::renderList($v['st'], array(), function($k,$i,$values) {
                    return '<div class="checkbox"><label><input type="checkbox" name="rm[]"
                    '.( ! empty($i['a'])?' checked="checked"':'').' value="'.$k.'" />'.$i['t'].'</label></div>';
                  }, 2, array('class' => 'l-filter-metro-item-list'), 'div');
                  ?>
                  <div class="clearfix"></div>
                </div>
              </div>
              <?php
            } unset($v);
            echo '<div class="mrgt5"></div>';
            $btn_active = count($filter_rm) > 0;
            if ($btn_active) {
              $btn_value = tpl::declension(count($filter_rm), _t('filter','станция;станции;станций'));
            }
          }
          $btn_value_plus = ($btn_active > 1);
        # Метро: закрываем {dropdown}, выводим {button}
          $drawDropdown(false, true, $btn_active);
          $drawButton( _t('filter','Метро'),
            array('id'=>0,'key'=>'metro','type'=> ($nBranches == 1 ? 'price' : 'metro'),'parent'=>0,'seek'=>true), $btn_active, $btn_value, $btn_value_plus);
        }
      }

# ---------------------------------------------------------------------------------------
# Дин. свойства (по порядку):
      $drawDynprops($this, false);

# c фото
      if($extra['photos']) $drawCheckbox(1, 'ph', _t('filter', 'С фото'), $filter_ph);
# тип владельца
      if($extra['owner_business'] && $extra['owner_search'] ) {
        $i = 0;
        foreach(array(BBS::OWNER_PRIVATE, BBS::OWNER_BUSINESS) as $owner_type) {
          if( $extra['owner_search'] & $owner_type ) {
            $drawCheckbox(1, 'ow['.($i++).']', $extra['owner_business_title'][$owner_type], in_array($owner_type, $filter_ow), $owner_type);
          }
        }
      }
# Дополнительные чекбоксы:
      $drawCheckbox(1, NULL);