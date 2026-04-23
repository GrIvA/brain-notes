<?php

declare(strict_types=1);

namespace App\Responder;

use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;

/**
 * Helper class to generate HTTP redirects.
 *
 * This class is designed to be used via dependency injection.
 * Register it in the container and inject the {@see RouteParserInterface}
 * when constructing the service.
 */
final class RedirectHandler
{
    private RouteParserInterface $routeParser;

    public function __construct(RouteParserInterface $routeParser)
    {
        $this->routeParser = $routeParser;
    }

    /**
     * Creates a redirect for the given URL or page ID.
     *
     * If an integer is supplied, it is assumed to be a page ID and will be
     * resolved to an alias using the global helper `getAliasByPageID()`.
     *
     * @param ResponseInterface $response   The response to modify.
     * @param string|int        $destination URL, path or page ID.
     * @param array<string,int|string> $queryParams Optional query string parameters.
     *
     * @return ResponseInterface The response with a 302 status and Location header.
     */
    public function redirectToUrl(
        ResponseInterface $response,
        string|int $destination,
        array $queryParams = []
    ): ResponseInterface {
        // Resolve page ID to URL alias if needed.
        if (is_int($destination)) {
            // The helper function is part of the legacy codebase.
            // It returns a string URL for the given page ID.
            $destination = getAliasByPageID($destination);
        }

        // Append query string if parameters are provided.
        if ($queryParams !== []) {
            $destination = sprintf('%s?%s', $destination, http_build_query($queryParams));
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', $destination);
    }

    /**
     * Creates a redirect for the given named route.
     *
     * @param ResponseInterface $response   The response to modify.
     * @param string            $routeName  The name of the route defined in Slim.
     * @param array<string,mixed> $data      Named arguments for the route placeholders.
     * @param array<string,string> $queryParams Optional query string parameters.
     *
     * @return ResponseInterface The response with a 302 status and Location header.
     */
    public function redirectToRouteName(
        ResponseInterface $response,
        string $routeName,
        array $data = [],
        array $queryParams = []
    ): ResponseInterface {
        $url = $this->routeParser->urlFor($routeName, $data, $queryParams);
        return $this->redirectToUrl($response, $url);
    }
}
