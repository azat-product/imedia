<?php ?>
<script type="text/javascript">
//<![CDATA[
    function doChangePass( submitForm )
    {
        var $changePass = $('#changepass');
        var change = $changePass.val();
        $changePass.val( change==1?0:1 );
        $('#changepassdiv').slideToggle('fast', function(){ if(change==0) $('#password0').focus(); if(submitForm) document.forms.adminProfileForm.submit(); } );
    }
    
    function onSubmit()
    {
        if($('#changepass').val()==1) {
            if($('#password0').val() == '' || $('#password1').val() == '' || $('#password2').val() == '') {
                doChangePass(true);
                return false;
            }
        }
        return true;
    }
//]]> 
</script>

<form action="" method="post" name="adminProfileForm">
<input type="hidden" name="changepass" id="changepass" value="0" />
<table class="admtbl tbledit">
<tr>
	<td class="row1" width="65" rowspan="2">
        <img src="<?= UsersAvatar::url($user_id, $avatar, UsersAvatar::szNormal); ?>" width="50" class="img-polaroid" alt="" />
    </td>
	<td class="row2">
        <? if($changelogin){ ?><input type="text" name="login" value="<?= $login ?>" />
        <? } else { ?>
            <strong><?= $name ?></strong>
        <? } ?>
        <hr style="margin:10px 0; width:210px;" />
        <? foreach($groups as $key=>$g) { ?>
            <span style="color:<?= (!empty($g['color']) ? $g['color'] : '#000') ?>; font-weight:bold; text-decoration:none;"><?= $g['title'] ?></span><? if($key+1!=sizeof($groups)){ ?>, <? } ?>
        <? } ?>
    </td>
</tr>
<tr>
    <td colspan="2"></td>
</tr>
<tr>
	<td class="row1" colspan="2">
        <div id="changepassdiv" style="display:none; text-align:left; margin:-4px 0 0 -4px;">
            <table style="width:100%;">
                <tr>
                    <td class="row1 field-title" width="65"><?= _t('users', 'Текущий:'); ?></td>
                    <td class="row2"><input type="password" name="password0" id="password0" autocomplete="off" /></td>
                </tr>
                <tr>
                    <td class="row1 field-title"><?= _t('users', 'Новый:'); ?></td>
                    <td class="row2"><input type="password" name="password1" id="password1" /></td>
                </tr>
                <tr>
                    <td class="row1 field-title"><?= _t('users', 'Еще раз:'); ?></td>
                    <td class="row2"><input type="password" name="password2" id="password2" /></td>
                </tr>
            </table>
        </div>
	</td>
</tr>
<? bff::hook('users.admin.profile.form', array('data'=>&$aData)); ?>
<tr class="footer">
	<td colspan="3">
        <input type="submit" class="btn btn-success button submit" value="<?= _te('', 'Save') ?>" onclick="return onSubmit();" />
        <input type="button" class="btn button submit" value="<?= _te('users', 'Изменить пароль'); ?>" onclick="doChangePass(false);" />
        <? if(FORDEV){ ?>
        <input type="button" class="btn button submit" value="<?= _te('','Edit') ?>" onclick="bff.redirect('<?= $this->adminLink('user_edit&rec='.$user_id.'&tuid='.$tuid) ?>');" />
        <? } ?>
    </td>
</tr>
</table>
</form>