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
 * @ORM\Table(name="LazaretSessions")
 * @ORM\Entity
 */
class LazaretSession
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

     /**
     * @ORM\Column(type="integer", name="usr_id", nullable=true)
     */
    private $usrId;

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
     * @ORM\OneToMany(targetEntity="LazaretFile", mappedBy="session", cascade={"all"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $files;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  integer        $usrId
     * 
     * @return LazaretSession
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
     * @return \User_Adapter
     */
    public function getUser(Application $app)
    {
        $user = null;

        try {
            $user = \User_Adapter::getInstance($this->usrId, $app);
        } catch (\Exception $e) {

        }

        return $user;
    }

    /**
     * @param  \DateTime      $created
     * 
     * @return LazaretSession
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
     * @param  \DateTime      $updated
     * 
     * @return LazaretSession
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
     * @param  LazaretFile $files
     * @return LazaretSession
     */
    public function addFile(LazaretFile $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * @param LazaretFile $files
     */
    public function removeFile(LazaretFile $files)
    {
        $this->files->removeElement($files);
    }

    /**
     * @return LazaretFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }
}
