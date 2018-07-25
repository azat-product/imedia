<?php

?>
<div class="ipopup">
<div class="ipopup-wrapper">
    <div class="ipopup-title"><?= _t('banners', 'Просмотр баннера'); ?></div>
    <div class="ipopup-content" style="width:<?php if($type == Banners::TYPE_IMAGE) { echo $img_width; } ?>px;">
        <?php if($type == Banners::TYPE_CODE || $type == Banners::TYPE_TEASER) {
            echo $type_data;
         } else if($type == Banners::TYPE_FLASH) {
            $flash = $this->flashData($type_data); 
            ?>
            <div id="popup_bn_fl"></div>
            <script type="text/javascript">
             $(function(){
                   var banner_url = '<?= $this->buildUrl($id, $flash['file'], Banners::szFlash); ?>';
                   $('#popup_bn_fl').html('<object type="application/x-shockwave-flash" data="'+banner_url+'" width="<?= $flash['width']; ?>" height="<?= $flash['height']; ?>"><param name="movie" value="'+banner_url+'" /></object>');
                });
            </script>
      <?php } else { ?>
            <img id="popup_bn_img" src="<?= $this->buildUrl($id, $img, Banners::szView); ?>" />
      <?php } ?>
    </div> 
</div>
</div>