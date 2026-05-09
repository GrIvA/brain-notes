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
    private \App\Services\EncryptionService $encryptionService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->noteModel = $container->get(NoteModel::class);
        $this->sectionModel = $container->get(SectionModel::class);
        $this->notebookModel = $container->get(NotebookModel::class);
        $this->encryptionService = $container->get(\App\Services\EncryptionService::class);
    }

    public function decryptUI(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);
        $tmpl = $this->container->get('tmpl');

        $html = $tmpl->fetch('components/note_password_modal.tpl', [
            'noteId' => $id
        ]);

        $res->getBody()->write($html);
        return $res;
    }

    public function decrypt(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);
        $data = $req->getParsedBody();
        $password = $data['password'] ?? '';
        $isHtmx = $req->hasHeader('HX-Request');

        $noteData = $this->noteModel->findById($id);
        if (!$noteData) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        $registryModel = $this->container->get(\App\Models\RegistryModel::class);
        $note = new \App\Entities\Note($noteData, $registryModel);

        if (!$this->checkSectionAccess((int)$noteData['section_id'], $user)) {
            $isPublic = $note->isPublic();
            if (!$isPublic && (!$user || !$this->checkSectionAccess((int)$noteData['section_id'], $user))) {
                return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
            }
        }

        $decryptedContent = $this->encryptionService->decrypt($note->getRawContent(), $password);

        if ($decryptedContent === null) {
            if ($isHtmx) {
                $res->getBody()->write('<i class="fa-solid fa-circle-exclamation"></i> Невірний пароль');
                return $res->withHeader('HX-Retarget', '#decrypt-error');
            }
            return \App\Responder\JsonHandler::response($res, ['error' => 'Invalid password'], 403);
        }

        if ($isHtmx) {
            $tmpl = $this->container->get('tmpl');
            $html = $tmpl->compileCode('{$content|markdown}')->fetch([
                'content' => $decryptedContent
            ]);
            $res->getBody()->write($html);
            return $res->withHeader('HX-Trigger', '{"close-decrypt-modal": true}');
        }

        return \App\Responder\JsonHandler::response($res, [
            'content' => $decryptedContent
        ]);
    }

    public function editUI(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized'], 401);
        }
        $id = (int)($args['id'] ?? 0);
        $note = $this->noteModel->findById($id);

        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        $tmpl = $this->container->get('tmpl');
        $html = $tmpl->fetch('components/note_edit_form.tpl', [
            'note' => $note
        ]);

        $res->getBody()->write($html);
        return $res;
    }

    public function viewFragment(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);
        $note = $this->noteModel->findById($id);

        if (!$note) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        // Check access (could be public)
        $registryModel = $this->container->get(\App\Models\RegistryModel::class);
        $noteEntity = new \App\Entities\Note($note, $registryModel);

        if (!$this->checkSectionAccess((int)$note['section_id'], $user) && !$noteEntity->isPublic()) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Access denied'], 403);
        }

        $tmpl = $this->container->get('tmpl');
        $html = $tmpl->compileCode('{$content|markdown}')->fetch([
            'content' => $note['content']
        ]);

        $res->getBody()->write($html);
        return $res;
    }

    public function createUI(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized'], 401);
        }
        $queryParams = $req->getQueryParams();
        $sectionId = (int)($queryParams['section_id'] ?? 0);
        $tmpl = $this->container->get('tmpl');
        
        $html = $tmpl->fetch('components/note_create_modal.tpl', [
            'sectionId' => $sectionId
        ]);

        $res->getBody()->write($html);
        return $res;
    }

    public function moveUI(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized'], 401);
        }
        $id = (int)($args['id'] ?? 0);
        $tmpl = $this->container->get('tmpl');
        
        // For now, return a simple modal or placeholder
        $html = '<dialog open id="move-note-modal"><article><header><a href="#close" class="close" hx-on:click="document.getElementById(\'move-note-modal\').remove()"></a>Перенесення нотатки</header><p>Тут буде дерево розділів для вибору (TBD).</p><footer><button class="secondary outline" hx-on:click="document.getElementById(\'move-note-modal\').remove()">Закрити</button></footer></article></dialog>';

        $res->getBody()->write($html);
        return $res;
    }

    public function store(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized'], 401);
        }
        $data = $req->getParsedBody();

        $sectionId = (int)($data['section_id'] ?? 0);
        if (!$this->checkSectionAccess($sectionId, $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized access to section'], 403);
        }

        $content = $data['content'] ?? '';
        $attributes = (int)($data['attributes'] ?? 0);
        $password = $data['password'] ?? '';

        if (!empty($password)) {
            $content = $this->encryptionService->encrypt($content, $password);
            $attributes |= NoteModel::ATTR_ENCRYPT;
        }

        $id = $this->noteModel->create([
            'section_id' => $sectionId,
            'title' => $data['title'] ?? 'New Note',
            'content' => $content,
            'attributes' => $attributes
        ]);

        if ($req->hasHeader('HX-Request')) {
            return $res->withHeader('HX-Redirect', "/note/$id");
        }

        return \App\Responder\JsonHandler::response($res, ['id' => $id, 'message' => 'Note created'], 201);
    }

    public function show(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);
        $queryParams = $req->getQueryParams();
        $password = $queryParams['password'] ?? ($req->getParsedBody()['password'] ?? '');

        $noteData = $this->noteModel->findById($id);
        if (!$noteData || !$this->checkSectionAccess((int)$noteData['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        $registryModel = $this->container->get(\App\Models\RegistryModel::class);
        $noteEntity = new \App\Entities\Note($noteData, $registryModel);
        $note = $noteEntity->toArray();

        // If password is provided, try to decrypt. If it fails — return 403.
        if ($noteEntity->isEncrypted() && !empty($password)) {
            $decrypted = $this->encryptionService->decrypt($noteEntity->getRawContent(), $password);
            if ($decrypted === null) {
                return \App\Responder\JsonHandler::response($res, ['error' => 'Invalid password'], 403);
            }
            $note['content'] = $decrypted;
        }

        $tagModel = $this->container->get(TagModel::class);
        $note['tags'] = $tagModel->getTagsByNoteId($id);

        return \App\Responder\JsonHandler::response($res, $note);
    }

    public function update(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);
        $data = $req->getParsedBody();

        $note = $this->noteModel->findById($id);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        // Handle encryption/decryption on update
        $currentContent = $note['content'];
        $currentAttributes = (int)$note['attributes'];
        $newContent = $data['content'] ?? $currentContent;
        $password = $data['password'] ?? '';
        $oldPassword = $data['old_password'] ?? '';

        if (!empty($password) || (isset($data['content']) && ($currentAttributes & NoteModel::ATTR_ENCRYPT)) || (!empty($oldPassword) && isset($data['password']))) {
            // Case 1: Was encrypted
            if ($currentAttributes & NoteModel::ATTR_ENCRYPT) {
                if (empty($oldPassword)) {
                    return \App\Responder\JsonHandler::response($res, ['error' => 'Old password required for encrypted note'], 400);
                }
                // Verify old password
                $decrypted = $this->encryptionService->decrypt($currentContent, $oldPassword);
                if ($decrypted === null) {
                    return \App\Responder\JsonHandler::response($res, ['error' => 'Invalid old password'], 403);
                }

                if (empty($password) && isset($data['password'])) {
                    // ACTION: Remove encryption
                    $data['content'] = $data['content'] ?? $decrypted;
                    $data['attributes'] = $currentAttributes & ~NoteModel::ATTR_ENCRYPT;
                } else {
                    // ACTION: Re-encrypt (either with same password or new one)
                    $effectivePassword = !empty($password) ? $password : $oldPassword;
                    $contentToEncrypt = $data['content'] ?? $decrypted;
                    $data['content'] = $this->encryptionService->encrypt($contentToEncrypt, $effectivePassword);
                }
            }
            // Case 2: Not encrypted, but setting a password now
            elseif (!empty($password)) {
                $data['content'] = $this->encryptionService->encrypt($newContent, $password);
                $data['attributes'] = $currentAttributes | NoteModel::ATTR_ENCRYPT;
            }
        }

        // Remove auxiliary fields that are not in the database table
        unset($data['password'], $data['old_password']);

        // If changing section, check access to target section
        if (isset($data['section_id'])) {
            if (!$this->checkSectionAccess((int)$data['section_id'], $user)) {
                return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized access to target section'], 403);
            }
        }

        $this->noteModel->update($id, $data);

        if ($req->hasHeader('HX-Request')) {
            if (count($data) === 1 && isset($data['title'])) {
                $res->getBody()->write($data['title']);
                return $res;
            }
            if (isset($data['content']) && !isset($data['password'])) {
                $tmpl = $this->container->get('tmpl');
                $html = $tmpl->compileCode('{$content|markdown}')->fetch([
                    'content' => $data['content']
                ]);
                $res->getBody()->write($html);
                return $res;
            }
        }

        return \App\Responder\JsonHandler::response($res, ['message' => 'Note updated']);
    }

    public function delete(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);

        $note = $this->noteModel->findById($id);
        if (!$note || !$this->checkSectionAccess((int)$note['section_id'], $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Note not found'], 404);
        }

        $this->noteModel->delete($id);

        if ($req->hasHeader('HX-Request')) {
            return $res->withHeader('HX-Redirect', '/home');
        }

        return \App\Responder\JsonHandler::response($res, ['message' => 'Note deleted']);
    }

    public function move(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $data = $req->getParsedBody();

        $targetSectionId = (int)($data['target_section_id'] ?? 0);
        if (!$this->checkSectionAccess($targetSectionId, $user)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized access to target section'], 403);
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
                return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized access to some notes'], 403);
            }

            $count = $this->noteModel->moveNotes($noteIds, $targetSectionId);
            return \App\Responder\JsonHandler::response($res, ['message' => "Moved $count notes"]);
        } elseif ($sourceSectionId) {
            if (!$this->checkSectionAccess((int)$sourceSectionId, $user)) {
                return \App\Responder\JsonHandler::response($res, ['error' => 'Unauthorized access to source section'], 403);
            }
            $count = $this->noteModel->migrateAll((int)$sourceSectionId, $targetSectionId);
            return \App\Responder\JsonHandler::response($res, ['message' => "Migrated $count notes"]);
        }

        return \App\Responder\JsonHandler::response($res, ['error' => 'Invalid parameters'], 400);
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
            'user_id' => $user ? $user->getId() : null
        ];

        if (!empty($queryParams['section_id'])) {
            $criteria['section_id'] = $this->sectionModel->getAllChildIds((int)$queryParams['section_id']);
        }

        $notesData = $this->noteModel->findFiltered($criteria);
        $notes = [];

        // Attach tags to each note. Content hiding is now handled by the Note entity.
        $tagModel = $this->container->get(TagModel::class);
        $registryModel = $this->container->get(\App\Models\RegistryModel::class);

        foreach ($notesData as $data) {
            $noteEntity = new \App\Entities\Note($data, $registryModel);
            $note = $noteEntity->toArray();
            $note['tags'] = $tagModel->getTagsByNoteId((int)$note['id']);
            $notes[] = $note;
        }

        $tmpl = $this->container->get('tmpl');
        $view = $queryParams['view'] ?? '';
        $template = $view === 'modal' ? 'components/modal_note_list.tpl' : 'components/note_list.tpl';

        $html = $tmpl->fetch($template, [
            'notes' => $notes,
            'user' => $user ? $user->toArray() : null
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
}
