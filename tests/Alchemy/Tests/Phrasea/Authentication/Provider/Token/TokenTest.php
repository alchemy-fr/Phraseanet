<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider\Token;

use Alchemy\Phrasea\Authentication\Provider\Token\Token;

class TokenTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIdAndProvider()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $id = 'Id-' . mt_rand();

        $token = new Token($provider, $id);
        $this->assertEquals($provider, $token->getProvider());
        $this->assertEquals($id, $token->getId());

        return $token;
    }

    public function testGetIdentity()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $id = 'Id-' . mt_rand();

        $identity = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Provider\Token\Identity')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $token = new Token($provider, $id);
        $this->assertEquals($identity, $token->getIdentity());
    }

    public function getTemplates()
    {
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $id = 'Id-' . mt_rand();

        $identity = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Provider\Token\Identity')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $templates = [25, 42];

        $provider->expects($this->once())
            ->method('getTemplates')
            ->with($identity)
            ->will($this->returnValue($templates));

        $token = new Token($provider, $id);
        $this->assertEquals($templates, $token->getTemplates());
    }
}
