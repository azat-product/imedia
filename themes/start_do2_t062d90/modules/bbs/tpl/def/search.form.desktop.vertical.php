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
if ( ! isset($filter_seek)) {
  $filter_seek = (!BBS::CATS_TYPES_EX && $extra['f']['ct'] == BBS::TYPE_SEEK);
}

/**
 * Отрисовка общей структуры блока фильтра
 * @param string $items - HTML содержание блока
 * @param array $button( - параметры кнопки
 *                  'title'      - название кнопки
 *                  'meta'       - meta-данные (id - ID дин.свойства или 0, key - ключ для инициализации app.popup, type - тип фильтра(checks,range), parent - является ли фильтр parent-фильтром)
 *                  'active'     - активная кнопка
 *                  'value'      - текст выбранного значения
 *                  'value_plus' - выбрано ли несколько значений (true)
 *                  'hide'       - скрыть блок
 * )
 * @param array $block( - параметры блока
 *                  'reset'      - отображать кнопку "сбросить фильтр"
 *                  'id'         - id блока
 *                  'submit'     - отображать кнопку "фильтровать"
 *                  'extra'      - доп. параметры
 * )
 */
$drawBlock = function($items = '', $button = array(), $block = array()) use ($lng) {
  $id = ! empty($block['id']) ? $block['id'] : \func::generator(5);
  ?>
  <div class="l-filterAside-element<?= ! empty($button['active']) ? ' selected' : '' ?><?= ! empty($button['hide']) ? ' hide' : '' ?> j-filter-bl" data='<?= ! empty($button['meta']) ? func::php2js($button['meta']) : '' ?>'>
    <a href="#j-f-asideFilter-right-<?= $id ?>" data-toggle="collapse" class="l-filterAside-element-heading collapsed">
      <?= isset($button['title']) ? $button['title'] : '' ?>
      <span class="j-value"><?= ! empty($button['value']) ? $button['value'] : $lng['btn_reset'] ?> <i class="fa fa-plus-square extra<?= empty($button['value_plus']) ? ' hidden' : '' ?>"></i></span>
      <i class="fa fa-chevron-down l-filterAside-element-heading-arrow"></i>
    </a>
    <div id="j-f-asideFilter-right-<?= $id ?>" class="collapse j-filter-body">
      <?php if(empty($block['ul'])): ?>
      <div class="l-filterAside-element-in">
        <?= $items ?>
        <?php if( ! empty($block['reset'])): $disabled = empty($button['active']); ?>
        <div class="checkbox mrgb0<?= $disabled ? ' disabled' : '' ?>">
          <label>
            <input type="checkbox" class="j-reset" <?= $disabled  ? 'checked="checked" disabled="disabled"' : '' ?> /> <?= $lng['btn_reset'] ?>
          </label>
        </div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <ul class="l-filterAside-element-links">
        <?= $items ?>
      </ul>
      <?php if( ! empty($block['reset'])): $disabled = empty($button['active']); ?>
      <div class="l-filterAside-element-in">
        <div class="checkbox mrgb0<?= $disabled ? ' disabled' : '' ?>">
          <label>
            <input type="checkbox" class="j-reset" <?= $disabled  ? 'checked="checked" disabled="disabled"' : '' ?> /> <?= $lng['btn_reset'] ?>
          </label>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
<?php
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
  static $html = array(0=>'',1=>'', '2' => '');
  if( $name !== NULL ) {
    if( ! $group ) $html[$group] .= '<div class="checkbox '.( ! empty($html[$group]) ? ' mrgb0' : '').( $inSeek ? ' j-seek' : '' ).'"'.($filter_seek && ! $inSeek ? ' style="display:none;"' : '' ).'>';
    $html[$group] .= '<div class="checkbox"> <label class="j-checkbox"> <input type="checkbox" name="'.$name.'" value="'.$value.'" '.($selected ? ' checked="checked"' : '').' />&nbsp;'.$title.'</label></div>';
    if( ! $group ) $html[$group] .= '</div>';
  } else {
    if(empty($html[$group])) return;
    if( $group ) { ?><div class="l-filterAside-element j-filter-bl"> <div class="l-filterAside-element-in<?= ($inSeek ? ' j-seek' : '') ?>"> <?php }
      if( ! empty($html[$group]) ) echo $html[$group];
    if( $group ) { ?></div></div><?php }
      $html[$group] = '';
  }
};


