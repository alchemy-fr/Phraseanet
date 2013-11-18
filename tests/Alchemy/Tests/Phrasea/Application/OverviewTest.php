<?php

namespace Alchemy\Tests\Phrasea\Application;

use Alchemy\Phrasea\Border\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OverviewTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function testDatafilesRouteAuthenticated()
    {
        $crawler = self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $content_disposition = explode(';', $response->headers->get('content-disposition'));
        $this->assertEquals('inline', $content_disposition[0]);
        $this->assertEquals(self::$DI['record_1']->get_preview()->get_mime(), $response->headers->get('content-type'));
        $this->assertEquals(self::$DI['record_1']->get_preview()->get_size(), $response->headers->get('content-length'));
    }

    public function testDatafilesNonExistentSubdef()
    {
        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/asubdefthatdoesnotexists/');

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    public function testEtag()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'testEtag');
        copy(__DIR__ . '/../../../../files/cestlafete.jpg', $tmp);

        $media = self::$DI['app']['mediavorus']->guess($tmp);

        $file = new File(self::$DI['app'], $media, self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);

        $record->generate_subdefs($record->get_databox(), self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/datafiles/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/preview/');
        $response = self::$DI['client']->getResponse();

        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertTrue($response->isOk());
        $this->assertNotNull($response->getEtag());
        $this->assertInstanceOf('DateTime', $response->getLastModified());
        $this->assertEquals(0, $response->getMaxAge());
        $this->assertEquals(0, $response->getTtl());
        $this->assertGreaterThanOrEqual(0, $response->getAge());
        $this->assertNull($response->getExpires());

        unlink($tmp);
    }

    public function testDatafilesRouteNotAuthenticated()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testDatafilesRouteNotAuthenticatedIsOkInPublicFeed()
    {
        $this->insertOneFeedItem(self::$DI['user'], true, 1, self::$DI['record_1']);
        self::$DI['record_1']->move_to_collection(self::$DI['collection_no_access'], self::$DI['app']['phraseanet.appbox']);

        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        self::$DI['record_1']->move_to_collection(self::$DI['collection'], self::$DI['app']['phraseanet.appbox']);
    }

    public function testDatafilesRouteNotAuthenticatedUnknownSubdef()
    {
        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/notfoundreview/');

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
        $token = "unexisting_token";
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . "/" . self::$DI['record_1']->get_record_id() . '/caption/?token='.$token;

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testCaptionWithaWrongRecord()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $url = '/permalink/v1/unexisting_record/unexisting_id/caption/?token=unexisting_token';

        $crawler = self::$DI['client']->request('GET', $url);
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

        $story->substitute_subdef($name, $media, self::$DI['app']);

        self::$DI['client']->request('GET', '/datafiles/' . $story->get_sbas_id() . '/' . $story->get_record_id() . '/' . $name . '/');
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function get_a_caption(array $headers = [])
    {
        $token = self::$DI['record_1']->get_thumbnail()->get_permalink()->get_token();
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . "/" . self::$DI['record_1']->get_record_id() . '/caption/?token='.$token;

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $caption = self::$DI['record_1']->get_caption()->serialize(\caption_record::SERIALIZE_JSON);
        $this->assertEquals($caption, $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function get_a_permalinkBCcompatibility(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/whateverIwannt/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/' . $token . '/preview/';

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(rtrim(self::$DI['app']['configuration']['main']['servername'], '/') . "/permalink/v1/1/". self::$DI['record_1']->get_record_id()."/caption/?token=".$token, $response->headers->get("Link"));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPermalinkRouteNotAuthenticatedIsOkInPublicFeed()
    {
        $item = $this->insertOneFeedItem(self::$DI['user'], true);

        self::$DI['app']['authentication']->closeAccount();
        self::$DI['client']->request('GET', '/permalink/v1/' . $item->getRecord(self::$DI['app'])->get_sbas_id() . '/' . $item->getRecord(self::$DI['app'])->get_record_id() . '/preview/');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    protected function get_a_permaviewBCcompatibility(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/whateverIwannt/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/' . $token . '/preview/';

        $url = $url . 'view/';
        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function get_a_permalink(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/whateverIwannt.jpg?token=' . $token . '';

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(rtrim(self::$DI['app']['configuration']['main']['servername'], '/') . "/permalink/v1/1/". self::$DI['record_1']->get_record_id()."/caption/?token=".$token, $response->headers->get("Link"));
        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function get_a_permaview(array $headers = [])
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/?token=' . $token . '';

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(200, $response->getStatusCode());
    }
}
