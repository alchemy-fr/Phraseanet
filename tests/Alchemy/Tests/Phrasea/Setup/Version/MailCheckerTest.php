<?php

namespace Alchemy\Tests\Phrasea\Setup\Version;

use Alchemy\Phrasea\Setup\Version\MailChecker;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailCheckerTest extends \PhraseanetTestCase
{
    public function testMailChecker()
    {
        $checker = new MailChecker(self::$DI['app']['phraseanet.appbox'], 'usr_tmp');
        $this->assertEmpty($checker->getWrongEmailUsers());
    }

    public function testItHasNoDuplicateEmailUsers()
    {
        $checker = new MailChecker(self::$DI['app']['phraseanet.appbox'], 'usr_tmp');
        $this->assertFalse($checker->hasWrongEmailUsers());
    }
}
