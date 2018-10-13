<?php
/**
 * @var $this \bff\tpl\admin\BlockListFilters
 */
use bff\tpl\admin\BlockListFilters;

?>
<div class="j-list-filter">
<form method="get" action="<?= tpl::adminLink(NULL) ?>" onsubmit="return false;" class="form-inline">
<?= HTML::input('hidden', $this->getControllerName(), ['name'=>'s', 'class'=>'j-module']); ?>
<?= HTML::input('hidden', $this->getControllerAction(), ['name'=>'ev', 'class'=>'j-method']); ?>
<?= HTML::input('hidden', $this->list->pagination()->getCurrentPage(), ['name'=>'page', 'class'=>'j-page-value']); ?>
<?= HTML::input('hidden', $this->list->order(false, false), ['name'=>$this->list->orderKey(), 'class'=>'j-order-value']); ?>
<?php
    # Tabs:
    echo $this->tabs()->render();
?>
<?php

if ( ! empty($this->filters)) {

    # Filters
    $sizeClasses = array(
        'mini' => 'input-mini',
        'small' => 'input-small',
        'medium' => 'input-medium',
        'large' => 'input-large',
        'xlarge' => 'input-xlarge',
        'xxlarge' => 'input-xxlarge',
    );
    foreach ($this->filters as $id=>$f)
    {
        $html = false;
        $value = $f['value'];
        $attr = &$f['attr'];
        $attr['name'] = $id;
        HTML::attributeAdd($attr, 'class', 'j-input');
        if (isset($f['width'])) {
            $attr['style']['width'] = 'width:'.$f['width'].(is_numeric($f['width']) ? 'px' : '');
        }
        if (isset($f['size']) && array_key_exists($f['size'], $sizeClasses)) {
            HTML::attributeAdd($attr, 'class', $sizeClasses[$f['size']]);
        }
        switch ($f['input'])
        {
            case BlockListFilters::INPUT_CUSTOM:
            {
                if (is_callable($f['callback'], true)) {
                    $html = call_user_func($f['callback'], $f);
                }
            } break;
            case BlockListFilters::INPUT_HIDDEN:
            {
                $attr['type'] = 'hidden';
                $attr['data-input'] = 'hidden';
                $html = HTML::input('input', $value, $attr);
            } break;
            case BlockListFilters::INPUT_TEXT:
            {
                if ( ! array_key_exists('type', $attr)) {
                    $attr['type'] = 'text';
                }
                if (empty($value) && $attr['type'] === 'number') {
                    $value = '';
                }
                $attr['data-input'] = 'text';
                $html = HTML::input('input', $value, $attr);
            } break;
            case BlockListFilters::INPUT_CHECKBOX:
            {
                $attr['type'] = 'checkbox';
                if ( ! empty($value)) {
                    $attr[] = 'checked';
                }
                $attr['data-input'] = 'checkbox';
                $html = '<label class="checkbox" style="border: 1px solid #ddd; border-radius: 4px; padding: 3px 6px 2px 6px; margin-bottom: 1px;">' .
                    HTML::input('input', $value, $attr) .
                    HTML::escape($f['title'])
                     . '</label>';
            } break;
            case BlockListFilters::INPUT_SELECT:
            {
                if (empty($f['options'])) {
                    continue;
                } else if (is_callable($f['options'], true)) {
                    $f['options'] = call_user_func($f['options']);
                }
                $attr['data-input'] = 'select';
                $options = (!empty($f['options']) && is_array($f['options']) ? $f['options'] : array());
                $options = HTML::selectOptions($options, $value,
                    (isset($f['empty']) ? $f['empty'] : false),
                    (isset($f['idKey']) ? $f['idKey'] : false),
                    (isset($f['titleKey']) ? $f['titleKey'] : false),
                    (isset($f['options.attr']) && is_array($f['options.attr']) ? $f['options.attr'] : array())
                );
                $html = '<div style="margin-top: 1px;"><select'.HTML::attributes($attr).'>'.$options.'</select></div>';
            } break;
            case BlockListFilters::INPUT_DATE:
            {
                $this->jsInclude('datepicker');
                HTML::attributeAdd($attr, 'class', 'j-datepicker');
                $attr['type'] = 'text';
                if ( ! isset($attr['style']['width'])) {
                    $attr['style']['width'] = 'width:65px;';
                }
                $attr['data-input'] = 'text';
                $html = HTML::input('input', $value, $attr);
                if ( ! empty($f['title']) && ! (isset($attr['placeholder']) && $attr['placeholder'] === $f['title'])) {
                    $html = '<label>'.$f['title'].'&nbsp;'.$html.'</label>';
                }
            } break;
            case BlockListFilters::INPUT_AUTOCOMPLETE:
            {
                if (empty($f['url']) || ! is_string($f['url'])) continue 2;
                $this->jsInclude('autocomplete');
                $rand = mt_rand(1,1000);

                # value
                $classId = 'j-input-autocomplete-'.$id.'-'.$rand.'-id';
                $attrHidden = [
                    'id' => $classId,
                    'type' => 'hidden',
                    'data-input' => 'autocomplete-value',
                    'class' => 'j-input',
                ];

                $html = '<label class="relative">';
                $html .= HTML::input('input', $value, $attrHidden);

                # title
                $classTitle = 'j-input-autocomplete-'.$id.'-'.$rand.'-title';
                $attr['id'] = $classTitle;
                $attr['type'] = 'text';
                $attr['data-input'] = 'autocomplete-title';
                $attr['autocomplete'] = 'off';
                HTML::attributeAdd($attr, 'class', 'autocomplete');
                $valueTitle = ($value > 0 ? (isset($f['value-title']) ? $f['value-title'] : $value) : '');
                $html .= HTML::input('input', $valueTitle, $attr);

                $html .= '</label>';

                $suggest = '{}';
                if (!empty($f['suggest'])) {
                    if (is_string($f['suggest'])) {
                        $suggest = $f['suggest'];
                    } else if (is_callable($f['suggest'], true)) {
                        $suggest = func::php2js(call_user_func($f['suggest'], $f, $id));
                    } else if (is_array($f['suggest'])) {
                        $suggest = func::php2js($f['suggest']);
                    }
                }

                $html.= '<script type="text/javascript">
                    $(function(){
                        $(\'#'.$classTitle.'\').autocomplete(\''.$f['url'].'\', {
                            valueInput: $(\'#'.$classId.'\'), 
                            params:'.( ! empty($f['params']) ? func::php2js($f['params']) : '{}').',
                            suggest:'.$suggest.',
                            onSelect:'.( ! empty($f['onSelect']) ? func::php2js($f['onSelect']) : 'function(){ var $el = $(this.valueInput); $el.closest(\'form\').trigger(\'autocomplete.select\', $el.attr(\'name\')); }').'
                        });
                    });
                </script>';
            } break;
        }

        if ($html !== false) {
            ?>
            <div class="left" style="margin-right: 5px;">
                <?= $html ?>
            </div>
            <?php
        }
    }
?>
<div class="left">
    <div class="btn-group">
        <input type="button" class="btn btn-small j-button-submit" value="<?= _te('', 'найти'); ?>" />
        <a class="btn btn-small j-button-reset" title="<?= _te('', 'сбросить'); ?>"><i class="disabled icon icon-refresh"></i></a>
    </div>
</div>
<div class="clear-all"></div>

<?php } ?>

</form>
</div>