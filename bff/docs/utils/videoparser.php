<?php namespace bff\utils;

/**
 * Компонент вспомогательных методов работы с видео ссылками
 * @version 0.44
 * @modified 15.jun.2014
 */

class VideoParser_
{
    # ID доступных провайдеров:
    const PROVIDER_UNKNOWN = 0;
    const PROVIDER_YOUTUBE = 1;
    const PROVIDER_VIMEO   = 2;
    const PROVIDER_RUTUBE  = 4;
    const PROVIDER_VK      = 8;

    /**
     * Конструктор
     */
    public function __construct()
    {
    }

    /**
     * Получение данных о видео в формате oEmbed http://oembed.com/
     * Формат oEmbed:
     * {
     * "version": "1.0", - версия oEmbed формата
     * "type": "video", - тип данных: "video"
     * "width": 240, - ширина видео
     * "height": 160, - высота видео
     * "duration": 568, - длительность видео (только Vimeo)
     * "title": "ZB8T0193", - заголовок видео
     * "description": "ZB8T0193 is a short-movie, Camera etc ...", - описание видео (только Vimeo)
     * "html": "<iframe width="459" height="344" src=\"http://www.youtube.com/embed/bDOYN-6gdRE?feature=oembed\"></iframe>",
     * "author_name": "schmoyoho",
     * "author_url": "http://www.youtube.com/user/schmoyoho",
     * "provider_name": "YouTube",
     * "provider_url": "http://www.youtube.com/"
     * "thumbnail_url": "http://i1.ytimg.com/vi/bDOYN-6gdRE/hqdefault.jpg", - ссылка на preview изображение
     * "thumbnail_width": 480, - ширина preview изображение
     * "thumbnail_height": 360, - высота preview изображение
     * }
     * Дополнительно:
     * 'embed_url' - embed-url
     * 'video_url' - video-url
     * 'flash_url' - flash-url
     * 'video_id'  - ID video
     * 'provider_id' - ID провайдера (self::PROVIDER_)
     * @param string $parseData video-url, embed-url, iframe-код
     * @param boolean $thumbnailEx получать 'thumbnail_url_ex' (альтернативный способ)
     * @return array
     */
    public function embed($parseData, $thumbnailEx = false)
    {
    }

}