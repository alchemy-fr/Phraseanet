<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="UsrListOwners", uniqueConstraints={@ORM\UniqueConstraint(name="unique_owner", columns={"user_id", "id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\UsrListOwnerRepository")
 */
class UsrListOwner
{
    const ROLE_USER = 1;
    const ROLE_EDITOR = 2;
    const ROLE_ADMIN = 3;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\Column(type="string")
     */
    private $role;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\ManyToOne(targetEntity="UsrList", inversedBy="owners", cascade={"persist"})
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id")
     */
    private $list;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $user
     *
     * @return UsrListowner
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set role
     *
     * @param  string       $role
     * @return UsrListOwner
     */
    public function setRole($role)
    {
        if ( ! in_array($role, [self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_USER]))
            throw new \Exception('Unknown role `' . $role . '`');

        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set created
     *
     * @param  \DateTime    $created
     * @return UsrListOwner
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  \DateTime    $updated
     * @return UsrListOwner
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set list
     *
     * @param  UsrList      $list
     * @return UsrListOwner
     */
    public function setList(UsrList $list = null)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * Get list
     *
     * @return UsrList
     */
    public function getList()
    {
        return $this->list;
    }
}
