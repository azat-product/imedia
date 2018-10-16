<style type="text/css">
    .star-rating-author-cat-avarage {
        line-height:32px;
        font-size:1.25em;
    }

    .star-rating-author-cat-avarage .fa-star{color: red;}
</style>

<span class="star-rating-author-cat-avarage">
    <? $intValue = round($value);  ?>
    <? for($i = 1; $i <= 5; $i++): ?>
        <span class="fa  <?= $intValue >= $i ? 'fa-star' : 'fa-star-o'?>" data-rating="<?=$i?>"></span>
    <? endfor;?>
    (<span><?=number_format($value, 2)?></span>)
</span>
