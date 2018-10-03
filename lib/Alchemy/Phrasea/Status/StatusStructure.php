<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Status;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Aim to represent status structure of a databox
 * which is the combination of a databox and a collection of status
 */
class StatusStructure implements \IteratorAggregate
{
    private $databox;
    private $status;
    private $url;
    private $path;

    public function __construct(\databox $databox, ArrayCollection $status = null)
    {
        $this->databox = $databox;
        $this->status = $status ?: new ArrayCollection();

        $unique_id = md5(implode('-', array(
            $this->databox->get_host(),
            $this->databox->get_port(),
            $this->databox->get_dbname()
        )));

        // path to status icon
        $this->path = __DIR__ . "/../../../../config/status/" . $unique_id;
        // url to status icon
        $this->url = "/custom/status/" . $unique_id;
    }

    public function getIterator()
    {
        return $this->status->getIterator();
    }

    /**
     * Get url to status icons
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get path to status icons
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get databox where belong status
     *
     * @return \databox
     */
    public function getDatabox()
    {
        return $this->databox;
    }

    /**
     * Get the bits used in structure
     * $
     * @return array[] int
     */
    public function getBits()
    {
        return $this->status->getKeys();
    }

    /**
     * Set new status at nth position
     * 
     * @param int   $nthBit
     * @param array $status
     *
     * @return $this
     */
    public function setStatus($nthBit, array $status)
    {
        $this->status->set($nthBit, $status);

        return $this;
    }

    /**
     * Check whether structure has nth status set
     *
     * @param int $nthBit
     *
     * @return bool
     */
    public function hasStatus($nthBit)
    {
        return $this->status->containsKey($nthBit);
    }

    /**
     * Get status at nth position
     *
     * @param int $nthBit
     *
     * @return array|null
     */
    public function getStatus($nthBit)
    {
        return $this->status->get($nthBit);
    }

    /**
     * Remove status at nth position
     *
     * @param int $nthBit
     *
     * @return $this
     */
    public function removeStatus($nthBit)
    {
        $this->status->remove($nthBit);

        return $this;
    }

    public function toArray()
    {
        return $this->status->toArray();
    }
}
