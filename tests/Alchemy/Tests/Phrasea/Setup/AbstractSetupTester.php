<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Application;

abstract class AbstractSetupTester extends \PhraseanetTestCase
{
    private $tearDownHandlers = [];

    public function tearDown()
    {
        foreach ($this->tearDownHandlers as $handler) {
            $handler();
        }

        parent::tearDown();
    }

    protected function uninstall()
    {
        rename(__DIR__ . '/../../../../../config/configuration.yml', __DIR__ . '/../../../../../config/configuration.yml.test');

        $this->tearDownHandlers[] = function () {
            rename(__DIR__ . '/../../../../../config/configuration.yml.test', __DIR__ . '/../../../../../config/configuration.yml');
        };
    }

    protected function goBackTo31()
    {
        $app = new Application('test');
        $conn = $app['phraseanet.appbox']->get_connection();

        $this->uninstall();

        copy(__DIR__ . '/../../../../../hudson/_GV.php', __DIR__ . '/../../../../../config/_GV.php');

        file_put_contents( __DIR__ . '/../../../../../config/_GV.php', str_replace('http://local.phrasea/', 'http://local.phrasea.tester/', file_get_contents( __DIR__ . '/../../../../../config/_GV.php')));

        file_put_contents(__DIR__ . '/../../../../../config/connexion.inc', "<?php\n
\$hostname = '".$conn->getHost()."';
\$port = '".$conn->getPort()."';
\$user = '".$conn->getUsername()."';
\$password = '".$conn->getPassword()."';
\$dbname = 'ab_unitTests';
            ");

        $this->tearDownHandlers[] = function () {
            @unlink(__DIR__ . '/../../../../../config/_GV.php');
            @unlink(__DIR__ . '/../../../../../config/connexion.inc');
        };
    }

    protected function goBackTo35()
    {
        $app = new Application('test');
        $conn = $app['phraseanet.appbox']->get_connection();

        $this->uninstall();

        file_put_contents(__DIR__ . '/../../../../../config/config.inc', "<?php\n\$servername = 'http://local.phrasea';\n");
        file_put_contents(__DIR__ . '/../../../../../config/connexion.inc', "<?php\n
\$hostname = '".$conn->getHost()."';
\$port = '".$conn->getPort()."';
\$user = '".$conn->getUsername()."';
\$password = '".$conn->getPassword()."';
\$dbname = '".$conn->getDatabase()."';
            ");

        $this->tearDownHandlers[] = function () {
                @unlink(__DIR__ . '/../../../../../config/config.inc');
                @unlink(__DIR__ . '/../../../../../config/connexion.inc');
            };
    }
}
