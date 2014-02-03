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
 * @ORM\Table(name="Registration")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\RegistrationRepository")
 */
class Registration
{
   /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="user_id")
     */
    private $user;

    /**
     * @ORM\Column(type="integer", name="base_id")
     */
    private $baseId;

    /**
     * @ORM\Column(type="boolean", name="pending")
     */
    private $pending = true;

    /**
     * @ORM\Column(type="boolean", name="rejected")
     */
    private $rejected = false;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $pending
     *
     * @return Registration
     */
    public function setPending($pending)
    {
        $this->pending = (Boolean) $pending;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isPending()
    {
        return $this->pending;
    }

    /**
     * @param mixed $rejected
     *
     * @return Registration
     */
    public function setRejected($rejected)
    {
        $this->rejected = (Boolean) $rejected;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isRejected()
    {
        return $this->rejected;
    }

    /**
     * @param mixed $user
     *
     * @return Registration
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $baseId
     *
     * @return Registration
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \Datetime $created
     */
    public function setCreated(\Datetime $created)
    {
        $this->created = $created;
    }

    /**
     * @param \Datetime $updated
     */
    public function setUpdated(\Datetime $updated)
    {
        $this->updated = $updated;
    }
}
