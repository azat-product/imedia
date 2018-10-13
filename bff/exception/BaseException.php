<?php

namespace bff\exception;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Базовое исключение
 * @modified 7.jun.2018
 */
class BaseException extends Exception
{
    /**
     * Объект запроса
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Объект ответа, отправляемый HTTP клиенту
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Инициализация
     * @param ServerRequestInterface $request объект запроса
     * @param ResponseInterface $response объект ответа
     */
    public static function init(ServerRequestInterface $request, ResponseInterface $response = NULL)
    {
        $exception = new static();
        $exception->setRequest($request);
        $exception->setResponse( ! is_null($response) ? $response : new \Response());

        return $exception;
    }

    /**
     * Устанавливаем объект запроса
     * @param ServerRequestInterface $request объект запроса
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Получаем объект запроса
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Устанавливаем объект ответа
     * @param ResponseInterface $response объект ответа
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Получаем объект ответа
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}