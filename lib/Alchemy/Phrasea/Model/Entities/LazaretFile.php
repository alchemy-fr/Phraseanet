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
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use \record_adapter;

/**
 * @ORM\Table(name="LazaretFiles")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\LazaretFileRepository")
 */
class LazaretFile
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $thumbFilename;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $originalName;

    /**
     * @ORM\Column(type="integer")
     */
    private $base_id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $sha256;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $forced = false;

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
     * @ORM\OneToMany(targetEntity="LazaretAttribute", mappedBy="lazaretFile", cascade={"all"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $attributes;

    /**
     * @ORM\OneToMany(targetEntity="LazaretCheck", mappedBy="lazaretFile", cascade={"all"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $checks;

    /**
     * @ORM\ManyToOne(targetEntity="LazaretSession", inversedBy="files", cascade={"persist"})
     * @ORM\JoinColumn(name="lazaret_session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attributes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->checks = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set filename
     *
     * @param  string      $filename
     * @return LazaretFile
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set thumbFilename
     *
     * @param  string      $thumbFilename
     * @return LazaretFile
     */
    public function setThumbFilename($thumbFilename)
    {
        $this->thumbFilename = $thumbFilename;

        return $this;
    }

    /**
     * Get thumbFilename
     *
     * @return string
     */
    public function getThumbFilename()
    {
        return $this->thumbFilename;
    }

    /**
     * Set originalName
     *
     * @param  string      $originalName
     * @return LazaretFile
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get originalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set base_id
     *
     * @param  integer     $baseId
     * @return LazaretFile
     */
    public function setBaseId($baseId)
    {
        $this->base_id = $baseId;

        return $this;
    }

    /**
     * Get base_id
     *
     * @return integer
     */
    public function getBaseId()
    {
        return $this->base_id;
    }

    /**
     * Get the Destination Collection
     *
     * @return \collection
     */
    public function getCollection(Application $app)
    {
        return \collection::getByBaseId($app, $this->getBaseId());
    }

    /**
     * Set uuid
     *
     * @param  string      $uuid
     * @return LazaretFile
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set sha256
     *
     * @param  string      $sha256
     * @return LazaretFile
     */
    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;

        return $this;
    }

    /**
     * Get sha256
     *
     * @return string
     */
    public function getSha256()
    {
        return $this->sha256;
    }

    /**
     * Set forced
     *
     * @param  boolean     $forced
     * @return LazaretFile
     */
    public function setForced($forced)
    {
        $this->forced = $forced;

        return $this;
    }

    /**
     * Get forced
     *
     * @return boolean
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * Set created
     *
     * @param  \DateTime   $created
     * @return LazaretFile
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

    /**
     * Set updated
     *
     * @param  \DateTime   $updated
     * @return LazaretFile
     */
    public function setUpdated(\DateTime $updated)
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
     * Add attributes
     *
     * @param  LazaretAttribute $attributes
     * @return LazaretFile
     */
    public function addAttribute(LazaretAttribute $attributes)
    {
        $this->attributes[] = $attributes;

        return $this;
    }

    /**
     * Remove attributes
     *
     * @param LazaretAttribute $attributes
     */
    public function removeAttribute(LazaretAttribute $attributes)
    {
        $this->attributes->removeElement($attributes);
    }

    /**
     * Get attributes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Add checks
     *
     * @param  LazaretCheck $checks
     * @return LazaretFile
     */
    public function addCheck(LazaretCheck $checks)
    {
        $this->checks[] = $checks;

        return $this;
    }

    /**
     * Remove checks
     *
     * @param LazaretCheck $checks
     */
    public function removeCheck(LazaretCheck $checks)
    {
        $this->checks->removeElement($checks);
    }

    /**
     * @param LazaretCheck $checks
     * @return string
     */
    public function getCheckerName(LazaretCheck $checks)
    {
        $checkNameTab = explode('\\', $checks->getCheckClassname());

        return $checkNameTab[4];
    }

    /**
     * Get checks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChecks()
    {
        return $this->checks;
    }

    /**
     *  get the actual eligible checker from the list saved in DB when uploaded file
     *
     * @param Application $app
     * @return array
     */
    public function getEligibleChecks(Application $app)
    {
        $eligibleChecks = [];
        foreach($this->getChecks() as $check) {
            try {
                $app['border-manager']->getCheckerFromFQCN($check->getCheckClassname());
                $eligibleChecks[] = $check;
            } catch (RuntimeException $e) {
                // the checker is not enable ( not found )
                continue;
            }
        }

        return $eligibleChecks;
    }

    /**
     * @return array $checkers
     */
    public function getChecksWhithNameKey()
    {
        $checkers = [];
        foreach($this->checks as $check){
            $checkers[$this->getCheckerName($check)] = $check;
        }

        return $checkers;
    }

    /**
     * Set session
     *
     * @param  LazaretSession $session
     * @return LazaretFile
     */
    public function setSession(LazaretSession $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return LazaretSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get an array of records that can be substitued by the Lazaret file
     *
     * @return record_adapter[]
     */
    public function getRecordsToSubstitute(Application $app, $includeReason = false)
    {
        $merged = [];
        /** @var LazaretCheck $check */
        foreach($this->getEligibleChecks($app) as $check) {
            /** @var record_adapter $record */
            $conflicts = $check->listConflicts($app);
            foreach ($conflicts as $record) {
                if($includeReason) {
                    if (!array_key_exists($record->getRecordId(), $merged)) {
                        $merged[$record->getRecordId()] = [
                            'record' => $record,
                            'reasons' => []
                        ];
                    }
                    $merged[$record->getRecordId()]['reasons'][$this->getCheckerName($check)] = $check->getReason($app['translator']);
                }
                else {
                    $merged[$record->getRecordId()] = $record;
                }
            }
        }

        return $merged;
    }

    /**
     * @param Application $app
     * @return array|null
     */
    public function getStatus(Application $app)
    {
        /**@var LazaretAttribute $atribute*/
        foreach ($this->attributes as $atribute) {
            if ($atribute->getName() == AttributeInterface::NAME_STATUS) {
                $databox = $this->getCollection($app)->get_databox();
                $statusStructure = $databox->getStatusStructure();
                $recordsStatuses = [];
                foreach ($statusStructure as $status) {
                    $bit = $status['bit'];
                    if (!isset($recordsStatuses[$bit])) {
                        $recordsStatuses[$bit] = $status;
                    }
                    $statusSet = \databox_status::bitIsSet(bindec($atribute->getValue()), $bit);
                    if (!isset($recordsStatuses[$bit]['flag'])) {
                        $recordsStatuses[$bit]['flag'] = (int) $statusSet;
                    }
                }
                return $recordsStatuses;
            }
        }
        return null;
    }

}
