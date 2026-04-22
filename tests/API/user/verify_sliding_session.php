<?php

declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config' . DS);

require ROOTDIR . 'vendor/autoload.php';

use App\Entities\User;
use App\Models\RegistryModel;
use App\Services\AuthService;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Psr\Log\LoggerInterface;
use Medoo\Medoo;

// 1. Setup Mock DB and Logger
$db = new Medoo(['type' => 'sqlite', 'database' => ':memory:']);

// Use the actual table name 'registry' as defined in RegistryModel
$db->query("CREATE TABLE registry (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_id INTEGER,
    tag_type TEXT,
    endpoint TEXT,
    parent_id INTEGER DEFAULT NULL,
    tag_key TEXT,
    tag_value TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL
)");

$db->query("CREATE TABLE log (id INTEGER PRIMARY KEY, machine TEXT, date DATETIME, level INTEGER, message TEXT)");

$logger = new class implements LoggerInterface {
    public function emergency(\Stringable|string $m, array $c = []): void {}
    public function alert(\Stringable|string $m, array $c = []): void {}
    public function critical(\Stringable|string $m, array $c = []): void {}
    public function error(\Stringable|string $m, array $c = []): void {}
    public function warning(\Stringable|string $m, array $c = []): void {}
    public function notice(\Stringable|string $m, array $c = []): void {}
    public function info(\Stringable|string $m, array $c = []): void { echo "LOG: $m\n"; }
    public function debug(\Stringable|string $m, array $c = []): void {}
    public function log($l, \Stringable|string $m, array $c = []): void {}
};

$registryModel = new RegistryModel($db);
$settings = [
    'secret' => 'test-secret-key-12345678901234567890',
    'issuer' => 'test-issuer',
    'audience' => 'test-audience',
    'lifetime' => 3600 // 1 hour
];

$authService = new AuthService($settings, $registryModel, $logger);

// 2. Mock User with all required fields
$user = new User([
    'id' => 5, 
    'email' => 'test@test.com', 
    'password_hash' => 'hash',
    'roles_mask' => 1
], $registryModel);

echo "Starting Sliding Session Test...\n";

// Test 1: Issue initial token
$tokenStr = $authService->issueToken($user);
$parser = new Parser(new JoseEncoder());
$token = $parser->parse($tokenStr);
$oldJti = $token->claims()->get('jti');
echo "✓ Initial token issued. JTI: $oldJti\n";

// Test 2: Refresh token
$newTokenStr = $authService->refreshSession($user, (string)$oldJti);
$newToken = $parser->parse($newTokenStr);
$newJti = $newToken->claims()->get('jti');

if ($oldJti !== $newJti) {
    echo "✓ New token has different JTI: $newJti\n";
} else {
    echo "✗ New token has same JTI!\n";
}

// Test 3: Check JTI registry
$activeSessions = $user->tags('auth')->getAll('active_session');
if (in_array($newJti, $activeSessions)) {
    echo "✓ New JTI is in registry.\n";
} else {
    echo "✗ New JTI not found in registry!\n";
}

if (!in_array($oldJti, $activeSessions)) {
    echo "✓ Old JTI was removed from registry.\n";
} else {
    echo "✗ Old JTI still exists in registry!\n";
}

echo "Sliding Session tests completed.\n";
