<?php
/**
 * Блок перелинковки
 * @var $this BBS
 * @var $cats array категории
 * @var $regs array регионы
 */
?>
    <ul class="sr-breakLisk hidden-phone" data-tabs="tabs">
        <? foreach($cats as $k => $v): ?>
            <li><a href="#categories-tab-<?= $k ?>" data-toggle="tab"><?= $v['t'] ?></a></li>
        <? endforeach; ?>
    </ul>

    <div class="sr-breakLisk-content tab-content hidden-phone">
        <? foreach($cats as $k => $v): ?>
            <div class="tab-pane" id="categories-tab-<?= $k ?>">
                <? $links = array(); foreach($v['data'] as $vv){ $links[] = '<a href="'.$vv['link'].'">'.$vv['title'].'</a>'; } ?>
                <?= join(', ', $links); ?>
            </div>
        <? endforeach; ?>
    </div>

<? $showAll = false; if( ! empty($regs) || ! empty($crumb)): ?>
    <div class="sr-bottomLocations hidden-phone">
        <? if( ! empty($crumb)): ?>
            <ul class="sr-bottomLocations-path">
                <? foreach($crumb as $v): ?>
                    <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
                <? endforeach; ?>
            </ul>
        <? endif; ?>
        <? if( ! empty($regs)):
            $cols = array(); $i = 0;
            foreach($regs as $v){
                $hide = '';
                if(isset($cols[$i]) && count($cols[$i]) >= 5){
                    $hide =  ' class="hide"';
                    $showAll = true;
                }
                $cols[$i][] = '<li'.$hide.'><a href="'.$v['link'].'"><span>'.$v['title'].'</span></a>&nbsp;('.$v['items'].')</li> ';
                $i++; if($i >= 5){ $i = 0; }
            } ?>
            <div class="sr-bottomLocations-list" id="j-bottomLocations">
                <? foreach($cols as $v): ?>
                    <ul class="sr-bottomLocations-list-col">
                        <?= join("\n", $v) ?>
                    </ul>
                <? endforeach; ?>
            </div>
            <? if($showAll): ?><a href="javascript:void(0);" class="ajax pseudo-link-ajax" id="j-bottomLocations-show"><?= _t('bbs', 'Все...'); ?></a><? endif; ?>
        <? endif; ?>
    </div>
<? endif; ?>

<? if($showAll): ?>
    <script type="text/javascript">
        <? js::start(); ?>
        $(function(){
            $('#j-bottomLocations-show').on('click',function(e){
                e.preventDefault();
                $('#j-bottomLocations ul li').removeClass('hide');
                $(this).addClass('hide');
            });
        });
        <? js::stop(); ?>
    </script>
<? endif; ?>