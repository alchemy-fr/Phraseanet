<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

class ApiAccountRepositoryTest extends \PhraseanetTestCase
{
    public function testFindByUserAndApplication()
    {
        $acc = self::$DI['app']['orm.em']->getRepository('Phraseanet:ApiAccount')->findByUserAndApplication(self::$DI['user_notAdmin'], self::$DI['oauth2-app-user-not-admin']);
        $this->assertEquals(1, count($acc));
    }
}
