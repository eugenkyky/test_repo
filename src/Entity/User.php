<?php

namespace Services\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
* @Table(name="user")
* @Entity
*/
class User implements UserInterface
{
    /**
    * @Column(type="integer")
    * @Id
    * @GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
    * @Column(type="string", length=25, unique=true)
    */
    private $username;

    /**
    * @Column(type="string", length=64, unique=true)
    */
    private $password;

    /**
     * @Column(type="string", length=64, unique=true)
     */
    private $apikey;

    public function getUsername()
    {
        return $this->username;
    }

    public function getApikey()
    {
        return $this->apikey;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
        return null;
    }
}