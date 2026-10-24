<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider\Token;

use Alchemy\Phrasea\Authentication\Provider\Token\Token;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TokenTest extends \PhraseanetTestCase
{
    public function testGetIdAndProvider()
    {
        $provider = $this->createMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $id = 'Id-' . mt_rand();

        $token = new Token($provider, $id);
        $this->assertEquals($provider, $token->getProvider());
        $this->assertEquals($id, $token->getId());

        return $token;
    }

    public function testGetIdentity()
    {
        $provider = $this->createMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $id = 'Id-' . mt_rand();

        $identity = $this->createStub('Alchemy\Phrasea\Authentication\Provider\Token\Identity');

        $provider->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $token = new Token($provider, $id);
        $this->assertEquals($identity, $token->getIdentity());
    }

    public function getTemplates()
    {
        $provider = $this->createMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $id = 'Id-' . mt_rand();

        $identity = $this->createMock('Alchemy\Phrasea\Authentication\Provider\Token\Identity');

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
