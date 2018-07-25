<?php namespace app\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class LoginAuto
{
    public function __invoke(ServerRequestInterface $request, $next)
    {
        \Users::i()->loginAuto();

        return $next($request);
    }
}