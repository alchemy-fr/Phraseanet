<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

class LazaretTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;
    private static $need_records = false;

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
        $originalEm = self::$DI['app']['EM'];

        $fileLazaret = $this->getMock('Alchemy\Phrasea\Model\Entities\LazaretFile', ['getRecordsToSubstitute', 'getSession', 'getCollection'], [], '', false);

        $fileLazaret
            ->expects($this->any())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue([self::$DI['record_1']]));

        $fileLazaret
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($this->getMock('Alchemy\Phrasea\Model\Entities\LazaretSession')));

        $fileLazaret
            ->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue(self::$DI['collection']));

        //mock one Alchemy\Phrasea\Model\Repositories\LazaretFile::getFiles
        $repo = $this->getMock('Alchemy\Phrasea\Model\Repositories\LazaretFile', ['findPerPage'], [], '', false);

        $repo->expects($this->once())
            ->method('findPerPage')
            ->will($this->returnValue([$fileLazaret]));

        //mock Doctrine\ORM\EntityManager::getRepository
        $em = $this->getMock('Doctrine\ORM\EntityManager', ['getRepository'], [], '', false);

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'))
            ->will($this->returnValue($repo));

        $route = '/prod/lazaret/';

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $crawler = self::$DI['client']->request(
            'GET', $route
        );

        $this->assertResponseOk(self::$DI['client']->getResponse());

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertEquals(1, $crawler->filter('.records-subititution')->count());

        $em = $fileLazaret = $repo = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::getElement
     */
    public function testGetElement()
    {
        $originalEm = self::$DI['app']['EM'];

        $em = $this->getMock('Doctrine\ORM\EntityManager', ['find'], [], '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('GET', '/prod/lazaret/' . $id . '/');

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

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
        $originalEm = self::$DI['app']['EM'];

        $em = $this->getMock('Doctrine\ORM\EntityManager', ['find'], [], '', false);

        $id = 1;

        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue(null));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('GET', '/prod/lazaret/' . $id . '/');

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::addElement
     */
    public function testAddElement()
    {
        $originalEm = self::$DI['app']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $lazaretFileName = self::$DI['app']['root.path'] . '/tmp/lazaret/' . $lazaretFile->getFilename();
        $lazaretThumbFileName = self::$DI['app']['root.path'] . '/tmp/lazaret/' . $lazaretFile->getThumbFilename();

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $lazaretFileName);
        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $lazaretThumbFileName);

        //mock lazaret Attribute
        $lazaretAttribute = $this->getMock('Alchemy\Phrasea\Model\Entities\LazaretAttribute', [], [], '', false);

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
            AttributeInterface::NAME_METADATA, AttributeInterface::NAME_STORY, AttributeInterface::NAME_STATUS, AttributeInterface::NAME_METAFIELD
                ));
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);
        //Provide some valid test values
        $lazaretAttribute->expects($this->exactly(4))
            ->method('getValue')
            ->will($this->onConsecutiveCalls('metadataValue', $story->get_serialize_key(), '00001111', 'metafieldValue'));

        //Add the 5 attribute
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);

        $id = 1;
        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->EqualTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/force-add/', [
            'bas_id'          => $lazaretFile->getBaseId(),
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4] //Check only the four first attributes
        ]);

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

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
        $originalEm = self::$DI['app']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        //Ommit base_id mandatory param
        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/force-add/', [
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4] //Check only the four first attributes
        ]);

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::addElement
     */
    public function testAddElementException()
    {
        $originalEm = self::$DI['app']['EM'];
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('POST', '/prod/lazaret/99999/force-add/', [
            'bas_id'          => 1,
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4] //Check only the four first attributes
        ]);

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::denyElement
     */
    public function testDenyElement()
    {
        $lazaretFile = $this->insertOneLazaretFile();

        $route = sprintf('/prod/lazaret/%s/deny/', $lazaretFile->getId());

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(l.id) FROM \Alchemy\Phrasea\Model\Entities\LazaretFile l');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::denyElement
     */
    public function testEmptyLazaret()
    {
        $lazaretFile = $this->insertOneLazaretFile();

        $route = sprintf('/prod/lazaret/empty/');
        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        $query = self::$DI['app']['EM']->createQuery(
            'SELECT COUNT(l.id) FROM \Alchemy\Phrasea\Model\Entities\LazaretFile l'
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
        $route = sprintf('/prod/lazaret/%s/deny/', '99999');

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElement()
    {
        $originalEm = self::$DI['app']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $record = $this->getMock('record_adapter', ['substitute_subdef'], [], '', false);

        //expect one call to substitute the documents
        $record->expects($this->once())
            ->method('substitute_subdef')
            ->with($this->equalTo('document'));

        $databox = $this->getMock('databox', [], [], '', false);

        //expect to fetch record
        $databox->expects($this->once())
            ->method('get_record')
            ->with($this->equalTo(self::$DI['record_1']->get_record_id()))
            ->will($this->returnValue($record));

        $collection = $this->getMock('collection', [], [], '', false);

        //expect to fetch databox
        $collection->expects($this->once())
            ->method('get_databox')
            ->will($this->returnValue($databox));

        $lazaretFile = $this->getMock('Alchemy\Phrasea\Model\Entities\LazaretFile', [], [], '', false);

        //expect to fetch possible records to subtitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue([self::$DI['record_2'], self::$DI['record_1']]));

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', __DIR__ . '/../../../../../../tmp/lazaret/cestlafete.jpg');

        $lazaretFile->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $lazaretFile->expects($this->any())
            ->method('getThumbFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $lazaretFile->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->EqualTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        $called = false;
        self::$DI['app']['phraseanet.logger'] = self::$DI['app']->protect(function () use (&$called) {
            $called = true;

            return $this->getMockBuilder('\Session_Logger')
                    ->disableOriginalConstructor()
                    ->getMock();
        });

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/accept/', [
            'record_id' => self::$DI['record_1']->get_record_id()
        ]);
        $this->assertTrue($called);

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);
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
        $originalEm = self::$DI['app']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $lazaretFile = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\LazaretFile')
            ->disableOriginalConstructor()
            ->getMock();

        //expect to fetch possible records to subtitute
        //no records to subsitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue([]));

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        $id = 1;

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/accept/', [
            'record_id' => self::$DI['record_1']->get_record_id()
        ]);

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElementException()
    {
        $route = sprintf('/prod/lazaret/%s/accept/', '99999');

        self::$DI['client']->request('POST', $route, ['record_id' => 1]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::acceptElement
     */
    public function testAcceptElementBadRequestException()
    {
        $originalEm = self::$DI['app']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        //Ommit record_id mandatory param
        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/accept/');

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::thumbnailElement
     */
    public function testThumbnailElement()
    {
        $originalEm = self::$DI['app']['EM'];

        //mock Doctrine\ORM\EntityManager
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $lazaretFile = $this->getMock('Alchemy\Phrasea\Model\Entities\LazaretFile', [], [], '', false);

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', __DIR__ . '/../../../../../../tmp/lazaret/cestlafete.jpg');

        $lazaretFile->expects($this->any())
            ->method('getThumbFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $lazaretFile->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $id = 1;

        //Expect the retrieval of the lazaret file with the provided id
        $em->expects($this->any())
            ->method('find')
            ->with($this->EqualTo('Alchemy\Phrasea\Model\Entities\LazaretFile'), $this->EqualTo($id))
            ->will($this->returnValue($lazaretFile));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        self::$DI['client']->request('GET', '/prod/lazaret/' . $id . '/thumbnail/');

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $this->assertResponseOk($response);

        $em = $lazaretFile = null;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Lazaret::thumbnailElement
     */
    public function testThumbnailException()
    {
        $route = sprintf('/prod/lazaret/%s/thumbnail/', '99999');

        self::$DI['client']->request('GET', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertFalse($response->isOk());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    private function getOneLazaretFile()
    {
        //The lazaret session
        $lazaretSession = new \Alchemy\Phrasea\Model\Entities\LazaretSession();
        $lazaretSession->setUsrId(self::$DI['user']->get_id());
        $lazaretSession->setUpdated(new \DateTime('now'));
        $lazaretSession->setCreated(new \DateTime('-1 day'));

        //The lazaret file
        $lazaretFile = new \Alchemy\Phrasea\Model\Entities\LazaretFile();
        $lazaretFile->setOriginalName('test');
        $lazaretFile->setFilename('test001.jpg');
        $lazaretFile->setThumbFilename('test001.jpg');
        $lazaretFile->setBaseId(self::$DI['collection']->get_base_id());
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
