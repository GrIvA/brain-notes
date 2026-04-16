<?php

namespace App\Responder;

use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;

final readonly class RedirectHandler
{
    public function __construct ()
    {
    }

    /**
     * Creates a redirect for the given url.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param string $destination The redirect destination (url or route name)
     * @param array<string, int|string> $queryParams Optional query string parameters
     *
     * @return ResponseInterface The response
     */
    public static function redirectToUrl(
        ResponseInterface $response,
        string $destination,
        array $queryParams = [],
    ): ResponseInterface {
        global $app;
        if (is_numeric($destination)) {
            $destination = getAliasByPageID($destination);
        }

        if ($queryParams) {
            $destination = sprintf('%s?%s', $destination, http_build_query($queryParams));
        }

        return $response->withStatus(302)->withHeader('Location', $destination);
    }

    /**
     * Creates a redirect for the given route name.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param string $routeName The redirect route name
     * @param array<string, string> $data Named argument replacement data
     * @param array<string, string> $queryParams Optional query string parameters
     *
     * @return ResponseInterface The response
     */
    public static function redirectToRouteName(
        ResponseInterface $response,
        string $routeName,
        array $data = [],
        array $queryParams = [],
    ): ResponseInterface {
        //$router_contex = RouteContext::fromRequest($req);
        //$route = $router_contex->getRoute();
        return $this->redirectToUrl($response, $this->routeParser->urlFor($routeName, $data, $queryParams));
    }
}
