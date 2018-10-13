<?php

use bff\files\Attachment;

class InternalMailAttachment_ extends Attachment
{
    public function getUrl()
    {
        return bff::url('im');
    }

    /**
     * Формирование ссылки вложения
     * @param string $attachData данные о вложении, в формате "fileName;fileSize;extension;realFilename"
     * @param boolean $targetBlank открывать в новой вкладке
     * @param array $linkAttr дополнительные атрибуты ссылки
     * @return string
     */
    public function getAttachLink($attachData, $targetBlank = true, array $linkAttr = array())
    {
        if (empty($attachData)) {
            return '';
        }
        $data = explode(';', strval($attachData), 4);
        if (empty($data) || sizeof($data) < 3) {
            return '';
        }

        $linkAttr['href'] = $this->getUrl() . $data[0];
        if ($targetBlank) {
            $linkAttr['target'] = '_blank';
        }

        return '<a' . HTML::attributes($linkAttr) . '>' .
        (!empty($data[3]) ? HTML::escape($data[3]) : $data[0]) .
        '</a> <small>(' . tpl::filesize($data[1]) . ')</small>';
    }

    /**
     * Формирование пути вложения
     * @param $attachData
     * @return string
     */
    public function getAttachPath($attachData)
    {
        if(empty($attachData)){
            return '';
        }
        $data = explode(';', strval($attachData), 4);
        if (empty($data) || sizeof($data) < 3) {
            return '';
        }
        return $this->path.$data[0];
    }
}