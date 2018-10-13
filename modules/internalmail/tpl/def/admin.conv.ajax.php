<?php

$attach = $this->attach();

foreach($list as $v){ ?>
<tr>
    <td width="20" style="vertical-align:top;"><div class="im-conv-tri-<?= (!$v['my']?'from':'to') ?>"></div></td>
    <td style="padding-right: 20px; vertical-align:top;" class="<? if(!$v['my']){ ?>from<? } ?><?= $v['blocked']? ' disabled':'' ?>">
        <strong><?= ($v['my'] ? $name : $i['name']) ?></strong><span class="desc small"> <?= tpl::date_format2($v['created'],true); ?>:</span>
        <a class="j-message-block ajax small desc <?= $v['blocked']? 'j-is-blocked':'' ?>" href="javascript:void(0);" data-id="<?= $v['id'] ?>"><?= HTML::escape( (!$v['blocked'] ? _t('internalmail', 'заблокировать') : _t('internalmail', 'разблокировать')), 'js'); ?></a>
        <br />
        <?= $v['message'] ?>
        <? if(InternalMail::attachmentsEnabled() && ! empty($v['attach'])) {
            echo '<br />'.$attach->getAttachLink($v['attach']);
        } ?>
    </td>
</tr>
<? }

if( empty($list) ) { ?>
<tr class="norecords">
    <td colspan="2"><?= _t('internalmail', 'не найдено ни одного сообщения'); ?></td>
</tr>
<? }