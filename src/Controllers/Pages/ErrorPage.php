<?php

declare(strict_types=1);

namespace App\Controllers\Pages;

use App\Controllers\SiteController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ErrorPage extends SiteController
{
    private int $errorCode = 500;
    private string $errorTitle = 'Внутрішня помилка сервера';
    private string $errorMessage = 'Вибачте, щось пішло не так. Ми вже працюємо над виправленням.';
    private ?string $errorTrace = null;

    public function setErrorData(int $code, string $title, string $message, ?string $trace = null): void
    {
        $this->errorCode = $code;
        $this->errorTitle = $title;
        $this->errorMessage = $message;
        $this->errorTrace = $trace;
    }

    public function handle(Request $request, Response $response, array $args): Response
    {
        $this->collectHandleParams($request, $args);
        
        $this->params['code'] = $this->errorCode;
        $this->params['title'] = $this->errorTitle;
        $this->params['message'] = $this->errorMessage;
        $this->params['trace'] = $this->errorTrace;

        return $this->resultHandling(['status' => self::HANDLING_STATUS_OK], $response->withStatus($this->errorCode));
    }

    protected function getPageID(): int
    {
        return self::PAGE_ERROR;
    }

    protected function getTemplateName(): string
    {
        return 'error';
    }

    protected function getAdditionalPageParams(): void
    {
        $this->params['common']['title_of_page'] = "Помилка " . $this->errorCode;
    }
}
