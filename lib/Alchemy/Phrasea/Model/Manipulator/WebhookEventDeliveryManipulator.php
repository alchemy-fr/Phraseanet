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

use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Repositories\WebhookEventDeliveryRepository;
use Doctrine\Common\Persistence\ObjectManager;

class WebhookEventDeliveryManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;

    public function __construct(ObjectManager $om, WebhookEventDeliveryRepository $repo)
    {
        $this->om = $om;
        $this->repository = $repo;
    }

    public function create(ApiApplication $application, WebhookEvent $event)
    {
        $delivery = new WebhookEventDelivery();
        $delivery->setThirdPartyApplication($application);
        $delivery->setWebhookEvent($event);

        $this->update($delivery);

        return $delivery;
    }

    public function delete(WebhookEventDelivery $delivery)
    {
        $this->om->remove($delivery);
        $this->om->flush();
    }

    public function update(WebhookEventDelivery $delivery)
    {
        $this->om->persist($delivery);
        $this->om->flush();
    }

    public function deliverySuccess(WebhookEventDelivery $delivery)
    {
        $delivery->setDelivered(true);
        $delivery->setDeliverTries($delivery->getDeliveryTries() + 1);
        $this->update($delivery);
    }

    public function deliveryFailure(WebhookEventDelivery $delivery)
    {
        $delivery->setDelivered(false);
        $delivery->setDeliverTries($delivery->getDeliveryTries() + 1);
        $this->update($delivery);
    }

    public function isWebhookDeactivate(ApiApplication $apiApplication)
    {
        $r = $this->repository->findUndeliveredEventsFromLastAppUpdate($apiApplication);

        // if failed to deliver webhook to the url in 5 different events
        // calculation based after app update ( any change on api application )
        // so deactivate the webhook
        if (count($r) >= 5) {
            $apiApplication->setWebhookActive(false);
            $this->om->persist($apiApplication);
            $this->om->flush();

            return true;
        }

        return false;
    }
}
