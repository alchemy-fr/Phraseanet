<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;

/**
 * The abstract checker class
 */
abstract class AbstractChecker implements CheckerInterface
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var \databox[]
     */
    protected $databoxes = [];

    /**
     * @var \collection[]
     */
    protected $collections = [];

    /**
     * @var \collection[]
     */
    protected $compareIgnoreCollections = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Restrict the checker to a set of databoxes.
     * Warning, you can not restrict on both databoxes and collections
     *
     * @param  \databox[] $databoxes A databox or an array of databoxes
     * @return \databox[]
     *
     * @throws \LogicException           If already restricted to collections
     * @throws \InvalidArgumentException In case invalid databoxes are provided
     */
    public function restrictToDataboxes($databoxes)
    {
        if ($this->collections) {
            throw new \LogicException('You can not restrict on databoxes and collections simultanously');
        }

        $this->databoxes = [];

        foreach ($this->toIterator($databoxes) as $databox) {
            if (!$databox instanceof \databox) {
                throw new \InvalidArgumentException('Restrict to databoxes only accept databoxes as argument');
            }
            $this->databoxes[] = $databox;
        }

        return $this->databoxes;
    }

    /**
     * Restrict the checker to a set of collections.
     * Warning, you can not restrict on both databoxes and collections
     *
     * @param  \collection[] $collections
     * @return \collection[]
     *
     * @throws \LogicException           If already restricted to databoxes
     * @throws \InvalidArgumentException In case invalid collections are provided
     */
    public function restrictToCollections($collections)
    {
        if ($this->databoxes) {
            throw new \LogicException('You can not restrict on databoxes and collections simultanously');
        }

        $this->collections = [];

        foreach ($this->toIterator($collections) as $collection) {
            if (!$collection instanceof \collection) {
                throw new \InvalidArgumentException('Restrict to collections only accept collections as argument');
            }
            $this->collections[] = $collection;
        }

        return $this->collections;
    }

    public function setCompareIgnoreCollections($collections)
    {
        $this->compareIgnoreCollections = $collections;
    }

    /**
     * Returns true if the checker should be executed against the current file
     *
     * @param File $file A file to check
     *
     * @return Boolean
     */
    public function isApplicable(File $file)
    {
        if (empty($this->databoxes) && empty($this->collections)) {
            return true;
        }

        if (null === $file->getCollection()) {
            return true;
        }

        $fileDatabox = $file->getCollection()->get_databox();

        foreach ($this->databoxes as $databox) {
            if ($databox->get_sbas_id() ===
                $fileDatabox->get_sbas_id()) {
                return true;
            }
        }

        foreach ($this->collections as $collection) {
            if ($collection->get_base_id() === $file->getCollection()->get_base_id()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Makes datas iterable
     *
     * @return \ArrayObject
     */
    protected function toIterator($data)
    {
        return new \ArrayObject(is_array($data) ? $data : [$data]);
    }
}
