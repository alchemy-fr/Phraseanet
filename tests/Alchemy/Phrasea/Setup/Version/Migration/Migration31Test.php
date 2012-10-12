<?php

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\AbstractSetupTester;
use Symfony\Component\Yaml\Parser;

require_once __DIR__ . '/../../AbstractSetupTester.inc';

class Migration31Test extends AbstractSetupTester
{

    public function testMigrateFails()
    {
        $migration = $this->getMigration();
        try {
            $migration->migrate();
            $this->fail('Should fail');
        } catch (\LogicException $e) {

        }
    }

    public function testMigrate()
    {
        $app = $this->goBackTo31();
        $migration = $this->getMigration();
        $migration->migrate();

        $parser = new Parser;

        $app['phraseanet.appbox']->get_connection()->exec('use ab_unitTests');

        $sql = 'SELECT `key`, value FROM registry WHERE type = :type';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':type' => \registry::TYPE_ENUM_MULTI));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->assertEquals(2, count($rs));

        foreach($rs as $row) {
            $value = $parser->parse($row['value']);
            $this->assertInternalType('array', $value);
        }

        \connection::close_connections();

        require __DIR__ . '/../../../../../../config/config.inc';

        $this->assertEquals('http://local.phrasea.tester/', $servername);

        unlink(__DIR__ . '/../../../../../../config/_GV.php.old');
        unlink(__DIR__ . '/../../../../../../config/config.inc');
    }

    private function getMigration()
    {
        return new Migration31(new Application('test'));
    }
}
