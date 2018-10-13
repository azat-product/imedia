<?php
    $enotify = Users::i()->getEnotifyTypes($enotify); # уведомления
    $aData = HTML::escape($aData, 'html', array('login', 'name', 'email', 'phone_number', 'surname', 'site', 'addr_addr'));
    $edit = $user_id > 0;
    $bNoShop = ( ! $shop_id );
    $limitsPayed = BBS::limitsPayedEnabled();
    $permissionDelete = $edit && ! empty($permissionDelete) && ! $superadmin;
?>

<? if($edit) {
    $aData['popup'] = false;
    echo $this->viewPHP($aData, 'admin.user.status');
    echo $this->viewPHP($aData, 'admin.user.comment');
} ?>
<? if( ! empty($unfake_url)): ?>
    <div class="alert alert-block" style="margin-bottom: 5px; padding: 10px;">
        <?= _t('users', 'Сгенерированный пользователь'); ?>
        <a href="<?= $unfake_url ?>" class="btn pull-right btn-mini btn-warning" onclick="return bff.confirm('sure');"><?= _t('users', 'конвертировать'); ?></a>
    </div>
<? endif; ?>

<div class="tabsBar">
    <script type="text/javascript">
        function jUserTab(key, link)
        {
            $('.tab-form').hide(); 
            $('#tab-'+key).show();
            if(key == 'shop' && typeof jShopInfo != 'undefined') {
                jShopInfo.onShow();
            }
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        }
    </script>
    <span class="tab tab-active"><a href="javascript:void(0);" onclick="return jUserTab('profile', this);"><?= _t('users', 'Профиль'); ?></a></span>
    <? if($edit) { ?>
    <? if($shops_on) { ?><span class="tab"><a href="javascript:void(0);" <? if($bNoShop){ ?> class="disabled" <? } ?> id="shop-form-tab" onclick="return jUserTab('shop', this);"><?= _t('shops', 'Магазин'); ?></a></span><? } ?>
    <? if($limitsPayed) { ?><span class="tab"><a href="javascript:void(0);" onclick="return jUserTab('limits', this);"><?= _t('bbs', 'Лимиты'); ?></a></span><? } ?>
    <? bff::hook('users.admin.user.form.tabs.extra', array('edit'=>$edit,'data'=>&$aData)) ?>
    <span class="tab"><a href="<?= $this->adminLink('listing&uid='.$user_id, 'bills'); ?>"><?= _t('users', 'Баланс'); ?> <span class="desc">(<?= $balance ?>)</span></a></span>
    <div class="right">
        <div style="margin:0 0 0 10px; float: right;<? if( ! $activated) { ?> display: none;<? } ?>" class="left u_block_links">
            <a href="javascript:void(0);" onclick="return jUserStatus.unblock(this);" class="u_unblock_lnk ajax clr-success <? if(!$blocked){ ?>hidden<? } ?>"><?= _t('', 'разблокировать'); ?></a>
            <a href="javascript:void(0);" onclick="return jUserStatus.block(this);" class="u_block_lnk ajax clr-error <? if($blocked){ ?>hidden<? } ?>"><?= _t('', 'заблокировать'); ?></a>
        </div>
        <div style="margin:0 0 0 10px; float: right;<? if (mb_strlen(trim($admin_comment)) > 0) { ?>display:none;<? } ?>" id="userAdminCommentBlockToggler">
            <a href="javascript:void(0);" onclick="$('#userAdminCommentBlock .j-edit').show(); return false;" class="ajax desc"><?= _t('', 'добавить заметку'); ?></a>
        </div>
    </div>
    <? } ?>
    <div class="clear"></div>
</div>

