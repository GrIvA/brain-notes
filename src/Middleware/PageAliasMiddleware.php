<?php

namespace App\Middleware;

use App\Models\PageAliasModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PageAliasMiddleware implements MiddlewareInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var PageAliasModel $model */
        $model = $this->container->get(PageAliasModel::class);

        $uri = $request->getUri();
        $path = explode('/', $uri->getPath());
        $lang_id = $request->getAttribute('lang_id');

        $route = null;
        if (is_array($path) && count($path) > 1 && empty($path[0])) {
            array_shift($path);
            $alias = $path[0];
            
            $route = $model->findByAlias($alias, (int)$lang_id);
            if (!$route) {
                $route = $model->findByRoute($uri->getPath());
            }
        }

        if (!empty($route) && is_array($route)) {
            array_shift($path);
            $postfix = count($path) ? '/'.implode('/', $path) : '';
            $request = $request
                ->withUri($uri->withPath($route['route'].$postfix))
                ->withAttribute('page_id', $route['page_id'])
                ->withAttribute('login_only', $route['is_login'])
                ->withAttribute('route', $route['route']);
        }

        return $handler->handle($request);
    }
}
