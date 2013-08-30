<?php

namespace Proxies\__CG__\Entities;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class LazaretFile extends \Entities\LazaretFile implements \Doctrine\ORM\Proxy\Proxy
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

    public function setFilename($filename)
    {
        $this->__load();
        return parent::setFilename($filename);
    }

    public function getFilename()
    {
        $this->__load();
        return parent::getFilename();
    }

    public function setThumbFilename($thumbFilename)
    {
        $this->__load();
        return parent::setThumbFilename($thumbFilename);
    }

    public function getThumbFilename()
    {
        $this->__load();
        return parent::getThumbFilename();
    }

    public function setOriginalName($originalName)
    {
        $this->__load();
        return parent::setOriginalName($originalName);
    }

    public function getOriginalName()
    {
        $this->__load();
        return parent::getOriginalName();
    }

    public function setBaseId($baseId)
    {
        $this->__load();
        return parent::setBaseId($baseId);
    }

    public function getBaseId()
    {
        $this->__load();
        return parent::getBaseId();
    }

    public function getCollection(\Alchemy\Phrasea\Application $app)
    {
        $this->__load();
        return parent::getCollection($app);
    }

    public function setUuid($uuid)
    {
        $this->__load();
        return parent::setUuid($uuid);
    }

    public function getUuid()
    {
        $this->__load();
        return parent::getUuid();
    }

    public function setSha256($sha256)
    {
        $this->__load();
        return parent::setSha256($sha256);
    }

    public function getSha256()
    {
        $this->__load();
        return parent::getSha256();
    }

    public function setForced($forced)
    {
        $this->__load();
        return parent::setForced($forced);
    }

    public function isForced()
    {
        $this->__load();
        return parent::isForced();
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

    public function addAttribute(\Entities\LazaretAttribute $attributes)
    {
        $this->__load();
        return parent::addAttribute($attributes);
    }

    public function removeAttribute(\Entities\LazaretAttribute $attributes)
    {
        $this->__load();
        return parent::removeAttribute($attributes);
    }

    public function getAttributes()
    {
        $this->__load();
        return parent::getAttributes();
    }

    public function addCheck(\Entities\LazaretCheck $checks)
    {
        $this->__load();
        return parent::addCheck($checks);
    }

    public function removeCheck(\Entities\LazaretCheck $checks)
    {
        $this->__load();
        return parent::removeCheck($checks);
    }

    public function getChecks()
    {
        $this->__load();
        return parent::getChecks();
    }

    public function setSession(\Entities\LazaretSession $session = NULL)
    {
        $this->__load();
        return parent::setSession($session);
    }

    public function getSession()
    {
        $this->__load();
        return parent::getSession();
    }

    public function getRecordsToSubstitute(\Alchemy\Phrasea\Application $app)
    {
        $this->__load();
        return parent::getRecordsToSubstitute($app);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'filename', 'thumbFilename', 'originalName', 'base_id', 'uuid', 'sha256', 'forced', 'created', 'updated', 'attributes', 'checks', 'session');
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