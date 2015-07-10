<?php

namespace Alchemy\Phrasea\Collection;

use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;

class CollectionRepositoryRegistry
{

    private $baseIdMap = null;

    /**
     * @var CollectionRepository[]
     */
    private $repositories = array();

    /**
     * @var CollectionReferenceRepository
     */
    private $referenceRepository;

    /**
     * @var CollectionRepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @param CollectionRepositoryFactory $collectionRepositoryFactory
     * @param CollectionReferenceRepository $referenceRepository
     */
    public function __construct(
        CollectionRepositoryFactory $collectionRepositoryFactory,
        CollectionReferenceRepository $referenceRepository
    ) {
        $this->repositoryFactory = $collectionRepositoryFactory;
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * @param $databoxId
     * @return CollectionRepository
     */
    public function getRepositoryByDatabox($databoxId)
    {
        if (!isset($this->repositories[$databoxId])) {
            $this->repositories[$databoxId] = $this->repositoryFactory->createRepositoryForDatabox($databoxId);
        }

        return $this->repositories[$databoxId];
    }

    /**
     * @param int $baseId
     * @return CollectionRepository
     * @throws \OutOfBoundsException if no repository was found for the given baseId.
     */
    public function getRepositoryByBase($baseId)
    {
        if ($this->baseIdMap === null) {
            $this->loadBaseIdMap();
        }

        if (isset($this->baseIdMap[$baseId])) {
            return $this->getRepositoryByDatabox($this->baseIdMap[$baseId]);
        }

        throw new \OutOfBoundsException('No repository available for given base [baseId: ' . $baseId . ' ].');
    }

    public function purgeRegistry()
    {
        $this->baseIdMap = null;
    }

    private function loadBaseIdMap()
    {
        $references = $this->referenceRepository->findAll();

        $this->baseIdMap = [];

        foreach ($references as $reference) {
            $this->baseIdMap[$reference->getBaseId()] = $reference->getDataboxId();
        }
    }
}
