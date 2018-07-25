<?php
/**
 * Кабинет пользователя: Сообщения
 * @var $this InternalMail
 * @var $list string список контактов (HTML)
 * @var $total integer общее кол-во контактов
 * @var $f array параметры фильтра списка
 * @var $folders array папки группировки контактов
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 */
tpl::includeJS(array('history'), true);
tpl::includeJS(array('internalmail.my'), false, 2);
?>

<form action="" class="form-search" id="j-my-messages-form">
  <input type="hidden" name="f" value="<?= $f['f'] ?>" id="j-my-messages-folder-value" />
  <input type="hidden" name="page" value="<?= $f['page'] ?>" />
  <input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-messages-pp-value" />

  <!-- Messages Filter -->
  <div class="usr-content-top">
    <div class="input-group usr-content-top-search">
      <input type="text" name="qq" value="<?= HTML::escape($f['qq']) ?>" class="form-control j-q" maxlength="50" />
      <span class="input-group-btn">
        <button type="submit" class="btn btn-default j-q-submit"><i class="fa fa-search"></i></button>
      </span>
    </div>
    <ul class="nav nav-pills nav-pills-sm" id="j-my-items-cat">
      <?php foreach($folders as $k=>$v) {
         ?><li class="u-cabinet__sub-navigation__sort <?php if($f['f'] == $k) { ?> active<?php } ?> j-folder-options"><a href="#" class="j-folder-option" data-value="<?= $k ?>"><span><?= $v['title'] ?></span></a></li><?php
      } ?>
    </ul>
  </div>

  <!-- Dialogs -->
  <div class="usr-dialogs" id="j-my-messages-list">
    <?= $list ?>
  </div>
  
  <!-- Pagination -->
  <div class="usr-pagination">
    <div id="j-my-messages-pp" class="usr-pagination-dropdown dropdown<?= ( ! $total ? ' hide' : '' ) ?>">
      <a class="btn btn-default j-pp-dropdown" data-toggle="dropdown" href="#">
        <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
        <b class="caret"></b>
      </a>
      <ul class="dropdown-menu pull-right">
        <?php foreach($pgn_pp as $k=>$v): ?>
          <li><a href="#" class="<?php if($k == $f['pp']) { ?>active <?php } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div id="j-my-messages-pgn">
      <?= $pgn ?>
    </div>
  </div>

</form>

<script type="text/javascript">
  <?php js::start() ?>
  $(function(){
    jMyMessages.init(<?= func::php2js(array(
      'lang' => array(),
      'folders' => $folders,
      'ajax' => true,
      )) ?>);
  });
  <?php js::stop() ?>
</script>