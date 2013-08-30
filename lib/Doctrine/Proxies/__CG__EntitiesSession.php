<?php

namespace Proxies\__CG__\Entities;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class Session extends \Entities\Session implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }

    /** @private */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int) $this->_identifier["id"];
        }
        $this->__load();
        return parent::getId();
    }

    public function setUser(\User_Adapter $user)
    {
        $this->__load();
        return parent::setUser($user);
    }

    public function setUsrId($usrId)
    {
        $this->__load();
        return parent::setUsrId($usrId);
    }

    public function getUser(\Alchemy\Phrasea\Application $app)
    {
        $this->__load();
        return parent::getUser($app);
    }

    public function getUsrId()
    {
        $this->__load();
        return parent::getUsrId();
    }

    public function setUserAgent($userAgent)
    {
        $this->__load();
        return parent::setUserAgent($userAgent);
    }

    public function getUserAgent()
    {
        $this->__load();
        return parent::getUserAgent();
    }

    public function setIpAddress($ipAddress)
    {
        $this->__load();
        return parent::setIpAddress($ipAddress);
    }

    public function getIpAddress()
    {
        $this->__load();
        return parent::getIpAddress();
    }

    public function setPlatform($platform)
    {
        $this->__load();
        return parent::setPlatform($platform);
    }

    public function getPlatform()
    {
        $this->__load();
        return parent::getPlatform();
    }

    public function setBrowserName($browserName)
    {
        $this->__load();
        return parent::setBrowserName($browserName);
    }

    public function getBrowserName()
    {
        $this->__load();
        return parent::getBrowserName();
    }

    public function setBrowserVersion($browserVersion)
    {
        $this->__load();
        return parent::setBrowserVersion($browserVersion);
    }

    public function getBrowserVersion()
    {
        $this->__load();
        return parent::getBrowserVersion();
    }

    public function setScreenWidth($screenWidth)
    {
        $this->__load();
        return parent::setScreenWidth($screenWidth);
    }

    public function getScreenWidth()
    {
        $this->__load();
        return parent::getScreenWidth();
    }

    public function setScreenHeight($screenHeight)
    {
        $this->__load();
        return parent::setScreenHeight($screenHeight);
    }

    public function getScreenHeight()
    {
        $this->__load();
        return parent::getScreenHeight();
    }

    public function setToken($token)
    {
        $this->__load();
        return parent::setToken($token);
    }

    public function getToken()
    {
        $this->__load();
        return parent::getToken();
    }

    public function setNonce($nonce)
    {
        $this->__load();
        return parent::setNonce($nonce);
    }

    public function getNonce()
    {
        $this->__load();
        return parent::getNonce();
    }

    public function setCreated(\DateTime $created)
    {
        $this->__load();
        return parent::setCreated($created);
    }

    public function getCreated()
    {
        $this->__load();
        return parent::getCreated();
    }

    public function setUpdated(\DateTime $updated)
    {
        $this->__load();
        return parent::setUpdated($updated);
    }

    public function getUpdated()
    {
        $this->__load();
        return parent::getUpdated();
    }

    public function addModule(\Entities\SessionModule $modules)
    {
        $this->__load();
        return parent::addModule($modules);
    }

    public function removeModule(\Entities\SessionModule $modules)
    {
        $this->__load();
        return parent::removeModule($modules);
    }

    public function getModules()
    {
        $this->__load();
        return parent::getModules();
    }

    public function getModuleById($moduleId)
    {
        $this->__load();
        return parent::getModuleById($moduleId);
    }

    public function hasModuleId($moduleId)
    {
        $this->__load();
        return parent::hasModuleId($moduleId);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'usrId', 'userAgent', 'ipAddress', 'platform', 'browserName', 'browserVersion', 'screenWidth', 'screenHeight', 'token', 'nonce', 'created', 'updated', 'modules');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields as $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}