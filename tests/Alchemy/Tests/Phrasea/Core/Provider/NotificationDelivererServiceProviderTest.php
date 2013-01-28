<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider
 */
class NotificationDelivererServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider', 'notification.deliverer', 'Alchemy\\Phrasea\\Notification\\Deliverer'),
        );
    }
}
