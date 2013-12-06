<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Factory;

class FactoryTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideNameAndOptions
     */
    public function testBuild($name, $options, $expected)
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $factory = new Factory($generator, $session);

        $this->assertInstanceOf($expected, $factory->build($name, $options));
    }

    public function provideNameAndOptions()
    {
        return [
            ['github', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Github'],
            ['google-plus', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\GooglePlus'],
            ['linkedin', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Linkedin'],
            ['twitter', ['consumer-key' => 'id', 'consumer-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Twitter'],
            ['viadeo', ['client-id' => 'id', 'client-secret' => 'secret'], 'Alchemy\Phrasea\Authentication\Provider\Viadeo'],
        ];
    }
}
