<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="UsrAuthProviders", uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_provider_per_user", columns={"usr_id", "provider"}),
 *          @ORM\UniqueConstraint(name="provider_ids", columns={"provider", "distant_id"})
 * })
 * @ORM\Entity(repositoryClass="Repositories\UsrAuthProviderRepository")
 */
class UsrAuthProvider
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
     * @ORM\Column(type="string", length=32)
     */
    private $provider;

    /**
     * @ORM\Column(type="string", length=192)
     */
    private $distant_id;

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
