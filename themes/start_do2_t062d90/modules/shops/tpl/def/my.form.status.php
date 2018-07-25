<?php
/**
 * Статус магазина: результат открытия / редактирования
 * @var $this Shops
 * @var $form_status string статус
 * @var $link string ссылка на страницу просмотра магазина
 * @var $title string название магазина
 * @var $blocked_reason string причина блокировки
 */

switch($form_status)
{
  case 'add.success': { # Магазин был успешно открыт
    if ($status == Shops::STATUS_REQUEST) {
      echo $this->showInlineMessage(array(
        _t('shops', 'Вы успешно открыли магазин "<strong>[title]</strong>".', array('title'=>$title)),
        '<br />',
        _t('shops', 'После проверки модератором ваш магазин будет активирован.')
        ));
    } else {
      echo $this->showInlineMessage(array(
        _t('shops', 'Вы успешно открыли магазин "<a [link]>[title]</a>"!', array(
          'link'=>'href="'.$link.'"', 'title'=>$title,
          )),
        ));
    }
  } break;
  case 'edit.moderating': { # Магазин ожидает проверки модератора
    echo $this->showInlineMessage(array(
      _t('shops', 'Редактирование настроек магазина будет доступно<br />после активации вашего магазина модератором.')
      ));
  } break;
  case 'edit.blocked': { # Магазин заблокирован модератором
    echo $this->showInlineMessage(array(
      _t('shops', 'Ваш магазин был заблокирован модератором, по следующей причине:'),
      '<br />',
      '<br />',
      '<strong>'.$blocked_reason.'</strong>'
      ));
  } break;
  case 'edit.notactive': { # Магазин деактивирован модератором
    echo $this->showInlineMessage(array(
      _t('shops', 'Ваш магазин был деактивирован модератором.'),
      '<br />',
      _t('shops', 'Для выяснения причины деактивации обратитесь к администратору.'),
      ));
  } break;
}