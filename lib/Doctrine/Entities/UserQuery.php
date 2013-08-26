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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="UserQueries")
 * @ORM\Entity(repositoryClass="Repositories\UserQueryRepository")
 */
class UserQuery
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $query;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * @param integer $usrId
     *
     * @return UserQuery
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * @param Application $app
     *
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->usrId, $app);
    }

    /**
     * @param \User_Adapter $user
     *
     * @return UserQuery
     */
    public function setUser(\User_Adapter $user)
    {
        $this->setUsrId($user->get_id());

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     *
     * @return UserQuery
     */
    public function setQuery($query)
    {
        $this->query = $query;

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
     * @param \DateTime $created
     *
     * @return UserQuery
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }
}
