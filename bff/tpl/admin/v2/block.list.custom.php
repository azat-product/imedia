<?php
/**
 * @var $this \bff\tpl\admin\BlockList
 * @var $rowId integer|boolean рендерить только указанную строку
 */

?>
<div class="j-list-body">
<?php
    # Rows
    if ( ! empty($this->rows)) {
        foreach ($this->rows as $row) {
            if ( ! array_key_exists($this->rowsIdKey, $row)) {
                continue;
            }
            if ($this->rowsFilter !== null && call_user_func($this->rowsFilter, $row) === false) {
                continue;
            }
            $attrRow = array('class'=>'j-list-row');
            $attrRow['data-id'] = $row[$this->rowsIdKey];
          ?><div<?= HTML::attributes($attrRow) ?>>
                <?php call_user_func_array($this->customRenderer, array($row)); ?>
            </div><?php
        }
    } else {
     ?>
        <div class="text-center">
            <?= _te('', 'Nothing found') ?>
        </div>
     <?php
    }
    ?>
</div>