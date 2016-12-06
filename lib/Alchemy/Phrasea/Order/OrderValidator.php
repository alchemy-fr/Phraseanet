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

use Alchemy\Phrasea\Application\Helper\AclAware;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Assert\Assertion;

class OrderValidator
{
    const VALIDATION_ACCEPT = false;
    const VALIDATION_DENY = true;

    use AclAware;

    /**
     * @var \appbox
     */
    private $appbox;

    /**
     * @var CollectionReferenceRepository
     */
    private $repository;

    public function __construct(\appbox $appbox, CollectionReferenceRepository $repository)
    {
        $this->appbox = $appbox;
        $this->repository = $repository;
    }

    /**
     * @param User $acceptor
     * @param OrderElement[] $elements
     * @return bool
     */
    public function isGrantedValidation(User $acceptor, $elements)
    {
        $acceptableCollections = $this->getAclForUser($acceptor)->getOrderMasterCollectionsBaseIds();

        $elementsCollections = [];

        foreach ($elements as $element) {
            $elementsCollections[$element->getBaseId()] = true;
        }

        return empty(array_diff(array_keys($elementsCollections), $acceptableCollections));
    }

    /**
     * @param PartialOrder $order
     * @return BasketElement[]
     */
    public function createBasketElements(PartialOrder $order)
    {
        $basketElements = [];

        $references = $this->getRecordReferenceCollection($order);

        foreach ($references->toRecords($this->appbox) as $record) {
            $basketElement = new BasketElement();
            $basketElement->setRecord($record);

            $basketElements[] = $basketElement;
        }

        return $basketElements;
    }

    /**
     * @param User $acceptor
     * @param PartialOrder $order
     */
    public function accept(User $acceptor, PartialOrder $order)
    {
        $this->acceptOrDenyPartialOrder($acceptor, $order, self::VALIDATION_ACCEPT);
    }

    /**
     * @param User $acceptor
     * @param PartialOrder $order
     */
    public function deny(User $acceptor, PartialOrder $order)
    {
        $this->acceptOrDenyPartialOrder($acceptor, $order, self::VALIDATION_DENY);
    }

    /**
     * @param User $user
     * @param BasketElement[] $elements
     */
    public function grantHD(User $user, $elements)
    {
        Assertion::allIsInstanceOf($elements, BasketElement::class);

        $acl = $this->getAclForUser($user);

        foreach ($elements as $element) {
            $recordReference = RecordReference::createFromDataboxIdAndRecordId(
                $element->getSbasId(),
                $element->getRecordId()
            );

            $acl->grant_hd_on($recordReference, $user, \ACL::GRANT_ACTION_ORDER);
        }
    }

    /**
     * @param PartialOrder $order
     * @return RecordReferenceCollection
     */
    private function getRecordReferenceCollection(PartialOrder $order)
    {
        $databoxIdMap = [];

        foreach ($this->repository->findMany($order->getBaseIds()) as $collectionReference) {
            $databoxIdMap[$collectionReference->getBaseId()] = $collectionReference->getDataboxId();
        }

        $references = new RecordReferenceCollection();

        foreach ($order->getElements() as $orderElement) {
            if (!isset($databoxIdMap[$orderElement->getBaseId()])) {
                throw new \RuntimeException('At least one collection was not found.');
            }

            $references->add(RecordReference::createFromDataboxIdAndRecordId(
                $databoxIdMap[$orderElement->getBaseId()],
                $orderElement->getRecordId()
            ));
        }

        return $references;
    }

    /**
     * @param User $acceptor
     * @param PartialOrder $order
     * @param bool $deny
     */
    private function acceptOrDenyPartialOrder(User $acceptor, PartialOrder $order, $deny)
    {
        $elements = $order->getElements();

        if (empty($elements)) {
            return;
        }

        $decrementCount = 0;

        foreach ($elements as $element) {
            $element->setOrderMaster($acceptor);
            if (null === $element->getDeny()) {
                ++$decrementCount;
            }
            $element->setDeny($deny);
        }

        if ($decrementCount) {
            $order->getOrder()->decrementTodo($decrementCount);
        }
    }
}
