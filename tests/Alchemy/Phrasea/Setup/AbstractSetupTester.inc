<?php

namespace Alchemy\Phrasea\Setup;

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
        rename(__DIR__ . '/../../../../config/config.yml', __DIR__ . '/../../../../config/config.yml.test');
        rename(__DIR__ . '/../../../../config/connexions.yml', __DIR__ . '/../../../../config/connexions.yml.test');
        rename(__DIR__ . '/../../../../config/services.yml', __DIR__ . '/../../../../config/services.yml.test');

        $this->tearDownHandlers[] = function() {
                rename(__DIR__ . '/../../../../config/config.yml.test', __DIR__ . '/../../../../config/config.yml');
                rename(__DIR__ . '/../../../../config/connexions.yml.test', __DIR__ . '/../../../../config/connexions.yml');
                rename(__DIR__ . '/../../../../config/services.yml.test', __DIR__ . '/../../../../config/services.yml');
            };
    }

    protected function goBackTo31()
    {
        $app = new Application('test');
        $credentials = $app['phraseanet.appbox']->get_connection()->get_credentials();

        $this->uninstall();

        file_put_contents(__DIR__ . '/../../../../config/_GV.php', "<?php\ndefine('GV_ServerName', 'http://local.phrasea.tester/');\n");
        file_put_contents(__DIR__ . '/../../../../config/connexion.inc', "<?php\n
\$hostname = '".$credentials['hostname']."';
\$port = '".$credentials['port']."';
\$user = '".$credentials['user']."';
\$password = '".$credentials['password']."';
\$dbname = 'ab_unitTests';
            ");

        $this->tearDownHandlers[] = function() {
            @unlink(__DIR__ . '/../../../../config/_GV.php');
            @unlink(__DIR__ . '/../../../../config/connexion.inc');
        };
    }

    protected function goBackTo35()
    {
        $app = new Application('test');
        $credentials = $app['phraseanet.appbox']->get_connection()->get_credentials();
        
        $this->uninstall();

        file_put_contents(__DIR__ . '/../../../../config/config.inc', "<?php\n\$servername = 'http://local.phrasea';\n");
        file_put_contents(__DIR__ . '/../../../../config/connexion.inc', "<?php\n
\$hostname = '".$credentials['hostname']."';
\$port = '".$credentials['port']."';
\$user = '".$credentials['user']."';
\$password = '".$credentials['password']."';
\$dbname = '".$credentials['dbname']."';
            ");

        $this->tearDownHandlers[] = function() {
                @unlink(__DIR__ . '/../../../../config/config.inc');
                @unlink(__DIR__ . '/../../../../config/connexion.inc');
            };
    }
}
