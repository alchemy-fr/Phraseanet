<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class FirewallTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/Admin.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function testRequiredAuth()
    {
        $core = \bootstrap::getCore();
        $response = $core['Firewall']->requireAuthentication($this->app);
        $this->assertNull($response);
        $appbox = \appbox::get_instance($core);
        $session = $appbox->get_session();
        $session->logout();
        $response = $core['Firewall']->requireAuthentication($this->app);
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }
}

?>
