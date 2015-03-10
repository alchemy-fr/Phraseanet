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
 * @ORM\Table(name="FtpCredential")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FtpCredentialRepository")
 */
class FtpCredential
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="ftpCredential")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     **/
    private $user;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $active = false;

    /**
     * @ORM\Column(type="string", length=128, options={"default" = ""})
     */
    private $address = '';

    /**
     * @ORM\Column(type="string", length=128, options={"default" = ""})
     */
    private $login = '';

    /**
     * @ORM\Column(type="string", length=128, options={"default" = ""})
     */
    private $password = '';

    /**
     * @ORM\Column(type="string", length=128, name="reception_folder", options={"default" = ""})
     */
    private $receptionFolder = '';

    /**
     * @ORM\Column(type="string", length=128, name="repository_prefix_name", options={"default" = ""})
     */
    private $repositoryPrefixName = '';

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $passive = false;

    /**
     * @ORM\Column(type="boolean", name="tls", options={"default" = 0})
     */
    private $ssl = false;

    /**
     * @ORM\Column(type="integer", name="max_retry", options={"default" = 5})
     */
    private $maxRetry = 5;

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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return FtpCredential
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     *
     * @return FtpCredential
     */
    public function setActive($active)
    {
        $this->active = (Boolean) $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     *
     * @return FtpCredential
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     *
     * @return FtpCredential
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return FtpCredential
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceptionFolder()
    {
        return $this->receptionFolder;
    }

    /**
     * @param string $receptionFolder
     *
     * @return FtpCredential
     */
    public function setReceptionFolder($receptionFolder)
    {
        $this->receptionFolder = $receptionFolder;

        return $this;
    }

    /**
     * @return string
     */
    public function getRepositoryPrefixName()
    {
        return $this->repositoryPrefixName;
    }

    /**
     * @param string $repositoryPrefixName
     *
     * @return FtpCredential
     */
    public function setRepositoryPrefixName($repositoryPrefixName)
    {
        $this->repositoryPrefixName = $repositoryPrefixName;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPassive()
    {
        return $this->passive;
    }

    /**
     * @param string $passive
     *
     * @return FtpCredential
     */
    public function setPassive($passive)
    {
        $this->passive = (Boolean) $passive;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSsl()
    {
        return $this->ssl;
    }

    /**
     * @param string $ssl
     *
     * @return FtpCredential
     */
    public function setSsl($ssl)
    {
        $this->ssl = (Boolean) $ssl;

        return $this;
    }

    /**
     * @return integer
     */
    public function getMaxRetry()
    {
        return $this->maxRetry;
    }

    /**
     * @param string $maxRetry
     *
     * @return FtpCredential
     */
    public function setMaxRetry($maxRetry)
    {
        $this->maxRetry = $maxRetry;

        return $this;
    }

    /**
     * @return \Datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return FtpCredential
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }
}
