<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entities\SessionModule
 */
class SessionModule
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $module_id
     */
    private $module_id;

    /**
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $updated
     */
    private $updated;

    /**
     * @var Entities\Session
     */
    private $session;


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
     * Set module_id
     *
     * @param integer $moduleId
     * @return SessionModule
     */
    public function setModuleId($moduleId)
    {
        $this->module_id = $moduleId;
        return $this;
    }

    /**
     * Get module_id
     *
     * @return integer 
     */
    public function getModuleId()
    {
        return $this->module_id;
    }

    /**
     * Set created
     *
     * @param datetime $created
     * @return SessionModule
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
     * @return SessionModule
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

    /**
     * Set session
     *
     * @param Entities\Session $session
     * @return SessionModule
     */
    public function setSession(\Entities\Session $session = null)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Get session
     *
     * @return Entities\Session 
     */
    public function getSession()
    {
        return $this->session;
    }
}