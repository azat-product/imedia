<?php
/**
 * @var $this BBS
 */
tplAdmin::adminPageSettings(array('icon' => 'icon-search'));

# tabs:
$tab = $this->input->postget('tab');
$tabs = bff::filter('seo.admin.settings.tabs', array(
    'robots' => array('t' => 'Robots.txt'),
    'sitemap' => array('t' => 'Sitemap.xml'),
));
if ( ! isset($tabs[$tab])) {
    $tab = key($tabs);
}

?>
<style type="text/css">
    .tooltip-inner { max-width: inherit; }
</style>
<div id="j-site-robots-block">
    <?php if ( ! empty($alerts)){ foreach($alerts as $v){ ?>
        <div class="alert alert-info" style="margin-bottom: 5px; padding: 10px;"><?= $v ?></div>
    <?php }} ?>
    <div class="tabsBar relative j-tabs">
        <?php foreach($tabs as $k=>$v){ ?>
            <span class="tab j-tab<?php if($k==$tab){ ?> tab-active<?php } ?>" data-tab="<?= HTML::escape($k) ?>"><?= $v['t'] ?></span>
        <?php } ?>
        <div  class="j-progress" style="position: absolute; right: 10px; top: 5px; display: none;" >
            <span class="progress"></span>
        </div>
    </div>


    <div class="j-tab-bl<?= $tab != 'robots' ? ' displaynone' : '' ?>" data-tab="robots">
        <form method="post" action="" class="j-robots" enctype="multipart/form-data">
            <input type="hidden" name="save" value="1" />

            <table class="admtbl tbledit">
                <tr>
                    <td style="vertical-align:top;">
                        <textarea name="robots_template" <?= empty($robotsEnabled) ? 'disabled="disabled"' : '' ?> style="min-height: 250px;"><?= ! empty($robots_template) ? HTML::escape($robots_template) : '' ?></textarea>
                    </td>
                    <td style="vertical-align:top; width: 220px; min-width: 200px; padding-left: 20px;">
                        <div>
                            <div class="text-info"><?= _t('seo', 'Макросы') ?>:</div>
                            <hr class="cut">
                            <div style="margin-top: 10px;">
                                <a href="javascript:void(0);" class="j-tpl-macros" data-key="{sitemap}">{sitemap}</a><br/>
                                <?= _t('seo', 'Ссылка на файл'); ?> Sitemap.xml
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <?php if ( ! empty($robotsEnabled)){ ?>
                <input type="submit" class="btn btn-success btn-small button submit" value="<?= _te('', 'Сохранить'); ?>" />
                    <a href="javascript:" class="btn btn-small j-recommended"><?= _t('seo', 'Настройки по умолчанию') ?></a>
                <?php } ?>
            </div>

        </form>
    </div>

    <div class="j-tab-bl<?= $tab != 'sitemap' ? ' displaynone' : '' ?>" data-tab="sitemap">
        <div class="well well-small">
            <?= _t('seo', 'Файл [file] обновляется автоматически раз в сутки.', array('file' => $sitemapExists ? '<a href="'.$sitemapUrl.'?nocache='.func::generator().'" target="_blank"><b>Sitemap.xml</b></a>' : '<b>Sitemap.xml</b>')); ?><br />
            <?php if($sitemapExists){ ?><?= _t('seo', 'Последнее обновление: [date]', array('date' => '<b>'.tpl::date_format3(date('Y-m-d H:i:s', $sitemapModified)).'</b>')) ?><br /><?php } ?>
            <a href="javascript:" class="btn btn-default btn-mini j-refresh-sitemap" style="margin-top: 5px;"><?= _t('seo', 'Обновить принудительно'); ?></a>
        </div>
    </div>

</div>
<script type="text/javascript">
$(function () {
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';
    var $block = $('#j-site-robots-block');
    var $progress = $block.find('.j-progress');

    $block.on('click', '.j-tab', function (e) {
        e.preventDefault();
        var $el = $(this);
        $el.addClass('tab-active').siblings().removeClass('tab-active');
        $block.find('.j-tab-bl').addClass('displaynone').filter('[data-tab="'+$el.data('tab')+'"]').removeClass('displaynone');
    });

    var $robotsForm = $block.find('.j-robots');

    var $template = $robotsForm.find('[name="robots_template"]');
    $template.autogrow({minHeight:250, lineHeight:20});

    $robotsForm.on('click', '.j-tpl-macros', function(e){ nothing(e);
        var key = $(this).data('key');
        bff.textInsert($template, key);
    });

    $('.j-tooltip').tooltip();

    $block.on('click', '.j-delete-robots', function (e) {
        e.preventDefault();
        if ( ! bff.confirm('sure')) return;
        bff.ajax(ajaxUrl+'&act=delete-robot', {}, function (data) {
            if (data && data.success) {
                bff.success(data.msg);
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }
        }, $progress);
    });

    $block.on('click', '.j-delete-sitemap', function (e) {
        e.preventDefault();
        if ( ! bff.confirm('sure')) return;
        bff.ajax(ajaxUrl+'&act=delete-sitemap', {}, function (data) {
            if (data && data.success) {
                bff.success(data.msg);
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }
        }, $progress);
    });

    $block.on('click', '.j-show-more', function (e) {
        e.preventDefault();
        $block.find('.j-more-bl').removeClass('displaynone');
        $(this).addClass('displaynone');
    });

    $block.on('click', '.j-recommended', function (e) {
        e.preventDefault();
        $template.val(<?= func::php2js(SEO::robotsTemplate()) ?>);
        $template.focus();
    });

    $block.on('click', '.j-refresh-sitemap', function (e) {
        e.preventDefault();
        bff.ajax(ajaxUrl+'&act=refresh-sitemap', {}, function (data) {
            if (data && data.success) {
                bff.success(data.msg);
            }
        }, $progress);
    });
});
</script>