<form action="" name="modifyUserForm" id="modifyUserForm" method="post" enctype="multipart/form-data">
<input type="hidden" name="shop_id" value="<?= $shop_id ?>" />
<table class="admtbl tbledit relative">
<tbody id="tab-profile" class="tab-form relative">
<tr>
    <td class="row1 field-title" style="width:150px;"><?= _t('users', 'ФИО:'); ?></td>
    <td class="row2">
        <input maxlength="50" type="text" name="name" id="user_name" value="<?= $name ?>" />
        <? if($edit && !empty($social)) { ?>
        <div style="display: inline-block; vertical-align: middle; margin: 2px 0 0 5px;">
            <? foreach ($social as $v):
                if (empty($v['profile_url'])) continue;
                ?><a href="<?= HTML::escape($v['profile_url']) ?>" class="social <?= $v['provider_key'] ?>" target="_blank" rel="noreferrer noopener"></a>&nbsp;<?
               endforeach; ?>
        </div>
        <? } ?>
        <div style="position: absolute; right: 5px; top: 5px; text-align: center;">
            <div style="margin-bottom: 5px;">
                <img id="avatar" src="<?= UsersAvatar::url($user_id, $avatar, UsersAvatar::szNormal); ?>" class="img-polaroid" alt="" />
            </div>
            <input type="hidden" name="avatar_del" id="avatar_delete_flag" value="0" />
            <? if($avatar && $edit) {?><a href="javascript:void(0);" id="avatar_delete_link" title="<?= _te('users', 'удалить текущий аватар'); ?>" class="desc ajax" onclick="jUser.deleteAvatar('<?= UsersAvatar::url($user_id, '', UsersAvatar::szNormal) ?>');"><?= _t('', 'удалить'); ?></a><? } ?>
        </div>
    </td>
</tr>
<tr style="display: none;">
    <td class="row1 field-title"><?= _t('users', 'Фамилия:'); ?></td>
    <td class="row2">
        <input maxlength="35" type="text" name="surname" id="user_surname" value="<?= $surname ?>" />
    </td>
</tr>
<? if(Users::registerPhone()){ ?>
<tr class="required">
	<td class="row1 field-title"><?= _t('users', 'Телефон'); ?><span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="text" name="phone_number" maxlength="50" pattern="[0-9+]*" value="<?= (!empty($phone_number) ? '+'.$phone_number : '') ?>" <? if(empty($phone_number)) { ?> placeholder="Не указан" <? } ?> autocomplete="off" />
    </td>
</tr>
<? } ?>
<tr class="required check-email">
	<td class="row1 field-title"><?= _t('', 'E-mail'); ?><span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="text" id="email" name="email" maxlength="100" value="<?= $email ?>" autocomplete="off" />
    </td>
</tr>
<tr class="required">
    <td class="row1 field-title"><?= _t('users', 'Логин'); ?><span class="required-mark">*</span>:</td>
    <td class="row2">
        <input maxlength="35" type="text" name="login" id="user_login" value="<?= $login ?>" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="left field-title"><?= _t('users', 'Аватар'); ?></span>:</td>
    <td class="row2"><input type="file" name="avatar" size="17" /></td>
