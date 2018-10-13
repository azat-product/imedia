<?php
/**
 * @var $this \bff\tpl\admin\BlockList
 * @var $jsObject string
 */

# Content only:
if ($this->isAJAX()) {
    $this->ajaxResponseForm([
        'list' => $this->rowsRender(),
        'pgn'  => $this->pages(),
    ]);
}

?>

<div id="<?= $this->cssClass('form-container') ?>" style="display: none;"><?= '' ?></div>

<div id="<?= $this->cssClass('container') ?>">

<?php
    # Filters
    echo $this->filters();

    # Table: Columns + Rows
    if ($this->table) {
    ?>
    <table class="table table-condensed table-hover admtbl tblhover j-list">
        <?= $this->rowsRender(); ?>
    </table>
    <?php
    } else {

    # Custom render:
    ?>
    <div class="j-list">
        <?= $this->rowsRender(); ?>
    </div>
    <?php
    }
?>

<div class="j-list-pgn">
<?php
    # Pagination
    echo $this->pages();
?>
</div>

</div>

<script type="text/javascript">
    <?php
        $params = array(
            'tab' => $this->tabs()->getActive(),
            'rotate' => false,
            'form' => array(
                'block'  => '#'.$this->cssClass('form-container'),
            ),
            'orderSeparator' => $this->orderSeparator,
        );
    ?>
    $(function(){
        <?php if ( ! empty($jsObject)) { ?>
            <?= $jsObject ?> =
        <?php } ?>
        bff.list('#<?= $this->cssClass('container') ?>', <?= func::php2js($params) ?>);
    });
</script>