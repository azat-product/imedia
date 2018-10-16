<? $class_prefix = !empty($class_prefix) ? $class_prefix : '' ?>

<style type="text/css">
    .<?=$class_prefix?>star-rating-item-avarage {
        line-height:32px;
        font-size:1.25em;
    }

    .<?=$class_prefix?>star-rating-item-avarage .fa-star{color: red;}
</style>

<span class="<?=$class_prefix?>star-rating-item-avarage">
    <? $intValue = round($value);  ?>
    <? for($i = 1; $i <= 5; $i++): ?>
        <span class="fa  <?= $intValue >= $i ? 'fa-star' : 'fa-star-o'?>" data-rating="<?=$i?>"></span>
    <? endfor;?>
    (<span <?=!empty($allow_edit) ? 'id="star-rating-item-avarage"' : ''?>><?=number_format($value, 2)?></span>)
</span>


