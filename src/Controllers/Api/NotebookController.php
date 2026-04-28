<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\NotebookModel;
use App\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class NotebookController extends AbstractController
{
    private NotebookModel $notebookModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->notebookModel = $container->get(NotebookModel::class);
    }

    public function index(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($res, ['error' => 'Unauthorized'], 401);
        }
        $notebooks = $this->notebookModel->findByUserId($user->getId());

        return $this->jsonResponse($res, $notebooks);
    }

    public function store(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($res, ['error' => 'Unauthorized'], 401);
        }
        $data = $req->getParsedBody();

        $title = $data['title'] ?? '';
        $attributes = (int)($data['attributes'] ?? 0);

        if (empty($title)) {
            return $this->jsonResponse($res, ['error' => 'Title is required'], 400);
        }

        $id = $this->notebookModel->create([
            'user_id' => $user->getId(),
            'title' => $title,
            'attributes' => $attributes
        ]);

        return $this->jsonResponse($res, ['id' => $id, 'message' => 'Notebook created'], 201);
    }

    public function delete(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($res, ['error' => 'Unauthorized'], 401);
        }
        $id = (int)($args['id'] ?? 0);

        $notebook = $this->notebookModel->findById($id);
        if (!$notebook || $notebook['user_id'] !== $user->getId()) {
            return $this->jsonResponse($res, ['error' => 'Notebook not found'], 404);
        }

        $this->notebookModel->delete($id);
        return $this->jsonResponse($res, ['message' => 'Notebook deleted']);
    }

    public function update(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($res, ['error' => 'Unauthorized'], 401);
        }
        $id = (int)($args['id'] ?? 0);
        $data = $req->getParsedBody();

        $notebook = $this->notebookModel->findById($id);
        if (!$notebook || (int)$notebook['user_id'] !== $user->getId()) {
            return $this->jsonResponse($res, ['error' => 'Notebook not found'], 404);
        }

        $attributes = (int)($data['attributes'] ?? $notebook['attributes']);
        $title = $data['title'] ?? $notebook['title'];

        // Якщо встановлюється прапор ATTR_DEFAULT, скидаємо його у інших зошитів
        if ($attributes & NotebookModel::ATTR_DEFAULT && !((int)$notebook['attributes'] & NotebookModel::ATTR_DEFAULT)) {
            $this->notebookModel->resetDefaultNotebook($user->getId());
        }

        $this->notebookModel->update($id, [
            'title' => $title,
            'attributes' => $attributes
        ]);

        return $this->jsonResponse($res, ['message' => 'Notebook updated']);
    }

    private function jsonResponse(ResponseInterface $res, $data, int $status = 200): ResponseInterface
    {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
