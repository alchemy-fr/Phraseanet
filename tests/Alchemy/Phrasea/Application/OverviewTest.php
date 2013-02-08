<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApplicationOverviewTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/Overview.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    function testDatafilesRouteAuthenticated()
    {
        $registry = registry::get_instance();
        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $content_disposition = explode(';', $response->headers->get('content-disposition'));
        $this->assertEquals('inline', $content_disposition[0]);
        $this->assertEquals(static::$records['record_1']->get_preview()->get_mime(), $response->headers->get('content-type'));
        $this->assertEquals(static::$records['record_1']->get_preview()->get_size(), $response->headers->get('content-length'));

        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/asubdefthatdoesnotexists/');
        $response = $this->client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    function testEtag()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'testEtag');
        copy(__DIR__ . '/../../../testfiles/cestlafete.jpg', $tmp);

        $media = self::$core['mediavorus']->guess(new \SplFileInfo($tmp));

        $file = new Alchemy\Phrasea\Border\File($media, self::$collection);
        $record = record_adapter::createFromFile($file);

        $crawler = $this->client->request('GET', '/datafiles/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertTrue($response->isOk());
        $this->assertNull($response->getEtag());
        $this->assertNull($response->getLastModified());
        $this->assertEquals(0, $response->getMaxAge());
        $this->assertEquals(0, $response->getTtl());
        $this->assertNull($response->getExpires());

        $record->generate_subdefs($record->get_databox(), self::$core['monolog']);

        $crawler = $this->client->request('GET', '/datafiles/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

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

    function testDatafilesRouteNotAuthenticated()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $appbox->get_session()->logout();
        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/preview/');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());

        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/notfoundreview/');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    function testPermalinkAuthenticated()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $this->assertTrue($appbox->get_session()->is_authenticated());
        $this->get_a_permalink();
    }

    function testPermalinkNotAuthenticated()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $appbox->get_session()->logout();
        $this->assertFalse($appbox->get_session()->is_authenticated());
        $this->get_a_permalink();
    }

    public function testGetAStorythumbnail()
    {
        $story = \record_adapter::createStory(self::$collection);

        $media = $this->getMockBuilder('MediaVorus\Media\Media')
            ->disableOriginalConstructor()
            ->getMock();

        $symfoFile = new UploadedFile(__DIR__ . '/../../../testfiles/cestlafete.jpg', 'cestlafete.jpg');

        $media->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue($symfoFile));

        $story->substitute_subdef('thumbnail', $media);

        $this->client->request('GET', '/datafiles/' . $story->get_sbas_id() . '/' . $story->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }


    protected function get_a_permalink()
    {
        $token = static::$records['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/whateverIwannt/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/' . $token . '/preview/';

        $crawler = $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $url = $url . 'view/';
        $crawler = $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
