<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="UsrListOwners", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_owner", columns={"usr_id", "id"})
 * })
 * @ORM\Entity(repositoryClass="Repositories\UsrListOwnerRepository")
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
     * @ORM\Column(type="integer")
     */
    private $usr_id;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  integer      $usrId
     * 
     * @return UsrListOwner
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * @param \User_Adapter $user
     * 
     * @return UsrListOwner
     */
    public function setUser(\User_Adapter $user)
    {
        return $this->setUsrId($user->get_id());
    }

    /**
     * @param Application $app
     * 
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->getUsrId(), $app);
    }

    /**
     * @param  string       $role
     * 
     * @return UsrListOwner
     */
    public function setRole($role)
    {
        if (!in_array($role, array(self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_USER))) {
            throw new InvalidArgumentException(sprintf('Unknown role `%s`.', $role));
        }

        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param  \DateTime    $created
     * 
     * @return UsrListOwner
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param  \DateTime    $updated
     * 
     * @return UsrListOwner
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param  UsrList $list
     * 
     * @return UsrListOwner
     */
    public function setList(UsrList $list = null)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return UsrList
     */
    public function getList()
    {
        return $this->list;
    }
}
