<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\JMSServiceProviderServiceProvider
 */
class JMSServiceProviderServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\JMSSerializerServiceProvider', 'serializer', 'JMS\Serializer\Serializer'),
        );
    }
}
