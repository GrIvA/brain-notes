<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Custom Exception handling middleware.
 * Uses ErrorPage controller for HTML and native JSON rendering.
 */
class ExceptionHandlingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $displayErrorDetails = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($request, $exception);
        }
    }

    private function handleException(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        $statusCode = $this->getHttpStatusCode($exception);
        $response = $this->responseFactory->createResponse($statusCode);

        // Log error
        if (isset($this->logger)) {
            $this->logger->error(
                sprintf(
                    '%s File %s:%s , Method: %s, Path: %s',
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                    $request->getMethod(),
                    $request->getUri()->getPath()
                )
            );
        }

        // CLI handling
        if (PHP_SAPI === 'cli') {
            throw $exception;
        }

        $reasonPhrase = $response->getReasonPhrase();

        // JSON Format Detection
        if (
            !str_contains($request->getHeaderLine('Accept'), 'text/html')
            && !str_contains($request->getHeaderLine('Content-Type'), 'text/html')
            && (str_contains($request->getUri()->getPath(), '/api/') || !str_contains($request->getHeaderLine('Accept'), '*/*'))
        ) {
            return $this->renderJsonError($exception, $response, $statusCode, $reasonPhrase);
        }

        // HTML Format using our ErrorPage controller
        $code = $statusCode;
        $title = $reasonPhrase ?: 'Error';
        $message = $exception->getMessage();
        $trace = null;

        if ($this->displayErrorDetails) {
            $trace = (string)$exception;
        }

        if ($exception instanceof HttpException) {
            $title = $exception->getTitle();
            $message = $exception->getDescription();
        }

        // If not displaying details and not a HttpException, use generic message
        if (!$this->displayErrorDetails && !($exception instanceof HttpException)) {
            $title = 'Внутрішня помилка сервера';
            $message = 'Вибачте, щось пішло не так. Ми вже працюємо над виправленням.';
        }

        /** @var \App\Controllers\Pages\ErrorPage $errorPage */
        $errorPage = $this->container->get('ErrorPage');
        $errorPage->setErrorData($code, $title, $message, $trace);
        
        return $errorPage->handle($request, $response, []);
    }

    private function renderJsonError(Throwable $exception, ResponseInterface $response, int $statusCode, string $reasonPhrase): ResponseInterface
    {
        $error = [
            'error' => [
                'message' => $reasonPhrase ?: 'Internal Server Error',
                'code' => $statusCode,
            ],
        ];

        if ($this->displayErrorDetails) {
            $error['error']['details'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        } elseif ($exception instanceof HttpException) {
            $error['error']['message'] = $exception->getMessage();
        }

        return \App\Responder\JsonHandler::response($response, $error, $statusCode);
    }

    private function getHttpStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return (int)$exception->getCode();
        }
        
        if ($exception instanceof \DomainException || $exception instanceof \InvalidArgumentException) {
            return 400;
        }

        return 500;
    }
}
