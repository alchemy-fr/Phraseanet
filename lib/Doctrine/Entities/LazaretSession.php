<?php

namespace Entities;


/**
 * Entities\LazaretSession
 */
class LazaretSession
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
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $updated
     */
    private $updated;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $files;

    public function __construct()
    {
        $this->files = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param  integer        $usrId
     * @return LazaretSession
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * Get user
     *
     * @return \User_Adapter
     */
    public function getUser()
    {
        $user = null;

        try {
            $appbox = \appbox::get_instance(\bootstrap::getCore());
            $user = \User_Adapter::getInstance($this->usr_id, $appbox);
        } catch (\Exception $e) {

        }

        return $user;
    }

    /**
     * Set created
     *
     * @param  datetime       $created
     * @return LazaretSession
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
     * @param  datetime       $updated
     * @return LazaretSession
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
     * Add files
     *
     * @param  Entities\LazaretFile $files
     * @return LazaretSession
     */
    public function addLazaretFiles(\Entities\LazaretFile $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * Get files
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Add files
     *
     * @param  Entities\LazaretFile $files
     * @return LazaretSession
     */
    public function addLazaretFile(\Entities\LazaretFile $files)
    {
        $this->files[] = $files;

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
}