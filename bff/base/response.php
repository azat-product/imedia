<?php namespace bff\base;

/**
 * Класс ответа клиенту
 * @version 0.2
 * @modified 22.aug.2017
 */

use \Zend\Diactoros\Response as DiactorosResponse;
use Zend\Diactoros\Stream;

class Response extends DiactorosResponse
{
    /**
     * Вставка HTML кода
     * @param string $code код для вставки
     * @param string $tag - 'body', 'head'
     * @param string $position - 'after:open', 'before:close'
     * @return self
     */
    public function injectHtml($code, $tag = 'body', $position = 'after:open')
    {
        if (!in_array(mb_strtolower($tag), array('body','head'))) {
            return $this;
        }

        $html = (string) $this->getBody();
        $pos = false;
        switch ($position)
        {
            case 'before:close': { # X</tag>
                $pos = mb_strripos($html, '</'.$tag.'>');
            } break;
            case 'after:close': { # </tag>X
                $pos = mb_strripos($html, '</'.$tag.'>');
                if ($pos !== false) {
                    $pos += mb_strlen('</'.$tag.'>');
                }
            } break;
            case 'after:open': { # <tag>X
                $pos = mb_stripos($html, '<'.$tag);
                if ($pos !== false) {
                    $pos = mb_stripos($html, '>', $pos) + 1;
                }
            } break;
        }

        if ($pos !== false) {
            $body = new Stream('php://temp', 'wb+');
            $body->write(
                mb_substr($html, 0, $pos) .
                $code .
                mb_substr($html, $pos)
            );
            $body->rewind();
            return $this->withBody($body);
        }

        return $this;
    }
}