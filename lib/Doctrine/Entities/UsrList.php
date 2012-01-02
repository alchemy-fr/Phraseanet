<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

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
  private $id;

  /**
   * @var string $name
   */
  private $name;

  /**
   * @var datetime $created
   */
  private $created;

  /**
   * @var datetime $updated
   */
  private $updated;

  /**
   * @var Entities\UsrListOwner
   */
  private $owners;

  /**
   * @var Entities\UsrListEntry
   */
  private $users;

  public function __construct()
  {
    $this->owners = new \Doctrine\Common\Collections\ArrayCollection();
    $this->users = new \Doctrine\Common\Collections\ArrayCollection();
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

  /**
   * Add users
   *
   * @param Entities\UsrListContent $users
   */
  public function addUsrListEntry(\Entities\UsrListEntry $users)
  {
    $this->users[] = $users;
  }

  /**
   * Get users
   *
   * @return Doctrine\Common\Collections\Collection 
   */
  public function getUsers()
  {
    return $this->users;
  }

}