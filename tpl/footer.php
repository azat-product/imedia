<?php
/**
 * Футер сайта
 */
    $aFooterMenu = Sitemap::view('footer');
    $footerLink = function ($item, $extraClass = '') {
        if (!empty($item['a'])) {
            return '<li>'.$item['title'].'</li>';
        }
        return '<li><a href="'.$item['link'].'"'.($item['target'] === '_blank' ? ' target="_blank"' : '').' class="'.(!empty($item['style']) ? $item['style'] : '').(!empty($extraClass) ? ' '.$extraClass : '').'">'.$item['title'].'</a></li>';
    };
    $footerText = Site::footerText();
    $aCounters = Site::i()->getCounters();
    $languages = Site::languagesList();

?>
<!-- BEGIN footer -->
<? if(DEVICE_DESKTOP_OR_TABLET): ?>
<p class="c-scrolltop" id="j-scrolltop" style="display: none;">
    <a href="#"><span><i class="fa fa-arrow-up"></i></span><?= _t('', 'Наверх'); ?></a>
</p>
<div id="footer" class="l-footer hidden-phone">
    <div class="content">
        <div class="container-fluid  l-footer__content">
            <div class="row-fluid l-footer__content_padding">
                <div class="span4">
                    <?= Site::copyright(); ?>
<?  ?>
                </div>
                <?php $footerCols = array('col1'=>['w'=>'2'], 'col2'=>['w'=>'3'], 'col3'=>['w'=>'3','last'=>true]);
                    foreach ($footerCols as $colKey=>$colData): ?>
                    <div class="span<?= $colData['w'] ?>">
                        <?php if ( ! empty($aFooterMenu[$colKey]['sub']) ) { ?>
                            <ul><?php
                                foreach($aFooterMenu[$colKey]['sub'] as $v):
                                    echo $footerLink($v);
                                endforeach; ?>
                            </ul>
                        <?php } if ( ! empty($colData['last'])) { ?>
                            <div class="l-footer__content__counters">
                                <?= Site::languagesSwitcher(); # Выбор языка ?>
                                <div class="l-footer__content__counters__list">
                                <?php if( ! empty($aCounters)) { ?>
                                    <?php foreach($aCounters as $v) { ?><div class="item"><?= $v['code'] ?></div><?php } ?>
                                <?php } ?>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                       <?php } ?>
                   </div>
               <?php endforeach; ?>
            </div>
        </div>
        <?php if (!empty($footerText)) { ?>
            <div class="l-footer_text mrgt10 mrgb10">
                <div class="container-fluid"><?= $footerText; ?></div>
            </div>
        <?php } ?>
    </div>
</div>
<? endif; ?>
<? if(DEVICE_PHONE): ?>
<div id="footer" class="l-footer l-footer_mobile visible-phone">
    <?php foreach ($aFooterMenu as $k => $col) { if (mb_stripos($k, 'col') === 0 && ! empty($col['sub'])) { ?>
        <div class="l-footer_mobile__menu">
            <ul><? foreach($col['sub'] as $v) {
                    echo $footerLink($v);
                } ?>
            </ul>
        </div>
    <?php } } ?>
    <div class="l-footer_mobile__lang mrgt20">
        <?= Site::languagesSwitcher(); # Выбор языка ?>
    </div>
    <div class="l-footer_mobile__copy mrgt15 mrgb30">
        <?= Site::copyright(); ?>
<?  ?>
        <br>
    </div>
    <? if ( ! empty($aCounters) && ! bff::deviceDesktopResponsive()) { ?>
    <div class="l-footer_mobile__counters mrgt20">
        <? foreach($aCounters as $v) { ?><div><?= $v['code'] ?></div><? } ?>
    </div>
    <? } ?>
    <?php if (!empty($footerText)) { ?>
        <div class="l-footer_text mrgt10 mrgb10">
            <div class="container-fluid"><?= $footerText; ?></div>
        </div>
    <?php } ?>
</div>
<? endif; ?>
<!-- END footer -->
<?= View::template('js'); ?>
<?= js::renderInline(js::POS_FOOT); ?>
<?

?>