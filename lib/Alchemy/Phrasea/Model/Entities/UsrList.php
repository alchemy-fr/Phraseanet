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
 * @ORM\Table(name="UsrLists")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\UsrListRepository")
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
     * @param  \DateTime $updated
     * @return UsrList
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
     * Add owners
     *
     * @param  UsrListOwner $owners
     * @return UsrList
     */
    public function addOwner(UsrListOwner $owners)
    {
        $this->owners[] = $owners;

        return $this;
    }

    /**
     * Remove owners
     *
     * @param UsrListOwner $owners
     */
    public function removeOwner(UsrListOwner $owners)
    {
        $this->owners->removeElement($owners);
    }

    /**
     * Get owners
     *
     * @return UsrListOwner[]
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * Add entries
     *
     * @param  UsrListEntry $entries
     * @return UsrList
     */
    public function addEntrie(UsrListEntry $entries)
    {
        $this->entries[] = $entries;

        return $this;
    }

    /**
     * Remove entries
     *
     * @param UsrListEntry $entries
     */
    public function removeEntrie(UsrListEntry $entries)
    {
        $this->entries->removeElement($entries);
    }

    /**
     * Get entries
     *
     * @return UsrListEntry[]|\Doctrine\Common\Collections\Collection
     */
    public function getEntries()
    {
        return $this->entries;
    }

    public function hasAccess(User $user)
    {
        foreach ($this->getOwners() as $owner) {
            if ($owner->getUser()->getId() == $user->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param  User         $user
     * @return UsrListOwner
     */
    public function getOwner(User $user)
    {
        foreach ($this->getOwners() as $owner) {
            if ($owner->getUser()->getId() == $user->getId()) {
                return $owner;
            }
        }

        throw new \Exception('This user is not an owner of the list');
    }

    /**
     * Return true if one of the entry is related to the given user
     *
     * @param  User    $user
     * @return boolean
     */
    public function has(User $user)
    {
        return $this->entries->exists(
            function ($key, $entry) use ($user) {
                return $entry->getUser()->getId() === $user->getId();
            }
        );
    }
}
