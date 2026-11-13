<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\ApiApplication;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ApiApplicationRepositoryTest extends \PhraseanetTestCase
{
    public function testFindByCreator()
    {
        $app = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiApplication')->findByCreator(self::$DI['user']);
        $this->assertCount(1, $app);
    }

    public function testFindByUser()
    {
        $app = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiApplication')->findByUser(self::$DI['user']);
        $this->assertCount(1, $app);
    }

    public function testFindAuthorizedAppsByUser()
    {
        $app = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiApplication')->findAuthorizedAppsByUser(self::$DI['user']);
        $this->assertCount(1, $app);
    }
}
