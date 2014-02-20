<?php

use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;

class UsrAuthProviderRepositoryTest extends \PhraseanetTestCase
{
    public function testFindWithProviderAndIdIsNullWhenNotFound()
    {
        $repo = self::$DI['app']['EM']->getRepository('Phraseanet:UsrAuthProvider');

        $this->assertNull($repo->findWithProviderAndId('provider-test', 12345));
    }

    public function testFindWithProviderAndIdReturnsOneResultWhenFound()
    {
        $repo = self::$DI['app']['EM']->getRepository('Phraseanet:UsrAuthProvider');

        $auth = new UsrAuthProvider();
        $auth->setUser(self::$DI['user']);
        $auth->setProvider('provider-test');
        $auth->setDistantId(12345);

        self::$DI['app']['EM']->persist($auth);
        self::$DI['app']['EM']->flush();

        $this->assertSame($auth, $repo->findWithProviderAndId('provider-test', 12345));
    }
}
