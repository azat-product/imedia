<?php

abstract class tplAdmin_ extends \bff\base\tpl
{
    /**
     * Блок: открытие
     * @param string $sTitle заголовок блока
     * @param string|bool|null $mIcon true - иконка списка; false - иконка формы; string - ключ иконки; null - определяем
     * @param array $aAttr доп. атрибуты блока
     * @param array $aLink ссылка
     * @param array $aFordev доп. ссылки в режиме FORDEV
     * @return string
     */
    public static function blockStart($sTitle, $mIcon = true, array $aAttr = array(), array $aLink = array(), array $aFordev = array())
    {
        # помечаем дальнейшее отсутствие необходимости открывать блок в основном шаблоне
        static::adminPageSettings(array('custom' => true));

        $aAttr['class'] = 'box' . (!empty($aAttr['class']) ? ' ' . $aAttr['class'] : '');

        if (!empty($aLink)) {
            if (empty($aLink['title'])) {
                $aLink['title'] = '';
            }
            if (!isset($aLink['href'])) $aLink['href'] = '#';
        }

        if (is_null($mIcon)) {
            $sett = static::adminPageSettings();
            if (!is_null($sett['icon'])) $mIcon = $sett['icon'];
            else {
                $mIcon = !(preg_match('/.*(add|edit|form).*/i', bff::$event) > 0);
            }
        }
        if (is_bool($mIcon)) {
            $mIcon = ($mIcon ? 'icon-align-justify' /* список */ : 'icon-edit' /* форма */);
        }
        $sFordevLinks = '';
        if (FORDEV) {
            $aFordev = bff::filter('admin.block.fordev.menu', $aFordev);
            if (!empty($aFordev)) {
                $sFordevLinks = '<ul class="dropdown-menu">';
                foreach ($aFordev as $v) {
                    $html = '';
                    if (!empty($v['debug-only']) && !BFF_DEBUG) continue;
                    if (!empty($v['icon'])) {
                        $html .= '<i class="' . $v['icon'] . '"></i>&nbsp;&nbsp;';
                        unset($v['icon']);
                    }
                    if (!empty($v['title'])) {
                        $html .= $v['title'];
                        unset($v['title']);
                    };
                    $sFordevLinks .= '<li><a' . HTML::attributes($v) . '>' . $html . '</a></li>';
                }
                $sFordevLinks .= '</ul>';
                if (mb_stripos($sFordevLinks, '<li>') === false) {
                    $sFordevLinks = '';
                }
            }
        }

        return '<div' . HTML::attributes($aAttr) . '>
                    <div class="box-header">
                        <h2><i class="' . $mIcon . '"></i><span class="break"></span><span class="caption">' . $sTitle . '</span></h2>
                        ' . (!empty($sFordevLinks) ? '<span class="fordev pull-right"><span class="break"></span><a href="#" onclick="$(this).next().toggle(); return false;"><i class="icon-wrench"></i></a>' . $sFordevLinks . '</span>' : '') . '
                        ' . (!empty($aLink) ? '<div class="right-link"><a' . HTML::attributes($aLink) . '>' . (!empty($aLink['title']) ? $aLink['title'] : '') . '</a></div>' : '') . '
                        <div class="clearfix"></div>
                    </div>
                    <div class="box-content">
                        <div class="text">';
    }

    /**
     * Блок: закрытие
     * @return string
     */
    public static function blockStop()
    {
        return '        </div>
                        <div class="bottom">
                            <span class="left"></span>
                            <span class="right"></span>
                        </div>
                     </div>
                 </div>';
    }
}