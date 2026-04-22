<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Force the request to be treated as JSON for API routes.
 * This ensures that the Error Handler returns a JSON response.
 */
class ApiContentTypeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Override the Accept header to force JSON response from Error Handler
        $request = $request->withHeader('Accept', 'application/json');

        return $handler->handle($request);
    }
}
