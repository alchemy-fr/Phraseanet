<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Alchemy\Phrasea\Application;

/**
 * @ORM\Table(name="FtpCredential")
 * @ORM\Entity(repositoryClass="Repositories\FtpCredentialRepository")
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
     * @ORM\Column(type="integer")
     */
    private $usrId;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="ftpCredential")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = false;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $address = '';

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $login = '';

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $password = '';

    /**
     * @ORM\Column(type="string", length=128, name="reception_folder")
     */
    private $receptionFolder = '';

    /**
     * @ORM\Column(type="string", length=128, name="repository_prefix_name")
     */
    private $repositoryPrefixName = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private $passive = false;

    /**
     * @ORM\Column(type="boolean", name="tls")
     */
    private $ssl = false;

    /**
     * @ORM\Column(type="integer", name="max_retry")
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
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * @param integer $usrId
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;
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

    /**
     * @return FtpCredential
     */
    public function resetCredentials()
    {
        $this->active = false;
        $this->address = '';
        $this->login = '';
        $this->maxRetry = 5;
        $this->passive = false;
        $this->password = '';
        $this->receptionFolder = '';
        $this->repositoryPrefixName = '';
        $this->ssl = false;

        return $this;
    }
}
