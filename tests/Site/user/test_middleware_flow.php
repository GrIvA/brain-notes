<?php

declare(strict_types=1);

define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config/');

require_once ROOTDIR . 'vendor/autoload.php';

use App\Models\UserModel;
use App\Models\RegistryModel;
use App\Middleware\AuthMiddleware;
use App\Entities\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Medoo\Medoo;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use DI\ContainerBuilder;

// Mock Container
$builder = new ContainerBuilder();
$builder->addDefinitions([
    'settings' => function () {
        return require SETDIR . 'settings.php';
    },
    'dbase' => function ($c) {
        return new Medoo($c->get('settings')['db_connect']);
    },
    UserModel::class => function ($c) {
        return new UserModel($c->get('dbase'));
    },
    RegistryModel::class => function ($c) {
        return new RegistryModel($c->get('dbase'));
    }
]);
$container = $builder->build();

$jwtSettings = $container->get('settings')['jwt'];
$middleware = new AuthMiddleware(
    $container->get(UserModel::class),
    $container->get(RegistryModel::class),
    $jwtSettings['secret']
);

echo "--- ТЕСТ MIDDLEWARE FLOW ---\n";

// 1. Create a valid token and save to registry
$userModel = $container->get(UserModel::class);
$userData = $userModel->findByEmail('admin@example.com'); // Use existing admin
$registryModel = $container->get(RegistryModel::class);
$userEntity = new User($userData, $registryModel);

$config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($jwtSettings['secret']));
$jti = bin2hex(random_bytes(16));
$now = new DateTimeImmutable();
$token = $config->builder()
    ->issuedBy($jwtSettings['issuer'])
    ->identifiedBy($jti)
    ->withClaim('uid', $userData['id'])
    ->expiresAt($now->modify('+1 hour'))
    ->getToken($config->signer(), $config->signingKey());

$jwt = $token->toString();

echo "Step 1: Request WITHOUT JTI in registry (should fail)\n";
$req1 = new ServerRequest('GET', '/home');
$req1 = $req1->withCookieParams(['auth_token' => $jwt]);
$handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
        return new Response(200);
    }
};

try {
    $middleware->process($req1, $handler);
    echo "Result: FAILED (Passed but should have failed)\n";
} catch (\Exception $e) {
    echo "Result: SUCCESS (Caught expected error: " . $e->getMessage() . ")\n";
}

echo "\nStep 2: Add JTI to registry and request (should pass)\n";
$userEntity->tags('auth')->set('active_session', $jti);

try {
    $res2 = $middleware->process($req1, $handler);
    echo "Result: SUCCESS (Status: " . $res2->getStatusCode() . ")\n";
} catch (\Exception $e) {
    echo "Result: FAILED (Error: " . $e->getMessage() . ")\n";
}

echo "\nStep 3: Remove JTI and request (should fail)\n";
$userEntity->tags('auth')->removeByKeyValue('active_session', $jti);

try {
    $middleware->process($req1, $handler);
    echo "Result: FAILED (Passed but should have failed)\n";
} catch (\Exception $e) {
    echo "Result: SUCCESS (Caught expected error: " . $e->getMessage() . ")\n";
}

echo "\n--- ТЕСТ MIDDLEWARE ЗАВЕРШЕНО ---\n";
