<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrList
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var datetime $created
     */
    protected $created;

    /**
     * @var datetime $updated
     */
    protected $updated;

    /**
     * @var Entities\UsrListOwner
     */
    protected $owners;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $entries;

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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @param datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Get created
     *
     * @return datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Add owners
     *
     * @param Entities\UsrListOwner $owners
     */
    public function addUsrListOwner(\Entities\UsrListOwner $owners)
    {
        $this->owners[] = $owners;
    }

    /**
     * Get owners
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getOwners()
    {
        return $this->owners;
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
     * Add entry
     *
     * @param Entities\UsrListEntry $entry
     */
    public function addUsrListEntry(\Entities\UsrListEntry $entry)
    {
        $this->entries[] = $entry;
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

    /**
     * Return true if one of the entry is related to the given user
     *
     * @param  \User_Adapter $user
     * @return boolean
     */
    public function has(\User_Adapter $user, Application $app)
    {
        return $this->entries->exists(
                function($key, $entry) use ($user) {
                    return $entry->getUser($app)->get_id() === $user->get_id();
                }
        );
    }
}