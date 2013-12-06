<?php

namespace Alchemy\Tests\Phrasea\Setup\Version;

use Alchemy\Phrasea\Setup\Version\MailChecker;

class MailCheckerTest extends \PhraseanetTestCase
{
    public function testMailChecker()
    {
        $conn = self::$DI['app']['phraseanet.appbox']->get_connection();
        $now = new \DateTime();

        $stmt = $conn->prepare('CREATE TEMPORARY TABLE usr_tmp (usr_id INT, usr_mail VARCHAR(50), usr_login VARCHAR(50), last_conn DATETIME);');
        $stmt->execute();
        $stmt->closeCursor();
        $stmt = $conn->prepare('INSERT INTO usr_tmp (usr_id, usr_mail, usr_login, last_conn) VALUES(1, "email@email.com", "login1", "'.$now->format('Y-m-D H:i:s').'");');
        $stmt->execute();
        $stmt->closeCursor();
        $stmt = $conn->prepare('INSERT INTO usr_tmp (usr_id, usr_mail, usr_login, last_conn) VALUES(2, "email@email.com", "login2", "'.$now->format('Y-m-D H:i:s').'");');
        $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);
        $users = MailChecker::getWrongEmailUsers(self::$DI['app'], 'usr_tmp');

        $this->assertEquals(1, count($users));
        $this->assertEquals(2, count($users['email@email.com']));
    }
}
