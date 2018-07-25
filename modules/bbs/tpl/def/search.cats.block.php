<?php
/**
 * Блок категорий со счетчиками под фильтром
 * @var $this BBS
 * @var $cats array категории
 */
$visible = config::sysAdmin('bbs.search.categories.block.visible', 7, TYPE_UINT);
if (!empty($cats)): ?>
<div class="f-categories hidden-phone">
    <div class="f-categories-in" id="j-f-categories-block">
        <?php $cnt = count($cats); $cols = array(); $i = 0; $j = 0;
        foreach ($cats as $v) {
            if ($j == $visible && $cnt > ($visible+1)) {
                $cols[$i][] = '<li class="f-categories-col-more"> <a href="#" class="ajax pseudo-link-ajax" id="j-f-categories-toggle"> '.
                    _t('bbs', 'Показать еще [num]', array('num' => tpl::declension($cnt - $visible, _t('bbs', 'категорию;категории;категорий')))).'</a></li> ';
            }
            $cols[$i][] = '<li'.($j >= $visible && $cnt > ($visible+1) ? ' class="hide"' : '').'> <a href="'.$v['link'].'"> '.
                    '<span class="f-categories-col-item">'.$v['title'].'</span>'.
                    '<span class="f-categories-col-count">'.number_format($v['items'], 0, '', ' ').'</span></a></li>';
            $i++; if($i >= 4){ $i = 0; }
            $j++;
        } ?>
        <?php foreach($cols as $v): ?>
            <ul class="f-categories-col">
                <?= join("\n", $v) ?>
            </ul>
        <?php endforeach; ?>
    </div>
</div>
<?php if($cnt > $visible): ?>
<script type="text/javascript">
<?php js::start(); ?>
$(function(){
    $('#j-f-categories-toggle').click(function(e){
        e.preventDefault();
        $(this).closest('li').remove();
        $('#j-f-categories-block ul li').removeClass('hide');
    });
});
<?php js::stop(); ?>
</script>
<?php endif; endif; ?>