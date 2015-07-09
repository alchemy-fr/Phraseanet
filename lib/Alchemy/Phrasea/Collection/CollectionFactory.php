<?php

namespace Alchemy\Phrasea\Collection;

use Alchemy\Phrasea\Application;
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

        return new \collection($this->app, $reference->getBaseId(), $reference, $row);
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
