<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
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
        return array(
            array('github', array('client-id' => 'id', 'client-secret' => 'secret'), 'Alchemy\Phrasea\Authentication\Provider\Github'),
            array('google-plus', array('client-id' => 'id', 'client-secret' => 'secret'), 'Alchemy\Phrasea\Authentication\Provider\GooglePlus'),
            array('linkedin', array('client-id' => 'id', 'client-secret' => 'secret'), 'Alchemy\Phrasea\Authentication\Provider\Linkedin'),
            array('twitter', array('consumer-key' => 'id', 'consumer-secret' => 'secret'), 'Alchemy\Phrasea\Authentication\Provider\Twitter'),
            array('viadeo', array('client-id' => 'id', 'client-secret' => 'secret'), 'Alchemy\Phrasea\Authentication\Provider\Viadeo'),
        );
    }
}
