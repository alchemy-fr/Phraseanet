<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/Prod/Lazaret.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LazaretTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
        $originalEm = $this->app['Core']['EM'];

        //mock one Entities\LazaretFile::getRecordsToSubstitute
        $fileLazaret = $this->getMock('Entities\LazaretFile', array('getRecordsToSubstitute'), array(), '', false);
        $fileLazaret
            ->expects($this->any())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue(array(static::$records['record_1'])));

        //mock one Repositories\LazaretFile::getFiles
        $repo = $this->getMock('Repositories\LazaretFile', array('getFiles'), array(), '', false);
        $repo->expects($this->once())
            ->method('getFiles')
            ->will($this->returnValue(array($fileLazaret)));

        //mock Doctrine\ORM\EntityManager::getRepository
        $em = $this->getMock('Doctrine\ORM\EntityManager', array('getRepository'), array(), '', false);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->EqualTo('Entities\LazaretFile'))
            ->will($this->returnValue($repo));

        $route = '/lazaret/';

        $this->app['Core']['EM'] = $em;

        $crawler = $this->client->request(
            'GET', $route
        );

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $this->assertEquals(1, $crawler->filter('div.records-subititution')->count());

        $em = $fileLazaret = $repo = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::getElement
     */
    public function testGetElement()
    {
        $this->markTestSkipped('Test response content');

        $originalEm = $this->app['Core']['EM'];

        $em = $this->getMock('Doctrine\ORM\EntityManager', array('find'), array(), '', false);

        $lazaretFile = new \Entities\LazaretFile();

        $lazaretFile->setOriginalName('test');
        $lazaretFile->setPathname('test\test');

        $id = 1;

        $em->expects($this->any())
            ->method('find')
            ->with(
                $this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id)
            )
            ->will($this->returnValue('salut'));

        $this->app['Core']['EM'] = $em;

        $route = '/lazaret/' . $id . '/';

        $this->client->request(
            'GET', $route
        );

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $em = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::getElement
     */
    public function testGetElementException()
    {
        $originalEm = $this->app['Core']['EM'];

        $em = $this->getMock('Doctrine\ORM\EntityManager', array('find'), array(), '', false);

        $lazaretFile = new \Entities\LazaretFile();

        $lazaretFile->setOriginalName('test');
        $lazaretFile->setPathname('test\test');

        $id = 1;

        $em->expects($this->any())
            ->method('find')
            ->with(
                $this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id)
            )
            ->will($this->returnValue(null));

        $this->app['Core']['EM'] = $em;

        $route = '/lazaret/' . $id . '/';

        $this->client->request(
            'GET', $route
        );

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertEquals(404, $response->getStatusCode());
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
