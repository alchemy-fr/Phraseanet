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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="FtpExports")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FtpExportRepository")
 */
class FtpExport
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $crash = 0;

    /**
     * @ORM\Column(type="integer", options={"default" = 3})
     */
    private $nbretry = 3;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $mail;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $addr;

    /**
     * @ORM\Column(type="boolean", name="use_ssl", options={"default" = 0})
     */
    private $ssl = false;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $pwd;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $passif = false;

    /**
     * @ORM\Column(type="string", length=128, options={"default" = "/"})
     */
    private $destfolder = '/';

    /**
     * @ORM\Column(type="string", length=128, nullable=true, options={"default" = 1})
     */
    private $sendermail;

    /**
     * @ORM\Column(type="string", length=128, name="text_mail_sender", nullable=true)
     */
    private $textMailSender;

    /**
     * @ORM\Column(type="string", length=128, name="text_mail_receiver", nullable=true)
     */
    private $textMailReceiver;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $foldertocreate;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $logfile = false;

    /**
     * @ORM\OneToMany(targetEntity="FtpExportElement", mappedBy="export", cascade={"ALL"})
     */
    private $elements;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new ArrayCollection();
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
     * @param User $user
     *
     * @return FtpExport
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
     * Set crash
     *
     * @param integer $crash
     *
     * @return FtpExport
     */
    public function setCrash($crash)
    {
        $this->crash = $crash;

        return $this;
    }

    /**
     * Get crash
     *
     * @return integer
     */
    public function getCrash()
    {
        return $this->crash;
    }

    /**
     * Increment crashes
     *
     * @return FtpExport
     */
    public function incrementCrash()
    {
        $this->crash++;

        return $this;
    }

    /**
     * Set nbretry
     *
     * @param integer $nbretry
     *
     * @return FtpExport
     */
    public function setNbretry($nbretry)
    {
        $this->nbretry = $nbretry;

        return $this;
    }

    /**
     * Get nbretry
     *
     * @return integer
     */
    public function getNbretry()
    {
        return $this->nbretry;
    }

    /**
     * Set mail
     *
     * @param string $mail
     *
     * @return FtpExport
     */
    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get mail
     *
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set addr
     *
     * @param string $addr
     *
     * @return FtpExport
     */
    public function setAddr($addr)
    {
        $this->addr = $addr;

        return $this;
    }

    /**
     * Get addr
     *
     * @return string
     */
    public function getAddr()
    {
        return $this->addr;
    }

    /**
     * Set ssl
     *
     * @param boolean $ssl
     *
     * @return FtpExport
     */
    public function setSsl($ssl)
    {
        $this->ssl = (Boolean) $ssl;

        return $this;
    }

    /**
     * Get ssl
     *
     * @return boolean
     */
    public function isSsl()
    {
        return $this->ssl;
    }

    /**
     * Set login
     *
     * @param string $login
     *
     * @return FtpExport
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set pwd
     *
     * @param string $pwd
     *
     * @return FtpExport
     */
    public function setPwd($pwd)
    {
        $this->pwd = $pwd;

        return $this;
    }

    /**
     * Get pwd
     *
     * @return string
     */
    public function getPwd()
    {
        return $this->pwd;
    }

    /**
     * Set passif
     *
     * @param boolean $passif
     *
     * @return FtpExport
     */
    public function setPassif($passif)
    {
        $this->passif = (Boolean) $passif;

        return $this;
    }

    /**
     * Get passif
     *
     * @return boolean
     */
    public function isPassif()
    {
        return $this->passif;
    }

    /**
     * Set destfolder
     *
     * @param string $destfolder
     *
     * @return FtpExport
     */
    public function setDestfolder($destfolder)
    {
        $this->destfolder = $destfolder;

        return $this;
    }

    /**
     * Get destfolder
     *
     * @return string
     */
    public function getDestfolder()
    {
        return $this->destfolder;
    }

    /**
     * Set sendermail
     *
     * @param string $sendermail
     *
     * @return FtpExport
     */
    public function setSendermail($sendermail)
    {
        $this->sendermail = $sendermail;

        return $this;
    }

    /**
     * Get sendermail
     *
     * @return string
     */
    public function getSendermail()
    {
        return $this->sendermail;
    }

    /**
     * Set textMailSender
     *
     * @param string $textMailSender
     *
     * @return FtpExport
     */
    public function setTextMailSender($textMailSender)
    {
        $this->textMailSender = $textMailSender;

        return $this;
    }

    /**
     * Get textMailSender
     *
     * @return string
     */
    public function getTextMailSender()
    {
        return $this->textMailSender;
    }

    /**
     * Set textMailReceiver
     *
     * @param string $textMailReceiver
     *
     * @return FtpExport
     */
    public function setTextMailReceiver($textMailReceiver)
    {
        $this->textMailReceiver = $textMailReceiver;

        return $this;
    }

    /**
     * Get textMailReceiver
     *
     * @return string
     */
    public function getTextMailReceiver()
    {
        return $this->textMailReceiver;
    }

    /**
     * Set foldertocreate
     *
     * @param string $foldertocreate
     *
     * @return FtpExport
     */
    public function setFoldertocreate($foldertocreate)
    {
        $this->foldertocreate = $foldertocreate;

        return $this;
    }

    /**
     * Get foldertocreate
     *
     * @return string
     */
    public function getFoldertocreate()
    {
        return $this->foldertocreate;
    }

    /**
     * Set logfile
     *
     * @param boolean $logfile
     *
     * @return FtpExport
     */
    public function setLogfile($logfile)
    {
        $this->logfile = (Boolean) $logfile;

        return $this;
    }

    /**
     * Get logfile
     *
     * @return boolean
     */
    public function isLogfile()
    {
        return $this->logfile;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return FtpExport
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

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return FtpExport
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
     * Add elements
     *
     * @param FtpExportElement $elements
     *
     * @return FtpExport
     */
    public function addElement(FtpExportElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * Remove elements
     *
     * @param FtpExportElement $elements
     *
     * @return FtpExport
     */
    public function removeElement(FtpExportElement $elements)
    {
        $this->elements->removeElement($elements);

        return $this;
    }

    /**
     * Get elements
     *
     * @return FtpExportElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }
}
