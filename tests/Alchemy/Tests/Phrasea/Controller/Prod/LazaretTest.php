<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\HttpKernelBrowser as Client;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class LazaretTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;

    public function setUp(): void
    {
        parent::setUp();

        self::$DI['app']['dispatcher']->removeSubscriber(self::$DI['app']['phraseanet.session-manager-subscriber']);
    }

    public function testListElement()
    {
        $lazaretFile = $this->getOneLazaretFile();
        $route = '/prod/lazaret/';

        /** @var \Doctrine\Persistence\ObjectManager $em */
        $em = self::$DI['app']['orm.em'];
        $em->persist($lazaretFile);
        $em->flush();
        $crawler = self::$DI['client']->request(
            'GET', $route
        );

        $this->assertResponseOk(self::$DI['client']->getResponse());
        $this->assertGreaterThanOrEqual(1, $crawler->filter('.wrapper-item')->count());
    }

    public function testGetElement()
    {
        $lazaretFile = $this->getOneLazaretFile();

        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue($lazaretFile));

        self::$DI['client']->request('GET', '/prod/lazaret/' . $id . '/');

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $content = json_decode($response->getContent());

        $this->assertGoodJsonContent($content);
        $this->assertObjectHasProperty('message', $content);
        $this->assertObjectHasProperty('result', $content);
        $this->assertObjectHasProperty('filename', $content->result);
        $this->assertObjectHasProperty('base_id', $content->result);
        $this->assertObjectHasProperty('created', $content->result);
        $this->assertObjectHasProperty('updated', $content->result);
        $this->assertObjectHasProperty('pathname', $content->result);
        $this->assertObjectHasProperty('sha256', $content->result);
        $this->assertObjectHasProperty('uuid', $content->result);
    }

    public function testGetElementException()
    {
        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue(null));

        self::$DI['client']->request('GET', '/prod/lazaret/' . $id . '/');

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testAddElement()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $originalEm = self::$DI['app']['orm.em'];
        $em = $this->createEntityManagerMock();

        $lazaretFile = $this->getOneLazaretFile();

        $lazaretFileName = self::$DI['app']['tmp.lazaret.path'].'/'.$lazaretFile->getFilename();
        $lazaretThumbFileName = self::$DI['app']['tmp.lazaret.path'].'/'.$lazaretFile->getThumbFilename();

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $lazaretFileName);
        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $lazaretThumbFileName);

        //mock lazaret Attribute
        $lazaretAttribute = $this->createMock('Alchemy\Phrasea\Model\Entities\LazaretAttribute');

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
            ->will($this->onConsecutiveCalls('metadataValue', $story->getId(), '00001111', 'metafieldValue'));

        //Add the 5 attribute
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->addAttribute($lazaretAttribute);

        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        self::$DI['app']['orm.em'] = $em;

        $randomValue = $this->setSessionFormToken('prodLazaret');

        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/force-add/', [
            'bas_id'          => $lazaretFile->getBaseId(),
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4], //Check only the four first attributes
            'prodLazaret_token'  => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        self::$DI['app']['orm.em'] = $originalEm;
        $story->delete();
    }

    public function testAddElementWithCopyMeta()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $originalEm = self::$DI['app']['orm.em'];
        $em = $this->createEntityManagerMock();

        // $lazaretFile = $this->getOneLazaretFile();

        //The lazaret session
        $lazaretSession = new \Alchemy\Phrasea\Model\Entities\LazaretSession();
        $lazaretSession->setUser(self::$DI['user']);
        $lazaretSession->setUpdated(new \DateTime('now'));
        $lazaretSession->setCreated(new \DateTime('-1 day'));

        //The lazaret file
        $lazaretFile = $this->createMock(LazaretFile::class);
//        $lazaretFile->setOriginalName('test');

//        $lazaretFile->setFilename('test001.jpg');
        $lazaretFile->method('getFilename')->willReturn('test001.jpg');

//        $lazaretFile->setThumbFilename('test001.jpg');
        $lazaretFile->method('getThumbFilename')->willReturn('test001.jpg');

//        $lazaretFile->setBaseId(self::$DI['collection']->get_base_id());
        $lazaretFile->method('getBaseId')->willReturn(self::$DI['collection']->get_base_id());
        $lazaretFile->method('getCollection')->willReturn(self::$DI['collection']);

