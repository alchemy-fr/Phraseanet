<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class classContentNegotiationServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'negotiator',
                'Negotiation\Negotiator',
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'language.negotiator',
                'Negotiation\LanguageNegotiator'
            )
        );
    }
}
