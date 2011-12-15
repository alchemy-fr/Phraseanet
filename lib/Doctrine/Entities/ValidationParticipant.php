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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ValidationParticipant
{
  
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $usr_id
     */
    private $usr_id;

    /**
     * @var Entities\ValidationData
     */
    private $datases;

    /**
     * @var Entities\ValidationSession
     */
    private $session;

    public function __construct()
    {
        $this->datases = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param Entities\ValidationData $datases
     */
    public function addValidationData(\Entities\ValidationData $datases)
    {
        $this->datases[] = $datases;
    }

    /**
     * Get datases
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getDatases()
    {
        return $this->datases;
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
}