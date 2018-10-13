<?php
/**
 * @var $this Plugin_User_Online_Do_p0bdd61
 * @var $user_id integer user id
 * @var $last_activity string last activity
 * @var $online boolean online status, true - online, false - offline
 * @var $page_key string
 */

$this->css('css/main.css');
if ($page_key == 'page_itemView' || $page_key == 'page_itemShopView' || $page_key == 'page_userProfile') {
	$div = true; 
} else { 
	$div = false;
}

if ($online) { 
	if ($div) { ?><div><?php } ?>
		<div class="<?php if ($page_key == 'page_cabinetMessagesChat') { ?>mrgl15<?php } ?> c-status-wrap">
		    <span class="c-status c-status-online"></span>
		    <small><?= $this->lang('онлайн'); ?></small>
		</div>
	<?php if ($div) { ?></div><?php }
} else { 
	if ($div) { ?><div><?php } ?>
		<div class="<?php if ($page_key == 'page_cabinetMessagesChat') { ?>mrgl15<?php } ?> c-status-wrap j-tooltip" data-toggle="tooltip" data-placement="bottom" title="<?= HTML::escape($last_activity) ?>">
		    <span class="c-status c-status-offline"></span>
		    <small><?= $this->lang('офлайн'); ?></small>
		</div>
	<?php if ($div) { ?></div><?php } ?>
<?php } ?>
