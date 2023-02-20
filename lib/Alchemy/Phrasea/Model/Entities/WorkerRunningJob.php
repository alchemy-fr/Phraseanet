<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(
 *     name="WorkerRunningJob",
 *     uniqueConstraints={
 *          @ORM\uniqueConstraint(name="flock", columns={"databox_id", "record_id", "flock"})
 *      },
 *     indexes={
 *          @ORM\index(name="worker_running_job_databox_id", columns={"databox_id"}),
 *          @ORM\index(name="worker_running_job_record_id", columns={"record_id"}),
 *          @ORM\index(name="worker_running_job_work", columns={"work"}),
 *          @ORM\index(name="worker_running_job_created", columns={"created"}),
 *          @ORM\index(name="worker_running_job_published", columns={"published"}),
 *          @ORM\index(name="worker_running_job_finished", columns={"finished"}),
 *          @ORM\index(name="worker_running_job_status", columns={"status"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository")
 */
class WorkerRunningJob
{
    const FINISHED = 'finished';
    const RUNNING  = 'running';
    const ERROR    = 'error';
    const INTERRUPT = 'canceled';

    const ATTEMPT  = 'attempt ';

    const TYPE_PULL     = 'uploader pull';
    const TYPE_PUSH     = 'uploader push';

    const MAX_RESULT = 500;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="databox_id", nullable=true)
     */
    private $databoxId;

    /**
     * @ORM\Column(type="integer", name="record_id", nullable=true)
     */
    private $recordId;

    /**
     * @ORM\Column(type="string", length=64, name="flock", nullable=true)
     */
    private $flock;

    /**
     * @ORM\Column(type="string", length=64, name="work", nullable=true)
     */
    private $work;

    /**
     * @ORM\Column(type="string", length=64, name="work_on", nullable=true)
     */
    private $workOn;

    /**
     * @ORM\Column(type="string", name="commit_id", nullable=true)
     */
    private $commitId;

    /**
     * @ORM\Column(type="string", name="asset_id", nullable=true)
     */
    private $assetId;

    /**
     * @ORM\Column(type="string", name="info", nullable=true)
     */
    private $info;

    /**
     * @ORM\Column(type="json_array", name="payload", nullable=true)
     */
    private $payload;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finished;

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
     * @return mixed
     */
    public function getFlock()
    {
        return $this->flock;
    }

    /**
     * @param mixed $flock
     * @return WorkerRunningJob
     */
    public function setFlock($flock)
    {
        $this->flock = $flock;

        return $this;
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
     * @param $commitId
     * @return $this
     */
    public function setCommitId($commitId)
    {
        $this->commitId = $commitId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCommitId()
    {
        return $this->commitId;
    }

    /**
     * @param $assetId
     * @return $this
     */
    public function setAssetId($assetId)
    {
        $this->assetId = $assetId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * @param $info
     * @return $this
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param array $payload
     *
     * @return WorkerRunningJob
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;

        return $this;
    }

    public function getPayload()
    {
        return $this->payload;
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