</tr> 
<? if(!$edit){ ?>
<tr class="required check-password">
	<td class="row1 field-title"><?= _t('users', 'Пароль'); ?><span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="password" id="password" name="password" autocomplete="off" value="" maxlength="100" />
    </td>
</tr> 
<tr class="required check-password">
	<td class="row1 field-title"><?= _t('users', 'Подтверждение пароля'); ?><span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="password" id="password2" name="password2" class="check-password2" maxlength="100" autocomplete="off" value="" />
    </td>
</tr>
<? } else { ?>
<tr class="required check-password">
    <td class="row1">
        <span class="field-title"><?= _t('users', 'Пароль'); ?><span class="required-mark">*</span></span>:
        <input type="hidden" name="changepass" id="changepass" value="0" />
    </td>
    <td class="row2">
        <div id="passwordCurrent" style="height:17px; padding-top:5px;">
            <a href="javascript:void(0);" class="ajax" onclick="jUser.doChangePassword(1); return false;"><?= _t('users', 'изменить пароль'); ?></a>
        </div>
        <div id="passwordChange" style="display:none; height:22px;">
            <input type="text" id="password" name="password" value="" maxlength="100" />
            &nbsp;&nbsp;<a href="javascript:void(0);" class="ajax desc" onclick="jUser.doChangePassword(0); return false;"><?= _t('', 'отмена'); ?></a>
        </div>
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('users', 'Регистрация:'); ?></td>
    <td class="row2"><?= tpl::date_format2($created, true) ?>, <a class="desc" href="<?= $this->adminLink('ban') ?>"><?= long2ip($created_ip) ?></a></td>
</tr> 
<tr>
    <td class="row1 field-title"><?= _t('users', 'Авторизация:'); ?></td>
    <td class="row2">
        <?= tpl::date_format2($last_login,true); ?><span class="desc"> - <?= _t('users', 'последняя'); ?>, <a class="bold desc" href="<?= $this->adminLink('ban') ?>"><?= long2ip($last_login_ip); ?></a></span>
        <? if($last_login2){ ?><br /><?= tpl::date_format2($last_login2,true); ?><span class="desc"> - <?= _t('users', 'предпоследняя'); ?></span><? } ?>
    </td>
</tr>
<? } ?>
<tr>
    <td class="row1 field-title"><?= _t('', 'Город:'); ?></td>
    <td class="row2">
        <?= Geo::i()->citySelect($region_id, true, 'region_id', array(
            'form' => 'users-settings'
        )); ?>
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('', 'Точный адрес:'); ?></td>
    <td class="row2">
        <input type="text" name="addr_addr" value="<?= $addr_addr ?>" style="width: 300px;" maxlength="400" />
    </td>
</tr>
<?
if($this->profileBirthdate)
{
    $aData['birthdate'] = $this->getBirthdateOptions($aData['birthdate']); # дата рождения
?>
<tr>
    <td class="row1 field-title"><?= _t('users', 'Дата рождения:'); ?></td>
    <td class="row2">
        <select name="birthdate[day]" style="width:45px;"><?= $birthdate['days'] ?></select>
        <select name="birthdate[month]" style="width:90px;"><?= $birthdate['months'] ?></select>
        <select name="birthdate[year]" style="width:57px;"><?= $birthdate['years'] ?></select>
    </td>
</tr>
<? } ?>
<tr<? if( ! $this->profileSex) { ?> style="display: none;"<? } ?>>
    <td class="row1 field-title"><?= _t('users', 'Пол:'); ?></td>
    <td class="row2">
        <?
            $aSex = array(
                Users::SEX_FEMALE => _t('users', 'Женщина'),
                Users::SEX_MALE   => _t('users', 'Мужчина'),
            );
            foreach($aSex as $k=>$v) { ?><label class="radio inline"><input type="radio" name="sex" value="<?= $k ?>" <? if($sex == $k){ ?>checked="checked"<? } ?> /><?= $v ?></label><? } ?>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Телефоны (контактные)'); ?></span>:</td>
    <td class="row2">
        <div id="j-user-phones"></div>
    </td>
</tr>
<?php foreach (Users::contactsFields() as $contact): ?>
<tr>
    <td class="row1 field-title">
        <?= $contact['title'] ?>:
    </td>
    <td class="row2">
        <input type="text" name="contacts[<?= $contact['key'] ?>]" value="<?= isset($contacts[$contact['key']]) ? HTML::escape($contacts[$contact['key']]) : '' ?>" />
    </td>
</tr>
<?php endforeach; ?>
<? bff::hook('users.admin.user.form', array('edit'=>$edit,'data'=>&$aData)) ?>
<tr style="display: none;">
    <td class="row1"><span class="field-title"><?= _t('users', 'Ссылка на сайт:'); ?></span><span class="right desc">http://</span></td>
    <td class="row2">
        <input type="text" class="stretch" name="site" value="<?= $site ?>" />
    </td>
</tr>
<tr style="display: none;">
    <td class="row1 field-title"><?= _t('users', 'О себе:'); ?></td>
    <td class="row2">
        <textarea class="stretch" name="about"><?= HTML::escape($about) ?></textarea>
    </td>
</tr>
<tr>
    <td colspan="2"><hr class="cut" /></td>
