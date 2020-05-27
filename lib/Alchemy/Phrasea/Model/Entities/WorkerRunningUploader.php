<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WorkerRunningUploader",
 *      indexes={
 *          @ORM\index(name="commit_id", columns={"commit_id"}),
 *          @ORM\index(name="asset_id", columns={"asset_id"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WorkerRunningUploaderRepository")
 */
class WorkerRunningUploader
{
    const DOWNLOADED    = 'downloaded';
    const RUNNING       = 'running';

    const TYPE_PULL     = 'pull';
    const TYPE_PUSH     = 'push';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="commit_id")
     */
    private $commitId;

    /**
     * @ORM\Column(type="string", name="asset_id")
     */
    private $assetId;

    /**
     * @ORM\Column(type="string", name="type")
     */
    private $type;

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

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}
