<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WorkerRunningJob",
 *      indexes={
 *          @ORM\index(name="databox_id", columns={"databox_id"}),
 *          @ORM\index(name="record_id", columns={"record_id"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository")
 */
class WorkerRunningJob
{
    const FINISHED = 'finished';
    const RUNNING  = 'running';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */

    private $id;

    /**
     * @ORM\Column(type="integer", name="databox_id")
     */
    private $databoxId;

    /**
     * @ORM\Column(type="integer", name="record_id")
     */
    private $recordId;

    /**
     * @ORM\Column(type="integer", name="work")
     */
    private $work;

    /**
     * @ORM\Column(type="string", name="work_on")
     */
    private $workOn;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $published;

    /**
     * @ORM\Column(type="string", name="status")
     */
    private $status;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $databoxId
     * @return $this
     */
    public function setDataboxId($databoxId)
    {
        $this->databoxId = $databoxId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataboxId()
    {
        return $this->databoxId;
    }


    /**
     * @param $recordId
     * @return $this
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecordId()
    {
        return $this->recordId;

    }


    /**
     * @param $work
     * @return $this
     */
    public function setWork($work)
    {
        $this->work = $work;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWork()
    {
        return $this->work;
    }


    /**
     * @param $workOn
     * @return $this
     */
    public function setWorkOn($workOn)
    {
        $this->workOn = $workOn;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWorkOn()
    {
        return $this->workOn;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $published
     * @return $this
     */
    public function setPublished(\DateTime $published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }
}