</tr>
<? if($admin && bff::moduleExists('internalmail')) { ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Сообщения:'); ?></span></td>
    <td class="row2"><label class="checkbox"><input type="checkbox" name="im_noreply"<? if($im_noreply){ ?> checked="checked"<? } ?> />пользователи не могут отвечать на его сообщения</label></td>
</tr>
<? } ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Уведомления и подписка'); ?></span>:</td>
    <td class="row2">
        <? foreach($enotify as $k=>$v){ ?>
            <label class="checkbox"><input type="checkbox" name="enotify[]" value="<?= $k ?>" <? if($v['a']){ ?>checked="checked"<? } ?> /><?= $v['title'] ?></label>
        <? } ?>
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('users', 'Принадлежность к группе:'); ?></td>
    <td class="row2">
        <table>
            <tr>
                <td width="240">
                    <strong><?= _t('users', 'Группы пользователей:'); ?></strong><br />
                    <select multiple name="exists_values[]" id="exists_values" style="width:230px; height:100px;"><?= $exists_options ?></select>
                </td>
                <td width="35">
                     <div style="width:33px; height:12px;">&nbsp;</div>
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&gt;&gt;" onclick="bff.formSelects.MoveAll('exists_values', 'group_id');" />
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&gt;" onclick="bff.formSelects.MoveSelect('exists_values', 'group_id');" />
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&lt;" onclick="bff.formSelects.MoveSelect('group_id', 'exists_values');" />
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&lt;&lt;" onclick="bff.formSelects.MoveAll('group_id', 'exists_values');" />
                </td>
                <td width="240">
                    <strong><?= _t('users', 'Активные группы:'); ?></strong><br />
                    <select multiple name="group_id[]" id="group_id" style="width:230px; height:100px;"><?= $active_options ?></select>
                </td>
               	<td>&nbsp;</td>
            </tr>
        </table>
    </td>
</tr>
</tbody>

<? if($shops_on) { ?>
<tbody id="tab-shop" style="display: none;" class="tab-form">
<tr><td colspan="2">
<? if($bNoShop) { ?>
    <div class="desc" style="margin: 15px 3px;" id="shop-form-add"><?= _t('users', 'Магазин еще не создан, [a]создать</a>.', array('a' => '<a href="'.$this->adminLink('add&user='.$user_id, 'shops').'" target="_blank">')); ?></div>
<? } else { ?>
    <table id="shop-form" class="admtbl tbledit">
        <?= $shop_form ?>
    </table>
<? } ?>
</td></tr>
</tbody>
<? } ?>

<? if($limitsPayed) { ?>
    <tbody id="tab-limits" style="display: none;" class="tab-form">
    <tr><td colspan="2">
        <?= $limits ?>
    </td></tr>
    </tbody>
<? } ?>

<? bff::hook('users.admin.user.form.tabs.content', array('edit'=>$edit,'data'=>&$aData)) ?>

