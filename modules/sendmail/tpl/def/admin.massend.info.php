<?php

?>
<div id="popupMassend" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title"><?= _t('sendmail', 'Информация о рассылке'); ?></div>
        <div class="ipopup-content" style="width:643px;">

                <table class="admtbl tbledit">
                    <tr>
                        <td class="row1 field-title right" width="150"><?= _t('', 'От:'); ?></td>
                        <td class="row2" style="padding-left:20px;">
                            <? if(! empty($fromname)): ?>
                                <?= $fromname ?> &lt;<?= $from ?>&gt;
                            <? else: ?>
                                <?= $from ?>
                            <? endif; ?>
                        </td>
                    </tr>            
                    <tr>
                        <td class="row1 field-title right"></td>
                        <td class="row2" style="padding-left:20px;"><?= $subject ?></td>
                    </tr>  
                    <tr>
                        <td class="row1 field-title right" style="vertical-align:top;"><?= _t('sendmail', 'Сообщение:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><?= $body ?></td>
                    </tr>                  
                    <tr>
                        <td class="row1 field-title right"><?= _t('sendmail', 'Всего получателей:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><?= $total ?></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right"><?= _t('sendmail', 'Получатели:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><?= empty($shop_only) ? _t('', 'Все') : _t('sendmail', 'Только для пользователей магазинов') ?></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right"><?= _t('sendmail', 'Отправлено:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><span class="clr-success"><?= $success ?></span><span class="desc"> / </span><span class="clr-error"><?= $fail ?></span></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right"><?= _t('sendmail', 'Начало рассылки:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><?= tpl::date_format3($started, 'd.m.Y H:i') ?></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right"><?= _t('sendmail', 'Окончание рассылки:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><strong><? if($status == Sendmail::STATUS_FINISHED){ echo tpl::date_format3($finished, 'd.m.Y H:i'); } else{ ?><?= _t('sendmail', 'незавершена'); ?><? } ?></strong></td>
                    </tr> 
                    <? if (isset($time_total) && isset($time_avg)): ?>
                    <tr>
                        <td class="row1 field-title right"><?= _t('sendmail', 'Время отправки:'); ?></td>
                        <td class="row2" style="padding-left:20px;"><?= _t('sendmail', '[s] сек.', array('s' => $time_total)); ?> <span class="desc">(<?= _t('sendmail', '[s] сек. - среднее', array('s' => $time_avg)); ?>)</span></td>
                    </tr>
                    <? endif; ?>
                </table>

        </div>
    </div>
</div>
