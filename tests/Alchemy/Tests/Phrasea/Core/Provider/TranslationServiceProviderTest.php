<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
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
