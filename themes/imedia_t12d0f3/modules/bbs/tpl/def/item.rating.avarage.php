<? $class_prefix = !empty($class_prefix) ? $class_prefix : '' ?>


<div class="mrgt10 <?=$class_prefix?>star-rating-item-avarage star-imedia">
    <? $intValue = round($value);  ?>
    <? for($i = 1; $i <= 5; $i++): ?>
        <span class="fa fa-star  <?= $intValue >= $i ? 'active' : ''?>" data-rating="<?=$i?>"></span>
    <? endfor;?>
    (<span <?=!empty($allow_edit) ? 'id="star-rating-item-avarage"' : ''?>><?=number_format($value, 2)?></span>)
</div>


