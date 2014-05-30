<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider
 */
class PhraseaVersionServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider', 'phraseanet.version', 'Alchemy\Phrasea\Core\Version'),
        );
    }
}
