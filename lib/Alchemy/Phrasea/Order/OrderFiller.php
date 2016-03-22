<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Assert\Assertion;
use Doctrine\ORM\EntityManagerInterface;

class OrderFiller
{
    /**
     * @var CollectionReferenceRepository
     */
    private $repository;
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(CollectionReferenceRepository $repository, EntityManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
    }

    /**
     * @param \record_adapter[]|\Traversable $records
     */
    public function assertAllRecordsHaveOrderMaster($records)
    {
        Assertion::allIsInstanceOf($records, \record_adapter::class);

        $collectionIds = [];

        foreach ($records as $record) {
            $collectionIds[] = $record->getBaseId();
        }

        $collectionIds = array_unique($collectionIds);

        $hasOneAdmin = [];

        foreach ($this->repository->findHavingOrderMaster($collectionIds) as $reference) {
            $hasOneAdmin[] = $reference->getBaseId();
        }

        $collectionsWithoutAdmin = array_diff($collectionIds, $hasOneAdmin);

        if (!empty($collectionsWithoutAdmin)) {
            throw new \RuntimeException(sprintf('Some collections have no order master: %s', implode(', ', $collectionsWithoutAdmin)));
        }
    }

    /**
     * @param \record_adapter[]|\Traversable $records
     * @param Order $order
     */
    public function fillOrder(Order $order, $records)
    {
        Assertion::allIsInstanceOf($records, \record_adapter::class);

        foreach ($records as $key => $record) {
            $orderElement = new OrderElement();
            $orderElement->setBaseId($record->getBaseId());
            $orderElement->setRecordId($record->getRecordId());

            $order->addElement($orderElement);

            $this->manager->persist($orderElement);
        }

        $order->setTodo($order->getElements()->count());

        $this->manager->persist($order);

        $this->manager->flush();
    }
}
