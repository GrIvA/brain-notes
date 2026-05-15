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
        $sectionModel = $this->container->get(\App\Models\SectionModel::class);

        $sectionId = isset($this->params['section_id']) ? (int)$this->params['section_id'] : null;

        $userId = $this->user ? (int)$this->user->getId() : null;

        $criteria = ['user_id' => $userId];
        if ($sectionId) {
            $section = $sectionModel->findById($sectionId);
            if ($section) {
                $this->params['common']['active_notebook_id'] = (int)$section['notebook_id'];
                // Ensure cookie is in sync if it was different
                if (!isset($this->params['active_notebook_id']) || (int)$this->params['active_notebook_id'] !== (int)$section['notebook_id']) {
                    setcookie('active_notebook_id', (string)$section['notebook_id'], time() + 60 * 60 * 24 * 365, '/');
                }
            }
            $criteria['section_id'] = $sectionModel->getAllChildIds($sectionId);
            $this->params['common']['active_section_id'] = $sectionId;
        }

        $notesData = $noteModel->findFiltered($criteria);
        $notes = [];
        $registryModel = $this->container->get(\App\Models\RegistryModel::class);

        // Use consistent tag loading logic
        $this->params['tags'] = $userId
            ? $tagModel->getCombinedTags($userId)
            : $tagModel->getPublicTags();

        // Attach tags to each note and hide encrypted content via Entity logic
        foreach ($notesData as $data) {
            $noteEntity = new \App\Entities\Note($data, $registryModel);
            $note = $noteEntity->toArray();
            $note['tags'] = $tagModel->getTagsByNoteId((int)$note['id']);
            $notes[] = $note;
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
