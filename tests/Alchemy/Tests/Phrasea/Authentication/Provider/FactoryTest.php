<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Factory;

/**
 * @group functional
 * @group legacy
 */
class FactoryTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideNameAndOptions
     */
    public function testBuild($id, $type, $display, $title, $options, $expected)
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $factory = new Factory($generator, $session);

        $this->assertInstanceOf($expected, $factory->build($id, $type, $display, $title, $options));
    }

    public function provideNameAndOptions()
    {
        return [
            ['github-test'     , 'Github'    , true, 'Gihtub'  , ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Github'],
            ['linkedin-test'   , 'Linkedin'  , true, 'Linkedin', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Linkedin'],
            ['twitter-test'    , 'Twitter'   , true, 'Twitter' , ['consumer-key' => 'id', 'consumer-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Twitter'],
            ['viadeo-test'     , 'Viadeo'    , true, 'Viadeo'  , ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Viadeo'],
        ];
    }
}
