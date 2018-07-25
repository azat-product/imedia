<?php use bff\db\Dynprops;
/**
 * Форма объявления: добавление / редактирование - дин. свойства
 * @var $this Dynprops
 * @var $dynprops array основные дин. свойства
 * @var $children array вложенные дин. свойства
 */

$drawControl = function($title, $value, $required, array $class = array()){
    if($required) $class[] = 'j-required';
    ?>
    <div class="control-group j-control-group<?= ( ! empty($class) ? ' '.join(' ', $class) : '' ) ?>">
        <label class="control-label"><?= $title ?><? if($required) { ?><span class="required-mark">*</span><? } ?></label>
        <div class="controls">
            <?= $value ?>
        </div>
    </div>
    <?
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
$drawDynprops = function($self, $numFirst = false) use (&$dynprops, &$aExtraSettings, $ownerColumn, $drawControl, &$children)
{
    $prefix = 'd';
    foreach($dynprops as $d)
    {
        if ( ( $numFirst && ! $d['num_first'] ) ||
             ( ! $numFirst && $d['num_first'] ) ) continue;

        $ID = $d['id'];
        $ownerID = $d[$ownerColumn];
        $name = $prefix.'['.$ownerID.']'.'['.$ID.']';
        $nameChild = $prefix.'['.$ownerID.']';
        $html = '';
        $class = array('j-dp');

        # метки доп. настроек
        foreach($aExtraSettings as $k=>$v) {
            if( $v['input'] == 'checkbox' && ! empty($d[$k]) ) {
                $class[] = 'j-dp-ex-'.$k;
            }
        }

        switch($d['type'])
        {
            # Группа св-в с единичным выбором
            case Dynprops::typeRadioGroup:
            {
                $value = (isset($d['value'])? $d['value'] : $d['default_value']);
                if ( ! empty($d['group_one_row']) ) {
                    $html = '';
                    foreach($d['multi'] as $v) {
                        if ( ! $v['value']) continue;
                        $html .= '<label class="radio inline"><input type="radio" name="'.$name.'"
                                '.($v['value'] == $value?' checked="checked"':'').' value="'.$v['value'].'" data-num="'.$v['num'].'" />'.$v['name'].'</label>';
                    }
                } else {
                    $html = HTML::renderList($d['multi'], $value, function($k,$i,$values) use ($name) {
                            $v = &$i['value']; if ( ! $v) return '';
                            return '<li><label class="radio"><input type="radio" name="'.$name.'"
                                '.($v == $values?' checked="checked"':'').' value="'.$v.'" data-num="'.$i['num'].'" />'.$i['name'].'</label></li>';
                        },
                        array(2=>4,3=>15),
                        array('class'=>'unstyled span'.(sizeof($d['multi']) > 15 ? 4 : 6))
                    );
                }
                if( ! empty($d['description']) ) {
                    $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
                }

            } break;

            # Группа св-в с множественным выбором
            case Dynprops::typeCheckboxGroup:
            {
                $value = ( isset($d['value']) && $d['value'] ? explode(';', $d['value']) : explode(';', $d['default_value']) );
                if ( ! empty($d['group_one_row']) ) {
                    $html = '';
                    foreach($d['multi'] as $v) {
                        $html .= '<label class="checkbox inline"><input type="checkbox" name="'.$name.'[]"
                                '.(in_array($v['value'],$value)?' checked="checked"':'').' value="'.$v['value'].'" data-num="'.$v['num'].'" />'.$v['name'].'</label>';
                    }
                } else {
                    $html = HTML::renderList($d['multi'], $value, function($k,$i,$values) use ($name) {
                            $v = &$i['value'];
                            return '<li><label class="checkbox"><input type="checkbox" name="'.$name.'[]"
                                '.(in_array($v,$values)?' checked="checked"':'').' value="'.$v.'" data-num="'.$i['num'].'" />'.$i['name'].'</label></li>';
                        },
                        array(2=>4,3=>15),
                        array('class'=>'unstyled span'.(sizeof($d['multi']) > 15 ? 4 : 6))
                    );
                }
                if( ! empty($d['description']) ) {
                    $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
                }

            } break;

            # Выбор Да/Нет
            case Dynprops::typeRadioYesNo:
            {
                $value = (isset($d['value'])? $d['value'] : $d['default_value']);
                $html = '<label class="radio inline"><input type="radio" name="'.$name.'" value="2" '.($value == 2?'checked="checked"':'').' />'.$self->langText['yes'].'</label>&nbsp;
                         <label class="radio inline"><input type="radio" name="'.$name.'" value="1" '.($value == 1?'checked="checked"':'').' />'.$self->langText['no'].'</label>';
                if( ! empty($d['description']) ) {
                    $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
                }
            } break;

            # Флаг
            case Dynprops::typeCheckbox:
            {
                $value = (isset($d['value'])? $d['value'] : $d['default_value']);
                $html = '<label class="checkbox"><input type="hidden" name="'.$name.'" value="0" /><input type="checkbox" name="'.$name.'" value="1" '.($value?'checked="checked"':'').' />'.$self->langText['yes'].'</label>';
                if( ! empty($d['description']) ) {
                    $html .= '<input type="hidden" /><span class="help-block">'.$d['description'].'</span>';
                }
            } break;

            # Выпадающий список
            case Dynprops::typeSelect:
            {
                $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
                if($d['parent'])
                {
                    $html = '<select name="'.$name.'" onchange="jForm.dpSelect('.$d['id'].', this.value, \''.$nameChild.'\');">';
                    $html .= HTML::selectOptions($d['multi'], $value, false, 'value', 'name');
                    $html .= '</select>';
                    if( ! empty($d['description']) ) {
                        $html .= '<span class="help-block">'.$d['description'].'</span>';
                    }
                    $drawControl($d['title'], $html, $d['req'], $class);

                    $html = '<span class="j-dp-child-'.$ID.'">';
                    if( ! empty($value) && isset($children[$ID])) {
                       $html .= $self->formChild($children[$ID], array('name'=>$nameChild,'class'=>($d['req']?' j-required':'')));
                    }
                    $html .= '</span>';
                    if( empty($value) ) {
                        $class[] = 'hide';
                        $class[] = 'j-dp-child-hidden';
                    }
                    $drawControl($d['child_title'], $html, $d['req'], $class);

                    continue 2;
                }
                else
                {
                    $html = '<select name="'.$name.'">'.HTML::selectOptions($d['multi'], $value, false, 'value', 'name').'</select>';
                    if( ! empty($d['description']) ) {
                        $html .= '<span class="help-block">'.$d['description'].'</span>';
                    }
                }
            } break;

            # Однострочное текстовое поле
            case Dynprops::typeInputText:
            {
                $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
                $html = '<input type="text" name="'.$name.'" value="'.HTML::escape($value).'" class="input-block-level" />';
                if( ! empty($d['description']) ) {
                    $html .= '<span class="help-block">'.$d['description'].'</span>';
                }

            } break;

            # Многострочное текстовое поле
            case Dynprops::typeTextarea:
            {
                $value = (isset($d['value']) ? $d['value'] : $d['default_value']);
                $html = '<textarea name="'.$name.'" rows="5" class="input-block-level" autocapitalize="off">'.HTML::escape($value).'</textarea>';
                # уточнение к названию
                if( ! empty($d['description']) ) {
                    $html .= '<span class="help-block">'.$d['description'].'</span>';
                }
            } break;

            # Число
            case Dynprops::typeNumber:
            {
                $value = (isset($d['value'])? $d['value'] : $d['default_value']);
                if( empty($d['description']) ) {
                    $html = '<input type="text" name="'.$name.'" value="'.$value.'" class="input-small" pattern="[0-9\.,]*" />';
                } else {
                    if( mb_strlen(strip_tags($d['description'])) <=5 ) {
                        $html = '<div class="input-append">
                                    <input type="text" name="'.$name.'" value="'.$value.'" class="input-small" pattern="[0-9\.,]*" />
                                    <span class="add-on">'.$d['description'].'</span>
                                 </div>';
                    } else {
                        $html  = '<input type="text" name="'.$name.'" value="'.$value.'" class="input-small" pattern="[0-9\.,]*" />';
                        $html .= '<span class="help-inline">'.$d['description'].'</span>';

                    }
                }
            } break;

            # Диапазон
            case Dynprops::typeRange:
            {
                $value = (isset($d['value']) && $d['value'] ? $d['value'] : $d['default_value']);

                $html = '<select name="'.$name.'" class="input-small">';
                if( ! empty($value) && ! intval($value)) {
                    $html .= '<option value="0">'.$value.'</option>';
                }
                if($d['start'] <= $d['end']) {
                    for($i = $d['start']; $i <= $d['end'];$i += $d['step']) {
                       $html .= '<option value="'.$i.'"'.($value == $i ? ' selected="selected"' : '').'>'.$i.'</option>';
                    }
                } else {
                    for($i = $d['start']; $i >= $d['end'];$i -= $d['step']) {
                       $html .= '<option value="'.$i.'"'.($value == $i ? ' selected="selected"' : '').'>'.$i.'</option>';
                    }
                }
                $html .= '</select>';

                # уточнение к названию
                if( ! empty($d['description']) ) {
                    $html .= '<span class="help-block">'.$d['description'].'</span>';
                }
            } break;
        }

        $drawControl($d['title'], $html, $d['req'], $class);
    }
};

$drawDynprops($this, true);
$drawDynprops($this, false);