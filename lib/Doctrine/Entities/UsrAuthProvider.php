<?php

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 * UsrAuthProvider
 */
class UsrAuthProvider
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
    private $provider;

    /**
     * @var string
     */
    private $distant_id;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \DateTime
     */
    private $created;

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
     * @param  integer         $usrId
     * @return UsrAuthProvider
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

    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->usr_id, $app);
    }

    /**
     * Set provider
     *
     * @param  string          $provider
     * @return UsrAuthProvider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set distant_id
     *
     * @param  string          $distantId
     * @return UsrAuthProvider
     */
    public function setDistantId($distantId)
    {
        $this->distant_id = $distantId;

        return $this;
    }

    /**
     * Get distant_id
     *
     * @return string
     */
    public function getDistantId()
    {
        return $this->distant_id;
    }

    /**
     * Set updated
     *
     * @param  \DateTime       $updated
     * @return UsrAuthProvider
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
     * Set created
     *
     * @param  \DateTime       $created
     * @return UsrAuthProvider
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
}
