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
                'negociator',
                'Negotiation\Negotiator',
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'format.negociator',
                'Negotiation\FormatNegotiator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider',
                'langage.negociator',
                'Negotiation\LanguageNegotiator'
            )
        );
    }
}