//        $lazaretFile->setSession($lazaretSession);
        $lazaretFile->method('getSession')->willReturn($lazaretSession);


//        $lazaretFile->setSha256('3191af52748620e0d0da50a7b8020e118bd8b8a0845120b0bb');
//        $lazaretFile->setUuid('7b8ef0e3-dc8f-4b66-9e2f-bd049d175124');
//        $lazaretFile->setCreated(new \DateTime('-1 day'));
//        $lazaretFile->setUpdated(new \DateTime('now'));

        $lazaretFileName = self::$DI['app']['tmp.lazaret.path'].'/'.$lazaretFile->getFilename();
        $lazaretThumbFileName = self::$DI['app']['tmp.lazaret.path'].'/'.$lazaretFile->getThumbFilename();

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $lazaretFileName);
        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', $lazaretThumbFileName);


        //mock lazaret Attribute
        $lazaretAttribute = $this->createMock('Alchemy\Phrasea\Model\Entities\LazaretAttribute');

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
            ->will($this->onConsecutiveCalls('metadataValue', $story->getId(), '00001111', 'metafieldValue'));

        //Add the 5 attribute
//        $lazaretFile->addAttribute($lazaretAttribute);
//        $lazaretFile->addAttribute($lazaretAttribute);
//        $lazaretFile->addAttribute($lazaretAttribute);
//        $lazaretFile->addAttribute($lazaretAttribute);
//        $lazaretFile->addAttribute($lazaretAttribute);
        $lazaretFile->method('getAttributes')->willReturn([$lazaretAttribute, $lazaretAttribute, $lazaretAttribute, $lazaretAttribute, $lazaretAttribute]);

        //expect to fetch possible records to subtitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue([self::$DI['record_2'], self::$DI['record_1']]));

        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->exactly(2))
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        $randomValue = $this->setSessionFormToken('prodLazaret');

        self::$DI['app']['orm.em'] = $em;
        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/force-add/', [
            'copy_meta'       => 1,
            'record_id'       => self::$DI['record_1']->get_record_id(),
            'bas_id'          => $lazaretFile->getBaseId(),
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4], //Check only the four first attributes
            'prodLazaret_token'  => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        self::$DI['app']['orm.em'] = $originalEm;
        $story->delete();
    }

    public function testAddElementBadRequestException()
    {
        $id = 1;

        $randomValue = $this->setSessionFormToken('prodLazaret');

        //Ommit base_id mandatory param
        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/force-add/', [
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4], //Check only the four first attributes
            'prodLazaret_token'  => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testAddElementException()
    {
        $randomValue = $this->setSessionFormToken('prodLazaret');

        self::$DI['client']->request('POST', '/prod/lazaret/99999/force-add/', [
            'bas_id'          => 1,
            'keep_attributes' => 1,
            'attributes'      => [1, 2, 3, 4], //Check only the four first attributes
            'prodLazaret_token'  => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testDenyElement()
    {
        $lazaretFile = self::$DI['lazaret_1'];
        $randomValue = $this->setSessionFormToken('prodLazaret');

        $route = sprintf('/prod/lazaret/%s/deny/', $lazaretFile->getId());

        self::$DI['client']->request('POST', $route, ['prodLazaret_token'  => $randomValue]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        $query = self::$DI['app']['orm.em']->createQuery('SELECT COUNT(l.id) FROM Phraseanet:LazaretFile l');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $lazaretFile = null;
    }

    public function testEmptyLazaret()
    {
        $route = sprintf('/prod/lazaret/empty/');
        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent(json_decode($response->getContent()));

        $query = self::$DI['app']['orm.em']->createQuery(
            'SELECT COUNT(l.id) FROM Phraseanet:LazaretFile l'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);
    }

    public function testDenyElementException()
    {
        $randomValue = $this->setSessionFormToken('prodLazaret');

        $route = sprintf('/prod/lazaret/%s/deny/', '99999');

        self::$DI['client']->request('POST', $route, ['prodLazaret_token'  => $randomValue]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testAcceptElement()
    {
        $em = $this->createEntityManagerMock();

        self::$DI['app']['subdef.substituer'] = $this->createStub('Alchemy\Phrasea\Media\SubdefSubstituer');
        $record = self::$DI['record_2'];

        //expect one call to substitute the documents
        self::$DI['app']['subdef.substituer']->expects($this->once())
            ->method('substituteDocument')
            ->with($record);

        $databox = $this->createMock('databox');

        //expect to fetch record
        $databox->expects($this->once())
            ->method('get_record')
            ->with($this->equalTo(self::$DI['record_1']->get_record_id()))
            ->will($this->returnValue($record));

        $collection = $this->createMock('collection');

        //expect to fetch databox
        $collection->expects($this->once())
            ->method('get_databox')
            ->will($this->returnValue($databox));

        $lazaretFile = $this->createMock('Alchemy\Phrasea\Model\Entities\LazaretFile');

        //expect to fetch possible records to subtitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue([self::$DI['record_2'], self::$DI['record_1']]));

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', self::$DI['app']['tmp.lazaret.path'].'/cestlafete.jpg');

        $lazaretFile
            ->method('getFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $lazaretFile
            ->method('getThumbFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $lazaretFile
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue($lazaretFile));

        //In any case we expect the deletion of the lazaret file
        $em->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($lazaretFile));

        //Then flush
        $em->expects($this->once())
            ->method('flush');

        $called = false;
        self::$DI['app']['phraseanet.logger'] = self::$DI['app']->protect(function () use (&$called) {
            $called = true;

            return $this->createMock('\Session_Logger');
        });

        self::$DI['app']['orm.em'] = $em;

        $randomValue = $this->setSessionFormToken('prodLazaret');

        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/accept/', [
            'record_id'         => self::$DI['record_1']->get_record_id(),
            'prodLazaret_token' => $randomValue
        ]);
        $this->assertTrue($called);

        $response = self::$DI['client']->getResponse();

        $content = json_decode($response->getContent());

        $this->assertResponseOk($response);
        $this->assertGoodJsonContent($content);
    }

    public function testAcceptElementNoRecordException()
    {
        $lazaretFile = $this->createMock('Alchemy\Phrasea\Model\Entities\LazaretFile');

        //expect to fetch possible records to subtitute
        //no records to subsitute
        $lazaretFile->expects($this->once())
            ->method('getRecordsToSubstitute')
            ->will($this->returnValue([]));

        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue($lazaretFile));

        $id = 1;

        $randomValue = $this->setSessionFormToken('prodLazaret');

        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/accept/', [
            'record_id'         => self::$DI['record_1']->get_record_id(),
            'prodLazaret_token' => $randomValue
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testAcceptElementException()
    {
        $randomValue = $this->setSessionFormToken('prodLazaret');

        $route = sprintf('/prod/lazaret/%s/accept/', '99999');

        self::$DI['client']->request('POST', $route, ['record_id' => 1, 'prodLazaret_token' => $randomValue]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testAcceptElementBadRequestException()
    {
        $id = 1;

        $randomValue = $this->setSessionFormToken('prodLazaret');

        //Ommit record_id mandatory param
        self::$DI['client']->request('POST', '/prod/lazaret/' . $id . '/accept/', ['prodLazaret_token' => $randomValue]);

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
        $this->assertBadJsonContent(json_decode($response->getContent()));
    }

    public function testThumbnailElement()
    {
        $lazaretFile = $this->createMock('Alchemy\Phrasea\Model\Entities\LazaretFile');

        copy(__DIR__ . '/../../../../../files/cestlafete.jpg', self::$DI['app']['tmp.lazaret.path'].'/cestlafete.jpg');

        $lazaretFile
            ->method('getThumbFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $lazaretFile
            ->method('getFilename')
            ->will($this->returnValue('cestlafete.jpg'));

        $id = 1;

        self::$DI['app']['repo.lazaret-files'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.lazaret-files']->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->will($this->returnValue($lazaretFile));

        self::$DI['client']->request('GET', '/prod/lazaret/' . $id . '/thumbnail/');

        $response = self::$DI['client']->getResponse();

        $this->assertResponseOk($response);
    }

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
        $lazaretSession->setUser(self::$DI['user']);
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
        $this->assertObjectHasProperty('success', $content);
        $this->assertObjectHasProperty('message', $content);
        $this->assertTrue($content->success);
    }

    private function assertBadJsonContent($content)
    {
        $this->assertTrue(is_object($content));
        $this->assertObjectHasProperty('success', $content);
        $this->assertObjectHasProperty('message', $content);
        $this->assertFalse($content->success);
    }

    private function assertResponseOk(Response $response)
    {
        $this->assertTrue($response->isOk());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }
}
