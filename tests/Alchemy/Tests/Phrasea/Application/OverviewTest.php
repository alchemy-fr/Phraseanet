<?php

namespace Alchemy\Tests\Phrasea\Application;

use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class OverviewTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testDatafilesRouteAuthenticated()
    {
        $subdef = 'preview';
        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('has_access_to_subdef')
            ->with($this->isInstanceOf('\record_adapter'), $this->equalTo($subdef))
            ->will($this->returnValue(true));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        $app = $this->getApplication();
        $app['acl'] = $aclProvider;

        $record1 = $this->getRecord1();

        $path = $app['url_generator']->generate('datafile', [
            'sbas_id' => $record1->getDataboxId(),
            'record_id' => $record1->getRecordId(),
            'subdef' => $subdef,
        ]);

        $response = $this->request('GET', $path);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('inline', explode(';', $response->headers->get('content-disposition'))[0]);
        $this->assertEquals($record1->get_preview()->get_mime(), $response->headers->get('content-type'));
        $this->assertEquals($record1->get_preview()->get_size(), $response->headers->get('content-length'));
    }

    public function testDatafilesNonExistentSubdef()
    {
        $path = self::$DI['app']['url_generator']->generate('datafile', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'subdef' => 'unknown_preview',
        ]);

        self::$DI['client']->request('GET', $path);
        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    public function testLastModified()
    {
        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('has_access_to_subdef')
            ->with($this->isInstanceOf('\record_adapter'), $this->isType('string'))
            ->will($this->returnValue(true));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        self::$DI['app']['acl'] = $aclProvider;

        $path = self::$DI['app']['url_generator']->generate('datafile', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'subdef' => 'preview',
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertInstanceOf('DateTime', $response->getLastModified());
        $this->assertEquals(0, $response->getMaxAge());
        $this->assertEquals(0, $response->getTtl());
        $this->assertGreaterThanOrEqual(0, $response->getAge());
        $this->assertNull($response->getExpires());
    }

    public function testDatafilesRouteNotAuthenticated()
    {
        self::$DI['app']['authentication']->closeAccount();
        $path = self::$DI['app']['url_generator']->generate('datafile', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'subdef' => 'preview',
        ]);

        self::$DI['client']->request('GET', $path);
        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testDatafilesRouteNotAuthenticatedIsOkInPublicFeed()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        self::$DI['record_5']->move_to_collection(self::$DI['collection_no_access'], self::$DI['app']['phraseanet.appbox']);
        $path = self::$DI['app']['url_generator']->generate('datafile', [
            'sbas_id' => self::$DI['record_5']->get_sbas_id(),
            'record_id' => self::$DI['record_5']->get_record_id(),
            'subdef' => 'preview',
        ]);

        self::$DI['client']->request('GET', $path);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        self::$DI['record_5']->move_to_collection(self::$DI['collection'], self::$DI['app']['phraseanet.appbox']);
    }

    public function testDatafilesRouteNotAuthenticatedUnknownSubdef()
    {
        self::$DI['app']['authentication']->closeAccount();
        $path = self::$DI['app']['url_generator']->generate('datafile', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'subdef' => 'preview',
        ]);

        self::$DI['client']->request('GET', $path);
        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testPermalinkAuthenticated()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_permalinkBCcompatibility(["Content-Type" => "image/jpeg"]);
        $this->get_a_permaviewBCcompatibility(["Content-Type" => "text/html; charset=UTF-8"]);
        $this->get_a_permalink(["Content-Type" => "image/jpeg"]);
        $this->get_a_permaview(["Content-Type" => "text/html; charset=UTF-8"]);
    }

    public function testPermalinkAuthenticatedWithDownloadQuery()
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();

        $path = self::$DI['app']['url_generator']->generate('permalinks_permalink' ,[
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'label' => 'whatever.jpg',
            'subdef' => 'preview',
            'token' => $token,
            'download' => '1'
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertRegExp('/^attachment;/', $response->headers->get('content-disposition', ''));
        $url = self::$DI['app']['url_generator']->generate('permalinks_caption', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'token' => $token,
        ], true);
        $this->assertEquals($url, $response->headers->get("Link"));
    }

    public function testPermalinkNotAuthenticated()
    {
        self::$DI['app']['authentication']->closeAccount();
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_permalinkBCcompatibility(["Content-Type" => "image/jpeg"]);
        $this->get_a_permaviewBCcompatibility(["Content-Type" => "text/html; charset=UTF-8"]);
        $this->get_a_permalink(["Content-Type" => "image/jpeg"]);
        $this->get_a_permaview(["Content-Type" => "text/html; charset=UTF-8"]);
    }

    public function testCaptionAuthenticated()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_caption(["Content-Type" => "application/json"]);
    }

    public function testCaptionNotAuthenticated()
    {
        self::$DI['app']['authentication']->closeAccount();
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_caption(["Content-Type" => "application/json"]);
    }

    public function testCaptionWithaWrongToken()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $path = self::$DI['app']['url_generator']->generate('permalinks_caption', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'token' => 'unexisting_token',
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testCaptionWithaWrongRecord()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $path = self::$DI['app']['url_generator']->generate('permalinks_caption', [
            'sbas_id' => 0,
            'record_id' => 4,
            'token' => 'unexisting_token',
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetAStorythumbnail()
    {
        $this->substituteAndCheck('thumbnail');
    }

    public function testGetAStoryPreview()
    {
        $this->substituteAndCheck('preview');
    }

    private function substituteAndCheck($name)
    {
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        $media = $this->getMockBuilder('MediaVorus\Media\MediaInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $symfoFile = new UploadedFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'cestlafete.jpg');

        $media->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue($symfoFile));

        self::$DI['app']['subdef.substituer']->substitute($story, $name, $media);

        $path = self::$DI['app']['url_generator']->generate('datafile', [
            'sbas_id' =>  $story->getDataboxId(),
            'record_id' => $story->getRecordId(),
            'subdef' => $name,
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function get_a_caption(array $headers = [])
    {
        $path = self::$DI['app']['url_generator']->generate('permalinks_caption', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'token' => self::$DI['record_1']->get_thumbnail()->get_permalink()->get_token(),
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $caption = self::$DI['app']['serializer.caption']->serialize(self::$DI['record_1']->get_caption(), CaptionSerializer::SERIALIZE_JSON);
        $this->assertEquals($caption, $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());

        self::$DI['client']->request('OPTIONS', $path);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('GET, HEAD, OPTIONS', $response->headers->get('Allow'));
    }

    private function get_a_permalinkBCcompatibility(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();

        $path = self::$DI['app']['url_generator']->generate('permalinks_permalink_old' ,[
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'label' => 'whatever',
            'subdef' => 'preview',
            'token' => $token
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $url = self::$DI['app']['url_generator']->generate('permalinks_caption', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'token' => $token,
        ], true);
        $this->assertEquals($url, $response->headers->get("Link"));
        $this->assertTrue($response->isOk());
    }

    public function testPermalinkRouteNotAuthenticatedIsOkInPublicFeed()
    {
        /** @var Feed $feed */
        $feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 2);
        /** @var FeedEntry $entry */
        $entry = $feed->getEntries()->first();
        /** @var FeedItem $item */
        $item = $entry->getItems()->first();

        $record = $item->getRecord(self::$DI['app']);

        // Ensure permalink is created
        \media_Permalink_Adapter::getPermalink(
            self::$DI['app'],
            $record->getDatabox(),
            $record->get_subdef('preview')
        );

        $path = self::$DI['app']['url_generator']->generate('permalinks_permaview', [
            'sbas_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'subdef' => 'preview',
        ]);

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', $path);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    private function get_a_permaviewBCcompatibility(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $path = self::$DI['app']['url_generator']->generate('permalinks_permaview_old' ,[
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'label' => 'whatever',
            'subdef' => 'preview',
            'token' => $token
        ]);
        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertTrue($response->isOk());
    }

    private function get_a_permalink(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();

        $path = self::$DI['app']['url_generator']->generate('permalinks_permalink' ,[
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'label' => 'whatever.jpg',
            'subdef' => 'preview',
            'token' => $token
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        $this->assertRegExp('/^inline;/', $response->headers->get('content-disposition'));
        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $url = self::$DI['app']['url_generator']->generate('permalinks_caption', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'token' => $token,
        ], true);
        $this->assertEquals($url, $response->headers->get("Link"));
        $this->assertTrue($response->isOk());

        self::$DI['client']->request('OPTIONS', $path);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('GET, HEAD, OPTIONS', $response->headers->get('Allow'));
    }

    private function get_a_permaview(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();

        $path = self::$DI['app']['url_generator']->generate('permalinks_permaview', [
            'sbas_id' => self::$DI['record_1']->get_sbas_id(),
            'record_id' => self::$DI['record_1']->get_record_id(),
            'subdef' => 'preview',
            'token' => $token
        ]);

        self::$DI['client']->request('GET', $path);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(200, $response->getStatusCode());

        self::$DI['client']->request('OPTIONS', $path);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('GET, HEAD, OPTIONS', $response->headers->get('Allow'));
    }
}
