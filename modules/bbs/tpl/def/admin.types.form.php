<?= $this->locale->buildForm($aData, 'bbs-cattype','
<tr class="required">
    <td class="row1" style="width:70px;"><span class="field-title">'._t('bbs', 'Название').'</span>:</td>
    <td class="row2"><input class="stretch lang-field" type="text" name="title[<?= $key ?>]" id="bbs-cattype-title-<?= $key ?>" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" /></td>
</tr>', array('popup'=>true));