<tr class="footer">
    <td colspan="2">
        <hr style="margin: 0 0 10px 0;" />
        <div class="left">
            <input type="hidden" name="back" class="j-back" value="0" />
            <div class="btn-group">
                <input type="submit" class="btn btn-success button submit j-submit" value="<?= ($edit ? _t('', 'Save') : _t('', 'Создать')) ?>" data-loading-text="<?= ($edit ? 'Сохранить' : 'Создать') ?>" />
                <? if($edit) { ?>
                    <input type="submit" class="btn btn-success button submit j-submit" value="<?= _te('users', 'и вернуться'); ?>" data-loading-text="<?= _te('users', 'и вернуться'); ?>" onclick="$('#modifyUserForm').find('.j-back').val(1);" />
                <? } ?>
            </div>
            <? if($edit && $session_id && ! $superadmin) { ?>
            <input type="button" class="btn clr-error button" value="<?= _te('users', 'Разлогинить'); ?>" onclick="bff.confirm('sure', {r:'<?= $this->adminLink('user_action&type=logout&rec='.$user_id.'&tuid='.$tuid) ?>'});" />
            <? } ?>
            <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="history.back();" />
        </div>
        <div class="right">
            <? if (!empty($admin_auth_url)): ?>
                <a href="<?= $admin_auth_url ?>" class="btn" target="_blank"><?= _t('users', 'Авторизоваться'); ?></a>
            <? endif; ?>
            <? if($permissionDelete): ?><a href="javascript:" class="btn btn-danger j-user-delete"><?= _t('users', 'Удалить'); ?></a><? endif; ?>
        </div>
        <div class="clear"></div>
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
//<![CDATA[ 
var jUser = (function(){
    var $form;
    $(function(){
        $form = $('#modifyUserForm');
        bff.iframeSubmit($form, function(data){
            if(data && data.success) {
                if (data.hasOwnProperty('redirect')) {
                    bff.redirect(bff.adminLink(data.redirect,'users'));
                } else if(data.reload) {
                    setTimeout(function(){ location.reload(); }, 1000);
                } else if(data.back) {
                    history.back();
                } else {
                    bff.success('<?= _t('', 'Данные успешно сохранены'); ?>');
                }
            }
        },{
            beforeSubmit: function(){
                //check groups
                if( document.getElementById('group_id').options.length == 0 ) {
                    bff.error('<?= _t('users', 'укажите <strong>принадлежность к группе</strong>'); ?>');
                    return false;
                }
                bff.formSelects.SelectAll('group_id');
                return true;
            },
            button: '.j-submit'
        });

        initPhones(<?= $this->profilePhonesLimit ?>, <?= func::php2js($phones) ?>);

        <? if ($permissionDelete && ! empty($tuid)): ?>
        $form.on('click', '.j-user-delete', function(e){
            e.preventDefault();
            if ( ! confirm('<?= _tejs('users', 'Удалить учетную запись пользователя?'); ?>')) return;
            if ( ! confirm('<?= _tejs('users', 'Отмена изменений будет невозможна, продолжить?'); ?>')) return;
            document.location='<?= $this->adminLink('user_action&type=delete&rec='.$user_id.'&tuid='.$tuid) ?>';
        });
        <? endif; ?>
    });

    function initPhones(limit, phones)
    {
        var index  = 0, total = 0;
        var $block = $('#j-user-phones');

        function add(value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            $block.append('<div class="j-phone">\
                                <input type="text" maxlength="40" name="phones['+index+']" value="'+(value?value.replace(/"/g, "&quot;"):'')+'" class="left j-value" placeholder="<?= _te('users', 'Номер телефона'); ?>" />\
                                <div class="left" style="margin: 3px 0 0 4px;">'+(total==1 ? '<a class="ajax desc j-plus" href="javascript:void(0);"><?= _t('users', '+ еще телефон'); ?></a>' : '<a href="javascript:void(0);" class="but cross j-remove"></a>')+'</div>\
                                <div class="clear"></div>\
                            </div>');
        }

        $block.on('click', 'a.j-plus', function(e){ nothing(e);
            add('');
        });

        $block.on('click', 'a.j-remove', function(e){ nothing(e);
            var $ph = $(this).closest('.j-phone');
            if( $ph.find('.j-value').val() != '' ) {
                if(confirm('<?= _t('users', 'Удалить телефон?'); ?>')) {
                    $ph.remove(); total--;
                }
            } else {
                $ph.remove(); total--;
            }
        });

        phones = phones || {};
        for(var i in phones) {
            if( phones.hasOwnProperty(i) ) {
                add(phones[i].v);
            }
        }
        if( ! total && limit > 0 ) {
            add('');
        }
    }

    return {
        deleteAvatar: function(defaultAvatar)
        {
            if(confirm('<?= _t('users', 'Удалить текущий аватар?'); ?>')) {
                $('#avatar').attr('src', defaultAvatar);
                $('#avatar_delete_flag').val(1);
                $('#avatar_delete_link').remove();
            }
            return false;
        },
        doChangePassword: function(change)
        {
            $('#passwordCurrent, #passwordChange').toggle();
            $('#changepass').val( change );

            if(change)
                $('#password').focus();
                
            return false;
        }
    };
}());
//]]>
</script>