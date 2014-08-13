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
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

class WebhookEventManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;

    public function __construct(ObjectManager $om, EntityRepository $repo)
    {
        $this->om = $om;
        $this->repository = $repo;
    }

    public function create($eventName, $type, array $data)
    {
        $event = new WebhookEvent();
        $event->setName($eventName);
        $event->setType($type);
        $event->setData($data);

        $this->update($event);

        return $event;
    }

    public function delete(WebhookEvent $event)
    {
        $this->om->remove($event);
        $this->om->flush();
    }

    public function update(WebhookEvent $event)
    {
        $this->om->persist($event);
        $this->om->flush();
    }

    public function processed(WebhookEvent $event)
    {
        $event->setProcessed(true);
        $this->update($event);
    }
}
