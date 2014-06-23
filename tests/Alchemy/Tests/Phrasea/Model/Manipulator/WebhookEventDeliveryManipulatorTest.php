<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventDeliveryManipulator;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Entities\ApiApplication;

class WebhookEventDeliveryManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipApp = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipApp->create(
            uniqid('app'),
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );

        $manipulator = new WebhookEventDeliveryManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-delivery']);
        $nbHooks = count(self::$DI['app']['repo.webhook-delivery']->findAll());
        $manipulator->create($application, self::$DI['webhook-event']);
        $this->assertGreaterThan($nbHooks, count(self::$DI['app']['repo.webhook-delivery']->findAll()));
    }

    public function testDelete()
    {
        $manipApp = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipApp->create(
            uniqid('app'),
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );
        $manipulator = new WebhookEventDeliveryManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-delivery']);
        $eventDelivery = $manipulator->create($application, self::$DI['webhook-event']);
        $countBefore = count(self::$DI['app']['repo.webhook-delivery']->findAll());
        $manipulator->delete($eventDelivery);
        $this->assertGreaterThan(count(self::$DI['app']['repo.webhook-delivery']->findAll()), $countBefore);
    }

    public function testUpdate()
    {
        $manipApp = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipApp->create(
            uniqid('app'),
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );
        $manipulator = new WebhookEventDeliveryManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-delivery']);
        $eventDelivery = $manipulator->create($application, self::$DI['webhook-event']);
        $this->assertEquals(0, $eventDelivery->getDeliveryTries());
        $eventDelivery->setDeliverTries(1);
        $manipulator->update($eventDelivery);
        $eventDelivery = self::$DI['app']['repo.webhook-delivery']->find($eventDelivery->getId());
        $this->assertEquals(1, $eventDelivery->getDeliveryTries());
    }

    public function testDeliverySuccess()
    {
        $manipApp = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipApp->create(
            uniqid('app'),
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );
        $manipulator = new WebhookEventDeliveryManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-delivery']);
        $eventDelivery = $manipulator->create($application, self::$DI['webhook-event']);
        $tries = $eventDelivery->getDeliveryTries();
        $manipulator->deliverySuccess($eventDelivery);
        $this->assertTrue($eventDelivery->isDelivered());
        $this->assertGreaterThan($tries, $eventDelivery->getDeliveryTries());
    }

    public function testDeliveryFailure()
    {
        $manipApp = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipApp->create(
            uniqid('app'),
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );
        $manipulator = new WebhookEventDeliveryManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-delivery']);
        $eventDelivery = $manipulator->create($application, self::$DI['webhook-event']);
        $tries = $eventDelivery->getDeliveryTries();
        $manipulator->deliveryFailure($eventDelivery);
        $this->assertfalse($eventDelivery->isDelivered());
        $this->assertGreaterThan($tries, $eventDelivery->getDeliveryTries());
    }
}
