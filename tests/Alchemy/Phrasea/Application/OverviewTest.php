<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\Filesystem\Filesystem;

class ApplicationOverviewTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    function testDatafilesRouteAuthenticated()
    {
        $registry = self::$application['phraseanet.registry'];
        $crawler = $this->client->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $content_disposition = explode(';', $response->headers->get('content-disposition'));
        $this->assertEquals('inline', $content_disposition[0]);
        $this->assertEquals(self::$DI['record_1']->get_preview()->get_mime(), $response->headers->get('content-type'));
        $this->assertEquals(self::$DI['record_1']->get_preview()->get_size(), $response->headers->get('content-length'));
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    function testDatafilesNonExistentSubdef()
    {
        $crawler = $this->client->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/asubdefthatdoesnotexists/');
    }

    function testEtag()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'testEtag');
        copy(__DIR__ . '/../../../testfiles/cestlafete.jpg', $tmp);

        $media = self::$application['mediavorus']->guess($tmp);

        $file = new Alchemy\Phrasea\Border\File($media, self::$collection);
        $record = record_adapter::createFromFile($file, self::$application);

        $record->generate_subdefs($record->get_databox(), self::$application);

        $crawler = $this->client->request('GET', '/datafiles/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertTrue($response->isOk());
        $this->assertNotNull($response->getEtag());
        $this->assertInstanceOf('DateTime', $response->getLastModified());
        $this->assertNull($response->getMaxAge());
        $this->assertNull($response->getTtl());
        $this->assertGreaterThanOrEqual(0, $response->getAge());
        $this->assertNull($response->getExpires());

        unlink($tmp);
    }

    function testDatafilesRouteNotAuthenticated()
    {
        $appbox = self::$application['phraseanet.appbox'];
        self::$application->closeAccount();
        $crawler = $this->client->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/preview/');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());

        $crawler = $this->client->request('GET', '/datafiles/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/notfoundreview/');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    function testPermalinkAuthenticated()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $this->assertTrue(self::$application->isAuthenticated());
        $this->get_a_permalink();
    }

    function testPermalinkNotAuthenticated()
    {
        $appbox = self::$application['phraseanet.appbox'];
        self::$application->closeAccount();
        $this->assertFalse(self::$application->isAuthenticated());
        $this->get_a_permalink();
    }

    protected function get_a_permalink()
    {
        $token = self::$DI['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/whateverIwannt/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/' . $token . '/preview/';

        $crawler = $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $url = $url . 'view/';
        $crawler = $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
