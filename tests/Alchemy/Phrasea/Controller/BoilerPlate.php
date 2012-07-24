<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';
/**
 * Always load the controller file for CodeCoverage
 */
require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/My/Controller.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * This class is a BoilerPlate for a Controller Test
 *
 *  - You should extends PhraseanetWebTestCaseAuthenticatedAbstract if the
 * controller required authentication
 *
 *  - The Class Name should end with "Test" to be detected by
 *
 */
class BoilerPlate extends \PhraseanetWebTestCaseAbstract
{
    /**
     * As controllers use WebTestCase, it requires a client
     */
    protected $client;

    /**
     * The application loader
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../Path/To/Application.php';
        
        $app['debug'] = true;
        unset($app['exception_handler']);
        
        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
