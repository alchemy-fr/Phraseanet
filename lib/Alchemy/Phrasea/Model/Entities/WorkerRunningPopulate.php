<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="WorkerRunningPopulate",
 *      indexes={
 *          @ORM\index(name="host", columns={"host"}),
 *          @ORM\index(name="port", columns={"port"}),
 *          @ORM\index(name="index_name", columns={"index_name"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\WorkerRunningPopulateRepository")
 */
class WorkerRunningPopulate
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
     * @ORM\Column(type="string", name="host")
     */
    private $host;

    /**
     * @ORM\Column(type="string", name="port")
     */
    private $port;

    /**
     * @ORM\Column(type="string", name="index_name")
     */
    private $indexName;

    /**
     * @ORM\Column(type="integer", name="databox_id")
     */
    private $databoxId;

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
     * @param $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param $indexName
     * @return $this
     */
    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndexName()
    {
        return $this->indexName;
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
