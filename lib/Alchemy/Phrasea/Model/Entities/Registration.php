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

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Registrations",uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_registration", columns={"user_id","base_id","pending"})
 * })
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\Column(type="integer", name="base_id")
     */
    private $baseId;

    /**
     * @ORM\Column(type="boolean", name="pending", options={"default" = 1})
     */
    private $pending = true;

    /**
     * @ORM\Column(type="boolean", name="rejected", options={"default" = 0})
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
     * @param User $user
     * @return Registration
     */
    public function setUser(User $user)
    {
        $this->user  = $user;

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
     * @param Application $app
     *
     * @return \collection
     */
    public function getCollection(Application $app)
    {
        return \collection::getByBaseId($app, $this->baseId);
    }

    /**
     * @param \collection $collection
     *
     * @return $this
     */
    public function setCollection(\collection $collection)
    {
        $this->baseId = $collection->get_base_id();

        return $this;
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
