<?php

use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class UsrAuthProviderRepositoryTest extends \PhraseanetTestCase
{
    public function testFindWithProviderAndIdIsNullWhenNotFound()
    {
        $repo = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');

        $this->assertNull($repo->findWithProviderAndId('provider-test', 12345));
    }

    public function testFindWithProviderAndIdReturnsOneResultWhenFound()
    {
        $repo = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');

        $auth = new UsrAuthProvider();
        $auth->setUser(self::$DI['user']);
        $auth->setProvider('provider-test');
        $auth->setDistantId(12345);

        self::$DI['app']['orm.em']->persist($auth);
        self::$DI['app']['orm.em']->flush();

        $this->assertSame($auth, $repo->findWithProviderAndId('provider-test', 12345));
    }
}
