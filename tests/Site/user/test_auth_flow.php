<?php

declare(strict_types=1);

define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config/');

require_once ROOTDIR . 'vendor/autoload.php';

use App\Models\UserModel;
use App\Models\RegistryModel;
use App\Controllers\Api\AuthController;
use App\Entities\User;
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

$authController = new AuthController($container);
$db = $container->get('dbase');

echo "--- ТЕСТ АВТЕНТИФІКАЦІЇ ---\n";

// 1. Реєстрація
$email = 'test_' . time() . '@example.com';
$password = 'password123';
$name = 'Test User';

$req = new ServerRequest('POST', '/register');
$req = $req->withParsedBody([
    'email' => $email,
    'password' => $password,
    'name' => $name
]);

$res = new Response();
$res = $authController->register($req, $res, []);

echo "Registration status: " . $res->getStatusCode() . "\n";
$cookieHeader = $res->getHeaderLine('Set-Cookie');
echo "Set-Cookie: $cookieHeader\n";

if ($res->getStatusCode() !== 302) {
    die("Registration failed!\n");
}

// Extract JWT from cookie
preg_match('/auth_token=([^;]+)/', $cookieHeader, $matches);
$jwt = $matches[1] ?? null;

if (!$jwt) {
    die("JWT not found in cookie!\n");
}

// 2. Перевірка JTI в реєстрі
$userModel = $container->get(UserModel::class);
$userData = $userModel->findByEmail($email);
$registryModel = $container->get(RegistryModel::class);
$userEntity = new User($userData, $registryModel);

$sessions = $userEntity->tags('auth')->getAll('active_session');
echo "Active sessions count: " . count($sessions) . "\n";
if (count($sessions) === 0) {
    die("JTI not found in TagRegistry!\n");
}

// 3. Logout
$jti = $sessions[0]; // Assuming this is the one we just created
$reqLogout = new ServerRequest('POST', '/logout');
$reqLogout = $reqLogout->withAttribute('user', $userEntity);
$reqLogout = $reqLogout->withAttribute('jti', $jti);

$resLogout = new Response();
$resLogout = $authController->logout($reqLogout, $resLogout, []);

echo "Logout status: " . $resLogout->getStatusCode() . "\n";
$cookieLogout = $resLogout->getHeaderLine('Set-Cookie');
echo "Logout Set-Cookie: $cookieLogout\n";

// 4. Перевірка що JTI видалено
$sessionsAfter = $userEntity->tags('auth')->getAll('active_session');
echo "Active sessions count after logout: " . count($sessionsAfter) . "\n";

if (count($sessionsAfter) >= count($sessions)) {
    die("JTI was not removed from TagRegistry!\n");
}

echo "\n--- ТЕСТ УСПІШНО ЗАВЕРШЕНО ---\n";
