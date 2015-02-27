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

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Tasks")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\TaskRepository")
 */
class Task
{
    const STATUS_STARTED = 'started';
    const STATUS_STOPPED = 'stopped';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $jobId;

    /**
     * @ORM\Column(type="text")
     */
    private $settings = '<?xml version="1.0" encoding="UTF-8"?><tasksettings></tasksettings>';

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $completed = false;

    /**
     * @ORM\Column(type="string", options={"default" = "started"})
     */
    private $status = self::STATUS_STARTED;

    /**
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $crashed = 0;

    /**
     * @ORM\Column(type="boolean", name="single_run", options={"default" = 0})
     */
    private $singleRun = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="last_execution")
     */
    private $lastExecution;

    /**
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $period = 0;

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
     * Set name
     *
     * @param  string $name
     * @return Task
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set JobId
     *
     * @param  string $jobId
     * @return Task
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * Get JobId
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Set settings
     *
     * @param  string $settings
     * @return Task
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return string
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set completed
     *
     * @param  boolean $completed
     * @return Task
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * Get completed
     *
     * @return boolean
     */
    public function isCompleted()
    {
        return $this->completed;
    }

    /**
     * Set status
     *
     * @param  string $status
     * @return Task
     */
    public function setStatus($status)
    {
        if (!in_array($status, [static::STATUS_STARTED, static::STATUS_STOPPED], true)) {
            throw new InvalidArgumentException('Invalid status value.');
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set crashed
     *
     * @param  integer $crashed
     * @return Task
     */
    public function setCrashed($crashed)
    {
        $this->crashed = $crashed;

        return $this;
    }

    /**
     * Get crashed
     *
     * @return integer
     */
    public function getCrashed()
    {
        return $this->crashed;
    }

    /**
     * Set task whether single run or not (run once and die).
     *
     * @param $singleRun
     * @return Task
     */
    public function setSingleRun($singleRun)
    {
        $this->singleRun = (Boolean) $singleRun;

        return $this;
    }

    /**
     * Return true if the task is single run (run once and die).
     *
     * @return boolean
     */
    public function isSingleRun()
    {
        return $this->singleRun;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return Task
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
     * @return Task
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
     * Set last execution
     *
     * @param  \DateTime $lastExecution
     * @return Task
     */
    public function setExecuted($lastExecution)
    {
        $this->lastExecution = $lastExecution;

        return $this;
    }

    /**
     * Get last execution
     *
     * @return \DateTime
     */
    public function getLastExecution()
    {
        return $this->lastExecution;
    }

    public function getPeriod()
    {
        return $this->period;
    }

    public function setPeriod($period)
    {
        $this->period = (integer) $period;

        return $this;
    }
}
