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
 * Session
 */
class Session
{
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
    private $user_agent;

    /**
     * @var string
     */
    private $ip_address;

    /**
     * @var string
     */
    private $platform;

    /**
     * @var string
     */
    private $browser_name;

    /**
     * @var string
     */
    private $browser_version;

    /**
     * @var integer
     */
    private $screen_width;

    /**
     * @var integer
     */
    private $screen_height;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $nonce;

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
    private $modules;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->modules = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function setUser(\User_Adapter $user)
    {
        return $this->setUsrId($user->get_id());
    }

    /**
     * Set usr_id
     *
     * @param  integer $usrId
     * @return Session
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    public function getUser(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
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
     * Set user_agent
     *
     * @param  string  $userAgent
     * @return Session
     */
    public function setUserAgent($userAgent)
    {
        $this->user_agent = $userAgent;

        return $this;
    }

    /**
     * Get user_agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set ip_address
     *
     * @param  string  $ipAddress
     * @return Session
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    /**
     * Get ip_address
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * Set platform
     *
     * @param  string  $platform
     * @return Session
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Get platform
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set browser_name
     *
     * @param  string  $browserName
     * @return Session
     */
    public function setBrowserName($browserName)
    {
        $this->browser_name = $browserName;

        return $this;
    }

    /**
     * Get browser_name
     *
     * @return string
     */
    public function getBrowserName()
    {
        return $this->browser_name;
    }

    /**
     * Set browser_version
     *
     * @param  string  $browserVersion
     * @return Session
     */
    public function setBrowserVersion($browserVersion)
    {
        $this->browser_version = $browserVersion;

        return $this;
    }

    /**
     * Get browser_version
     *
     * @return string
     */
    public function getBrowserVersion()
    {
        return $this->browser_version;
    }

    /**
     * Set screen_width
     *
     * @param  integer $screenWidth
     * @return Session
     */
    public function setScreenWidth($screenWidth)
    {
        $this->screen_width = $screenWidth;

        return $this;
    }

    /**
     * Get screen_width
     *
     * @return integer
     */
    public function getScreenWidth()
    {
        return $this->screen_width;
    }

    /**
     * Set screen_height
     *
     * @param  integer $screenHeight
     * @return Session
     */
    public function setScreenHeight($screenHeight)
    {
        $this->screen_height = $screenHeight;

        return $this;
    }

    /**
     * Get screen_height
     *
     * @return integer
     */
    public function getScreenHeight()
    {
        return $this->screen_height;
    }

    /**
     * Set token
     *
     * @param  string  $token
     * @return Session
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set nonce
     *
     * @param  string  $nonce
     * @return Session
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Get nonce
     *
     * @return string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return Session
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
     * @return Session
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
     * Add modules
     *
     * @param  \Entities\SessionModule $modules
     * @return Session
     */
    public function addModule(\Entities\SessionModule $modules)
    {
        $this->modules[] = $modules;

        return $this;
    }

    /**
     * Remove modules
     *
     * @param \Entities\SessionModule $modules
     */
    public function removeModule(\Entities\SessionModule $modules)
    {
        $this->modules->removeElement($modules);
    }

    /**
     * Get modules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Get a module by its identifier
     *
     * @param  integer                     $moduleId
     * @return Entities\SessionModule|null
     */
    public function getModuleById($moduleId)
    {
        foreach ($this->getModules() as $module) {
            if ($module->getModuleId() == $moduleId) {
                return $module;
            }
        }

        return null;
    }

    public function hasModuleId($moduleId)
    {
        foreach ($this->getModules() as $module) {
            if ($module->getModuleId() == $moduleId) {
                return true;
            }
        }

        return false;
    }
}
