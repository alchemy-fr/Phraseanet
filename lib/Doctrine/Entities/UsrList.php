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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="UsrLists")
 * @ORM\Entity(repositoryClass="Repositories\UsrListRepository")
 */
class UsrList
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

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
     * @ORM\OneToMany(targetEntity="UsrListOwner", mappedBy="list", cascade={"all"})
     */
    private $owners;

    /**
     * @ORM\OneToMany(targetEntity="UsrListEntry", mappedBy="list", cascade={"all"})
     */
    private $entries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->owners = new ArrayCollection();
        $this->entries = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string  $name
     * 
     * @return UsrList
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  \DateTime $created
     * 
     * @return UsrList
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
     * @param  \DateTime $updated
     * 
     * @return UsrList
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
     * @param  UsrListOwner $owners
     * 
     * @return UsrList
     */
    public function addOwner(UsrListOwner $owners)
    {
        $this->owners[] = $owners;

        return $this;
    }

    /**
     * @param UsrListOwner $owners
     */
    public function removeOwner(UsrListOwner $owners)
    {
        $this->owners->removeElement($owners);
    }

    /**
     * @return UsrListOwner[]
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * @param  UsrListEntry $entries
     * 
     * @return UsrList
     */
    public function addEntrie(UsrListEntry $entries)
    {
        $this->entries[] = $entries;

        return $this;
    }

    /**
     * @param UsrListEntry $entries
     */
    public function removeEntrie(UsrListEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * @return UsrListEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Returns true if given user has access to the current list.
     * 
     * @param \User_Adapter $user
     * @param Application $app
     * 
     * @return boolean
     */
    public function hasAccess(\User_Adapter $user, Application $app)
    {
        foreach ($this->getOwners() as $owner) {
            if ($owner->getUser($app)->get_id() == $user->get_id()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  \User_Adapter          $user
     * 
     * @return UsrListOwner
     */
    public function getOwner(\User_Adapter $user, Application $app)
    {
        foreach ($this->getOwners() as $owner) {
            if ($owner->getUser($app)->get_id() == $user->get_id()) {
                return $owner;
            }
        }

        throw new \Exception('This user is not an owner of the list');
    }

    /**
     * Returns true if one of the entry is related to the given user
     *
     * @param  \User_Adapter $user
     * 
     * @return boolean
     */
    public function has(\User_Adapter $user, Application $app)
    {
        return $this->entries->exists(
            function($key, $entry) use ($user, $app) {
                return $entry->getUser($app)->get_id() === $user->get_id();
            }
        );
    }
}
