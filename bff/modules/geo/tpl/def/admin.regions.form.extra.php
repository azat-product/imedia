<?php
/**
 * Шаблон дополнительных полей формы всех типов регионов кроме "районов города" (Geo::lvlDistrict)
 * @var $this Geo
 * @var $id integer ID региона
 * @var $data array данные региона
 * @var $fields array настройки доп. полей
 */

foreach ($fields as $f):

    $name = $f['field'];
    switch ($f['type']):
        case 'text':
            ?>
                <tr>
                    <td class="row1 field-title"><?= $f['title'] ?>:</td>
                    <td class="row2">
                        <input type="text" name="<?= $name ?>"<?= $f['attr'] ?> value="<?= ( isset($data[$name]) ? HTML::escape($data[$name]) : '' ) ?>" />
                    </td>
                </tr>
            <?php
            break;
        case 'textarea':
        case 'wy':
            ?>
                <tr>
                    <td class="row1 field-title"><?= $f['title'] ?>:</td>
                    <td class="row2">
                        <textarea name="<?= $name ?>"<?= $f['attr'] ?>><?= ( isset($data[$name]) ? HTML::escape($data[$name]) : '' ) ?></textarea>
                    </td>
                </tr>
            <?php
            break;
        case 'checkbox':
            ?>
                <tr>
                    <td class="row1 field-title"></td>
                    <td class="row2">
                        <label class="checkbox"><input type="checkbox" name="<?= $name ?>"<?= $f['attr'] ?> <? if(!empty($data[$name])) { ?> checked="checked"<? } ?> /><?= $f['title'] ?></label>
                    </td>
                </tr>
            <?php
            break;
    endswitch;

endforeach;
