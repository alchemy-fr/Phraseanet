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

use Alchemy\Phrasea\Application;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="StoryWZ", uniqueConstraints={@ORM\UniqueConstraint(name="user_story", columns={"user_id", "sbas_id", "record_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\StoryWZRepository")
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
     * @ORM\Column(type="integer")
     */
    private $sbas_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $record_id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

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
     * Set sbas_id
     *
     * @param  integer $sbasId
     * @return StoryWZ
     */
    public function setSbasId($sbasId)
    {
        $this->sbas_id = $sbasId;

        return $this;
    }

    /**
     * Get sbas_id
     *
     * @return integer
     */
    public function getSbasId()
    {
        return $this->sbas_id;
    }

    /**
     * Set record_id
     *
     * @param  integer $recordId
     * @return StoryWZ
     */
    public function setRecordId($recordId)
    {
        $this->record_id = $recordId;

        return $this;
    }

    /**
     * Get record_id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    public function getRecord(Application $app)
    {
        return new \record_adapter($app, $this->getSbasId(), $this->getRecordId());
    }

    public function setRecord(\record_adapter $record)
    {
        $this->setRecordId($record->getRecordId());
        $this->setSbasId($record->getDataboxId());
    }
    /**
     * @param User $user
     *
     * @return StoryWZ
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
     * Set created
     *
     * @param  \DateTime $created
     * @return StoryWZ
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
