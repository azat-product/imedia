<?php use bff\db\Dynprops;

/**
 * Просмотр объявления: блок дин. свойств
 * @var $this bff\db\Dynprops
 * @var $dynprops array основные дин. свойства
 * @var $children array вложенные дин. свойства
 */

/**
 * Отрисовка свойства
 * @param string $title название свойства
 * @param mixed $value значение свойства
 * @param string $description доп. описание
 */
$drawProperty = function($title, $value, $description = '') {
    if( mb_strlen(strip_tags($description)) > 5 ) {
        $description = '';
    }
    ?>
    <li>
        <span class="v-descr_properties_attr"><?= $title ?>:</span>
        <span class="v-descr_properties_val"><?= $value ?>&nbsp;<?= $description ?></span>
    </li>
    <?
};

/**
 * Отрисовка дин. свойств
 * @param bff\db\Dynprops $self
 * @param boolean $numFirst дин. св-ва помеченные вне очереди (num_first = 1)
 */
$drawDynprops = function($self, $numFirst = false) use (&$dynprops, $drawProperty, $children)
{
    foreach($dynprops as $d)
    {
        if(empty($d['value'])) continue;
        if ( ( $numFirst && ! $d['num_first'] ) ||
             ( ! $numFirst && $d['num_first'] ) ) continue;

        $value = $d['value'];

        switch($d['type'])
        {
            case Dynprops::typeRadioGroup:
            {
                foreach($d['multi'] as $dm) {
                   if($dm['value'] == $value) {
                       $value = $dm['name'];
                       break;
                   }
                }
            }break;
            case Dynprops::typeRadioYesNo:
            {
                $value = ($value == 2 ? $self->langText['yes'] : ($value == 1 ? $self->langText['no'] : ''));
            }break;
            case Dynprops::typeCheckboxGroup:
            {
                $value = explode(';', $value);
                $res = array();
                foreach($d['multi'] as $dm) {
                   if(in_array($dm['value'], $value))
                       $res[] = $dm['name'];
                }
                $value = join(', ', $res);
            }break;
            case Dynprops::typeCheckbox:
            {
                $value = ($value ? $self->langText['yes'] : $self->langText['no']);
            }break;
            case Dynprops::typeSelect:
            {
                if($d['parent'])
                {
                    foreach($d['multi'] as $dm) {
                        if($value == $dm['value']) {
                            $drawProperty($d['title'], $dm['name']);
                            break;
                        }
                    }
                    if( ! empty($value) && isset($children[$d['id']])) {
                        $dmv = current( $children[$d['id']] );
                        foreach($dmv['multi'] as $dm) {
                            if($dm['value'] == $dmv['value']) {
                                $drawProperty($d['child_title'], $dm['name']);
                                break;
                            }
                        }
                    }
                    continue 2;
                } else {
                    foreach($d['multi'] as $dm) {
                        if($value == $dm['value']) {
                            $value = $dm['name'];
                            break;
                        }
                    }
                }
            }break;
            case Dynprops::typeInputText:
            case Dynprops::typeTextarea:
            case Dynprops::typeNumber:
            case Dynprops::typeRange:
            {
                # $value = $d['value'];
            } break;
        }

        if( ! empty($value) ) {
            $drawProperty($d['title'], $value, $d['description']);
        }
    }

};

# ---------------------------------------------------------------------------------------
# Дин. свойства (вне очереди):
$drawDynprops($this, true);
# ---------------------------------------------------------------------------------------
# Дин. свойства (по порядку):
$drawDynprops($this, false);
