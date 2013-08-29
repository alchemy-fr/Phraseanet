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
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $provider;

    /**
     * @ORM\Column(type="string", name="distant_id", length=192)
     */
    private $distantId;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $usrId
     *
     * @return UsrAuthProvider
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * @param Application $app
     *
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->usrId, $app);
    }

    /**
     * @param string $provider
     *
     * @return UsrAuthProvider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param  string          $distantId
     * @return UsrAuthProvider
     */
    public function setDistantId($distantId)
    {
        $this->distantId = $distantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDistantId()
    {
        return $this->distantId;
    }

    /**
     * @param \DateTime $updated
     *
     * @return UsrAuthProvider
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
     * @param \DateTime $created
     *
     * @return UsrAuthProvider
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
}
