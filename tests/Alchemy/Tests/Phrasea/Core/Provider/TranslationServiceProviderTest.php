<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\TranslatorServiceProvider
 */
class TranslationServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\TranslationServiceProvider', 'translator', 'Alchemy\Phrasea\Utilities\CachedTranslator'],
        ];
    }
}
