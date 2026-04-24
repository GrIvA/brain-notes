<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DS', DIRECTORY_SEPARATOR);
define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config' . DS);

require ROOTDIR . 'vendor/autoload.php';

use App\Models\IpModel;
use Medoo\Medoo;
use Psr\Log\LoggerInterface;
use App\Middleware\IpSecurityMiddleware;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Mock Logger matching PSR-3
$logger = new class implements LoggerInterface {
    public array $logs = [];
    public function emergency(\Stringable|string $m, array $c = []): void { $this->logs[] = "EMERGENCY: $m"; }
    public function alert(\Stringable|string $m, array $c = []): void { $this->logs[] = "ALERT: $m"; }
    public function critical(\Stringable|string $m, array $c = []): void { $this->logs[] = "CRITICAL: $m"; }
    public function error(\Stringable|string $m, array $c = []): void { $this->logs[] = "ERROR: $m"; }
    public function warning(\Stringable|string $m, array $c = []): void { $this->logs[] = "WARNING: $m"; }
    public function notice(\Stringable|string $m, array $c = []): void { $this->logs[] = "NOTICE: $m"; }
    public function info(\Stringable|string $m, array $c = []): void { $this->logs[] = "INFO: $m"; }
    public function debug(\Stringable|string $m, array $c = []): void { $this->logs[] = "DEBUG: $m"; }
    public function log($l, \Stringable|string $m, array $c = []): void { $this->logs[] = "$l: $m"; }
};

// Setup in-memory SQLite
$db = new Medoo(['type' => 'sqlite', 'database' => ':memory:']);

// Create tables
$db->query("CREATE TABLE ip_statuses (id INTEGER PRIMARY KEY, name TEXT)");
$db->query("INSERT INTO ip_statuses (id, name) VALUES (1, 'normal'), (2, 'allow'), (3, 'disabled')");
$db->query("CREATE TABLE track_ips (id INTEGER PRIMARY KEY, remote_addr TEXT UNIQUE, ip_status_id INTEGER, updated_at DATETIME)");
$db->query("CREATE TABLE ip_fails (id INTEGER PRIMARY KEY, remote_addr TEXT UNIQUE, fail_count INTEGER, first_fail_at DATETIME)");

$model = new IpModel($db, $logger);
$testIp = '127.0.0.1';

echo "Running security tests for IpModel...\n";

// 1. Test: First fail
$model->logFail($testIp, 3, 60);
$fail = $db->get('ip_fails', '*', ['remote_addr' => $testIp]);
if ($fail && (int)$fail['fail_count'] === 1) {
    echo "✓ Test 1 Passed: First fail recorded\n";
} else {
    echo "✗ Test 1 Failed\n";
}

// 2. Test: Increment fails within window
$model->logFail($testIp, 3, 60);
$fail = $db->get('ip_fails', '*', ['remote_addr' => $testIp]);
if ((int)$fail['fail_count'] === 2) {
    echo "✓ Test 2 Passed: Fail counter incremented\n";
} else {
    echo "✗ Test 2 Failed\n";
}

// 3. Test: Auto-block after limit (limit = 3)
$model->logFail($testIp, 3, 60);
if ($model->isBlocked($testIp)) {
    echo "✓ Test 3 Passed: IP auto-blocked after 3 fails\n";
    if (str_contains($logger->logs[0], 'ALERT')) {
        echo "✓ Test 3.1 Passed: Alert logged: " . $logger->logs[0] . "\n";
    }
} else {
    echo "✗ Test 3 Failed\n";
}

// 4. Test: Admin set status and log
$model->setStatus($testIp, IpModel::STATUS_ALLOW, 1);
if (!$model->isBlocked($testIp) && $model->getStatus($testIp) === 'allow') {
    echo "✓ Test 4 Passed: Admin changed status to allow\n";
    if (str_contains($logger->logs[1], 'SECURITY')) {
        echo "✓ Test 4.1 Passed: Admin action logged: " . $logger->logs[1] . "\n";
    }
} else {
    echo "✗ Test 4 Failed\n";
}

// 5. Test: Reset window after time interval
$testIp2 = '192.168.1.1';
$db->insert('ip_fails', [
    'remote_addr' => $testIp2,
    'fail_count' => 2,
    'first_fail_at' => date('Y-m-d H:i:s', time() - 3700) // 61 minutes ago
]);

$model->logFail($testIp2, 3, 60); // Should reset to 1
$fail2 = $db->get('ip_fails', '*', ['remote_addr' => $testIp2]);
if ($fail2 && (int)$fail2['fail_count'] === 1) {
    echo "✓ Test 5 Passed: Window reset after interval expired\n";
}

// 6. Test: Middleware interception of 404
echo "Running tests for IpSecurityMiddleware...\n";

$simpleContainer = new class($model, $db) implements \Psr\Container\ContainerInterface {
    private $m; private $d;
    public function __construct($m, $d) { $this->m = $m; $this->d = $d; }
    public function get(string $id) { 
        if ($id === App\Models\IpModel::class) return $this->m;
        if ($id === 'settings') return ['security' => ['ip_blocking' => ['max_404_attempts' => 2, 'interval_minutes' => 1]], 'public' => ['main_contact_email' => 'admin@test.com']];
        return null;
    }
    public function has(string $id): bool { return true; }
};

$middleware = new IpSecurityMiddleware($simpleContainer);
$testIp3 = '10.0.0.1';

// Handler that returns 404
$handler404 = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface {
        return (new Response())->withStatus(404);
    }
};

$req = new ServerRequest('GET', '/not-found', [], null, '1.1', ['REMOTE_ADDR' => $testIp3]);

// First 404
$middleware->process($req, $handler404);
$fail3 = $db->get('ip_fails', '*', ['remote_addr' => $testIp3]);
if ($fail3 && (int)$fail3['fail_count'] === 1) {
    echo "✓ Test 6.1 Passed: Middleware logged first 404\n";
}

// Second 404 (should block, limit is 2)
$middleware->process($req, $handler404);
if ($model->isBlocked($testIp3)) {
    echo "✓ Test 6.2 Passed: Middleware auto-blocked IP after 2 x 404\n";
}

// Third request (should be rejected with 403 by middleware)
$resBlocked = $middleware->process($req, $handler404);
if ($resBlocked->getStatusCode() === 403) {
    echo "✓ Test 6.3 Passed: Middleware rejected request from blocked IP with 403\n";
}

echo "All security tests completed.\n";
