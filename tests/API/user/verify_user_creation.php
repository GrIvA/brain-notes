<?php

declare(strict_types=1);

define('ROOTDIR', __DIR__ . '/');
define('SETDIR', ROOTDIR . 'config/');

require_once ROOTDIR . 'vendor/autoload.php';

use App\Models\UserModel;
use App\Enums\UserRole;
use Medoo\Medoo;

$settings = require SETDIR . 'settings.php';
$db = new Medoo($settings['db_connect']);
$userModel = new UserModel($db);

echo "--- СТВОРЕННЯ ТЕСТОВИХ КОРИСТУВАЧІВ ---\n";

// 1. Admin
$adminData = [
    'email' => 'admin@example.com',
    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
    'roles_mask' => UserRole::ADMIN->value | UserRole::USER->value,
    'name' => 'System Admin'
];

if (!$db->has('users', ['email' => $adminData['email']])) {
    $id = $userModel->create($adminData);
    echo "Admin created with ID: $id\n";
} else {
    echo "Admin already exists.\n";
}

// 2. Simple User
$userData = [
    'email' => 'user@example.com',
    'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
    'roles_mask' => UserRole::USER->value,
    'name' => 'Regular User'
];

if (!$db->has('users', ['email' => $userData['email']])) {
    $id = $userModel->create($userData);
    echo "User created with ID: $id\n";
} else {
    echo "User already exists.\n";
}

echo "Done.\n";
