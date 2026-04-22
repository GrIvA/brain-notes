<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\User;
use App\Models\RegistryModel;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Log\LoggerInterface;

class AuthService
{
    private array $settings;
    private RegistryModel $registryModel;
    private LoggerInterface $logger;

    public function __construct(array $settings, RegistryModel $registryModel, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->registryModel = $registryModel;
        $this->logger = $logger;
    }

    /**
     * Create a new JWT for the user and register JTI.
     */
    public function issueToken(User $user, string $type = 'login'): string
    {
        $jwtSettings = $this->settings;
        $lifetime = (int)$jwtSettings['lifetime'];
        
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
            ->expiresAt($now->modify('+' . $lifetime . ' seconds'))
            ->withClaim('uid', $user->getId())
            ->getToken($config->signer(), $config->signingKey());

        $jwt = $token->toString();

        // Register JTI
        $user->tags('auth')->set('active_session', $jti, null, (int)$now->getTimestamp());

        $this->logger->info(sprintf(
            "JWT Issued: type=%s, user_id=%d, jti=%s",
            $type, $user->getId(), $jti
        ));

        return $jwt;
    }

    /**
     * Generate Set-Cookie header value.
     */
    public function getCookieHeader(string $token): string
    {
        $lifetime = (int)$this->settings['lifetime'];
        return "auth_token=$token; Path=/; HttpOnly; Max-Age=$lifetime; SameSite=Lax";
    }

    /**
     * Refresh session: remove old JTI and issue new token.
     */
    public function refreshSession(User $user, string $oldJti): string
    {
        // Remove old JTI
        $user->tags('auth')->removeByKeyValue('active_session', $oldJti);
        
        // Issue new token
        return $this->issueToken($user, 'refresh');
    }

    /**
     * Invalidate session (logout).
     */
    public function invalidateSession(User $user, string $jti): void
    {
        $user->tags('auth')->removeByKeyValue('active_session', $jti);
        $this->logger->info(sprintf("JWT Invalidated: user_id=%d, jti=%s", $user->getId(), $jti));
    }
}
