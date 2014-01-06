<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 * UsrList
 */
class UsrList
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $owners;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $entries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->owners = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entries = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set name
     *
     * @param  string  $name
     * @return UsrList
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return UsrList
     */
    public function setCreated($created)
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
     * @param  \DateTime $updated
     * @return UsrList
     */
    public function setUpdated($updated)
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
     * Add owners
     *
     * @param  \Entities\UsrListOwner $owners
     * @return UsrList
     */
    public function addOwner(\Entities\UsrListOwner $owners)
    {
        $this->owners[] = $owners;

        return $this;
    }

    /**
     * Remove owners
     *
     * @param \Entities\UsrListOwner $owners
     */
    public function removeOwner(\Entities\UsrListOwner $owners)
    {
        $this->owners->removeElement($owners);
    }

    /**
     * Get owners
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * Add entries
     *
     * @param  \Entities\UsrListEntry $entries
     * @return UsrList
     */
    public function addEntrie(\Entities\UsrListEntry $entries)
    {
        $this->entries[] = $entries;

        return $this;
    }

    /**
     * Remove entries
     *
     * @param \Entities\UsrListEntry $entries
     */
    public function removeEntrie(\Entities\UsrListEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * Get entries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntries()
    {
        return $this->entries;
    }

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
     *
     * @param  \User_Adapter          $user
     * @return \Entities\UsrListOwner
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
     * Return true if one of the entry is related to the given user
     *
     * @param  \User_Adapter $user
     * @return boolean
     */
    public function has(\User_Adapter $user, Application $app)
    {
        return $this->entries->exists(
            function ($key, $entry) use ($user, $app) {
                return $entry->getUser($app)->get_id() === $user->get_id();
            }
        );
    }
}
