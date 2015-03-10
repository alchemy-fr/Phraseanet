<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\ApiApplication;

class ApiApplicationRepositoryTest extends \PhraseanetTestCase
{
    public function testFindByCreator()
    {
        $app = self::$DI['app']['orm.em']->getRepository('Phraseanet:ApiApplication')->findByCreator(self::$DI['user']);
        $this->assertCount(1, $app);
    }

    public function testFindByUser()
    {
        $app = self::$DI['app']['orm.em']->getRepository('Phraseanet:ApiApplication')->findByUser(self::$DI['user']);
        $this->assertCount(1, $app);
    }

    public function testFindAuthorizedAppsByUser()
    {
        $app = self::$DI['app']['orm.em']->getRepository('Phraseanet:ApiApplication')->findAuthorizedAppsByUser(self::$DI['user']);
        $this->assertCount(1, $app);
    }
}
