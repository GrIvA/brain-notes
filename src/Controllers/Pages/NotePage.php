<?php

declare(strict_types=1);

namespace App\Controllers\Pages;

use App\Controllers\SiteController;
use App\Models\NoteModel;
use App\Models\SectionModel;
use App\Models\NotebookModel;
use App\Models\TagModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotePage extends SiteController
{
    private ?array $noteData = null;

    public function handle(Request $request, Response $response, array $args): Response
    {
        $this->collectHandleParams($request, $args);
        
        $noteId = (int)($this->params['id'] ?? 0);
        $noteModel = $this->container->get(NoteModel::class);
        $this->noteData = $noteModel->findById($noteId);

        if (!$this->noteData) {
            return $response->withStatus(404);
        }

        $this->prepareNoteParams();

        return $this->resultHandling(['status' => self::HANDLING_STATUS_OK], $response);
    }

    private function prepareNoteParams(): void
    {
        $sectionModel = $this->container->get(SectionModel::class);
        $notebookModel = $this->container->get(NotebookModel::class);
        $tagModel = $this->container->get(TagModel::class);

        $noteId = (int)$this->noteData['id'];
        $section = $sectionModel->findById((int)$this->noteData['section_id']);
        $notebook = $notebookModel->findById((int)$section['notebook_id']);
        
        // Breadcrumbs: Notebook -> Sections path
        $breadcrumbs = [
            ['title' => $notebook['title'], 'url' => '#']
        ];
        $ancestors = $sectionModel->getAncestors((int)$this->noteData['section_id']);
        foreach ($ancestors as $ancestor) {
            $breadcrumbs[] = ['title' => $ancestor['title'], 'url' => '#'];
        }

        // Ownership & Permissions
        $isOwner = $this->user instanceof \App\Entities\User && (int)$this->user->getId() === (int)$notebook['user_id'];
        $isAdmin = $this->user instanceof \App\Entities\User && $this->user->hasRole(\App\Enums\UserRole::ADMIN);
        $canEdit = $isOwner || $isAdmin;

        // Tags
        $tags = $tagModel->getTagsByNoteId($noteId);
        $allUserTags = $canEdit ? $tagModel->getAllUserTags($this->user->getId()) : [];
        
        $this->params['note'] = $this->noteData;
        $this->params['breadcrumbs'] = $breadcrumbs;
        $this->params['tags'] = $tags;
        $this->params['allUserTags'] = $allUserTags;
        $this->params['canEdit'] = $canEdit;
    }

    protected function getPageID(): int
    {
        // For breadcrumbs to work, we might need a unique ID or use existing one
        return 0; 
    }

    protected function getTemplateName(): string
    {
        return 'note_view';
    }

    protected function getAdditionalPageParams(): void
    {
        $this->params['common']['title_of_page'] = $this->noteData['title'] ?? 'Note';
    }
}
