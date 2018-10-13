<?php
/**
 * Помощь: вопросы категории
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $title string заголовок
 * @var $subcats boolean есть подкатегории
 * @var $subcats_list array список подкатегории
 * @var $questions_list array список вопросов
 */
$lang_more = _t('help', 'Подробнее');
?>

<?= tpl::getBreadcrumbs($breadCrumbs); ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= $title ?></h1>
    </div>
    
    <div class="hl-list">
      
      <?php if($subcats) { ?>
        <ul class="hl-list-items">
          <?php foreach($subcats_list as &$v) { ?>
          <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
          <?php } unset($v); ?>
        </ul>
        <?php } else { ?>
        <ul class="hl-list-items">
          <?php foreach($questions_list as &$v) { ?>
          <?php if ( ! empty($v['textshort'])) { ?>
          <li><a href="#" class="link-ajax j-help-cat-question-ex"><span><?= $v['title'] ?></span></a>
            <div class="hl-list-textshort" style="display: none;">
              <div><?= $v['textshort'] ?></div>
              <?php if(!$v['content_no']){ ?><a href="<?= $v['link'] ?>" class="link-ico"><span><?= $lang_more ?></span> <i class="fa fa-angle-right c-link-icon"></i></a><?php } ?>
            </div>
          </li>
          <?php } else { ?>
          <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
          <?php } ?>
          <?php } unset($v); ?>
        </ul>
        <?php if (empty($questions_list)) { ?>
          <div class="alert alert-info">
            <?= _t('help', 'В данной рубрике пусто') ?>
          </div>
        <?php } ?>
      <?php } ?>

    </div>

  </div><!-- /.l-mainLayout-content -->

</div><!-- /.l-mainLayout -->

<script type="text/javascript">
  <?php js::start() ?>
  $(function(){
    $('.j-help-cat-question-ex').on('click touchstart', function(e){
      nothing(e);
      $(this).next().slideToggle()
    });
  });
  <?php js::stop() ?>
</script>