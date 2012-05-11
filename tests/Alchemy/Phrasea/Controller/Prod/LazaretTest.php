<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/Prod/Lazaret.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LazaretTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;
    protected static $need_records = false;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::listElement
     */
    public function testListElement()
    {
        $this->markTestSkipped('empty');

        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::getElement
     */
    public function testGetElement()
    {
        $this->markTestSkipped('empty');

        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::addElement
     */
    public function testAddElement()
    {
        $this->markTestSkipped('empty');
        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::denyElement
     */
    public function testDenyElement()
    {
        $this->markTestSkipped('empty');
        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElement()
    {
        $this->markTestSkipped('empty');
        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::thumbnailElement
     */
    public function testThumbnailElement()
    {
        $this->markTestSkipped('empty');
        $response = null;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }
}
