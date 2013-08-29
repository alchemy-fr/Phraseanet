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
 * @ORM\Table(name="Sessions", indexes={@ORM\index(name="usr_id", columns={"usr_id"})})
 * @ORM\Entity(repositoryClass="Repositories\SessionRepository")
 */
class Session
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $usr_id;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $user_agent;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $ip_address;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $platform;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $browser_name;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $browser_version;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $screen_width;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $screen_height;

    /**
     * @ORM\Column(type="string", length=128, nullable=true, unique=true)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $nonce;

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
     * @ORM\OneToMany(targetEntity="SessionModule", mappedBy="session", cascade={"all"})
     * @ORM\OrderBy({"module_id" = "ASC"})
     */
    private $modules;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->modules = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \User_Adapter $user
     * 
     * @return Session
     */
    public function setUser(\User_Adapter $user)
    {
        return $this->setUsrId($user->get_id());
    }

    /**
     * @param  integer $usrId
     * 
     * @return Session
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * @param Application $app
     * 
     * @return \User_adapter or null
     */
    public function getUser(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
    }

    /**
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * @param  string  $userAgent
     * 
     * @return Session
     */
    public function setUserAgent($userAgent)
    {
        $this->user_agent = $userAgent;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * @param  string  $ipAddress
     * 
     * @return Session
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param  string  $platform
     * 
     * @return Session
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param  string  $browserName
     * 
     * @return Session
     */
    public function setBrowserName($browserName)
    {
        $this->browser_name = $browserName;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrowserName()
    {
        return $this->browser_name;
    }

    /**
     * @param  string  $browserVersion
     * 
     * @return Session
     */
    public function setBrowserVersion($browserVersion)
    {
        $this->browser_version = $browserVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrowserVersion()
    {
        return $this->browser_version;
    }

    /**
     * @param  integer $screenWidth
     * 
     * @return Session
     */
    public function setScreenWidth($screenWidth)
    {
        $this->screen_width = $screenWidth;

        return $this;
    }

    /**
     * @return integer
     */
    public function getScreenWidth()
    {
        return $this->screen_width;
    }

    /**
     * @param  integer $screenHeight

     * @return Session
     */
    public function setScreenHeight($screenHeight)
    {
        $this->screen_height = $screenHeight;

        return $this;
    }

    /**
     * @return integer
     */
    public function getScreenHeight()
    {
        return $this->screen_height;
    }

    /**
     * @param  string  $token
     * 
     * @return Session
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param  string  $nonce
     * 
     * @return Session
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * @return string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param  \DateTime $created
     * 
     * @return Session
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
     * @return Session
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
     * @param  SessionModule $modules
     * 
     * @return Session
     */
    public function addModule(SessionModule $modules)
    {
        $this->modules[] = $modules;

        return $this;
    }

    /**
     * @param SessionModule $modules
     */
    public function removeModule(SessionModule $modules)
    {
        $this->modules->removeElement($modules);
    }

    /**
     * @return SessionModule[]
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param  integer $moduleId
     * @return SessionModule|null
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

    /**
     * Returns true if session has given module id.
     * 
     * @param integer $moduleId
     * 
     * @return boolean
     */
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
