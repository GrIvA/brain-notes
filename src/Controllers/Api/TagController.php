<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\TagModel;
use App\Models\NoteModel;
use App\Models\SectionModel;
use App\Models\NotebookModel;
use App\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class TagController extends AbstractController
{
    private TagModel $tagModel;
    private NoteModel $noteModel;
    private SectionModel $sectionModel;
    private NotebookModel $notebookModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->tagModel = $container->get(TagModel::class);
        $this->noteModel = $container->get(NoteModel::class);
        $this->sectionModel = $container->get(SectionModel::class);
        $this->notebookModel = $container->get(NotebookModel::class);
    }

    /**
     * Get all tags for the current user.
     */
    public function index(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $tags = $this->tagModel->getAllUserTags($user->getId());

        return \App\Responder\JsonHandler::response($res, $tags);
    }

    /**
     * Add a single tag to a specific note.
     */
    public function add(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $noteId = (int)($args['note_id'] ?? 0);
        $data = $req->getParsedBody();
        $tagName = $data['tag'] ?? '';

        if (empty($tagName)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Tag name is required'], 400);
        }

        // Check ownership of the note
        $note = $this->noteModel->findById($noteId);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        $this->tagModel->addTagToNote($noteId, $user->getId(), $tagName);

        // Redirect back or return a success header for HTMX to trigger a reload
        return $res->withHeader('HX-Refresh', 'true');
    }

    /**
     * Sync tags for a specific note.
     */
    public function sync(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $noteId = (int)($args['note_id'] ?? 0);
        $data = $req->getParsedBody();
        $tagNames = $data['tags'] ?? [];

        if (!is_array($tagNames)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Tags must be an array'], 400);
        }

        // Check ownership of the note
        $note = $this->noteModel->findById($noteId);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        $this->tagModel->syncTags($noteId, $user->getId(), $tagNames);

        return \App\Responder\JsonHandler::response($res, ['message' => 'Tags synchronized']);
    }

    /**
     * Search notes by tags.
     */
    public function search(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $queryParams = $req->getQueryParams();
        
        $tagIds = $queryParams['tag_ids'] ?? [];
        $mode = strtoupper($queryParams['mode'] ?? 'AND');

        if (empty($tagIds)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'At least one tag_id is required'], 400);
        }

        if (!is_array($tagIds)) {
            $tagIds = [$tagIds];
        }

        $notes = $this->tagModel->findNotesByTagIds($user->getId(), array_map('intval', $tagIds), $mode);

        return \App\Responder\JsonHandler::response($res, $notes);
    }

    /**
     * Remove a tag from a specific note.
     */
    public function remove(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $noteId = (int)($args['note_id'] ?? 0);
        $tagId = (int)($args['tag_id'] ?? 0);

        // Check ownership of the note
        $note = $this->noteModel->findById($noteId);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found or access denied'], 404);
        }

        $this->tagModel->removeTagFromNote($noteId, $tagId);

        return \App\Responder\JsonHandler::response($res, ['message' => 'Tag removed']);
    }

    private function checkSectionAccess(int $sectionId, ?User $user): bool
    {
        if (!$user) return false;
        $section = $this->sectionModel->findById($sectionId);
        if (!$section) return false;

        $notebook = $this->notebookModel->findById((int)$section['notebook_id']);
        return $notebook && (int)$notebook['user_id'] === $user->getId();
    }
}
