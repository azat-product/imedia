<?php namespace app\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RememberMe
{
    public function __invoke(ServerRequestInterface $request, $next)
    {
        if( ! \bff::isRobot() && \Users::loginRemember()) {
            \bff::security()->checkRememberMe();
        }

        return $next($request);
    }
}