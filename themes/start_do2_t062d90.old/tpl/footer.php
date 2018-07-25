<?php
/**
 * Футер сайта
 */
if (config::get('tpl.footer.hide', false, TYPE_BOOL)) {
  return;
}
$footerMenu = Sitemap::view('footer');
$footerLink = function ($item, $extraClass = '') {
  if (!empty($item['a'])) {
    return '<li>' . $item['title'] . '</li>';
  }
  return '<li><a href="' . $item['link'] . '"' . ($item['target'] === '_blank' ? ' target="_blank"' : '') . ' class="' . (!empty($item['style']) ? $item['style'] : '') . (!empty($extraClass) ? ' ' . $extraClass : '') . '">' . $item['title'] . '</a></li>';
};
$footerText = Site::footerText();
$counters = Site::i()->getCounters();

?>
<!-- BEGIN footer -->
<?php if (DEVICE_DESKTOP_OR_TABLET) { ?>
  <div class="l-footer">
    <div class="container">
      <div class="row">
        <div class="col-sm-3">
          <?= Site::copyright(); ?>
        </div>
        <?php if (!empty($footerMenu['col1']['sub'])) { ?>
          <div class="col-sm-2">
            <ul class="l-footer-menu">
              <?php foreach ($footerMenu['col1']['sub'] as $v) {
                echo $footerLink($v);
              } ?>
            </ul>
          </div>
        <?php } ?>
        <?php if (!empty($footerMenu['col2']['sub'])) { ?>
          <div class="col-sm-2">
            <ul class="l-footer-menu">
              <?php foreach ($footerMenu['col2']['sub'] as $v) {
                echo $footerLink($v);
              } ?>
            </ul>
          </div>
        <?php } ?>
        <?php if (!empty($footerMenu['col3']['sub'])) { ?>
          <div class="col-sm-2">
            <ul class="l-footer-menu">
              <?php foreach ($footerMenu['col3']['sub'] as $v) {
                echo $footerLink($v);
              } ?>
            </ul>
          </div>
        <?php } ?>
        <div class="col-sm-3">
          <?= Site::languagesSwitcher(); # Выбор языка ?>
          <div class="l-footer-counters">
            <?php if (!empty($counters)) { ?>
              <?php foreach ($counters as $v) { ?>
                <div class="item"><?= $v['code'] ?></div><?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>
<?php if (!empty($footerText)) { ?>
  <div class="l-footer-text">
    <div class="container">
      <?= $footerText; ?>
    </div>
  </div>
<?php } ?>
<!-- Scripts -->
<?= View::template('js'); ?>
<?= js::renderInline(js::POS_FOOT); ?>
<script>
  // Tooltips and popovers
  $(document).ready(function () {
    $('.has-tooltip').tooltip();
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
  });
</script>
