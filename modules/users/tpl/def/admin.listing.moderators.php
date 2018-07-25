<?php
    tplAdmin::adminPageSettings(array('link'=>array(
        'title'=>_t('users', '+ добавить модератора'),'href'=>$this->adminLink('user_add')
    )));
?>
<form action="<?= $this->adminLink(NULL) ?>" method="get" name="filters" id="filters">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="order" value="<?= $order_by.tpl::ORDER_SEPARATOR.$order_dir ?>" />
    <input type="hidden" name="page" value="<?= $page ?>" />
</form> 

<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?
            $aHeaderCols = array(
                'user_id' => array('t'=>_t('', 'ID'),'w'=>100,'order'=>'desc'),
                'email' => array('t'=>_t('', 'E-mail'),'order'=>'asc','align'=>'left'),
                'group' => array('t'=>_t('users', 'Принадлежность к группе'),'w'=>350),
                'action' => array('t'=>_t('', 'Action'),'w'=>85),
            );
            $urlOrderBy = $this->adminLink(bff::$event.'&page=1&order=');
            foreach($aHeaderCols as $k=>$v) {
                ?><th<? if( ! empty($v['w']) ) { ?> width="<?= $v['w'] ?>"<? } if( ! empty($v['align']) ) { ?>  class="<?= $v['align'] ?>"<? } ?>><?
                if( ! empty($v['order'])) {
                    if( $order_by == $k ) {
                        ?><a href="<?= $urlOrderBy.$k.tpl::ORDER_SEPARATOR.$order_dir_needed ?>"><?= $v['t'] ?><div class="order-<?= $order_dir ?>"></div></a><?
                    } else {
                        ?><a href="<?= $urlOrderBy.$k.tpl::ORDER_SEPARATOR.$v['order'] ?>"><?= $v['t'] ?></a><?
                    }
                } else {
                    echo $v['t'];
                }
                ?></th><?
            }
        ?>
    </tr>
</thead>
<?
$urlGroupPermissions = $this->adminLink('group_permission_listing&rec=');
foreach($users as $k=>$v) { $id = $v['user_id']; ?>
<tr class="row<?= $k%2 ?><? if( ! $v['activated'] ) { ?> disabled<? } ?>">
    <td><?= $id ?></td>
    <td class="left"><a href="mailto:<?= $v['email'] ?>"><?= $v['email'] ?></a></td>
    <td>
        <? foreach($v['groups'] as $key=>$g) { ?>
            <a title="<?= _te('users', 'доступ группы'); ?>" href="<?= $urlGroupPermissions.$g['group_id'] ?>" style="color:<?= $g['color'] ?>; font-weight:bold; text-decoration:none;">
                <?= $g['title'] ?></a><? if($key+1 != sizeof($v['groups'])) { ?>,<? } ?>
        <? } ?>
    </td>
    <td>
        <a class="but <? if( ! $v['blocked'] ) { ?>un<? } ?>block" href="#" onclick="return bff.userinfo(<?= $id ?>);" id="u<?= $id ?>"></a>
        <a class="but edit" href="<?= $this->adminLink('user_edit&tuid='.$v['tuid'].'&rec='.$id) ?>"></a>
    </td>
</tr>
<? } if( empty($users) ) { ?>
<tr class="norecords">
    <td colspan="4"><?= _t('users', 'нет пользователей'); ?></td>
</tr>
<? } ?>
</table>
<? echo $pgn;