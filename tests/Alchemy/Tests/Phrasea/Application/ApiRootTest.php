<?php

namespace Alchemy\Tests\Phrasea\Application;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

class ApiRootTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     *
     * @var Symfony\Component\HttpKernel\Client
     */
    protected $client;

    public function setUp()
    {
        parent::setUp();

        self::$DI['app'] = self::$DI->share(function() {
            $environment = 'test';
            $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Api.php';

            $app['debug'] = true;

            return $app;
        });

    }

    public function testRoot()
    {
        self::$DI['client']->request('GET', '/');

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('response', $data);
    }
}
