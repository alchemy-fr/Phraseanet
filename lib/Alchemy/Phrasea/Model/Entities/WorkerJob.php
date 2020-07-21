<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WorkerJob", indexes={@ORM\Index(name="worker_job_type", columns={"type"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WorkerJobRepository")
 */
class WorkerJob
{
    const WAITING   = "waiting";
    const RUNNING   = "running";
    const FINISHED  = "finished";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="type")
     */
    private $type;

    /**
     * @ORM\Column(type="json_array", name="data", nullable=false)
     */
    private $data;

    /**
     * @ORM\Column(type="string", name="status")
     */
    private $status;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $started;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finished;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $data
     *
     * @return WorkerJob
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $finished
     * @return $this
     */
    public function setFinished(\DateTime $finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFinished()
    {
        return $this->finished;
    }

    /**
     * @param \DateTime $started
     * @return $this
     */
    public function setStarted(\DateTime $started)
    {
        $this->started = $started;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStarted()
    {
        return $this->started;
    }
}
