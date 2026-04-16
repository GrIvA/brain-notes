<?php

namespace App\Controllers\Pages;

use App\Controllers\SiteController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller of home page.
 * ID: 1
 */
class Homepage extends SiteController
{
    public function __construct($container)
    {
        parent::__construct($container);
    }

    public function handle(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        parent::collectHandleParams($req, $args);
        $result = $this->checkArgs();

        return $this->resultHandling($result, $res);
    }

    // PRIVATE
    private function checkArgs(): array
    {
        //$tags = $this->user->getInfo()['tags'] ?? [];

        //$this->params['last_articles'] = $this->container['Article']->getLastArticles(6, $exept_ids, $tags);

        $result = ['status' => SiteController::HANDLING_STATUS_OK];
        return $result;
    }

    // PROTECTED

    protected function getPageID(): int
    {
        return SiteController::PAGE_HOME;
    }

    protected function getTemplateName(): string
    {
        return 'homepage';
    }

    protected function getAdditionalPageParams(): void
    {
        $this->params['common']['title_of_page'] = 'BN00001';
        $this->params['common']['desc_of_page'] = 'BN00002';
    }
}
