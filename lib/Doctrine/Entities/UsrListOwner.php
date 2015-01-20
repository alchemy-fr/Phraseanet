<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 * UsrListOwner
 */
class UsrListOwner
{
    const ROLE_USER = 1;
    const ROLE_EDITOR = 2;
    const ROLE_ADMIN = 3;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $usr_id;

    /**
     * @var string
     */
    private $role;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \Entities\UsrList
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
     * Set usr_id
     *
     * @param  integer      $usrId
     * @return UsrListOwner
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * Get usr_id
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    public function setUser(\User_Adapter $user)
    {
        return $this->setUsrId($user->get_id());
    }

    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->getUsrId(), $app);
    }

    /**
     * Set role
     *
     * @param  string       $role
     * @return UsrListOwner
     */
    public function setRole($role)
    {
        if ( ! in_array($role, array(self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_USER)))
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
     * @param  \DateTime    $updated
     * @return UsrListOwner
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
     * Set list
     *
     * @param  \Entities\UsrList $list
     * @return UsrListOwner
     */
    public function setList(\Entities\UsrList $list = null)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * Get list
     *
     * @return \Entities\UsrList
     */
    public function getList()
    {
        return $this->list;
    }
}
