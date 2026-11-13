<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\SuggestionFinder;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SuggestionFinderTest extends \PhraseanetTestCase
{
    public function testSuggestionIsFound()
    {
        $token = $this->getToken(self::$DI['user']->getEmail());

        $finder = new SuggestionFinder(self::$DI['app']['repo.users']);
        $user = $finder->find($token);

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $this->assertEquals(self::$DI['user']->getId(), $user->getId());
    }

    public function testSuggestionIsNotFound()
    {
        $token = $this->getToken(sprintf('%srandom%s@%srandom.com', uniqid(mt_rand(), true), uniqid(mt_rand(), true), uniqid(mt_rand(), true)));

        $finder = new SuggestionFinder(self::$DI['app']['repo.users']);
        $user = $finder->find($token);

        $this->assertNull($user);
    }

    protected function getToken($email)
    {
        $identity = new Identity([Identity::PROPERTY_EMAIL => $email]);

        $token = $this->createMock('Alchemy\Phrasea\Authentication\Provider\Token\Token');

        $token
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        return $token;
    }
}
