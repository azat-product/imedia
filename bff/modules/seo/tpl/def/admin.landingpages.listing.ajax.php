<?php
    /**
     * @var $this SEO
     */
    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <td class="small"><?= $id ?></td>
        <td class="left">
            <a href="<?= bff::urlBase(false).$v['landing_uri'] ?>" target="_blank"><?= HTML::escape($v['landing_uri']) ?></a>
            <? if(!empty($v['title'])) { ?><div class="desc small"><?= tpl::truncate($v['title'], 45, '...', true) ?></div><?php } ?>
        </td>
        <td class="left">
            <?= $v['original_uri'] ?>
        </td>
        <td class="left">
            <?php if(empty($v['joined'])) { ?>
                <a class="but <?= ($v['enabled']?'un':'') ?>block landingpage-toggle" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
                <a class="but edit landingpage-edit" title="<?= _te('', 'Edit') ?>" href="#" data-id="<?= $id ?>"></a>
                <a class="but del landingpage-del" title="<?= _te('', 'Delete') ?>" href="#" data-id="<?= $id ?>"></a>
            <?php } else { ?>
                <a class="but <?= ($v['enabled']?'un':'') ?>block landingpage-toggle" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
                <a class="but edit landingpage-edit" title="<?= _te('', 'Edit') ?>" href="#" data-id="<?= $id ?>"></a>
                <span class="icon icon-random disabled" title="<?= _te('seo', 'привязанная посадочная страница') ?>" style="margin-right: 6px;"></span>
            <?php } ?>
        </td>
    </tr>
<?php endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="4">
            <?= _t('', 'Nothing found') ?>
        </td>
    </tr>
<?php endif;