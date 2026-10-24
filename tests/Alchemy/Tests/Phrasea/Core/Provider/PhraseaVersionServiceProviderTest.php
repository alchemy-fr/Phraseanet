<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhraseaVersionServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider', 'phraseanet.version', 'Alchemy\Phrasea\Core\Version'],
        ];
    }
}
