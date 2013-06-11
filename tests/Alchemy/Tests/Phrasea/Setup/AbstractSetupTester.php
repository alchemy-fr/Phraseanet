<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Application;

abstract class AbstractSetupTester extends \PHPUnit_Framework_TestCase
{
    private $tearDownHandlers = array();

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

        $this->tearDownHandlers[] = function() {
            rename(__DIR__ . '/../../../../../config/configuration.yml.test', __DIR__ . '/../../../../../config/configuration.yml');
        };
    }

    protected function goBackTo31()
    {
        $app = new Application('test');
        $credentials = $app['phraseanet.appbox']->get_connection()->get_credentials();

        $this->uninstall();

        copy(__DIR__ . '/../../../../../hudson/_GV.php', __DIR__ . '/../../../../../config/_GV.php');

        file_put_contents( __DIR__ . '/../../../../../config/_GV.php', str_replace('http://local.phrasea/', 'http://local.phrasea.tester/', file_get_contents( __DIR__ . '/../../../../../config/_GV.php')));

        file_put_contents(__DIR__ . '/../../../../../config/connexion.inc', "<?php\n
\$hostname = '".$credentials['hostname']."';
\$port = '".$credentials['port']."';
\$user = '".$credentials['user']."';
\$password = '".$credentials['password']."';
\$dbname = 'ab_unitTests';
            ");

        $this->tearDownHandlers[] = function() {
            @unlink(__DIR__ . '/../../../../../config/_GV.php');
            @unlink(__DIR__ . '/../../../../../config/connexion.inc');
        };
    }

    protected function goBackTo35()
    {
        $app = new Application('test');
        $credentials = $app['phraseanet.appbox']->get_connection()->get_credentials();

        $this->uninstall();

        file_put_contents(__DIR__ . '/../../../../../config/config.inc', "<?php\n\$servername = 'http://local.phrasea';\n");
        file_put_contents(__DIR__ . '/../../../../../config/connexion.inc', "<?php\n
\$hostname = '".$credentials['hostname']."';
\$port = '".$credentials['port']."';
\$user = '".$credentials['user']."';
\$password = '".$credentials['password']."';
\$dbname = '".$credentials['dbname']."';
            ");

        $this->tearDownHandlers[] = function() {
                @unlink(__DIR__ . '/../../../../../config/config.inc');
                @unlink(__DIR__ . '/../../../../../config/connexion.inc');
            };
    }
}
