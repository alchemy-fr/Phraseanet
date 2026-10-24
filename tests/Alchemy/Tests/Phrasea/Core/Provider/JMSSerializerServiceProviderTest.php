<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\JMSServiceProviderServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class JMSSerializerServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\JMSSerializerServiceProvider', 'serializer', 'JMS\Serializer\Serializer'],
        ];
    }
}
