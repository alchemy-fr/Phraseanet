<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="ApiAccounts", indexes={@ORM\Index(name="usr_id", columns={"usr_id"}), @ORM\Index(name="application_id", columns={"application_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ApiAccountRepository")
 */
class ApiAccount
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
     * @var integer
     *
     * @ORM\Column(type="boolean")
     */
    private $revoked;

    /**
     * @var string
     *
     * @ORM\Column(name="api_version", type="string", length=16, nullable=false)
     */
    private $apiVersion;

    /**
     * @ORM\ManyToOne(targetEntity="ApiApplication")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", nullable=false)
     *
     * @return ApiApplication
     **/
    private $application;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @param string $apiVersion
     *
     * @return ApiAccount
     */
    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @param ApiApplication $application
     *
     * @return ApiAccount
     */
    public function setApplication(ApiApplication $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return ApiApplication
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param \DateTime $created
     *
     * @return ApiAccount
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

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $revoked
     *
     * @return ApiAccount
     */
    public function setRevoked($revoked)
    {
        $this->revoked = (Boolean) $revoked;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRevoked()
    {
        return $this->revoked;
    }

    /**
     * @param User $user
     *
     * @return ApiAccount
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
}
