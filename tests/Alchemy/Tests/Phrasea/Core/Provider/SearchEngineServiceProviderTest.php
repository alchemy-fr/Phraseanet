<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SearchEngineServiceProviderTest extends ServiceProviderTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider', 'phraseanet.SE', 'Alchemy\Phrasea\SearchEngine\SearchEngineInterface'],
        ];
    }
}
