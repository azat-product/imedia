<?php
/**
 * @var $this \bff\tpl\admin\BlockList
 * @var $columns boolean рендерить заголовок
 * @var $rowId integer|boolean рендерить только указанную строку
 */

 $rotationEnabled = $this->tabs()->isActiveRotation();

 $columnsVisible = 0;
 foreach ($this->columns as &$c) {
     $visible = true;
     if ($c['showIf'] !== false && ! call_user_func($c['showIf'], $c)) {
        $visible = false;
     }
     $c['visible'] = $visible;
     $columnsVisible++;
 } unset($c);

if ($columns) { ?>
<thead class="j-list-head">
    <tr class="header nodrag nodrop j-list-head-row">
    <?php
        # Columns
        foreach ($this->columns as $id=>$c) {
            if ( ! $c['visible']) continue;
            $attr = &$c['attr.head'];
            $attr['data-id'] = $id;
            HTML::attributeAdd($attr, 'class', 'j-list-column');
            if ( ! empty($c['width'])) {
                $attr['width'] = $c['width'];
            }
            if ($c['align'] !== false) {
                HTML::attributeAdd($attr, 'class', $c['align']);
            }
            ?>
            <th<?= HTML::attributes($attr) ?>>
                <?php if ($c['order'] !== false && ! $rotationEnabled) { ?>
                    <?php if ( ! empty($c['orderActive']) ) { ?>
                        <a href="javascript:void(0);" data-id="<?= $id ?>" data-direction="<?= $c['orderActive']['direction_next'] ?>" class="ajax j-sort"><?= $c['title'] ?></a>
                        <div class="j-list-col-order-dir order-<?= $c['orderActive']['direction'] ?>"></div>
                    <?php } else { ?>
                        <a href="javascript:void(0);" data-id="<?= $id ?>" data-direction="<?= $c['order'] ?>" class="ajax j-sort"><?= $c['title'] ?></a>
                        <div class="j-list-col-order-dir order-<?= $c['order'] ?>" style="display:none;"></div>
                    <?php } ?>
                <?php } else { ?>
                    <?= $c['title'] ?>
                <?php } ?>
            </th>
            <?php
        }
    ?>
    </tr>
</thead>
<?php } ?>
<tbody class="j-list-body">
<?php
    # Rows
    if ( ! empty($this->rows) && $columnsVisible > 0) {
        foreach ($this->columns as $key=>&$column) {
            if ( ! $column['visible']) continue;
            $attrCell = &$column['attr.cell'];
            if ($column['align'] !== false) {
                HTML::attributeAdd($attrCell, 'class', $column['align']);
            }
        } unset($column);
        foreach ($this->rows as $row) {
            if ( ! array_key_exists($this->rowsIdKey, $row)) {
                continue;
            }
            if ($this->rowsFilter !== null && call_user_func($this->rowsFilter, $row) === false) {
                continue;
            }
            $attrRow = array('class'=>'j-list-row');
            $attrRow['data-id'] = $row[$this->rowsIdKey];
            ?><tr<?= HTML::attributes($attrRow) ?>><?php
                foreach ($this->columns as $key=>$column) {
                    if ( ! $column['visible']) continue;
                    if (array_key_exists('render', $column)) {
                        $isFullRow = false;
                        $row[$key] = call_user_func_array($column['render'], array(
                            'value' => (array_key_exists($key, $row) ? $row[$key] : null),
                            'row' => &$row,
                            'options' => ['full'=>&$isFullRow],
                        ));
                        if ($isFullRow) {
                            echo $row[$key]; continue;
                        } else {
                            $column['type'] = $this::COLUMN_TYPE_CUSTOM;
                        }
                    }
                    ?><td<?= HTML::attributes($column['attr.cell']) ?>><?php
                        if (array_key_exists($key, $row)) {
                            switch ($column['type']) {
                                case $this::COLUMN_TYPE_ID: {
                                    ?><span class="small"><?= strval($row[$key]); ?></span><?php
                                } break;
                                case $this::COLUMN_TYPE_TEXT: {
                                    echo strval($row[$key]);
                                } break;
                                case $this::COLUMN_TYPE_DATE: {
                                    echo tpl::date_format3($row[$key]);
                                } break;
                                case $this::COLUMN_TYPE_CUSTOM: {
                                    echo $row[$key];
                                } break;
                            }
                        }
                    ?></td><?php
                }
            ?></tr><?php
        }
    } else {
     ?>
        <tr class="norecords j-list-empty">
            <td colspan="<?= $columnsVisible ?>"><?= _te('', 'Nothing found') ?></td>
        </tr>
     <?php
    }
    ?>
</tbody>