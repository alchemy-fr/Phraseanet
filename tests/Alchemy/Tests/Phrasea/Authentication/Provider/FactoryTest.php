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
    public function testBuild($name, $display, $title, $options, $expected)
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $factory = new Factory($generator, $session);

        $this->assertInstanceOf($expected, $factory->build($name, $display, $title, $options));
    }

    public function provideNameAndOptions()
    {
        return [
            ['github', true, 'Gihtub', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Github'],
            ['google-plus', true, 'Google +', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\GooglePlus'],
            ['linkedin', true, 'LinkedIN', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Linkedin'],
            ['twitter', true, 'Twitter', ['consumer-key' => 'id', 'consumer-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Twitter'],
            ['viadeo', true, 'Viadeo', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Viadeo'],
        ];
    }
}
