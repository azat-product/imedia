<div class="mrgt10 star-rating-author-cat-avarage star-imedia">
    <? $intValue = round($value);  ?>
    <? for($i = 1; $i <= 5; $i++): ?>
        <span class="fa fa-star <?= $intValue >= $i ? 'active' : 'active'?>" data-rating="<?=$i?>"></span>
    <? endfor;?>
    (<span><?=number_format($value, 2)?></span>)
</div>
