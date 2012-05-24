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
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::call
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::connect
     */
    public function testListElement()
    {
        $originalEm = $this->app['Core']['EM'];

        $fileLazaret = $this->getMock('Entities\LazaretFile', array('getRecordsToSubstitute', 'getSession', 'getCollection'), array(), '', false);

        $fileLazaret
            ->expects($this->any())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue(array(static::$records['record_1'])));

        $fileLazaret
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($this->getMock('Entities\LazaretSession')));

        $fileLazaret
            ->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue(self::$collection));

        //mock one Repositories\LazaretFile::getFiles
        $repo = $this->getMock('Repositories\LazaretFile', array('findPerPage'), array(), '', false);

        $repo->expects($this->once())
            ->method('findPerPage')
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

        $this->assertResponseOk($this->client->getResponse());

        $this->assertEquals(1, $crawler->filter('div.records-subititution')->count());

        $em = $fileLazaret = $repo = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::getElement
     */
    public function testGetElement()
    {
        $originalEm = $this->app['Core']['EM'];

        $em = $this->getMock('Doctrine\ORM\EntityManager', array('find'), array(), '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        $this->app['Core']['EM'] = $em;

        $this->client->request('GET', '/lazaret/' . $id . '/');

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);

        $content = json_decode($response->getContent());

        $this->assertGoodJsonContent($content);
        $this->assertObjectHasAttribute('message', $content);
        $this->assertObjectHasAttribute('result', $content);
        $this->assertObjectHasAttribute('filename', $content->result);
        $this->assertObjectHasAttribute('base_id', $content->result);
        $this->assertObjectHasAttribute('created', $content->result);
        $this->assertObjectHasAttribute('updated', $content->result);
        $this->assertObjectHasAttribute('pathname', $content->result);
        $this->assertObjectHasAttribute('sha256', $content->result);
        $this->assertObjectHasAttribute('uuid', $content->result);

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::getElement
     */
    public function testGetElementException()
    {
        $originalEm = $this->app['Core']['EM'];

        $em = $this->getMock('Doctrine\ORM\EntityManager', array('find'), array(), '', false);

        $id = 1;

        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue(null));

        $this->app['Core']['EM'] = $em;

        $this->client->request('GET', '/lazaret/' . $id . '/');

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::addElement
     */
    public function testAddElement()
    {
        $originalEm = $this->app['Core']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);

        $lazaretFile = $this->getOneLazaretFile();

        //mock lazaret Attribute
        $lazaretAttribute = $this->getMock('Entities\LazaretAttribute', array(), array(), '', false);

        //Expect to be called 3 times since we add 5 attribute to lazaretFile
        //and each one is called to verify if it is an attribute to keep
        $lazaretAttribute->expects($this->exactly(5))
            ->method('getId')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5));

        //Provide consecutive value for all type of attributes
        //Expect 4 call since the Fifth attribute is not eligible (see request attributes)
        $lazaretAttribute->expects($this->exactly(4))
            ->method('getName')
            ->will($this->onConsecutiveCalls(
                    Alchemy\Phrasea\Border\Attribute\Attribute::NAME_METADATA, Alchemy\Phrasea\Border\Attribute\Attribute::NAME_STORY, Alchemy\Phrasea\Border\Attribute\Attribute::NAME_STATUS, Alchemy\Phrasea\Border\Attribute\Attribute::NAME_METAFIELD
                ));
        $story = record_adapter::createStory(self::$collection);
        //Provide some valid test values
        $lazaretAttribute->expects($this->exactly(4))
            ->method('getValue')
            ->will($this->onConsecutiveCalls('metadataValue', $story->get_serialize_key(), '00001111', 'metafieldValue'));

        //Add the 5 attribute
        $lazaretFile->addLazaretAttribute($lazaretAttribute);
        $lazaretFile->addLazaretAttribute($lazaretAttribute);
        $lazaretFile->addLazaretAttribute($lazaretAttribute);
        $lazaretFile->addLazaretAttribute($lazaretAttribute);
        $lazaretFile->addLazaretAttribute($lazaretAttribute);

        $id = 1;
        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->EqualTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        $this->app['Core']['EM'] = $em;

        $this->client->request('POST', '/lazaret/' . $id . '/force-add/', array(
            'bas_id'          => $lazaretFile->getBaseId(),
            'keep_attributes' => 1,
            'attributes'      => array(1, 2, 3, 4) //Check only the four first attributes
        ));

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();
        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        $story->delete();

        $em = $lazaretFile = $lazaretSession = $lazaretAttribute = $story = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::addElement
     */
    public function testAddElementBadRequestException()
    {
        $originalEm = $this->app['Core']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        $this->app['Core']['EM'] = $em;

        //Ommit base_id mandatory param
        $this->client->request('POST', '/lazaret/' . $id . '/force-add/', array(
            'keep_attributes' => 1,
            'attributes'      => array(1, 2, 3, 4) //Check only the four first attributes
        ));

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::addElement
     */
    public function testAddElementException()
    {
        $originalEm = $this->app['Core']['EM'];

        $this->client->request('POST', '/lazaret/99999/force-add/', array(
            'bas_id'          => 1,
            'keep_attributes' => 1,
            'attributes'      => array(1, 2, 3, 4) //Check only the four first attributes
        ));

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::denyElement
     */
    public function testDenyElement()
    {
        $lazaretFile = $this->insertOneLazaretFile();

        $route = sprintf('/lazaret/%s/deny/', $lazaretFile->getId());

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(l.id) FROM \Entities\LazaretFile l'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::denyElement
     */
    public function testDenyElementException()
    {
        $route = sprintf('/lazaret/%s/deny/', '99999');

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElement()
    {
        $originalEm = $this->app['Core']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);

        $record = $this->getMock('record_adapter', array('substitute_subdef'), array(), '', false);

        //expect one call to substitute the documents
        $record->expects($this->once())
            ->method('substitute_subdef')
            ->with($this->equalTo('document'));

        $databox = $this->getMock('databox', array(), array(), '', false);

        //expect to fetch record
        $databox->expects($this->once())
            ->method('get_record')
            ->with($this->equalTo(self::$records['record_1']->get_record_id()))
            ->will($this->returnValue($record));

        $collection = $this->getMock('collection', array(), array(), '', false);

        //expect to fetch databox
        $collection->expects($this->once())
            ->method('get_databox')
            ->will($this->returnValue($databox));

        $lazaretFile = $this->getMock('Entities\LazaretFile', array(), array(), '', false);

        //expect to fetch possible records to subtitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue(array(self::$records['record_2'], self::$records['record_1'])));

        $lazaretFile->expects($this->any())
            ->method('getPathname')
            ->will($this->returnValue(__DIR__ . '/../../../../testfiles/test001.CR2'));

        $lazaretFile->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->EqualTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        $this->app['Core']['EM'] = $em;

        $this->client->request('POST', '/lazaret/' . $id . '/accept/', array(
            'record_id' => self::$records['record_1']->get_record_id()
        ));

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent($content);

        $em = $lazaretFile = $collection = $databox = $record = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElementNoRecordException()
    {
        $originalEm = $this->app['Core']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);

        $lazaretFile = $this->getMockBuilder('Entities\LazaretFile')
            ->disableOriginalConstructor()
            ->getMock();

        //expect to fetch possible records to subtitute
        //no records to subsitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue(array()));

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        $id = 1;

        $this->app['Core']['EM'] = $em;

        $this->client->request('POST', '/lazaret/' . $id . '/accept/', array(
            'record_id' => self::$records['record_1']->get_record_id()
        ));

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElementException()
    {
        $route = sprintf('/lazaret/%s/accept/', '99999');

        $this->client->request('POST', $route, array('record_id' => 1));

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElementBadRequestException()
    {
        $originalEm = $this->app['Core']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        $this->app['Core']['EM'] = $em;

        //Ommit record_id mandatory param
        $this->client->request('POST', '/lazaret/' . $id . '/accept/');

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::thumbnailElement
     */
    public function testThumbnailElement()
    {
        $originalEm = $this->app['Core']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        $this->app['Core']['EM'] = $em;

        $this->client->request('GET', '/lazaret/' . $id . '/thumbnail/');

        $this->app['Core']['EM'] = $originalEm;

        $response = $this->client->getResponse();

        $this->assertResponseOk($response);

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::thumbnailElement
     */
    public function testThumbnailException()
    {
        $route = sprintf('/lazaret/%s/thumbnail/', '99999');

        $this->client->request('GET', $route);

        $response = $this->client->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    private function getOneLazaretFile()
    {
        //The lazaret session
        $lazaretSession = new \Entities\LazaretSession();
        $lazaretSession->setUsrId(self::$user->get_id());
        $lazaretSession->setUpdated(new \DateTime('now'));
        $lazaretSession->setCreated(new \DateTime('-1 day'));

        //The lazaret file
        $lazaretFile = new \Entities\LazaretFile();
        $lazaretFile->setOriginalName('test');
        $lazaretFile->setPathname(__DIR__ . '/../../../../testfiles/test001.CR2');
        $lazaretFile->setBaseId(self::$collection->get_base_id());
        $lazaretFile->setSession($lazaretSession);
        $lazaretFile->setSha256('3191af52748620e0d0da50a7b8020e118bd8b8a0845120b0bb');
        $lazaretFile->setUuid('7b8ef0e3-dc8f-4b66-9e2f-bd049d175124');
        $lazaretFile->setCreated(new \DateTime('-1 day'));
        $lazaretFile->setUpdated(new \DateTime('now'));

        return $lazaretFile;
    }

    private function assertGoodJsonContent($content)
    {
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content);
        $this->assertObjectHasAttribute('message', $content);
        $this->assertTrue($content->success);
    }

    private function assertBadJsonContent($content)
    {
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content);
        $this->assertObjectHasAttribute('message', $content);
        $this->assertFalse($content->success);
    }

    private function assertResponseOk(Response $response)
    {
        $this->assertTrue($response->isOk());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }
}
