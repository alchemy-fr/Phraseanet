<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Assert\Assertion;

class CollectionFactory
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    /**
     * @param int $databoxId
     * @param CollectionReference $reference
     * @param array $row
     * @return \collection
     */
    public function create($databoxId, CollectionReference $reference, array $row)
    {
        if ($databoxId != $reference->getDataboxId()) {
            throw new \InvalidArgumentException('Reference does not belong to given databoxId.');
        }

        $collection = new Collection($databoxId, $row['coll_id'], $row['asciiname']);

        $collection->setLabel('en', $row['label_en']);
        $collection->setLabel('fr', $row['label_fr']);
        $collection->setLabel('de', $row['label_de']);
        $collection->setLabel('nl', $row['label_nl']);
        $collection->setLogo($row['logo']);
        $collection->setPreferences($row['prefs']);
        $collection->setPublicWatermark($row['pub_wm']);

        return new \collection($this->app, $collection, $reference, $row);
    }

    /**
     * @param int $databoxId
     * @param CollectionReference[] $collectionReferences
     * @param array $rows
     * @return array
     */
    public function createMany($databoxId, $collectionReferences, array $rows)
    {
        Assertion::allIsInstanceOf($collectionReferences, CollectionReference::class);

        $collections = [];
        $indexedReferences = [];

        foreach ($collectionReferences as $reference) {
            $indexedReferences[$reference->getCollectionId()] = $reference;
        }

        foreach ($rows as $row) {
            $collections[$row['coll_id']] = $this->create($databoxId, $indexedReferences[$row['coll_id']], $row);
        }

        return $collections;
    }
}
