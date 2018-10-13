<?php
/**
 * @var $this \bff\tpl\admin\BlockTabs
 * @var $tabs array
 */
 if (empty($this->tabs)) {
    return;
 }
?>

<?= HTML::input('hidden', $this->getActive(), ['name'=>$this->getActiveKey(), 'class'=>'j-tab-value']); ?>
<div class="tabsBar j-tabs" id="<?= $this->cssClass() ?>">
    <?php
        foreach ($this->tabs as $tab) {
        $tab['attr']['data-tab'] = $tab['id'];
        HTML::attributeAdd($tab['attr'], 'class', 'j-tab');
        if ( ! array_key_exists('href', $tab['attr'])) {
            $tab['attr']['href'] = 'javascript:void(0);';
        }
    ?>
        <span class="tab<?php if(!empty($tab['active'])) { ?> tab-active<?php } ?>">
            <a <?= HTML::attributes($tab['attr']) ?>><span class="j-tab-title"><?= $tab['title'] ?></span></a></span> <?php } ?>
</div>