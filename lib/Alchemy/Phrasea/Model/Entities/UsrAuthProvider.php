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
 * @ORM\Table(name="UsrAuthProviders", uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_provider_per_user", columns={"user_id", "provider"}),
 *          @ORM\UniqueConstraint(name="provider_ids", columns={"provider", "distant_id"})
 * })
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository")
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

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
     * @param User $user
     *
     * @return usrAuthprovider
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
     * Set created
     *
     * @param  \DateTime       $created
     * @return UsrAuthProvider
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
}
