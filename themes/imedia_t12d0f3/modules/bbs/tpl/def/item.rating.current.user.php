<style type="text/css">
    .star-rating-user {
        line-height:32px;
        font-size:1.25em;
    }

    .star-rating-user .fa-star{color: red;}
</style>

<span class="star-rating-user">
    <? $value = (int) $aData['value']; ?>
    <? for($i = 1; $i <= 5; $i++): ?>
        <span class="fa  <?= $value >= $i ? 'fa-star' : 'fa-star-o'?>" data-rating="<?=$i?>"></span>
    <? endfor;?>
    <input type="hidden" name="whatever1" class="rating-value" value="<?=number_format($aData['value'], 2)?>">
</span>


