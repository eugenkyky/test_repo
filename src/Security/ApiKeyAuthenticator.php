<?php
/**
 * This class is API key authenticator for the Symfony Security component,
 * implementing its SimplePreAuthenticatorInterface
 *
 * @see http://symfony.com/doc/current/cookbook/security/api_key_authentication.html
 */

namespace Services\Security;

use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Security\Core\User\UserProviderInterface;
//use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\{TokenInterface,PreAuthenticatedToken};
use Symfony\Component\Security\Core\Exception\{AuthenticationException, BadCredentialsException};
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Psr\Log\LoggerInterface;
use Services\Security\ApiKeyUserProvider;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    protected $userProvider;
    protected $paramName;

    public function __construct(ApiKeyUserProvider $userProvider, $paramName)
    {
        $this->paramName = $paramName;
        $this->userProvider = $userProvider;
    }

    public function createToken(Request $request, $providerKey)
    {
        if (!$request->query->has($this->paramName)) {
            //TODO log
            throw new BadCredentialsException('No API key found');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $request->query->get($this->paramName),
            $providerKey
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $apiKey = $token->getCredentials();

        $user = $this->userProvider->getUsernameForApiKey($apiKey);

        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }


    public function onAuthenticationFailure(Request $request, AuthenticationException $Exception)
    {
        //TODO LOG
        return new Response("Authentication Failed.", 403);
    }
}