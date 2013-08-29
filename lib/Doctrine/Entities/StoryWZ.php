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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="StoryWZ", uniqueConstraints={@ORM\UniqueConstraint(name="user_story", columns={"usr_id", "sbas_id", "record_id"})})
 * @ORM\Entity(repositoryClass="Repositories\StoryWZRepository")
 */
class StoryWZ
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="sbas_id")
     */
    private $sbasId;

    /**
     * @ORM\Column(type="integer", name="record_id")
     */
    private $recordId;

    /**
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

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
     * @param  integer $sbasId
     * 
     * @return StoryWZ
     */
    public function setSbasId($sbasId)
    {
        $this->sbasId = $sbasId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getSbasId()
    {
        return $this->sbasId;
    }

    /**
     * @param  integer $recordId
     * 
     * @return StoryWZ
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * @param Application $app
     * 
     * @return \record_adapter
     */
    public function getRecord(Application $app)
    {
        return new \record_adapter($app, $this->getSbasId(), $this->getRecordId());
    }

    /**
     * @param \record_adapter $record
     * 
     * @return StoryWZ
     */
    public function setRecord(\record_adapter $record)
    {
        $this->setRecordId($record->get_record_id());
        $this->setSbasId($record->get_sbas_id());
        
        return $this;
    }

    /**
     * @param  integer $usrId
     * 
     * @return StoryWZ
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
     * 
     * @param \User_Adapter $user
     * 
     * @return StoryWZ
     */
    public function setUser(\User_Adapter $user)
    {
        return $this->setUsrId($user->get_id());
    }

    /**
     * @param Application $app
     * 
     * @return \User_Adapter|null
     */
    public function getUser(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
    }

    /**
     * @param  \DateTime $created
     * 
     * @return StoryWZ
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
