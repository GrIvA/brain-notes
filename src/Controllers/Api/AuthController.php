<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\UserModel;
use App\Models\RegistryModel;
use App\Entities\User;
use App\Enums\UserRole;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class AuthController extends AbstractController
{
    private UserModel $userModel;
    private RegistryModel $registryModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->userModel = $container->get(UserModel::class);
        $this->registryModel = $container->get(RegistryModel::class);
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
        return $this->authenticateUser($user, $res);
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

        return $this->authenticateUser($user, $res);
    }

    public function logout(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $user = $req->getAttribute('user');
        $jti = $req->getAttribute('jti');

        if ($user instanceof User && $jti) {
            $user->tags('auth')->removeByKeyValue('active_session', $jti);
        }

        $res = $res->withHeader('Set-Cookie', 'auth_token=; Path=/; HttpOnly; Max-Age=0');

        if ($req->hasHeader('HX-Request')) {
            return $res->withHeader('HX-Redirect', '/')->withStatus(200);
        }

        return $res->withHeader('Location', '/')->withStatus(302);
    }
    // PRIVATE

    private function authenticateUser(User $user, ResponseInterface $res): ResponseInterface
    {
        $jwtSettings = $this->settings['jwt'];
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSettings['secret'])
        );

        $jti = bin2hex(random_bytes(16));
        $now = new \DateTimeImmutable();
        $token = $config->builder()
            ->issuedBy($jwtSettings['issuer'])
            ->permittedFor($jwtSettings['audience'])
            ->identifiedBy($jti)
            ->issuedAt($now)
            ->expiresAt($now->modify('+24 hours'))
            ->withClaim('uid', $user->getId())
            ->getToken($config->signer(), $config->signingKey());

        $jwt = $token->toString();

        // Save JTI to Registry
        $user->tags('auth')->set('active_session', $jti, null, (int)$now->getTimestamp());

        // Set Cookie
        $cookie = "auth_token=$jwt; Path=/; HttpOnly; Max-Age=" . (24 * 60 * 60) . "; SameSite=Lax";

        $res = $res->withHeader('Set-Cookie', $cookie);

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
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
