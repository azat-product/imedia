<?php
/**
 * Просмотр баннера
 * @var $this Banners
 * @var $aData array данные баннера
 */

$aData = HTML::escape($aData, 'html', array('title', 'alt'));

if ($type == Banners::TYPE_CODE) {
  echo strtr($type_data, array(
    '{query}' => (!empty($query) ? HTML::escape($query, 'js') : ''),
  ));
  ?>
  <div style="display:none;"><img src="<?= $showURL ?>" width="1" height="1" alt=""/></div><?php
} else if ($type == Banners::TYPE_FLASH) {
  tpl::includeJS('swfobject', true);
  $flash = $this->flashData($type_data);
  $jsID = 'j-bn-' . $id . '-';
  ?>
  <script type="text/javascript">
    <?php js::start(); ?>
    if (FlashDetect.installed) {
      swfobject.embedSWF("<?= $this->buildUrl($id, $flash['file'], Banners::szFlash) ?>", "<?= $jsID . 'f' ?>", "<?= ($flash['width'] > 0 ? $flash['width'] : '100%') ?>", "<?= $flash['height'] ?>", "9.0.0", "<?= SITEURL_STATIC . '/js/bff/swfobject/expressInstall.swf' ?>", <?= (!empty($flash['key']) ? '{' . $flash['key'] . ':"' . HTML::escape($clickURL, 'js') . '"}' : 'false') ?>, {wmode: 'opaque'});
    } else {
      $(function () {
        $('#<?= $jsID . 'i' ?>').show();
      });
    }
    <?php js::stop(); ?>
  </script>
  <div id="<?= $jsID . 'f' ?>" style="display:none;"></div>
  <a <?= !empty($target_blank) ? 'target="_blank"' : '' ?> style="display:none;" href="<?= $clickURL; ?>" rel="nofollow"
                                                           title="<?= $title; ?>" id="<?= $jsID . 'i' ?>">
    <img src="<?= $showURL ?>" alt="<?= $alt; ?>"/>
  </a>
  <?php
} else {
  if (!empty($clickURL)) { ?><a <?= !empty($target_blank) ? 'target="_blank"' : '' ?> title="<?= $title; ?>"
                                                                                      href="<?= $clickURL ?>"
                                                                                      rel="nofollow"><img
      src="<?= $showURL ?>"
      alt="<?= $alt; ?>" <?php if (!empty($pos_data['height'])) { ?> height="<?= $pos_data['height'] ?>"<?php } ?> />
    </a>
  <?php } else { ?><img src="<?= $showURL ?>" alt="" /><?php }
}