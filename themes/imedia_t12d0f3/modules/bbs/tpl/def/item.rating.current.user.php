<span class="rating-control star-rating-user">
    <? $value = (int) $aData['value']; ?>
    <? for($i = 5; $i >= 1; $i--): ?>

        <input type="radio" name="rating" value="<?= $i; ?>" id="id_rating_<?= $i; ?>" required="" <?= $value == $i ? 'checked=""' : ''?>>
        <label data-rating="<?= $i?>" class="fa" for="id_rating_<?= $i; ?>"><?= $i; ?></label>
    <? endfor;?>
    <input  type="hidden" name="whatever1" class="rating-value" value="<?=number_format($aData['value'], 2)?>">
</span>

