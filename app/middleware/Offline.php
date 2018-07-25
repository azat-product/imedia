<?php namespace app\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Offline
{
    public function __invoke(ServerRequestInterface $request, $next)
    {
        if (\Site::isOffline()) {
            $response = new \Response();
            $response = $response->withHeader('Retry-After', 3600)
                                 ->withStatus(503, 'Service Unavailable');
            if ($response->getBody()->isWritable()) {
                $response->getBody()->write(\Site::i()->offlinePage());
            }
            \bff::respond($response, true);
        }

        return $next($request);
    }
}