<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\IpModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Psr\Container\ContainerInterface;
use Nyholm\Psr7\Response;

class IpSecurityMiddleware implements MiddlewareInterface
{
    private IpModel $ipModel;
    private ContainerInterface $container;
    private array $settings;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->ipModel = $container->get(IpModel::class);
        $this->settings = $container->get('settings')['security']['ip_blocking'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $this->getClientIp($request);

        // 1. Check if blocked
        if ($this->ipModel->isBlocked($ip)) {
            return $this->renderBlockedResponse($request, $ip);
        }

        try {
            $response = $handler->handle($request);

            // 2. Catch manual 404 responses
            if ($response->getStatusCode() === 404) {
                $this->logFail($ip);
            }

            return $response;

        } catch (HttpNotFoundException $e) {
            // 3. Catch Slim 404 exception
            $this->logFail($ip);
            throw $e; // Re-throw to let Slim error handler work
        }
    }

    private function logFail(string $ip): void
    {
        $this->ipModel->logFail(
            $ip,
            (int)$this->settings['max_404_attempts'],
            (int)$this->settings['interval_minutes']
        );
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $params = $request->getServerParams();
        return $params['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function renderBlockedResponse(ServerRequestInterface $request, string $ip): ResponseInterface
    {
        $accept = $request->getHeaderLine('Accept');
        $uri = $request->getUri()->getPath();
        $contact = $this->container->get('settings')['public']['main_contact_email'];

        $response = new Response();
        $response = $response->withStatus(403);

        // JSON Format
        if (str_contains($uri, '/api/') || str_contains($accept, 'application/json')) {
            $data = [
                'error' => 'IP_BLOCKED',
                'message' => "Your IP $ip is blocked due to suspicious activity.",
                'contact' => $contact
            ];
            return \App\Responder\JsonHandler::response($response, $data, 403);
        }

        // HTML Format
        if (str_contains($accept, 'text/html')) {
            $fenom = $this->container->get('tmpl');
            $html = $fenom->fetch('pages/blocked.tpl', [
                'ip' => $ip,
                'contact' => $contact,
            ]);
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html');
        }

        // Plain Text (Default)
        $response->getBody()->write("Error 403: IP Blocked ($ip). Contact: $contact");
        return $response->withHeader('Content-Type', 'text/plain');
    }
}