/**
 * Отрисовка дин. свойств
 * @param Dynprops $self
 * @param boolean $numFirst дин. св-ва помеченные вне очереди (num_first = 1)
 */
$drawDynprops = function($self, $numFirst = false) use (&$dynprops, &$extra, $lng, $drawBlock, $drawCheckbox) {
  $prefix = 'd';
  $prefix_child = 'dc';
  extract($extra['f'], EXTR_REFS | EXTR_PREFIX_ALL, 'filter');

  $i = 0;
  foreach ($dynprops as $d)
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
              $values = (isset($d['value']) && $d['value'] ? $d['value'] : array());
              $self->input->clean($values, TYPE_ARRAY_UINT);

              $btn_active = sizeof($values); reset($values);
              $btn_value = FALSE;
              $btn_value_plus = $btn_active > 1;
              $btn_reset = true;
              $btn_meta['type'] = 'checks';
              $items = '';
              if( ! empty($d['multi']) ) {
                if( isset($d['multi'][0]) && empty($d['multi'][0]['value']) ) {
                        unset($d['multi'][0]); # удаляем вариант "-- выберите --"
                      }
                      if ($btn_active) {
                        $valFirst = current($values);
                        foreach($d['multi'] as $m) {
                          if($m['value'] == $valFirst) {
                            $btn_value = $m['name']; break;
                          }
                        }
                      }
                      ob_start();
                      ob_implicit_flush(false);
                      foreach($d['multi'] as $v) { ?>
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="<?= $name ?>[]" <?= (in_array($v['value'], $values) ? ' checked="checked"' : '') ?> value="<?= $v['value'] ?>" data-num="<?= $v['num'] ?>" /><?= $v['name'] ?>
                        </label>
                      </div>
                      <?php }
                      $items = ob_get_clean();
                    }
                    $drawBlock($items, array(
                      'title' => $d['title'],
                      'value' => $btn_value,
                      'value_plus' => $btn_value_plus,
                      'active' => $btn_active,
                      'meta' => $btn_meta,
                      ), array('reset' => $btn_reset));

                # закрываем {dropdown}, выводим {button}
                    if ($d['parent'] ) {
                      ob_start();
                      ob_implicit_flush(false);
                      ?>
                      <div class="j-children"><?php

                        $parent_active = $btn_active && !empty($d['multi']);
                        $btn_active = 0;
                        $btn_value = FALSE;
                        $btn_value_plus = false;
                        $btn_reset = true;
                        $cnt_multi = 0;

                    if ($parent_active) # есть выбранные элементы в PARENT-свойстве
                    {
                      $aPairs = array();
                      foreach ($values as $v) $aPairs[] = array('parent_id' => $ID, 'parent_value' => $v);
                      $aChildren = $self->getByParentIDValuePairs($aPairs, true);
                      $aChildren = (!empty($aChildren[$ID]) ? $aChildren[$ID] : array());

                      foreach ($d['multi'] as $k => $m) {
                        if (empty($aChildren[$m['value']])) continue;
                        $cnt_multi++;

                        $dd = $aChildren[$m['value']];
                            # CHILD: реализуем поддержку типов, формирующих вывод в виде checkbox-списков
                        if (!in_array($dd['type'], array(Dynprops::typeSelect, Dynprops::typeSelectMulti, Dynprops::typeRadioGroup, Dynprops::typeCheckboxGroup)))
                          continue;
                            # CHILD: находим отмеченные(active=1), формируем "текст отмеченных"($btn_value)
                        if (isset($filter_dc[$dd['data_field']][$dd['id']])) {
                          foreach ($dd['multi'] as $kk => $mm) {
                            if (in_array($mm['value'], $filter_dc[$dd['data_field']][$dd['id']])) {
                              $dd['multi'][$kk]['active'] = ++$btn_active;
                              if ($btn_value === FALSE) $btn_value = $mm['name'];
                            }
                          }
                        }
                            # CHILD: выводим checkbox-списки с заголовками
                        $name_child = $prefix_child . '[' . $dd['data_field'] . '][' . $dd['id'] . ']';
                        ?>
                        <div id="dp-<?= $ID ?>-child-<?= $m['value'] ?>" data-num="<?= $m['num'] ?>">
                          <div class="l-filterAside-element-subtitle rel"><span><?= $m['name'] ?></span>
                            <hr/></div><?php
                            foreach($dd['multi'] as $v){ ?>
                            <div class="checkbox">
                              <label>
                                <input type="checkbox" name="<?= $name_child ?>[]" <?= ! empty($v['active']) ? ' checked="checked"' : '' ?> value="<?= $v['value'] ?>" /><?= $v['name'] ?>
                              </label>
                            </div>
                            <?php } ?>
                          </div><?php
                        }
                        $btn_value_plus = $btn_active > 1;
                      }
                    # CHILD: закрываем
                      ?></div><?php
                      $items = ob_get_clean();

                      $drawBlock($items, array(
                        'title' => $d['child_title'],
                        'value' => $btn_value,
                        'value_plus' => $btn_value_plus,
                        'active' => $btn_active,
                        'meta' => array('id'=>$ID,'key'=>'dp-'.$ID.'-child', 'type'=>'checks-child', 'parent'=>0, 'seek'=>$d['in_seek']),
                        'hide' => ! $parent_active || $cnt_multi == 0
                        ), array('reset' => $btn_reset));
                    }
                  } break;
            case Dynprops::typeRadioYesNo: # Выбор Да/Нет
            {
                # {checkbox}
              $drawCheckbox(1, $name, $d['title'], ! empty($d['value']), 2, $d['in_seek']);
            } break;
            case Dynprops::typeCheckbox: # Флаг
            {
                # {checkbox}
              $drawCheckbox(1, $name, $d['title'], ! empty($d['value']), 1, $d['in_seek']);
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

              ob_start();
              ob_implicit_flush(false);
              if ($custom)
              {
                ?>
                <div class="l-filterAside-fromto">
                  <div class="l-filterAside-fromto-input">
                    <input class="form-control j-from" name="<?= $name ?>[f]" type="text" placeholder="<?= $lng['range_from'] ?>" value="<?= ($from ?: '') ?>" />
                  </div>
                  <div class="l-filterAside-fromto-input">
                    <input class="form-control fromto-slider-range-max j-to"   name="<?= $name ?>[t]" type="text" placeholder="<?= $lng['range_to'] ?>" value="<?= ($to ?: '') ?>" />
                  </div>
                </div>
                  <?php
                  if ($d['parent'] && isset($aData['children'][$ID])) {
                    $childForm = $self->formChild($aData['children'][$ID], array('name'=>$prefix_child), true);
                    ?><span><?= $childForm; ?></span><?php
                  }
                  ?>
                <?php
              }
              if($self->searchRanges)
              {
                foreach ($d['search_ranges'] as $k => $v) { ?>
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="<?= $name ?>[r][]" <?= in_array($k, $value['r']) ? ' checked="checked"' : '' ?> value="<?= $k ?>" /><?= $v['title'] ?>
                  </label>
                </div>
                <?php }
              }
              $items = ob_get_clean();

              $drawBlock($items, array(
                'title' => $d['title'].( ! empty($d['description']) && mb_strlen(strip_tags($d['description'])) <=5  ? ' <small>('.$d['description'].')</small>' : '' ),
                'value' => $btn_value,
                'value_plus' => $btn_value_plus,
                'active' => $btn_active,
                'meta' => $btn_meta
                ), array('reset' => $btn_reset));
            } break;
          }
        }

    # отрисовуем чекбоксы дин.свойств:
        if($i && $numFirst) $drawCheckbox(1, NULL);
      };
      ?>
      <div id="j-f-vertical">

        <?php
    # фильтр подкатегорий:
        if (!empty($extra['cat']['subs_filter']))
        {
          $subs = & $extra['cat']['subs_filter'];
          $btn_active = false; $btn_value = '';
          foreach ($subs as &$v) {

            $btn_active = false;
            $btn_value = _t('filter','Все');
            ob_start();
            ob_implicit_flush(false);
            foreach($v['subs'] as $vv){
              $class = '';
              if($vv['active']) {
                $btn_active = true;
                $btn_value = $vv['title'];
                $class = ' class="active"';
              }
              if ($vv['items'] > 0) { ?>
              <li<?= $class ?>><a href="<?= $vv['link'] ?>" class="j-catLink" data-id="<?= $vv['id'] ?>" ><?= $vv['title'] ?></a></li>
              <?php }
            }
            ?>
            <li class="last"><a href="<?= $v['link'] ?>" class="j-catLink"><?= _t('filter','Все') ?></a></li>
            <?php
            $items = ob_get_clean();
            $drawBlock($items, array(
              'title' => ! empty($v['subs_title']) ? $v['subs_title'] : _t('filter','Выберите категорию'),
              'meta'  => array('id'=>0,'key'=>'subcats-'.$v['id'],'type'=>'subcats','parent'=>0,'seek'=>true),
              'value' => $btn_value,
              ), array('ul' => 1));
          } unset($v, $btn_active, $btn_value);
        }

    # ---------------------------------------------------------------------------------------
    # Дин. свойства (вне очереди):
        $drawDynprops($this, true);

    # ---------------------------------------------------------------------------------------
    # Цена:
        if( ! empty($extra['price']['enabled']) ) {
          extract($extra['price'], EXTR_PREFIX_ALL, 'price');
          $price_from = $filter_p['f'];
          $price_to = $filter_p['t'];
          $price_curr_fromto = ( ! empty($filter_p['c']) ? $filter_p['c'] : $price_sett['curr'] );
          $price_curr_fromto_text = ' '.Site::currencyData($price_curr_fromto, 'title_short');
          $price_curr_ranges_text = ' '.Site::currencyData($price_sett['curr'], 'title_short');
          $currencies = Site::model()->currencyData(false);

          $items = '';
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
              $items .= '<div class="checkbox"> <label> <input type="checkbox" name="p[r][]" '.
              ( ! empty($v['a'])?' checked="checked"':'').' value="'.$k.'" />'.$v['title'].'</label> </div>';
            } unset($v);
          }
          $btn_value = ( ($price_from || $price_to) ? (($price_from && $price_to) ? $price_from.' - '.$price_to : ($price_from ? $lng['range_from'].'&nbsp;'.$price_from : $lng['range_to'].'&nbsp;'.$price_to)).$price_curr_fromto_text : $btn_value );
          $btn_value_plus = ($btn_active > 1);
          ob_start();
          ob_implicit_flush(false);
          ?>
          <input type="hidden" name="p[c]" value="<?= $price_curr_fromto ?>" />
          <?php if(count($currencies) < 4): ?>
          <ul class="l-filterAside-currency">
            <?php foreach($currencies as $v){ ?>
            <li<?= $price_curr_fromto == $v['id'] ? ' class="active"' : '' ?>><a href="#" class="j-currency-select" data-id="<?= $v['id'] ?>"><?= $v['title_short'] ?></a></li>
            <?php } ?>
          </ul>
        <?php else: ?>
        <div class="l-filterAside-dropdown dropdown">
          <?= _t('filter','Валюта') ?>:
          <a class="dropdown-toggle link-ajax" data-toggle="dropdown" href="#"><span class="j-currency-selected"><?= $currencies[$price_curr_fromto]['title_short'] ?></span><b class="caret"></b> </a>
          <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
            <?php foreach($currencies as $v){ ?>
            <li<?= $price_curr_fromto == $v['id'] ? ' class="active"' : '' ?>><a href="#" class="j-currency-select" data-id="<?= $v['id'] ?>"><?= $v['title_short'] ?></a></li>
            <?php } ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="l-filterAside-fromto">
        <div class="l-filterAside-fromto-input">
          <input class="form-control j-from" name="p[f]" type="text" placeholder="<?= $lng['range_from'] ?>" value="<?= ($price_from ?: '') ?>" maxlength="15" />
        </div>
        <div class="l-filterAside-fromto-input">
          <input class="form-control j-to"   name="p[t]" type="text" placeholder="<?= $lng['range_to'] ?>" value="<?= ($price_to ?: '') ?>" maxlength="15" />
        </div>
      </div>
      <?php
      $items = ob_get_clean().$items;
      $drawBlock($items, array(
        'title' => ! empty($price_sett['title'][LNG]) ? $price_sett['title'][LNG] : _t('filter','Цена'),
        'active' => $btn_active,
        'value' => $btn_value,
        'value_plus' => $btn_value_plus,
        'meta' => array('id'=>0,'key'=>'price','type'=>'price','parent'=>0,'seek'=>true),
        ), array('reset' => 1, 'id' => 'price'));
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
        $btn_active = 0;
        $btn_value = false;

        ob_start();
        ob_implicit_flush(false);
            # Район: перечисляем список
        foreach ($aDistricts as $k => &$v) {
          if (in_array($v['id'], $filter_rd)) {
            $v['a'] = 1;
            $btn_active++;
            if ($btn_value === FALSE) $btn_value = $v['t'];
          }
          ?>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="rd[]" <?= ! empty($v['a']) ? ' checked="checked"' : '' ?> value="<?= $k ?>" /><?= $v['t'] ?>
            </label>
          </div>
          <?php

        } unset($v);
        $items = ob_get_clean();

        $btn_value_plus = ($btn_active > 1);
        $drawBlock($items, array(
          'title' => _t('filter','Район города'),
          'value' => $btn_value,
          'value_plus' => $btn_value_plus,
          'active' => $btn_active,
          'meta' => array('id'=>0,'key'=>'district','type'=>'price','parent'=>0,'seek'=>true),
          ), array('reset' => 1));
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

        $btn_active = 0;
        $btn_value = false;

        $nBranches = count($aMetro['data']);
        ob_start();
        ob_implicit_flush(false);

            # Метро: перечисляем список
        if ($nBranches == 1) {
          $ul = 0;
          $aMetro = reset($aMetro['data']);
          foreach($aMetro['st'] as $k => & $v){
            if (in_array($v['id'], $filter_rm)) {
              $v['a'] = 1;
              $btn_active++;
              if ($btn_value === FALSE) $btn_value = $v['t'];
            }
            ?>
            <div class="checkbox">
              <label>
                <input type="checkbox" name="rm[]" <?= ! empty($v['a']) ? ' checked="checked"' : '' ?> value="<?= $k ?>" /><?= $v['t'] ?>
              </label>
            </div>
            <?php

          } unset($v);
        } else {
          $ul = 1;
          foreach ($aMetro['data'] as &$v) {
            $cnt = 0;
            foreach ($v['st'] as &$vv) {
              if (in_array($vv['id'], $filter_rm)) {
                $vv['a'] = 1;
                $cnt++;
              }
            } unset($vv);
            ?>
            <li class="j-metro-branch">
              <a data-toggle="collapse" data-parent="#j-filterMetro" href="#j-filterMetro<?= $v['id'] ?>"><span><div class="c-metro-ico" style="background: <?= $v['color'] ?>;"><div class="c-metro-ico-cnt j-cnt"><?= $cnt ? $cnt : '' ?></div></div><?= $v['t'] ?></span></a>
              <div id="j-filterMetro<?= $v['id'] ?>" class="collapse">
                <div class="l-filterAside-element-in">
                  <?php
                  foreach($v['st'] as $k => $vv){
                    ?>
                    <div class="checkbox">
                      <label>
                        <input type="checkbox" name="rm[]" <?= ! empty($vv['a']) ? ' checked="checked"' : '' ?> value="<?= $k ?>" /><?= $vv['t'] ?>
                      </label>
                    </div>
                    <?php
                  }
                  ?>
                </div>
                <div class="clearfix"></div>
              </div>
            </li>
            <?php
          } unset($v);
          echo '<div class="mrgt5"></div>';
          $btn_active = count($filter_rm) > 0;
          if ($btn_active) {
            $btn_value = tpl::declension(count($filter_rm), _t('filter','станция;станции;станций'));
          }
        }
        $items = ob_get_clean();

        $btn_value_plus = ($btn_active > 1);
            # Метро: закрываем {dropdown}, выводим {button}
        $drawBlock($items, array(
          'title' => _t('filter','Метро'),
          'value' => $btn_value,
          'value_plus' => $btn_value_plus,
          'active' => $btn_active,
          'meta' => array('id'=>0,'key'=>'metro','type'=> ($nBranches == 1 ? 'price' : 'metro'),'parent'=>0,'seek'=>true),
          ), array('reset' => 1, 'ul' => $ul));
      }
    }

    # ---------------------------------------------------------------------------------------
    # Дин. свойства (по порядку):
    $drawDynprops($this, false);

    # С фото
    if($extra['photos']) $drawCheckbox(1, 'ph', _t('filter', 'С фото'), $filter_ph);
    # Тип владельца
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
    ?>
    
    <div class="l-filterAside-footer">
      <button type="button" class="btn btn-success btn-sm j-submit"><i class="fa fa-check"></i> <?= _t('filter', 'Применить'); ?></button>
      <a class="btn btn-default btn-sm j-reset-all" href="#"><i class="fa fa-refresh"></i> <span><?= _t('filter', 'Сбросить фильтр') ?></span></a>
    </div>
    

  </div>