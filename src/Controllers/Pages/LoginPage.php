<?php

declare(strict_types=1);

namespace App\Controllers\Pages;

use App\Controllers\SiteController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginPage extends SiteController
{
    public function handle(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        parent::collectHandleParams($req, $args);
        
        if ($req->getAttribute('user')) {
            return $res->withHeader('Location', '/')->withStatus(302);
        }

        return $this->resultHandling([], $res);
    }

    protected function getPageID(): int
    {
        return SiteController::PAGE_SIGN_IN;
    }

    protected function getTemplateName(): string
    {
        return 'login';
    }

    protected function getAdditionalPageParams(): void
    {
        $this->params['common']['title_of_page'] = 'Вхід';
    }
}
