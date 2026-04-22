<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\RegistryModel;
use App\Registry\TagRegistry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TestRegistryController extends AbstractController
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var RegistryModel $registryModel */
        $registryModel = $this->container->get(RegistryModel::class);

        // 1. Тест для Юзера (ID=1)
        $userTags = new TagRegistry($registryModel, 'user', 1, 'api');
        $jwt = $userTags->get('jwt');

        // 2. Тест для Зошита (ID=10) - побудова дерева
        $notebookTags = new TagRegistry($registryModel, 'notebook', 10);
        $tree = $notebookTags->tree();

        $data = [
            'status' => 'ok',
            'user_test' => [
                'entity_id' => 1,
                'endpoint' => 'api',
                'jwt_token' => $jwt
            ],
            'notebook_test' => [
                'entity_id' => 10,
                'tree' => $tree
            ]
        ];

        $response->getBody()->write((string)json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
