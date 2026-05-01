<?php

declare(strict_types=1);

namespace App\Controllers\Pages;

use App\Controllers\SiteController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RegisterPage extends SiteController
{
    public function handle(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        parent::collectHandleParams($req, $args);

        if ($req->getAttribute('user')) {
            return \App\Responder\RedirectHandler::redirectToUrl($res, '/');
        }

        return $this->resultHandling([], $res);
    }

    protected function getPageID(): int
    {
        return 4; // New page ID for register
    }

    protected function getTemplateName(): string
    {
        return 'register';
    }

    protected function getAdditionalPageParams(): void
    {
        $this->params['common']['title_of_page'] = 'Реєстрація';
    }
}
