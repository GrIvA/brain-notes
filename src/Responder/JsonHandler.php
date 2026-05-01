<?php

namespace App\Responder;

use Psr\Http\Message\ResponseInterface;

final readonly class JsonHandler
{
    /**
     * Returns a JSON response.
     *
     * @param ResponseInterface $response The response
     * @param mixed $data The data to encode
     * @param int $status The HTTP status code
     *
     * @return ResponseInterface The response
     */
    public static function response(
        ResponseInterface $response,
        mixed $data,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write((string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
