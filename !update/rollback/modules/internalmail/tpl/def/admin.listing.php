<?php
    tpl::includeJS(array('autocomplete'), true);
    $sUrl = $this->adminLink(bff::$event.'&f=');
    $bFolders = InternalMail::foldersEnabled();
?>

<?= tplAdmin::blockStart(_t('internalmail', 'Написать сообщение'), false, array('id'=>'imNewMessage','style'=>'display:none;')) ?>
<form action="" method="post" name="nmForm" enctype="multipart/form-data">
    <input type="hidden" name="f" value="<?= $f ?>" />
    <input type="hidden" name="act" value="send" />
    <input type="hidden" name="recipient" id="nmRecipient" value="0" />
    <table class="admtbl tbledit">
        <tr>
            <td colspan="2">
                <input type="text" value="" name="recipient_login" id="nmRecipientAutocomplete" class="autocomplete stretch" style="margin-bottom: 5px;" />
                <textarea name="message" onkeyup="checkTextLength(4096, this.value, $('#warn-message').get(0));" rows="5" placeholder="<?= _te('internalmail', 'Текст сообщения...'); ?>" style="resize: vertical;"></textarea>
                <span id="warn-message" class="clr-error" style="display:none;"></span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="left">
                    <input type="button" class="btn btn-success btn-small button submit" value="<?= _te('internalmail', 'Отправить сообщение'); ?>" onclick="imOnSendMessage();" />
                    <input type="button" class="btn button btn-small cancel" value="<?= _te('', 'Cancel') ?>" onclick="$('#imNewMessage').hide();" />
                </div>
                <? if( InternalMail::attachmentsEnabled() ){ ?>
                <div class="right"><div class="upload-res" style="margin-left: 15px;" id="im_attach_cur"></div></div>
                <div class="right">
                    <div class="form-upload">
                        <div class="upload-file">
                            <table>
                                <tbody class="desc">
                                    <tr><td>
                                            <div class="upload-btn">
                                                <span class="upload-mask">
                                                    <input type="file" onchange="bff.input.file(this, 'im_attach_cur');" name="attach" id="im_attach" />
                                                </span>
                                                <a class="ajax"><?= _t('internalmail', 'приложить файл'); ?></a>
                                            </div>
                                        </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <? } ?>
                <div class="clear-all"></div>
            </td>
        </tr>
    </table>
</form>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart(_t('internalmail', 'Сообщения / Личные сообщения'), true) ?>
<div class="tabsBar">
    <? if($bFolders) { ?>
        <span class="tab<? if($f==InternalMail::FOLDER_ALL){ ?> tab-active<? } ?>" onclick="bff.redirect('<?= $this->adminLink(bff::$event); ?>');"><?= _t('', 'Все'); ?></span>
        <? foreach($folders as $folderID=>$ff) { ?>
            <span class="tab<? if($f==$folderID){ ?> tab-active<? } ?>" onclick="bff.redirect('<?= $this->adminLink(bff::$event.'&f='.$folderID); ?>');"><i class="<?= $ff['icon-admin'] ?> disabled"></i>&nbsp;<?= $ff['title'] ?></span>
        <? } ?>
        <div class="right">
            <span id="progress-im" style="margin-right:10px; display:none;" class="progress"></span>
            <a href="#" id="imNewMessageLink" class="ajax"><?= _t('internalmail', 'написать сообщение'); ?></a>
        </div>
    <? } else { ?>
        <a href="#" id="imNewMessageLink" class="ajax"><?= _t('internalmail', 'написать сообщение'); ?></a>
        <span id="progress-im" style="margin-left:10px; display:none;" class="progress"></span>
    <? } ?>
</div>

<table class="table table-hover table-condensed table-striped admtbl tblhover">
<thead>
    <tr class="header">
        <th class="left"><?= _t('internalmail', 'Собеседник'); ?></th>
        <th width="200"></th>
    </tr>
