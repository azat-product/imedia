<?php
    tpl::includeJS('comments', true);
    $bCommonListing = (bff::$event == 'claims');
?>
<div class="comments" id="j-shop-claims">
    <?
    foreach($claims as $v)
    {
        $claimID = $v['id'];
        ?>
        <div class="comment" id="claim_id_<?= $claimID ?>">
            <a name="claim<?= $claimID ?>" ></a>
            <div id="claim_content_id_<?= $claimID ?>" class="ccontent">
                <div class="tb"><div class="tl"><div class="tr"></div></div></div>
                <div class="ctext">
                    <div class="claim-view"><?= nl2br($v['message']) ?></div>
                </div>
                <div class="bl"><div class="bb"><div class="br"></div></div></div>
            </div>
            <div class="info" style="margin:0px;">
                <ul>
                    <li><p><? if($v['user_id']){ ?><a href="#" onclick="return bff.userinfo(<?= $v['user_id'] ?>);" class="userlink author<? if($v['ublocked'] || $v['udeleted']){ ?> blocked<? } ?>"><?= ( ! empty($v['name']) ? $v['name'] : $v['login'] ) ?></a><? } else { ?><span class="desc"><?= $v['user_ip'] ?></span><? } ?></p></li>
                    <li class="date"><?= tpl::date_format2($v['created'], true); ?></li>
                    <? if(!$v['viewed']){ ?><li><a href="#" class="ajax text-success" onclick="jShopsClaims.viewed(<?= $claimID ?>, this); return false;"><?= _t('shops', 'Обработана'); ?></a></li><? } ?>
                    <li><a href="#" class="text-error delete ajax" onclick="jShopsClaims.del(<?= $claimID ?>); return false;"><?= _t('', 'Delete'); ?></a></li>
                    <? if($bCommonListing){ ?><li><a href="#" class="ajax" onclick="return bff.shopInfo(<?= $v['shop_id'] ?>);"><?= _t('shops', 'магазин #'); ?><?= $v['shop_id'] ?></a></li><? } ?>
                </ul>
            </div>
        </div>
    <?
    }
    if(empty($claims))
    { ?>
     <div class="alignCenter valignMiddle" style="height:30px; padding-top:15px;">
        <span class="desc"><?= _t('shops', 'нет жалоб'); ?></span>
     </div>
    <? } ?>
</div>
<script type="text/javascript">
var jShopsClaims = (function() {
    var $filter, $progress, processing = false, $counter, count;
    var ajax_url = '<?= $this->adminLink('claims&act='); ?>';

    $(function(){
        $filter = $('#j-shops-claims-filter');
        $progress = $('<?= ($bCommonListing ? '#shops-claims-progress' : '#form-progress' ) ?>');
        $counter = $('#shops-claims-counter > span');
        count = intval($counter.html());

        if($filter && $filter.length) {
            $filter.find('.j-perpage').on('change', function(){
                $filter.submit();
            });
            $('#j-shops-claims-status-tabs').on('click', '.j-tab', function(e){ nothing(e);
                $filter.find('.j-status-id').val( $(this).data('id') );
                $filter.submit();
            });
        }
    });

    return {
        del: function(id)
        {
            if( ! bff.confirm('sure')) return;
            if(processing) return;
            processing = true;
            bff.ajax(ajax_url+'delete', {claim_id: id}, function(data) {
                if(data) {
                    $('#claim_id_'+id).slideUp();
                    if(data.counter_update) {
                        $counter.html( (count>0 ? count-1 : '') );
                    }
                }
                processing = false;
            }, $progress);
        },
        viewed: function(id, link)
        {
            if(processing) return;
            processing = true;
            bff.ajax(ajax_url+'viewed', {claim_id: id}, function(data) {
                if(data) {
                    $(link).remove();
                    $counter.html( (count>0 ? count-1 : '') );
                }
                processing = false;
            }, $progress);
        }
    };
}());
</script>