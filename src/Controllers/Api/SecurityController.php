<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Abstract\AbstractController;
use App\Models\IpModel;
use App\Entities\User;
use App\Enums\UserRole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class SecurityController extends AbstractController
{
    private IpModel $ipModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->ipModel = $container->get(IpModel::class);
    }

    /**
     * Get all tracked IPs and their statuses.
     */
    public function listIps(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        if (!$this->isAdmin($req)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Forbidden'], 403);
        }

        $ips = $this->ipModel->getAllTrackedIps();
        return \App\Responder\JsonHandler::response($res, $ips);
    }

    /**
     * Set status for a specific IP.
     */
    public function setStatus(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        /** @var User $admin */
        $admin = $req->getAttribute('user');
        if (!$this->isAdmin($req)) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Forbidden'], 403);
        }

        $ip = $args['ip'] ?? '';
        $data = $req->getParsedBody();
        $status = $data['status'] ?? ''; // 'normal', 'allow', 'disabled'
        
        $statusMap = [
            'normal' => IpModel::STATUS_NORMAL,
            'allow' => IpModel::STATUS_ALLOW,
            'disabled' => IpModel::STATUS_DISABLED
        ];

        if (!isset($statusMap[$status])) {
            return \App\Responder\JsonHandler::response($res, ['error' => 'Invalid status. Use: normal, allow, disabled'], 400);
        }

        $this->ipModel->setStatus($ip, $statusMap[$status], $admin->getId());

        return \App\Responder\JsonHandler::response($res, ['message' => "Status for IP $ip updated to $status"]);
    }

    private function isAdmin(ServerRequestInterface $req): bool
    {
        /** @var User $user */
        $user = $req->getAttribute('user');
        return $user && $user->hasRole(UserRole::ADMIN);
    }
}
