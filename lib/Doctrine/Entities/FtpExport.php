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

use Alchemy\Phrasea\Application;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="FtpExports")
 * @ORM\Entity(repositoryClass="Repositories\FtpExportRepository")
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
     * @ORM\Column(type="integer")
     */
    private $crash = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $nbretry = 3;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $mail;

    /**
     * @ORM\Column(type="text")
     */
    private $addr;

    /**
     * @ORM\Column(type="boolean")
     */
    private $ssl = false;

    /**
     * @ORM\Column(type="text")
     */
    private $login;

    /**
     * @ORM\Column(type="text")
     */
    private $pwd;

    /**
     * @ORM\Column(type="boolean")
     */
    private $passif = false;

    /**
     * @ORM\Column(type="text")
     */
    private $destfolder;

    /**
     * @ORM\Column(type="text")
     */
    private $sendermail;

    /**
     * @ORM\Column(type="text", name="text_mail_sender")
     */
    private $textMailSender;

    /**
     * @ORM\Column(type="text", name="text_mail_receiver")
     */
    private $textMailReceiver;

    /**
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="text")
     */
    private $foldertocreate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $logfile;

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
     * Set crash
     *
     * @param  integer   $crash
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
     * @param  integer   $nbretry
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
     * @param  string    $mail
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
     * @param  string    $addr
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
     * @param  boolean   $ssl
     * @return FtpExport
     */
    public function setSsl($ssl)
    {
        $this->ssl = $ssl;

        return $this;
    }

    /**
     * Get ssl
     *
     * @return boolean
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * Set login
     *
     * @param  string    $login
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
     * @param  string    $pwd
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
     * @param  boolean   $passif
     * @return FtpExport
     */
    public function setPassif($passif)
    {
        $this->passif = $passif;

        return $this;
    }

    /**
     * Get passif
     *
     * @return boolean
     */
    public function getPassif()
    {
        return $this->passif;
    }

    /**
     * Set destfolder
     *
     * @param  string    $destfolder
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
     * @param  string    $sendermail
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
     * @param  string    $textMailSender
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
     * @param  string    $textMailReceiver
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
     * Set usrId
     *
     * @param  integer   $usrId
     * @return FtpExport
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * Get usrId
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * Get user
     *
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        return \User_Adapter::getInstance($this->getUsr_id(), $app);
    }

    /**
     * Set user
     *
     * @param  \User_Adapter $user
     * @return FtpExport
     */
    public function setUser(\User_Adapter $user)
    {
        $this->setUsr_id($user->get_id());

        return $this;
    }

    /**
     * Set foldertocreate
     *
     * @param  string    $foldertocreate
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
     * @param  boolean   $logfile
     * @return FtpExport
     */
    public function setLogfile($logfile)
    {
        $this->logfile = $logfile;

        return $this;
    }

    /**
     * Get logfile
     *
     * @return boolean
     */
    public function getLogfile()
    {
        return $this->logfile;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return FtpExport
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

    /**
     * Set updated
     *
     * @param  \DateTime $updated
     * @return FtpExport
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
     * Add elements
     *
     * @param  \Entities\FtpExportElement $elements
     * @return FtpExport
     */
    public function addElement(\Entities\FtpExportElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * Remove elements
     *
     * @param \Entities\FtpExportElement $elements
     */
    public function removeElement(\Entities\FtpExportElement $elements)
    {
        $this->elements->removeElement($elements);
    }

    /**
     * Get elements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getElements()
    {
        return $this->elements;
    }
}
