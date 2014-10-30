<?php


namespace Daimos\Tests\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class User
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    protected $username;

    /**
     * @ORM\ManyToOne(targetEntity="Role", cascade={"persist"})
     * @ORM\JoinColumn()
     */
    protected $role;

    public function __construct($username, Role $role = null)
    {
        $this->username = $username;
        $this->role = $role;
    }

    /**
     * @param Role $role
     * @return $this
     */
    public function setRole(Role $role = null)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
} 