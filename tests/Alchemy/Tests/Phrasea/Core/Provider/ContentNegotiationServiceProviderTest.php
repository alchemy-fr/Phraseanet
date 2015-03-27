<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;

class classContentNegotiationServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'negotiator',
                'Negotiation\Negotiator',
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'format.negotiator',
                'Negotiation\FormatNegotiator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'langage.negotiator',
                'Negotiation\LanguageNegotiator'
            )
        );
    }
}
