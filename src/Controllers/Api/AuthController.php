<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\UserModel;
use App\Models\RegistryModel;
use App\Entities\User;
use App\Enums\UserRole;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class AuthController extends AbstractController
{
    private UserModel $userModel;
    private RegistryModel $registryModel;
    private AuthService $authService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->userModel = $container->get(UserModel::class);
        $this->registryModel = $container->get(RegistryModel::class);
        $this->authService = $container->get(AuthService::class);
    }

    public function login(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $this->parseArgs($req, $args);
        $data = $req->getParsedBody();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->errorResponse($res, 'Email та пароль обов\'язкові');
        }

        $userData = $this->userModel->findByEmail($email);
        if (!$userData || !password_verify($password, $userData['password_hash'])) {
            return $this->errorResponse($res, 'Невірний email або пароль');
        }

        $user = new User($userData, $this->registryModel);
        
        $token = $this->authService->issueToken($user);
        $res = $res->withHeader('Set-Cookie', $this->authService->getCookieHeader($token));

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            return $res->withHeader('HX-Redirect', '/')->withStatus(200);
        }

        return $res->withHeader('Location', '/')->withStatus(302);
    }

    public function register(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $this->parseArgs($req, $args);
        $data = $req->getParsedBody();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $name = $data['name'] ?? '';

        if (empty($email) || empty($password) || empty($name)) {
            return $this->errorResponse($res, 'Всі поля обов\'язкові');
        }

        if ($this->userModel->findByEmail($email)) {
            return $this->errorResponse($res, 'Користувач вже існує');
        }

        $userId = $this->userModel->create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'roles_mask' => UserRole::USER->value
        ]);

        if (!$userId) {
            return $this->errorResponse($res, 'Помилка реєстрації');
        }

        $userData = $this->userModel->findById((int)$userId);
        $user = new User($userData, $this->registryModel);

        $token = $this->authService->issueToken($user);
        $res = $res->withHeader('Set-Cookie', $this->authService->getCookieHeader($token));

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            return $res->withHeader('HX-Redirect', '/')->withStatus(200);
        }

        return $res->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $jti = $req->getAttribute('jti');

        if ($user instanceof User && $jti) {
            $this->authService->invalidateSession($user, $jti);
        }

        $res = $res->withHeader('Set-Cookie', 'auth_token=; Path=/; HttpOnly; Max-Age=0');

        if ($req->hasHeader('HX-Request')) {
            return $res->withHeader('HX-Redirect', '/')->withStatus(200);
        }

        return $res->withHeader('Location', '/')->withStatus(302);
    }

    private function errorResponse(ResponseInterface $res, string $message): ResponseInterface
    {
        $res->getBody()->write($message);
        return $res->withHeader('Content-Type', 'text/plain')->withStatus(400);
    }
}