</thead>
<?
$sConversationURL = $this->adminLink('conv&i=');
foreach($contacts as $v)
{
    $userID = $v['user_id'];
    $shopID = $v['shop_id'];
    $url = $sConversationURL.$userID.'&shop='.$shopID;
?>
<tr class="row1">
    <td class="left">
        <a href="<?= $url ?>"><img src="<?= $v['avatar'] ?>" class="left img-polaroid" style="margin-right: 8px;" width="50" alt="" /></a>
        <div style="margin-bottom: 3px;">
            <a href="#" class="ajax" onclick="return bff.userinfo(<?= $userID ?>);"><?= ( ! empty($v['name']) ? $v['name'] : $v['login'] ) ?></a>
            <? if($shopID) { ?><i class="icon-shopping-cart" title="<?= _te('internalmail', 'Сообщение для магазина'); ?>"></i><? } ?>
            <? if( ! $v['activated'] ) { ?>&nbsp;<span class="disabled">[<?= _t('internalmail', 'неактивирован'); ?>]</span><? } ?>
        </div>
        <? if($v['msgs_new']){ ?>
            <a href="<?= $url ?>" class="label label-success"><?= tpl::declension($v['msgs_new'], _t('internalmail', 'новое сообщение;новых сообщения;новых сообщений')) ?></a>
        <? } else { ?>
            <a href="<?= $url ?>" class="label"><?= tpl::declension($v['msgs_total'], _t('internalmail', 'сообщение;сообщения;сообщений')) ?></a>
        <? } ?>
        <br />
        <span class="small desc">
            <? if($v['lastmsg']['author'] == $userID) {
                if( !$v['lastmsg']['is_new'] ) echo _t('internalmail', 'Вы предпочли промолчать');
            } else {
                if( $v['lastmsg']['is_new'] ) echo _t('internalmail', 'Ваше сообщение еще не прочитано');
                else echo _t('internalmail', 'Ваше сообщение прочитано [date]', array('date' => tpl::date_format2($v['lastmsg']['readed'], true)));
            } ?>
        </span>
    </td>
    <td class="right desc" style="padding-right: 20px; padding-top: 5px; vertical-align: top;">
        <div class="small"><?= tpl::date_format2($v['lastmsg']['created'], true, true, ' ', ', ') ?></div>
        <? if($bFolders): ?>
            <div class="im-folders-actions">
                <? foreach($folders as $folderID=>$ff) {
                    if( $v['admin'] && $ff['notforadmin'] ) continue;
                    ?><a title="<?= $ff['title'] ?>" data-user-id="<?= $userID ?>" data-shop-id="<?= $shopID ?>" data-folder-id="<?= $folderID ?>" class="f-action f-action-<?= $ff['class'] ?><? if(in_array($folderID,$v['folders'])){ ?> active<? } ?>" href="#"><i class="<?= $ff['icon-admin'] ?> icon-white"></i></a> <?
                } ?>
            </div>
            <div class="clearfix"></div>
        <? endif; ?>
    </td>
</tr>
<? } if(empty($contacts)) { ?>
<tr class="norecords">
    <td colspan="3"><?= _t('', 'Nothing found') ?></td>
</tr>
<? } ?>
</table>

<div><?= $pgn ?></div>

<?= tplAdmin::blockStop() ?>

<script type="text/javascript">
//<![CDATA[
$(function(){
    var ajaxUrl = '<?= $this->adminLink('ajax&act='); ?>';

    // new message
    var imNewMessageInited = false;
    $('#imNewMessageLink').click(function(e){ nothing(e);
        var $block = $('#imNewMessage');
        var $recipient = $block.find('#nmRecipientAutocomplete');
        if( ! imNewMessageInited) {
            imNewMessageInited = true;
            $block.show();
            $recipient.autocomplete(ajaxUrl+'recipients',
                        {valueInput: '#nmRecipient', width: false, placeholder: '<?= HTML::escape(_t('internalmail', 'Укажите e-mail получателя'), 'js'); ?>'});
        } else {
            $block.toggle();
        }
        $recipient.focus();
    });

    // folders actions
    $('.f-action').on('click', function(e){ nothing(e);
        var $btn = $(this);
        var userID = intval($btn.data('user-id'));
        var shopID = intval($btn.data('shop-id'));
        var folderID = intval($btn.data('folder-id'));
        if( ! userID || ! folderID ) return;
        bff.ajax(ajaxUrl+'move2folder', {iid: userID, shop: shopID, fid: folderID}, function(data){
            if(data && data.success) {
                $btn.toggleClass('active', (data.added==1));
            }
        });
    });

});

var imSendingProgress = false;
function imOnSendMessage()
{
    if(imSendingProgress) return;
    var form = document.forms.nmForm;
    if(form['recipient'].value==0 || form['recipient'].value==''){ form['recipient_login'].focus(); return; }
    if(form['message'].value.trim() == ''){ form['message'].focus(); return; }
    imSendingProgress = true;
    form.submit();
}
//]]>
</script>