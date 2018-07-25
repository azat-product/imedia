<?php use bff\db\Dynprops;

/**
 * Форма объявления: добавление / редактирование - дин. свойства
 * @var $this Dynprops
 * @var $dynprops array основные дин. свойства
 * @var $children array вложенные дин. свойства
 */

$drawControl = function ($title, $value, $required, array $class = array()) {
  if ($required) $class[] = 'j-required';
  ?>
  <div class="form-group j-control-group<?= (!empty($class) ? ' ' . join(' ', $class) : '') ?>">
    <label class="col-sm-3 control-label"><?= $title ?><?php if ($required) { ?><span class="required-mark">*</span><?php } ?>
    </label>
    <div class="col-md-3 col-sm-6">
      <?= $value ?>
    </div>
  </div>
  <?php
};

# ---------------------------------------------------------------------------------------
# Дин. свойства:
$aExtraSettings = $this->extraSettings();

/**
 * Отрисовка дин. свойств
 * @param bff\db\Dynprops $self
 * @param boolean $numFirst дин. св-ва помеченные вне очереди (num_first = 1)
 */
$ownerColumn = $this->ownerColumn;
$drawDynprops = function ($self, $numFirst = false) use (&$dynprops, &$aExtraSettings, $ownerColumn, $drawControl, &$children) {
  $prefix = 'd';
  foreach ($dynprops as $d) {
    if (($numFirst && !$d['num_first']) ||
      (!$numFirst && $d['num_first'])
    ) continue;

    $ID = $d['id'];
    $ownerID = $d[$ownerColumn];
    $name = $prefix . '[' . $ownerID . ']' . '[' . $ID . ']';
    $nameChild = $prefix . '[' . $ownerID . ']';
    $html = '';
    $class = array('j-dp');

    # метки доп. настроек
    foreach ($aExtraSettings as $k => $v) {
      if ($v['input'] == 'checkbox' && !empty($d[$k])) {
        $class[] = 'j-dp-ex-' . $k;
      }
    }

    switch ($d['type']) {
      # Группа св-в с единичным выбором
      case Dynprops::typeRadioGroup: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        if (!empty($d['group_one_row'])) {
          $html = '';
          foreach ($d['multi'] as $v) {
            if (!$v['value']) continue;
            $html .= '<div class="radio"><label><input type="radio" name="' . $name . '"
                                ' . ($v['value'] == $value ? ' checked="checked"' : '') . ' value="' . $v['value'] . '" data-num="' . $v['num'] . '" />' . $v['name'] . '</label></div>';
          }
        } else {
          $html = HTML::renderList($d['multi'], $value, function ($k, $i, $values) use ($name) {
            $v = &$i['value'];
            if (!$v) return '';
            return '<li><div class="radio"><label><input type="radio" name="' . $name . '"
                                ' . ($v == $values ? ' checked="checked"' : '') . ' value="' . $v . '" data-num="' . $i['num'] . '" />' . $i['name'] . '</label></div></li>';
          },
            array(2 => 4, 3 => 15),
            array('class' => 'unstyled span' . (sizeof($d['multi']) > 15 ? 4 : 6))
          );
        }
        if( ! empty($d['description']) ) {
          $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
        }
      }
        break;

      # Группа св-в с множественным выбором
      case Dynprops::typeCheckboxGroup: {
        $value = (isset($d['value']) && $d['value'] ? explode(';', $d['value']) : explode(';', $d['default_value']));
        if (!empty($d['group_one_row'])) {
          $html = '';
          foreach ($d['multi'] as $v) {
            $html .= '<div class="checkbox"><label><input type="checkbox" name="' . $name . '[]"
                                ' . (in_array($v['value'], $value) ? ' checked="checked"' : '') . ' value="' . $v['value'] . '" data-num="' . $v['num'] . '" />' . $v['name'] . '</label></div>';
          }
        } else {
          $html = HTML::renderList($d['multi'], $value, function ($k, $i, $values) use ($name) {
            $v = &$i['value'];
            return '<li><div class="checkbox"><label class="checkbox"><input type="checkbox" name="' . $name . '[]"
                                ' . (in_array($v, $values) ? ' checked="checked"' : '') . ' value="' . $v . '" data-num="' . $i['num'] . '" />' . $i['name'] . '</label></div></li>';
          },
            array(2 => 4, 3 => 15),
            array('class' => 'unstyled span' . (sizeof($d['multi']) > 15 ? 4 : 6))
          );
        }
        if( ! empty($d['description']) ) {
          $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
        }
      }
        break;

      # Выбор Да/Нет
      case Dynprops::typeRadioYesNo: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        $html = '<div class="radio-inline"><label><input type="radio" name="' . $name . '" value="2" ' . ($value == 2 ? 'checked="checked"' : '') . ' />' . $self->langText['yes'] . '</label></div>
                         <div class="radio-inline"><label><input type="radio" name="' . $name . '" value="1" ' . ($value == 1 ? 'checked="checked"' : '') . ' />' . $self->langText['no'] . '</label></div>';
        if( ! empty($d['description']) ) {
          $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
        }
      }
        break;

      # Флаг
      case Dynprops::typeCheckbox: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        $html = '<div class="checkbox"><label><input type="hidden" name="' . $name . '" value="0" /><input type="checkbox" name="' . $name . '" value="1" ' . ($value ? 'checked="checked"' : '') . ' />' . $self->langText['yes'] . '</label></div>';
        if( ! empty($d['description']) ) {
          $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
        }
      }
        break;

      # Выпадающий список
      case Dynprops::typeSelect: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        if ($d['parent']) {
          $html = '<select class="form-control" name="' . $name . '" onchange="jForm.dpSelect(' . $d['id'] . ', this.value, \'' . $nameChild . '\');">';
          $html .= HTML::selectOptions($d['multi'], $value, false, 'value', 'name');
          $html .= '</select>';
          if (!empty($d['description'])) {
            $html .= '<span class="help-block">' . $d['description'] . '</span>';
          }
          $drawControl($d['title'], $html, $d['req'], $class);

          $html = '<span class="j-dp-child-' . $ID . '">';
          if (!empty($value) && isset($children[$ID])) {
            $html .= $self->formChild($children[$ID], array('name' => $nameChild, 'class' => 'form-control'.($d['req'] ? ' j-required' : '')));
          }
          $html .= '</span>';
          if (empty($value)) {
            $class[] = 'hide';
            $class[] = 'j-dp-child-hidden';
          }
          $drawControl($d['child_title'], $html, $d['req'], $class);

          continue 2;
        } else {
          $html = '<select class="form-control" name="' . $name . '">' . HTML::selectOptions($d['multi'], $value, false, 'value', 'name') . '</select>';
          if (!empty($d['description'])) {
            $html .= '<span class="help-block">' . $d['description'] . '</span>';
          }
        }
      }
        break;

      # Однострочное текстовое поле
      case Dynprops::typeInputText: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        $html = '<input type="text" class="form-control" name="' . $name . '" value="' . HTML::escape($value) . '" class="input-block-level" />';
        if (!empty($d['description'])) {
          $html .= '<span class="help-block">' . $d['description'] . '</span>';
        }

      }
        break;

      # Многострочное текстовое поле
      case Dynprops::typeTextarea: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        $html = '<textarea name="' . $name . '" rows="5" class="form-control" autocapitalize="off">' . HTML::escape($value) . '</textarea>';
        # уточнение к названию
        if (!empty($d['description'])) {
          $html .= '<span class="help-block">' . $d['description'] . '</span>';
        }
      }
        break;

      # Число
      case Dynprops::typeNumber: {
        $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
        if (empty($d['description'])) {
          $html = '<input type="text" name="' . $name . '" value="' . $value . '" class="form-control" pattern="[0-9\.,]*" />';
        } else {
          if (mb_strlen(strip_tags($d['description'])) <= 5) {
            $html = '<div class="input-group">
                                    <input type="text" class="form-control input-small" name="' . $name . '" value="' . $value . '" pattern="[0-9\.,]*" />
                                    <span class="input-group-addon">' . $d['description'] . '</span>
                                 </div>';
          } else {
            $html = '<input type="text" class="form-control" name="' . $name . '" value="' . $value . '" class="input-small" pattern="[0-9\.,]*" />';
            $html .= '<div class="help-block">' . $d['description'] . '</div>';

          }
        }
      }
        break;

      # Диапазон
      case Dynprops::typeRange: {
        $value = (isset($d['value']) && $d['value'] ? $d['value'] : $d['default_value']);

        $html = '<select class="form-control" name="' . $name . '" class="input-small">';
        if (!empty($value) && !intval($value)) {
          $html .= '<option value="0">' . $value . '</option>';
        }
        if ($d['start'] <= $d['end']) {
          for ($i = $d['start']; $i <= $d['end']; $i += $d['step']) {
            $html .= '<option value="' . $i . '"' . ($value == $i ? ' selected="selected"' : '') . '>' . $i . '</option>';
          }
        } else {
          for ($i = $d['start']; $i >= $d['end']; $i -= $d['step']) {
            $html .= '<option value="' . $i . '"' . ($value == $i ? ' selected="selected"' : '') . '>' . $i . '</option>';
          }
        }
        $html .= '</select>';

        # уточнение к названию
        if (!empty($d['description'])) {
          $html .= '<div class="help-block">' . $d['description'] . '</div>';
        }
      }
        break;
    }

    $drawControl($d['title'], $html, $d['req'], $class);
  }
};

$drawDynprops($this, true);
$drawDynprops($this, false);