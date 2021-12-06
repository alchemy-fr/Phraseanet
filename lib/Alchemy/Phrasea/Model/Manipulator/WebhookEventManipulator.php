<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Webhook\WebhookPublisherInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

class WebhookEventManipulator implements ManipulatorInterface
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var WebhookPublisherInterface
     */
    private $publisher;

    public function __construct(ObjectManager $om, EntityRepository $repo, WebhookPublisherInterface $publisher)
    {
        $this->om = $om;
        $this->repository = $repo;
        $this->publisher = $publisher;
    }

    public function create($eventName, $type, array $data, array $collectionBaseIds = array())
    {
        $event = new WebhookEvent();

        $event->setName($eventName);
        $event->setType($type);
        $event->setData($data);

        if (count($collectionBaseIds) > 0) {
            $event->setCollectionBaseIds($collectionBaseIds);
        }

        $this->update($event);

        $this->publisher->publishWebhookEvent($event);

        return $event;
    }

    public function delete(WebhookEvent $event)
    {
        $this->om->remove($event);
        $this->om->flush();
    }

    public function update(WebhookEvent $event)
    {
        try {
            $this->om->persist($event);
            $this->om->flush();
        } catch (\Exception $e) {
        }
    }

    public function processed(WebhookEvent $event)
    {
        $event->setProcessed(true);
        $this->update($event);
    }
}
