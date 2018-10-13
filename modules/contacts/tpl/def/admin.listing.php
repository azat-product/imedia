<?php 

tplAdmin::adminPageSettings(array(
    'fordev' => array(
        'counters-refresh' => array('title'=>_t('contacts', 'пересчет счетчиков'), 'href'=>$this->adminLink('ajax&act=counters-refresh'), 'icon'=>'icon-refresh'),
    ),
));
?>
<div class="tabsBar pills">
    <? foreach($ctypes as $k=>$v) { ?>
        <span class="tab <? if($ctype==$k) { ?>tab-active<? } ?>"><a href="javascript:void(0);" onclick="return jContacts.onContactType(<?= $k ?>, this);"><?= $v['title'] ?><? if($v['cnt'] > 0){ ?> (<?= $v['cnt'] ?>)<? } ?></a></span>
    <? } ?>
    <div class="right"><div class="progress" style="display:none;" id="progress-contacts"></div></div>
    <div class="clear"></div>
</div>

<form action="" method="get" name="filters" id="contacts-filters" onsubmit="return false;" class="form-inline">
    <input type="hidden" name="page" value="<?= $page ?>" />
    <input type="hidden" name="ctype" value="<?= $ctype ?>" />
</form>

<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <th width="40">ID</th>
        <th class="left" style="padding-left: 18px;"><?= _t('contacts', 'Сообщение') ?></th>
        <th width="125"><?= _t('', 'Created') ?></th>
        <th width="110"><?= _t('', 'Action') ?></th>
    </tr>
</thead>
<tbody id="contacts-list">
    <?= $list; ?>
</tbody>
</table>
<div id="contacts-pgn"><?= $pgn; ?></div>

<script type="text/javascript">
var jContacts = (function()
{
    var $progress, $list, $listPgn, filters;
    var url = '<?= $this->adminLink('listing'); ?>';
    var urlAction = '<?= $this->adminLink('ajax&act='); ?>';
    var ctype = intval(<?= $ctype ?>);
    var _processing = false; 
    
    $(function(){
        $progress = $('#progress-contacts');
        $list     = $('#contacts-list');
        $listPgn  = $('#contacts-pgn');
        filters   = $('#contacts-filters').get(0);

        $list.on('click', 'a.contact-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del( id, this );
            return false;
        });
              
    });
    
    function isProcessing()
    {
        return _processing;
    }
    
    function del(id, link)
    {
        bff.ajaxDelete('<?= _t('contacts', 'Удалить сообщение?') ?>', id, urlAction+'delete&id='+id,
            link, {progress: $progress, repaint: false});
        return false;
    } 

    function updateList()
    {
        if(isProcessing()) return;
        var f = $(filters).serialize();
        bff.ajax(url, f, function(data){
            if(data) {
                $list.html(data.list);
                $listPgn.html(data.pgn);
                if(bff.h) {
                    window.history.pushState({}, document.title, url + '&' + f);
                }
            }
        }, function(p){
            _processing = p; $progress.toggle(); $list.toggleClass('disabled');
        });
    }
    
    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
        submit: function(resetForm){
            if(isProcessing()) return false;
            setPage(1);
            updateList();
        },
        page: function(id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
        },
        onContactType: function(ctypeNew, link)
        {
            if(isProcessing() || ctype == ctypeNew) return false;
            filters.ctype.value = ctype = ctypeNew;
            setPage(1);
            updateList();
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        },
        view: function(id, link)
        {
            if(id) {
                $.fancybox('', {ajax:true, href:urlAction+'view&id='+id});
            }
            return false;
        }
    };
}());
</script>