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
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testDatafilesRouteNotAuthenticatedIsOkInPublicFeed()
    {
        $publicFeed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], 'titre', 'subtitre');
        $publicFeed->set_public(true);
        $publisher = \Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], $publicFeed, self::$DI['user']);
        $entry = \Feed_Entry_Adapter::create(self::$DI['app'], $publicFeed, $publisher, 'titre', 'sub titre entry', 'author name', 'author email', false);
        self::$DI['record_1']->move_to_collection(self::$DI['collection_no_access'], self::$DI['app']['phraseanet.appbox']);
        $item = \Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], $entry, self::$DI['record_1']);

        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        self::$DI['record_1']->move_to_collection(self::$DI['collection'], self::$DI['app']['phraseanet.appbox']);
        $publicFeed->set_public(false);
    }

    public function testDatafilesRouteNotAuthenticatedUnknownSubdef()
    {
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/notfoundreview/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testPermalinkAuthenticated()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_permalinkBCcompatibility(array("Content-Type" => "image/jpeg"));
        $this->get_a_permaviewBCcompatibility(array("Content-Type" => "text/html; charset=UTF-8"));
        $this->get_a_permalink(array("Content-Type" => "image/jpeg"));
        $this->get_a_permaview(array("Content-Type" => "text/html; charset=UTF-8"));
    }

    public function testPermalinkAuthenticatedWithDownloadQuery()
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/whateverIwannt.jpg?token=' . $token . '&download=1';

        self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        $this->assertRegExp('/^attachment;/', $response->headers->get('content-disposition'));

        $this->assertEquals(rtrim(self::$DI['app']['phraseanet.configuration']['main']['servername'], '/') . "/permalink/v1/1/". self::$DI['record_1']->get_record_id()."/caption/?token=".$token, $response->headers->get("Link"));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPermalinkNotAuthenticated()
    {
        $this->logout(self::$DI['app']);
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_permalinkBCcompatibility(array("Content-Type" => "image/jpeg"));
        $this->get_a_permaviewBCcompatibility(array("Content-Type" => "text/html; charset=UTF-8"));
        $this->get_a_permalink(array("Content-Type" => "image/jpeg"));
        $this->get_a_permaview(array("Content-Type" => "text/html; charset=UTF-8"));
    }

    public function testCaptionAuthenticated()
    {
        $this->assertTrue(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_caption(array("Content-Type" => "application/json"));
    }

    public function testCaptionNotAuthenticated()
    {
        $this->logout(self::$DI['app']);
        $this->assertFalse(self::$DI['app']['authentication']->isAuthenticated());
        $this->get_a_caption(array("Content-Type" => "application/json"));
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

    protected function get_a_caption(array $headers = array())
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

        self::$DI['client']->request('OPTIONS', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('GET, HEAD, OPTIONS', $response->headers->get('Allow'));
    }

    protected function get_a_permalinkBCcompatibility(array $headers = array())
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/whateverIwannt/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/' . $token . '/preview/';

        self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(rtrim(self::$DI['app']['phraseanet.configuration']['main']['servername'], '/') . "/permalink/v1/1/". self::$DI['record_1']->get_record_id()."/caption/?token=".$token, $response->headers->get("Link"));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPermalinkRouteNotAuthenticatedIsOkInPublicFeed()
    {
        $publicFeed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], 'titre', 'subtitre');
        $publicFeed->set_public(true);
        $publisher = \Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], $publicFeed, self::$DI['user']);
        $entry = \Feed_Entry_Adapter::create(self::$DI['app'], $publicFeed, $publisher, 'titre', 'sub titre entry', 'author name', 'author email', false);
        $item = \Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], $entry, self::$DI['record_1']);

        $this->logout(self::$DI['app']);
        self::$DI['client']->request('GET', '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $publicFeed->set_public(false);
    }

    protected function get_a_permaviewBCcompatibility(array $headers = array())
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

    protected function get_a_permalink(array $headers = array())
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/whateverIwannt.jpg?token=' . $token . '';

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        $this->assertRegExp('/^inline;/', $response->headers->get('content-disposition'));
        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(rtrim(self::$DI['app']['phraseanet.configuration']['main']['servername'], '/') . "/permalink/v1/1/". self::$DI['record_1']->get_record_id()."/caption/?token=".$token, $response->headers->get("Link"));
        $this->assertEquals(200, $response->getStatusCode());

        self::$DI['client']->request('OPTIONS', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('GET, HEAD, OPTIONS', $response->headers->get('Allow'));
    }

    protected function get_a_permaview(array $headers = array())
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/?token=' . $token . '';

        $crawler = self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $response->headers->get($name));
        }

        $this->assertEquals(200, $response->getStatusCode());

        self::$DI['client']->request('OPTIONS', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('GET, HEAD, OPTIONS', $response->headers->get('Allow'));
    }
}
