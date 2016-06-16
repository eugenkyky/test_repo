<?php

namespace Services\Security;

use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\{TokenInterface,PreAuthenticatedToken};
use Symfony\Component\Security\Core\Exception\{AuthenticationException, BadCredentialsException};
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    protected $userProvider;
    protected $paramName;

    public function __construct(ApiKeyUserProvider $userProvider,string $paramName)
    {
        $this->paramName = $paramName;
        $this->userProvider = $userProvider;
    }

    public function createToken(Request $request, $providerKey): PreAuthenticatedToken
    {
        if (!$request->query->has($this->paramName)) {
            //TODO log, stat
            throw new BadCredentialsException('No API key found');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $request->query->get($this->paramName),
            $providerKey
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey): bool
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey): PreAuthenticatedToken
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

    public function onAuthenticationFailure(Request $request, AuthenticationException $Exception): Response
    {
        //TODO log, stat
        return new Response("Authentication Failed.", 403);
    }
}