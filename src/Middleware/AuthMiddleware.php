<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entities\User;
use App\Enums\UserRole;
use App\Models\RegistryModel;
use App\Models\UserModel;
use App\Services\AuthService;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class AuthMiddleware implements MiddlewareInterface
{
    private UserModel $userModel;
    private RegistryModel $registryModel;
    private string $secret;
    private array $allowedRoles;
    private ?AuthService $authService;

    /**
     * @param UserModel $userModel
     * @param RegistryModel $registryModel
     * @param string $secret
     * @param UserRole[] $allowedRoles empty means any authenticated user
     * @param AuthService|null $authService required for sliding session
     */
    public function __construct(
        UserModel $userModel,
        RegistryModel $registryModel,
        string $secret,
        array $allowedRoles = [],
        ?AuthService $authService = null
    ) {
        $this->userModel = $userModel;
        $this->registryModel = $registryModel;
        $this->secret = $secret;
        $this->allowedRoles = $allowedRoles;
        $this->authService = $authService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tokenStr = null;
        $authHeader = $request->getHeaderLine('Authorization');
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $tokenStr = substr($authHeader, 7);
        } else {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                $tokenStr = $cookies['auth_token'];
            }
        }

        if (!$tokenStr) {
            if (!empty($this->allowedRoles)) {
                throw new HttpUnauthorizedException($request, "Missing Authorization");
            }
            return $handler->handle($request->withAttribute('user', null));
        }

        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse($tokenStr);
            $validator = new Validator();
            $signer = new Sha256();
            $key = InMemory::plainText($this->secret);

            if (!$validator->validate($token, new SignedWith($signer, $key))) {
                throw new HttpUnauthorizedException($request, "Invalid token signature");
            }

            // Manual expiration check
            $now = new \DateTimeImmutable();
            if ($token->isExpired($now)) {
                throw new HttpUnauthorizedException($request, "Token expired");
            }

            $userId = (int) $token->claims()->get('uid');
            $userData = $this->userModel->findById($userId);

            if (!$userData) {
                if (!empty($this->allowedRoles)) {
                    throw new HttpUnauthorizedException($request, "User not found");
                }
                return $handler->handle($request->withAttribute('user', null));
            }

            $user = new User($userData, $this->registryModel);

            // Check JTI in TagRegistry
            $jti = $token->claims()->get('jti');
            if ($jti) {
                $activeSessions = $user->tags('auth')->getAll('active_session');
                if (!in_array($jti, $activeSessions)) {
                    if (!empty($this->allowedRoles)) {
                        throw new HttpUnauthorizedException($request, "Session invalidated");
                    }
                    return $handler->handle($request->withAttribute('user', null));
                }
            }

            // Role check
            if (!empty($this->allowedRoles)) {
                $hasAccess = false;
                foreach ($this->allowedRoles as $role) {
                    if ($user->hasRole($role)) {
                        $hasAccess = true;
                        break;
                    }
                }
                if (!$hasAccess) {
                    throw new HttpForbiddenException($request, "Insufficient permissions");
                }
            }

            // Add user to request attributes
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('jti', $jti);

            // --- Sliding Session Logic ---
            $response = $handler->handle($request);

            if ($this->authService && $jti) {
                $iat = $token->claims()->get('iat');
                $exp = $token->claims()->get('exp');
                
                if ($iat instanceof \DateTimeImmutable && $exp instanceof \DateTimeImmutable) {
                    $lifetime = $exp->getTimestamp() - $iat->getTimestamp();
                    $elapsed = $now->getTimestamp() - $iat->getTimestamp();
                    
                    // If more than 2/3 of lifetime has passed, refresh the token
                    if ($elapsed > (2 * $lifetime / 3)) {
                        $newToken = $this->authService->refreshSession($user, (string)$jti);
                        $response = $response->withHeader('Set-Cookie', $this->authService->getCookieHeader($newToken));
                    }
                }
            }

            return $response;

        } catch (\Exception $e) {
            if (!empty($this->allowedRoles)) {
                if ($e instanceof HttpUnauthorizedException || $e instanceof HttpForbiddenException) {
                    throw $e;
                }
                throw new HttpUnauthorizedException($request, "Token error: " . $e->getMessage());
            }
            return $handler->handle($request->withAttribute('user', null));
        }
    }
}
