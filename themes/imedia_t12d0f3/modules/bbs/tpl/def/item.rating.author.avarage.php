<? $class_prefix = !empty($class_prefix) ? $class_prefix : '' ?>

<style type="text/css">
    .<?=$class_prefix?>star-rating-author-avarage {
        line-height:32px;
        font-size:1.25em;
    }

    .<?=$class_prefix?>star-rating-author-avarage .fa-star{color: red;}
</style>

<span class="<?=$class_prefix?>star-rating-author-avarage">
    <? $intValue = round($value);  ?>
    <? for($i = 1; $i <= 5; $i++): ?>
        <span class="fa  <?= $intValue >= $i ? 'fa-star' : 'fa-star-o'?>" data-rating="<?=$i?>"></span>
    <? endfor;?>
    (<span id="star-rating-author-avarage"><?=number_format($value, 2)?></span>)
</span>
