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
 * @ORM\Table(name="Sessions")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\SessionRepository")
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

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
     * @ORM\Column(type="string", length=64, nullable=true)
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

    /**
     * @param User $user
     *
     * @return Session
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
     * @return Session
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
     * Add modules
     *
     * @param  SessionModule $modules
     * @return Session
     */
    public function addModule(SessionModule $modules)
    {
        $this->modules[] = $modules;

        return $this;
    }

    /**
     * Remove modules
     *
     * @param SessionModule $modules
     */
    public function removeModule(SessionModule $modules)
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
     * @param  integer            $moduleId
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
