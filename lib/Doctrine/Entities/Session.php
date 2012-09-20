<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entities\Session
 */
class Session
{
    /**
     * @var string $sess_id
     */
    private $sess_id;

    /**
     * @var text $sess_data
     */
    private $sess_data;

    /**
     * @var timestamp $sess_time
     */
    private $sess_time;


    /**
     * Set sess_id
     *
     * @param string $sessId
     * @return Session
     */
    public function setSessId($sessId)
    {
        $this->sess_id = $sessId;
        return $this;
    }

    /**
     * Get sess_id
     *
     * @return string 
     */
    public function getSessId()
    {
        return $this->sess_id;
    }

    /**
     * Set sess_data
     *
     * @param text $sessData
     * @return Session
     */
    public function setSessData($sessData)
    {
        $this->sess_data = $sessData;
        return $this;
    }

    /**
     * Get sess_data
     *
     * @return text 
     */
    public function getSessData()
    {
        return $this->sess_data;
    }

    /**
     * Set sess_time
     *
     * @param timestamp $sessTime
     * @return Session
     */
    public function setSessTime(\timestamp $sessTime)
    {
        $this->sess_time = $sessTime;
        return $this;
    }

    /**
     * Get sess_time
     *
     * @return timestamp 
     */
    public function getSessTime()
    {
        return $this->sess_time;
    }
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $usr_id
     */
    private $usr_id;

    /**
     * @var string $user_agent
     */
    private $user_agent;

    /**
     * @var string $ip_address
     */
    private $ip_address;

    /**
     * @var string $platform
     */
    private $platform;

    /**
     * @var string $browser_name
     */
    private $browser_name;

    /**
     * @var string $browser_version
     */
    private $browser_version;

    /**
     * @var integer $screen_width
     */
    private $screen_width;

    /**
     * @var integer $screen_height
     */
    private $screen_height;

    /**
     * @var string $token
     */
    private $token;

    /**
     * @var string $nonce
     */
    private $nonce;

    /**
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $updated
     */
    private $updated;


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
     * @return Session
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

    /**
     * Set user_agent
     *
     * @param string $userAgent
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
     * @param string $ipAddress
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
     * @param string $platform
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
     * @param string $browserName
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
     * @param string $browserVersion
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
     * @param integer $screenWidth
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
     * @param integer $screenHeight
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
     * @param string $token
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
     * @param string $nonce
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
     * @param datetime $created
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
     * @return datetime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}