<?php
/**
 * This class is a UserProvider for the Symfony Security component,
 * implementing its UserProviderInterface
 * @author  David Raison <david@tentwentyfour.lu>
 */

namespace Services\Security;

/*class ApiKeyUserProvider extends DatabaseUserProvider {
    /**
     * Implements getUsernameForApiKey used in the ApiKeyAuthenticator
     *
     * The ApiKeyAuthenticator will throw an exception if the returned value is falsy,
     * so we don't throw any Exception here.

    public function getUsernameForApiKey($apiKey)
    {
        $user = $this->repository->findOneByApikey($apiKey); // вот это вообще не поняо как работатет/ Видимо просто по названию
        if ($user) {
            return $user->getUsername();
        }
        return false;
    }
}*/
namespace Services\Security;

use Services\Entity\User;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\User\{ UserProviderInterface,UserInterface};
use Symfony\Component\Security\Core\Exception\{UsernameNotFoundException,UnsupportedUserException};
use Symfony\Component\Security\Core\Exception\AuthenticationException;
require __DIR__.'/../Entity/User.php';

class ApiKeyUserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct($em)
    {
        $this->conn = $em;
    }
    public function supportsClass($class)
    {
        return $class === 'Services\Entity\User';
    }

    public function loadUserByUsername($username) //в моем примере не используется
    {
        // finding user data
        /*
        $userData = '...';
        // pretend it returns an array on success, false if there is no user
        if ($userData) {
            $password = '...';
            // ...
            return new User($username, $password, $salt= '...', $roles='...');
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );*/
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof WebserviceUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function getUsernameForApiKey($apiKey){

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