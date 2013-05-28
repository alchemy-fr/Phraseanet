<?php

use Entities\UsrAuthProvider;

class UsrAuthProviderRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFindWithProviderAndIdIsNullWhenNotFound()
    {
        $repo = self::$DI['app']['EM']->getRepository('Entities\UsrAuthProvider');

        $this->assertNull($repo->findWithProviderAndId('provider-test', 12345));
    }

    public function testFindWithProviderAndIdReturnsOneResultWhenFound()
    {
        $repo = self::$DI['app']['EM']->getRepository('Entities\UsrAuthProvider');

        $auth = new UsrAuthProvider();
        $auth->setUsrId(42);
        $auth->setProvider('provider-test');
        $auth->setDistantId(12345);

        self::$DI['app']['EM']->persist($auth);
        self::$DI['app']['EM']->flush();

        $this->assertSame($auth, $repo->findWithProviderAndId('provider-test', 12345));
    }
}
