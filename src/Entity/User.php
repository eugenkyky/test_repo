<?php
// src/AppBundle/Entity/User.php
namespace Services\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
* @Table(name="user")
* @Entity
*
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
    * @Column(type="string", length=64)
    */
    private $password;

    /**
     * @Column(type="string", length=64)
     */
    private $apikey;

    /**
     * @Column(type="bigint")
     */
    /*private $consumedplace;

    public function getConsumedplace(){ //Для чего getter and setter нужны вообще?
        return $this->consumedplace;
    }

    public function setConsumedplace($bytes){
        $this->consumedplace = $bytes;
    }*/

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
        // see section on salt below
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
    }
}