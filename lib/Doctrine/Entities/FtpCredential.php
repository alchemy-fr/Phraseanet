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
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->usrId, $app);
    }

    /**
     * @return \User_Adapter
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
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = (Boolean) $active;
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
     */
    public function setAddress($address)
    {
        $this->address = $address;
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
     */
    public function setLogin($login)
    {
        $this->login = $login;
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
     */
    public function setPassword($password)
    {
        $this->password = $password;
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
     */
    public function setReceptionFolder($receptionFolder)
    {
        $this->receptionFolder = $receptionFolder;
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
     */
    public function setRepositoryPrefixName($repositoryPrefixName)
    {
        $this->repositoryPrefixName = $repositoryPrefixName;
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
     */
    public function setPassive($passive)
    {
        $this->passive = (Boolean) $passive;
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
     */
    public function setSsl($ssl)
    {
        $this->ssl = (Boolean) $ssl;
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
     */
    public function setMaxRetry($maxRetry)
    {
        $this->maxRetry = $maxRetry;
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
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }
}
