<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\NoteModel;
use App\Models\SectionModel;
use App\Models\NotebookModel;
use App\Models\TagModel;
use App\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class NoteController extends AbstractController
{
    private NoteModel $noteModel;
    private SectionModel $sectionModel;
    private NotebookModel $notebookModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->noteModel = $container->get(NoteModel::class);
        $this->sectionModel = $container->get(SectionModel::class);
        $this->notebookModel = $container->get(NotebookModel::class);
    }

    public function store(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $data = $req->getParsedBody();

        $sectionId = (int)($data['section_id'] ?? 0);
        if (!$this->checkSectionAccess($sectionId, $user)) {
            return $this->jsonResponse($res, ['error' => 'Unauthorized access to section'], 403);
        }

        $id = $this->noteModel->create([
            'section_id' => $sectionId,
            'title' => $data['title'] ?? 'New Note',
            'content' => $data['content'] ?? '',
            'attributes' => (int)($data['attributes'] ?? 0)
        ]);

        return $this->jsonResponse($res, ['id' => $id, 'message' => 'Note created'], 201);
    }

    public function show(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);

        $note = $this->noteModel->findById($id);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return $this->jsonResponse($res, ['error' => 'Note not found'], 404);
        }

        $tagModel = $this->container->get(TagModel::class);
        $note['tags'] = $tagModel->getTagsByNoteId($id);

        return $this->jsonResponse($res, $note);
    }

    public function update(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);
        $data = $req->getParsedBody();

        $note = $this->noteModel->findById($id);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return $this->jsonResponse($res, ['error' => 'Note not found'], 404);
        }

        // If changing section, check access to target section
        if (isset($data['section_id'])) {
            if (!$this->checkSectionAccess((int)$data['section_id'], $user)) {
                return $this->jsonResponse($res, ['error' => 'Unauthorized access to target section'], 403);
            }
        }

        $this->noteModel->update($id, $data);
        return $this->jsonResponse($res, ['message' => 'Note updated']);
    }

    public function delete(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);

        $note = $this->noteModel->findById($id);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return $this->jsonResponse($res, ['error' => 'Note not found'], 404);
        }

        $this->noteModel->delete($id);
        return $this->jsonResponse($res, ['message' => 'Note deleted']);
    }

    public function move(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $data = $req->getParsedBody();

        $targetSectionId = (int)($data['target_section_id'] ?? 0);
        if (!$this->checkSectionAccess($targetSectionId, $user)) {
            return $this->jsonResponse($res, ['error' => 'Unauthorized access to target section'], 403);
        }

        $noteIds = $data['note_ids'] ?? null;
        $sourceSectionId = $data['source_section_id'] ?? null;

        if ($noteIds && is_array($noteIds)) {
            $forbiddenNotes = [];
            $owners = [];

            foreach ($noteIds as $noteId) {
                $note = $this->noteModel->findById((int)$noteId);
                if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
                    $forbiddenNotes[] = $noteId;
                    if ($note) {
                        $owners[] = $this->getSectionOwnerId((int)$note['section_id']);
                    }
                }
            }

            if (!empty($forbiddenNotes)) {
                $this->log->alert(sprintf(
                    'SECURITY ALERT: User ID %d attempted to move notes %s owned by users %s',
                    $user->getId(),
                    implode(', ', $forbiddenNotes),
                    implode(', ', array_unique($owners))
                ));
                return $this->jsonResponse($res, ['error' => 'Unauthorized access to some notes'], 403);
            }

            $count = $this->noteModel->moveNotes($noteIds, $targetSectionId);
            return $this->jsonResponse($res, ['message' => "Moved $count notes"]);
        } elseif ($sourceSectionId) {
            if (!$this->checkSectionAccess((int)$sourceSectionId, $user)) {
                return $this->jsonResponse($res, ['error' => 'Unauthorized access to source section'], 403);
            }
            $count = $this->noteModel->migrateAll((int)$sourceSectionId, $targetSectionId);
            return $this->jsonResponse($res, ['message' => "Migrated $count notes"]);
        }

        return $this->jsonResponse($res, ['error' => 'Invalid parameters'], 400);
    }

    /**
     * Return a list of notes as HTML fragment.
     */
    public function listFiltered(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $queryParams = $req->getQueryParams();
        
        $criteria = [
            'tag_ids' => $queryParams['tag_ids'] ?? [],
            'section_id' => $queryParams['section_id'] ?? null,
            'user_id' => $user ? $user->getId() : null
        ];

        $notes = $this->noteModel->findFiltered($criteria);

        $tmpl = $this->container->get('tmpl');
        $html = $tmpl->fetch('components/note_list.tpl', [
            'notes' => $notes
        ]);

        $res->getBody()->write($html);
        return $res;
    }

    private function getSectionOwnerId(int $sectionId): ?int
    {
        $section = $this->sectionModel->findById($sectionId);
        if (!$section) return null;

        $notebook = $this->notebookModel->findById((int)$section['notebook_id']);
        return $notebook ? (int)$notebook['user_id'] : null;
    }

    private function checkSectionAccess(int $sectionId, ?User $user): bool
    {
        if (!$user) return false;

        $section = $this->sectionModel->findById($sectionId);
        if (!$section) return false;

        $notebook = $this->notebookModel->findById((int)$section['notebook_id']);
        return $notebook && (int)$notebook['user_id'] === $user->getId();
    }

    private function jsonResponse(ResponseInterface $res, $data, int $status = 200): ResponseInterface
    {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
