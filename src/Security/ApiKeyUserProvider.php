<?php

namespace Services\Security;

use Symfony\Component\Security\Core\User\{UserProviderInterface,UserInterface};
use Symfony\Component\Security\Core\Exception\{UnsupportedUserException,AuthenticationException };

require __DIR__.'/../Entity/User.php';

class ApiKeyUserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct($em)
    {
        $this->conn = $em;
    }

    public function supportsClass($class): bool
    {
        return $class === 'Services\Entity\User';
    }

    public function loadUserByUsername($username): UserInterface
    {
        //TODO
        /*
        finding user data
        $userData = db('select ..');

        if ($userData) {
            $password = '...';
            // ...
            return new User($username, $password, $salt= '...', $roles='...');
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );*/
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof WebserviceUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    public function getUsernameForApiKey($apiKey): UserInterface
    {
        $user = $this->conn->getRepository('Services\Entity\User')->findOneBy(array('apikey' => $apiKey));
        if (!$user) {
            throw new AuthenticationException(
                sprintf('API Key "%s" does not exist', $apiKey)
            );
        } else {
            return $user;
        }
    }
}