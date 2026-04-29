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
        $tagModel = $this->container->get(\App\Models\TagModel::class);
        $noteModel = $this->container->get(\App\Models\NoteModel::class);

        $userId = $this->user ? $this->user->getId() : null;
        
        $this->params['tags'] = $userId ? $tagModel->getAllUserTags($userId) : [];
        $this->params['notes'] = $noteModel->findFiltered(['user_id' => $userId]);

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
