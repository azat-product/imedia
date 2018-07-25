<?php

    $nMaxThSize = 0;
    foreach($sizes as $v) {
        $nMaxThSize = max($nMaxThSize, $v[0]);
    }
    $nPopupWidth = 345 + $nMaxThSize + 10;
?>

<div id="popupCropImage" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title"><?= _t('site', 'Редактирование изображения') ?></div>
        <div class="ipopup-content" style="width:<?= $nPopupWidth ?>px;">

            <form action="" method="post">
                <input type="hidden" name="x" value="0" />
                <input type="hidden" name="y" value="0" />
                <input type="hidden" name="w" value="0" />
                <input type="hidden" name="h" value="0" />
                <input type="hidden" name="crop" value="0,0,0,0" />
                <input type="hidden" name="filename" value="<?= $filename; ?>" />
                <table class="tbledit">
                    <tr>
                        <td colspan="2">
                            <br />
                            <div style="width: 345px;" class="left">
                                <img src="/img/admin/empty.gif" class="upload-crop-area" />
                            </div>
                            <div class="right">
                                <? foreach($sizes as $v) { ?>
                                <div class="img" style="width:<?= $v[0]; ?>px; height:<?= $v[1]; ?>px; overflow:hidden;"><img src="/img/admin/empty.gif" class="crop-preview" /></div>
                                <br />
                                <? } ?>
                            </div>
                            <div class="clear"></div>
                        </td>
                    </tr>
                    <tr class="footer">
                        <td colspan="2" style="text-align: center; padding-top: 15px;">
                            <input type="submit" class="btn btn-success button submit" value="<?= _te('', 'Save') ?>" />
                            <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="$.fancybox.close();" />
                        </td>
                    </tr>
                </table>
            </form>
        
        </div>
    </div>
</div> 