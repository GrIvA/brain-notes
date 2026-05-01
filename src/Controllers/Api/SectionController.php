<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\SectionModel;
use App\Models\NotebookModel;
use App\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class SectionController extends AbstractController
{
    private SectionModel $sectionModel;
    private NotebookModel $notebookModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->sectionModel = $container->get(SectionModel::class);
        $this->notebookModel = $container->get(NotebookModel::class);
    }

    public function tree(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $notebookId = (int)($args['id'] ?? 0);

        if (!$this->checkNotebookOwnership($notebookId, $user->getId())) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Notebook not found'], 404);
        }

        $tree = $this->sectionModel->getTree($notebookId);
        return \App\Responder\JsonHandler::response($res, $tree);
    }

    public function store(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $data = $req->getParsedBody();

        $notebookId = (int)($data['notebook_id'] ?? 0);
        $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
        $title = $data['title'] ?? '';

        if (!$this->checkNotebookOwnership($notebookId, $user->getId())) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Notebook not found or access denied'], 404);
        }

        if ($parentId !== null && !$this->sectionModel->findById($parentId)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Parent section not found'], 400);
        }

        $id = $this->sectionModel->create([
            'notebook_id' => $notebookId,
            'parent_id' => $parentId,
            'title' => $title,
            'sort_order' => (int)($data['sort_order'] ?? 0)
        ]);

        return \App\Responder\JsonHandler::response($res, ['id' => $id, 'message' => 'Section created'], 201);
    }

    public function move(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);
        $data = $req->getParsedBody();
        $newParentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;

        $section = $this->sectionModel->findById($id);
        if (!$section || !$this->checkNotebookOwnership((int)$section['notebook_id'], $user->getId())) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Section not found'], 404);
        }

        if ($this->sectionModel->move($id, $newParentId)) {
            return \App\Responder\JsonHandler::response($res, ['message' => 'Section moved']);
        }

        return \App\Responder\JsonHandler::response($res, ['error' => 'Invalid move operation (possible cycle or self-reference)'], 400);
    }

    public function delete(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        $id = (int)($args['id'] ?? 0);

        $section = $this->sectionModel->findById($id);
        if (!$section || !$this->checkNotebookOwnership((int)$section['notebook_id'], $user->getId())) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Section not found'], 404);
        }

        $this->sectionModel->delete($id);
        return \App\Responder\JsonHandler::response($res, ['message' => 'Section deleted']);
    }

    private function checkNotebookOwnership(int $notebookId, int $userId): bool
    {
        $notebook = $this->notebookModel->findById($notebookId);
        return $notebook && $notebook['user_id'] === $userId;
    }
}
