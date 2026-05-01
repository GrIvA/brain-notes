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

        $userId = $this->user ? (int)$this->user->getId() : null;
        $notes = $noteModel->findFiltered(['user_id' => $userId]);

        // Use consistent tag loading logic
        $this->params['tags'] = $userId 
            ? $tagModel->getCombinedTags($userId) 
            : $tagModel->getPublicTags();

        // Attach tags to each note
        foreach ($notes as &$note) {
            $note['tags'] = $tagModel->getTagsByNoteId((int)$note['id']);
        }
        $this->params['notes'] = $notes;

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
