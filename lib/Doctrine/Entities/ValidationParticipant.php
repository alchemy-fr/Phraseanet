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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ValidationParticipant
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $usr_id
     */
    protected $usr_id;

    /**
     * @var Entities\ValidationSession
     */
    protected $session;

    /**
     * @var datetime $reminded
     */
    protected $reminded = null;

    /**
     * @var Entities\ValidationData
     */
    protected $datas;

    /**
     * @var boolean $is_confirmed
     */
    protected $is_confirmed = false;

    /**
     * @var boolean $can_agree
     */
    protected $can_agree = false;

    /**
     * @var boolean $can_see_others
     */
    protected $can_see_others = false;

    /**
     * @var boolean $is_aware
     */
    protected $is_aware = false;

    public function __construct()
    {
        $this->datas = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set usr_id
     *
     * @param integer $usrId
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;
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

    /**
     * Add datases
     *
     * @param Entities\ValidationData $datas
     */
    public function addValidationData(\Entities\ValidationData $datas)
    {
        $this->datas[] = $datas;
    }

    /**
     * Set session
     *
     * @param Entities\ValidationSession $session
     */
    public function setSession(\Entities\ValidationSession $session)
    {
        $this->session = $session;
    }

    /**
     * Get session
     *
     * @return Entities\ValidationSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set is_aware
     *
     * @param boolean $isAware
     */
    public function setIsAware($isAware)
    {
        $this->is_aware = $isAware;
    }

    /**
     * Get is_aware
     *
     * @return boolean
     */
    public function getIsAware()
    {
        return $this->is_aware;
    }

    /**
     *
     * @param \User_Adapter $user
     * @return ValidationParticipant
     */
    public function setUser(\User_Adapter $user)
    {
        $this->usr_id = $user->get_id();

        return $this;
    }

    public function getUser()
    {
        return \User_Adapter::getInstance($this->getUsrId(), \appbox::get_instance(\bootstrap::getCore()));
    }

    /**
     * Set reminded
     *
     * @param datetime $reminded
     */
    public function setReminded($reminded)
    {
        $this->reminded = $reminded;
    }

    /**
     * Get reminded
     *
     * @return datetime
     */
    public function getReminded()
    {
        return $this->reminded;
    }

    /**
     * Get datas
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getDatas()
    {
        return $this->datas;
    }

    /**
     * Set is_confirmed
     *
     * @param boolean $isConfirmed
     */
    public function setIsConfirmed($isConfirmed)
    {
        $this->is_confirmed = $isConfirmed;
    }

    /**
     * Get is_confirmed
     *
     * @return boolean
     */
    public function getIsConfirmed()
    {
        return $this->is_confirmed;
    }

    /**
     * Set can_agree
     *
     * @param boolean $canAgree
     */
    public function setCanAgree($canAgree)
    {
        $this->can_agree = $canAgree;
    }

    /**
     * Get can_agree
     *
     * @return boolean
     */
    public function getCanAgree()
    {
        return $this->can_agree;
    }

    /**
     * Set can_see_others
     *
     * @param boolean $canSeeOthers
     */
    public function setCanSeeOthers($canSeeOthers)
    {
        $this->can_see_others = $canSeeOthers;
    }

    /**
     * Get can_see_others
     *
     * @return boolean
     */
    public function getCanSeeOthers()
    {
        return $this->can_see_others;
    }

    public function isReleasable()
    {

        if ($this->getIsConfirmed()) {
            return false;
        }

        foreach ($this->getDatas() as $validation_data) {
            /* @var $validation_data \Entities\ValidationData */
            if ($validation_data->getAgreement() === null) {
                return false;
            }
        }

        return true;
    }
}