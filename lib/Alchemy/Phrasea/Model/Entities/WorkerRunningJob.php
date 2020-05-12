<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;

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
}
