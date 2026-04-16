<?php

declare(strict_types=1);

define('ROOTDIR', __DIR__ . '/');
define('SETDIR', ROOTDIR . 'config/');

require_once ROOTDIR . 'vendor/autoload.php';

use App\Models\UserModel;
use App\Models\RegistryModel;
use App\Middleware\AuthMiddleware;
use App\Enums\UserRole;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Medoo\Medoo;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;

$settings = require SETDIR . 'settings.php';
$jwtSettings = $settings['jwt'];
$db = new Medoo($settings['db_connect']);

$userModel = new UserModel($db);
$registryModel = new RegistryModel($db);

$user = $userModel->findByEmail('admin@example.com');
if (!$user) {
    die("Run verify_user_creation.php first!\n");
}

echo "--- ГЕНЕРАЦІЯ ТЕСТОВОГО ТОКЕНА ---\n";

$config = Configuration::forSymmetricSigner(
    new Sha256(),
    InMemory::plainText($jwtSettings['secret'])
);

$now = new DateTimeImmutable();
$token = $config->builder()
    ->issuedBy($jwtSettings['issuer'])
    ->permittedFor($jwtSettings['audience'])
    ->issuedAt($now)
    ->expiresAt($now->modify('+1 hour'))
    ->withClaim('uid', $user['id'])
    ->getToken($config->signer(), $config->signingKey());

$jwt = $token->toString();
echo "Generated JWT for admin: $jwt\n\n";

echo "--- ТЕСТ MIDDLEWARE (ADMIN ROLE REQUIRED) ---\n";

$middleware = new AuthMiddleware(
    $userModel,
    $registryModel,
    $jwtSettings['secret'],
    [UserRole::ADMIN]
);

$request = new ServerRequest('GET', '/api/v1/test', ['Authorization' => 'Bearer ' . $jwt]);
$handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
        echo "SUCCESS: Middleware passed. User ID in request: " . $request->getAttribute('user')->getId() . "\n";
        return new Response();
    }
};

try {
    $middleware->process($request, $handler);
} catch (\Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}

echo "\n--- ТЕСТ MIDDLEWARE (PROVIDER ROLE REQUIRED - SHOULD FAIL) ---\n";

$middlewareFail = new AuthMiddleware(
    $userModel,
    $registryModel,
    $jwtSettings['secret'],
    [UserRole::PROVIDER]
);

try {
    $middlewareFail->process($request, $handler);
} catch (\Exception $e) {
    echo "SUCCESS (Expected Fail): " . $e->getMessage() . "\n";
}
