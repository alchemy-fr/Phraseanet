<?php

namespace Alchemy\Tests\Phrasea\Setup\Version;

use Alchemy\Phrasea\Setup\Version\MailChecker;

class MailCheckerTest extends \PhraseanetTestCase
{
    public function testMailChecker()
    {
        $users = MailChecker::getWrongEmailUsers(self::$DI['app'], 'usr_tmp');

        $this->assertEquals(0, count($users));
    }
}
