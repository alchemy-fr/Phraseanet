<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Controller\Api\V1Controller;
use Alchemy\Phrasea\ControllerProvider\Api\V1;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\ApiOauthToken;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Doctrine\Common\Collections\ArrayCollection;
use Guzzle\Common\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

abstract class ApiTestCase extends \PhraseanetWebTestCase
{
    abstract protected function getParameters(array $parameters = []);
    abstract protected function unserialize($data);
    abstract protected function getAcceptMimeType();

    protected $adminAccessToken;
    protected $userAccessToken;

    public function tearDown()
    {
        $this->unsetToken();
        parent::tearDown();
    }

    public function getApplicationPath()
    {
        return '/lib/Alchemy/Phrasea/Application/Api.php';
    }

    public function setUp()
    {
        parent::setUp();

        if (null === $this->adminAccessToken) {
            $tokens = self::$DI['app']['repo.api-oauth-tokens']->findOauthTokens(self::$DI['oauth2-app-acc-user']);
            if (count($tokens) === 0) {
                $this->fail(sprintf('No access token generated between user %s & application %s',
                    self::$DI['oauth2-app-acc-user']->getUser()->getLogin(),
                    self::$DI['oauth2-app-acc-user']->getApplication()->getName()
                ));
            }

            $this->adminAccessToken = current($tokens);
        }

        if (null === $this->userAccessToken) {
            $tokens = self::$DI['app']['repo.api-oauth-tokens']->findOauthTokens(self::$DI['oauth2-app-acc-user-not-admin']);
            if (count($tokens) === 0) {
                $this->fail(sprintf('No access token generated between user %s & application %s',
                    self::$DI['oauth2-app-acc-user-not-admin']->getUser()->getLogin(),
                    self::$DI['oauth2-app-acc-user-not-admin']->getApplication()->getName()
                ));
            }

            $this->userAccessToken = current($tokens);
        }
    }

    public function testRouteNotFound()
    {
        $route = '/api/v1/nothinghere';
        $this->setToken($this->userAccessToken);
        $client = $this->getClient();
        $client->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());

        $this->evaluateResponseNotFound($client->getResponse());
        $this->evaluateMetaNotFound($content);
    }

    public function testRouteMe()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/me/';

        $this->evaluateMethodNotAllowedRoute($route, [ 'POST', 'PUT' ]);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertArrayHasKey('user', $content['response']);

        $this->evaluateGoodUserItem($content['response']['user'], self::$DI['user_notAdmin']);
    }

    public function testRouteMeStructure()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/me/structures/';

        $this->evaluateMethodNotAllowedRoute($route, [ 'POST', 'PUT' ]);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertArrayHasKey('meta_fields', $content['response']);
        $this->assertArrayHasKey('aggregable_fields', $content['response']);
        $this->assertArrayHasKey('technical_fields', $content['response']);
    }

    public function testRouteMeSubdefs()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/me/subdefs/';

        $this->evaluateMethodNotAllowedRoute($route, [ 'POST', 'PUT' ]);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertArrayHasKey('subdefs', $content['response']);
    }

    public function testRouteMeCollections()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/me/collections/';
        $this->evaluateMethodNotAllowedRoute($route, [ 'POST', 'PUT' ]);
        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->assertArrayHasKey('collections', $content['response']);
    }

    protected function evaluateGoodUserItem($data, User $user)
    {
        foreach ([
            '@entity@'        => V1Controller::OBJECT_TYPE_USER,
            'id'              => $user->getId(),
            'email'           => $user->getEmail() ?: null,
            'login'           => $user->getLogin() ?: null,
            'first_name'      => $user->getFirstName() ?: null,
            'last_name'       => $user->getLastName() ?: null,
            'display_name'    => $user->getDisplayName() ?: null,
            'address'         => $user->getAddress() ?: null,
            'zip_code'        => $user->getZipCode() ?: null,
            'city'            => $user->getCity() ?: null,
            'country'         => $user->getCountry() ?: null,
            'phone'           => $user->getPhone() ?: null,
            'fax'             => $user->getFax() ?: null,
            'job'             => $user->getJob() ?: null,
            'position'        => $user->getActivity() ?: null,
            'company'         => $user->getCompany() ?: null,
            'geoname_id'      => $user->getGeonameId() ?: null,
            'last_connection' => $user->getLastConnection() ? $user->getLastConnection()->format(DATE_ATOM) : null,
            'created_on'      => $user->getCreated() ? $user->getCreated()->format(DATE_ATOM) : null,
            'updated_on'      => $user->getUpdated() ? $user->getUpdated()->format(DATE_ATOM) : null,
            'locale'          => $user->getLocale() ?: null,
        ] as $key => $value) {
            $this->assertArrayHasKey($key, $data, 'Assert key is present '.$key);
            if ($value) {
                $this->assertEquals($value, $data[$key], 'Check key '.$key);
            }
        }
    }

    protected function evaluateGoodFeed($feed)
    {
        $this->assertArrayHasKey('id', $feed);
        $this->assertArrayHasKey('title', $feed);
        $this->assertArrayHasKey('subtitle', $feed);
        $this->assertArrayHasKey('total_entries', $feed);
        $this->assertArrayHasKey('icon', $feed);
        $this->assertArrayHasKey('public', $feed);
        $this->assertArrayHasKey('readonly', $feed);
        $this->assertArrayHasKey('deletable', $feed);
        $this->assertArrayHasKey('created_on', $feed);
        $this->assertArrayHasKey('updated_on', $feed);

        $this->assertInternalType('integer', $feed['id']);
        $this->assertInternalType('string', $feed['title']);
        $this->assertInternalType('string', $feed['subtitle']);
        $this->assertInternalType('integer', $feed['total_entries']);
        $this->assertInternalType('boolean', $feed['icon']);
        $this->assertInternalType('boolean', $feed['public']);
        $this->assertInternalType('boolean', $feed['readonly']);
        $this->assertInternalType('boolean', $feed['deletable']);
        $this->assertInternalType('string', $feed['created_on']);
        $this->assertInternalType('string', $feed['updated_on']);

        $this->assertDateAtom($feed['created_on']);
        $this->assertDateAtom($feed['updated_on']);
    }

    protected function assertGoodEntry($entry)
    {
        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('author_email', $entry);
        $this->assertArrayHasKey('author_name', $entry);
        $this->assertArrayHasKey('created_on', $entry);
        $this->assertArrayHasKey('updated_on', $entry);
        $this->assertArrayHasKey('title', $entry);
        $this->assertArrayHasKey('subtitle', $entry);
        $this->assertArrayHasKey('items', $entry);
        $this->assertArrayHasKey('url', $entry);
        $this->assertArrayHasKey('feed_url', $entry);

        $this->assertInternalType('string', $entry['author_email']);
        $this->assertInternalType('string', $entry['author_name']);
        $this->assertDateAtom($entry['created_on']);
        $this->assertDateAtom($entry['updated_on']);
        $this->assertInternalType('string', $entry['title']);
        $this->assertInternalType('string', $entry['subtitle']);
        $this->assertInternalType('array', $entry['items']);

        foreach ($entry['items'] as $item) {
            $this->assertInternalType('integer', $item['item_id']);
            $this->evaluateGoodRecord($item['record']);
        }

        $this->assertRegExp('/\/feeds\/entry\/[0-9]+\//', $entry['url']);
        $this->assertRegExp('/\/feeds\/[0-9]+\/content\//', $entry['feed_url']);
    }

    protected function getAddRecordParameters()
    {
        return [
            'base_id' => self::$DI['collection']->get_base_id()
        ];
    }

    protected function getAddRecordFile()
    {
        $file = tempnam(sys_get_temp_dir(), 'upload');
        copy(__DIR__ . '/../../../../../files/iphone_pic.jpg', $file);

        return [
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($file, 'upload.jpg')
        ];
    }

    protected function checkLazaretFile($file)
    {
        $this->assertArrayHasKey('id', $file);
        $this->assertArrayHasKey('session', $file);
        $this->assertArrayHasKey('base_id', $file);
        $this->assertArrayHasKey('original_name', $file);
        $this->assertArrayHasKey('sha256', $file);
        $this->assertArrayHasKey('uuid', $file);
        $this->assertArrayHasKey('forced', $file);
        $this->assertArrayHasKey('checks', $file);
        $this->assertArrayHasKey('created_on', $file);
        $this->assertArrayHasKey('updated_on', $file);

        $this->assertInternalType('integer', $file['id']);
        $this->assertInternalType('array', $file['session']);
        $this->assertInternalType('integer', $file['base_id']);
        $this->assertInternalType('string', $file['original_name']);
        $this->assertInternalType('string', $file['sha256']);
        $this->assertInternalType('string', $file['uuid']);
        $this->assertInternalType('boolean', $file['forced']);
        $this->assertInternalType('array', $file['checks']);
        $this->assertInternalType('string', $file['updated_on']);
        $this->assertInternalType('string', $file['created_on']);

        $this->assertArrayHasKey('id', $file['session']);
        $this->assertArrayHasKey('usr_id', $file['session']);

        $this->assertRegExp('/[a-f0-9]{64}/i', $file['sha256']);
        $this->assertRegExp('/[a-f0-9-]+/i', $file['uuid']);

        foreach ($file['checks'] as $check) {
            $this->assertInternalType('string', $check);
        }

        $this->assertDateAtom($file['updated_on']);
        $this->assertDateAtom($file['created_on']);
    }

    protected function evaluateNotFoundRoute($route, $methods)
    {
        foreach ($methods as $method) {
            self::$DI['client']->request($method, $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
            $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

            $this->evaluateResponseNotFound(self::$DI['client']->getResponse());
            $this->evaluateMetaNotFound($content);
        }
    }

    protected function checkEmbed($embed, \record_adapter $record)
    {
        if ($embed['filesize'] === 0) {
            var_dump($embed);
        }
        $subdef = $record->get_subdef($embed['name']);
        $this->assertArrayHasKey("name", $embed);
        $this->assertArrayHasKey("permalink", $embed);
        $this->checkPermalink($embed['permalink'], $subdef);
        $this->assertArrayHasKey("height", $embed);
        $this->assertEquals($embed['height'], $subdef->get_height());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $embed['height']);
        $this->assertArrayHasKey("width", $embed);
        $this->assertEquals($embed['width'], $subdef->get_width());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $embed['width']);
        $this->assertArrayHasKey("filesize", $embed);
        $this->assertEquals($embed['filesize'], $subdef->get_size());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $embed['filesize']);
        $this->assertArrayHasKey("player_type", $embed);
        $this->assertEquals($embed['player_type'], $subdef->get_type());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $embed['player_type']);
        $this->assertArrayHasKey("mime_type", $embed);
        $this->assertEquals($embed['mime_type'], $subdef->get_mime());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $embed['mime_type']);
        $this->assertArrayHasKey("devices", $embed);
        $this->assertEquals($embed['devices'], $subdef->getDevices());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $embed['devices']);
    }

    protected function checkPermalink($permalink, \media_subdef $subdef)
    {
        if (!$subdef->is_physically_present()) {
            return;
        }
        $start = microtime(true);
        $this->assertNotNull($subdef->get_permalink());
        $this->assertInternalType('array', $permalink);
        $this->assertArrayHasKey("created_on", $permalink);
        $now = new \Datetime($permalink['created_on']);
        $interval = $now->diff($subdef->get_permalink()->get_created_on());
        $this->assertTrue(abs($interval->format('U')) < 2);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink['created_on']);
        $this->assertDateAtom($permalink['created_on']);
        $this->assertArrayHasKey("id", $permalink);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $permalink['id']);
        $this->assertEquals($subdef->get_permalink()->get_id(), $permalink['id']);
        $this->assertArrayHasKey("is_activated", $permalink);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $permalink['is_activated']);
        $this->assertEquals($subdef->get_permalink()->get_is_activated(), $permalink['is_activated']);
        $this->assertArrayHasKey("label", $permalink);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink['label']);
        $this->assertArrayHasKey("updated_on", $permalink);

        $expected = $subdef->get_permalink()->get_last_modified();
        $found = \DateTime::createFromFormat(DATE_ATOM, $permalink['updated_on']);

        $this->assertLessThanOrEqual(1, $expected->diff($found)->format('U'));
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink['updated_on']);
        $this->assertDateAtom($permalink['updated_on']);
        $this->assertArrayHasKey("page_url", $permalink);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink['page_url']);
        $this->assertEquals($subdef->get_permalink()->get_page(), $permalink['page_url']);
        $this->checkUrlCode200($permalink['page_url']);
        $this->assertPermalinkHeaders($permalink['page_url'], $subdef);

        $this->assertArrayHasKey("url", $permalink);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink['url']);
        $this->assertEquals($subdef->get_permalink()->get_url(), $permalink['url']);
        $this->checkUrlCode200($permalink['url']);
        $this->assertPermalinkHeaders($permalink['url'], $subdef, "url");

        $this->assertArrayHasKey("download_url", $permalink);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink['download_url']);
        $this->assertEquals($subdef->get_permalink()->get_url() . '&download=1', $permalink['download_url']);
        $this->checkUrlCode200($permalink['download_url']);
        $this->assertPermalinkHeaders($permalink['download_url'], $subdef, "download_url");
    }

    private function executeRequest($url)
    {
        static $request = [];

        if (isset($request[$url])) {
            return $request[$url];
        }

        static $webserver;

        if (null === $webserver) {
            try {
                $code = self::$DI['local-guzzle']->head('/api/')->send()->getStatusCode();
            } catch (GuzzleException $e) {
                $code = null;
            }
            $webserver = ($code < 200 || $code >= 400) ? false : rtrim(self::$DI['app']['conf']->get('servername'), '/');
        }
        if (false === $webserver) {
            $this->markTestSkipped('Install does not seem to rely on a webserver');
        }
        if (0 === strpos($url, $webserver)) {
            $url = substr($url, strlen($webserver));
        }

        return $request[$url] = self::$DI['local-guzzle']->head($url)->send();
    }

    protected function assertPermalinkHeaders($url, \media_subdef $subdef, $type_url = "page_url")
    {
        $response = $this->executeRequest($url);

        $this->assertEquals(200, $response->getStatusCode());

        switch ($type_url) {
            case "page_url" :
                $this->assertTrue(strpos((string) $response->getHeader('content-type'), "text/html") === 0);
                if ($response->hasHeader('content-length')) {
                    $this->assertNotEquals($subdef->get_size(), (string) $response->getHeader('content-length'));
                }
                break;
            case "url" :
                $this->assertTrue(strpos((string) $response->getHeader('content-type'), $subdef->get_mime()) === 0, 'Verify that header ' . (string) $response->getHeader('content-type') . ' contains subdef mime type ' . $subdef->get_mime());
                if ($response->hasHeader('content-length')) {
                    $this->assertEquals($subdef->get_size(), (string) $response->getHeader('content-length'));
                }
                break;
            case "download_url" :
                $this->assertTrue(strpos((string) $response->getHeader('content-type'), $subdef->get_mime()) === 0, 'Verify that header ' . (string) $response->getHeader('content-type') . ' contains subdef mime type ' . $subdef->get_mime());
                if ($response->hasHeader('content-length')) {
                    $this->assertEquals($subdef->get_size(), (string) $response->getHeader('content-length'));
                }
                break;
        }
    }

    protected function checkUrlCode200($url)
    {
        $response = $this->executeRequest($url);
        $code = $response->getStatusCode();
        $this->assertEquals(200, $code, sprintf('verification de url %s', $url));
    }

    protected function evaluateMethodNotAllowedRoute($route, $methods)
    {
        foreach ($methods as $method) {
            $client = $this->getClient();
            $client->request($method, $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
            $content = $this->unserialize($client->getResponse()->getContent());
            $this->assertTrue($client->getResponse()->headers->has('Allow'));
            $this->evaluateResponseMethodNotAllowed($client->getResponse());
            $this->evaluateMetaMethodNotAllowed($content);
        }
    }

    protected function evaluateBadRequestRoute($route, $methods)
    {
        foreach ($methods as $method) {
            $client = $this->getClient();
            $client->request($method, $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
            $content = $this->unserialize($client->getResponse()->getContent());
            $this->evaluateResponseBadRequest($client->getResponse());
            $this->evaluateMetaBadRequest($content);
        }
    }

    protected function evaluateMeta($content, $version = null)
    {
        if(mb_strpos($content['meta']['request'], '/api/v1') !== FALSE){
            $version = V1::VERSION;
        }

        $this->assertTrue(is_array($content), 'La reponse est un objet');
        $this->assertArrayHasKey('meta', $content);
        $this->assertArrayHasKey('response', $content);
        $this->assertTrue(is_array($content['meta']), 'Le bloc meta est un array');
        $this->assertTrue(is_array($content['response']), 'Le bloc reponse est un array');
        $this->assertEquals($version ?: V2::VERSION, $content['meta']['api_version']);
        $this->assertNotNull($content['meta']['response_time']);
        $this->assertEquals('UTF-8', $content['meta']['charset']);
    }

    protected function evaluateMeta200($content)
    {
        $this->evaluateMeta($content);
        $this->assertEquals(200, $content['meta']['http_code']);
        $this->assertNull($content['meta']['error_type']);
        $this->assertNull($content['meta']['error_message']);
        $this->assertNull($content['meta']['error_details']);
    }

    protected function evaluateMetaBadRequest($content)
    {
        $this->evaluateMeta($content);
        $this->assertNotNull($content['meta']['error_type']);
        $this->assertNotNull($content['meta']['error_message']);
        $this->assertEquals(400, $content['meta']['http_code']);
    }

    protected function evaluateMetaForbidden($content)
    {
        $this->evaluateMeta($content);
        $this->assertNotNull($content['meta']['error_type']);
        $this->assertNotNull($content['meta']['error_message']);
        $this->assertEquals(403, $content['meta']['http_code']);
    }

    protected function evaluateMetaNotFound($content)
    {
        $this->evaluateMeta($content);
        $this->assertNotNull($content['meta']['error_type']);
        $this->assertNotNull($content['meta']['error_message']);
        $this->assertEquals(404, $content['meta']['http_code']);
    }

    protected function evaluateMetaMethodNotAllowed($content)
    {
        $this->evaluateMeta($content);
        $this->assertNotNull($content['meta']['error_type']);
        $this->assertNotNull($content['meta']['error_message']);
        $this->assertEquals(405, $content['meta']['http_code']);
    }

    protected function evaluateResponse200(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code 200 ' . $response->getContent());
    }

    protected function evaluateResponseBadRequest(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(400, $response->getStatusCode(), 'Test status code 400 ' . $response->getContent());
    }

    protected function evaluateResponseForbidden(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(403, $response->getStatusCode(), 'Test status code 403 ' . $response->getContent());
    }

    protected function evaluateResponseNotFound(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(404, $response->getStatusCode(), 'Test status code 404 ' . $response->getContent());
    }

    protected function evaluateResponseMethodNotAllowed(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(405, $response->getStatusCode(), 'Test status code 405 ' . $response->getContent());
    }

    protected function evaluateGoodBasket($basket, User $user)
    {
        $this->assertTrue(is_array($basket));
        $this->assertArrayHasKey('basket_id', $basket);
        $this->assertArrayHasKey('owner', $basket);
        $this->evaluateGoodUserItem($basket['owner'], $user);
        $this->assertArrayHasKey('pusher', $basket);
        $this->assertArrayHasKey('created_on', $basket);
        $this->assertArrayHasKey('description', $basket);
        $this->assertArrayHasKey('name', $basket);
        $this->assertArrayHasKey('pusher_usr_id', $basket);
        $this->assertArrayHasKey('updated_on', $basket);
        $this->assertArrayHasKey('unread', $basket);

        if (!is_null($basket['pusher_usr_id'])) {
            $this->assertTrue(is_int($basket['pusher_usr_id']));
            $this->evaluateGoodUserItem($basket['pusher'], self::$DI['user_notAdmin']);
        }

        $this->assertTrue(is_string($basket['name']));
        $this->assertTrue(is_string($basket['description']));
        $this->assertTrue(is_bool($basket['unread']));
        $this->assertDateAtom($basket['created_on']);
        $this->assertDateAtom($basket['updated_on']);
    }

    protected function evaluateGoodRecord($record)
    {
        $this->assertArrayHasKey('databox_id', $record);
        $this->assertTrue(is_int($record['databox_id']));
        $this->assertArrayHasKey('record_id', $record);
        $this->assertTrue(is_int($record['record_id']));
        $this->assertArrayHasKey('mime_type', $record);
        $this->assertTrue(is_string($record['mime_type']));
        $this->assertArrayHasKey('title', $record);
        $this->assertTrue(is_string($record['title']));
        $this->assertArrayHasKey('original_name', $record);
        $this->assertTrue(is_string($record['original_name']));
        $this->assertArrayHasKey('updated_on', $record);
        $this->assertDateAtom($record['updated_on']);
        $this->assertArrayHasKey('created_on', $record);
        $this->assertDateAtom($record['created_on']);
        $this->assertArrayHasKey('collection_id', $record);
        $this->assertTrue(is_int($record['collection_id']));
        $this->assertArrayHasKey('thumbnail', $record);
        $this->assertArrayHasKey('sha256', $record);
        $this->assertTrue(is_string($record['sha256']));
        $this->assertArrayHasKey('technical_informations', $record);
        $this->assertArrayHasKey('phrasea_type', $record);
        $this->assertTrue(is_string($record['phrasea_type']));
        $this->assertTrue(in_array($record['phrasea_type'], ['audio', 'document', 'image', 'video', 'flash', 'unknown']));
        $this->assertArrayHasKey('uuid', $record);
        $this->assertTrue(Uuid::isValid($record['uuid']));

        if (!is_null($record['thumbnail'])) {
            $this->assertTrue(is_array($record['thumbnail']));
            $this->assertArrayHasKey('player_type', $record['thumbnail']);
            $this->assertTrue(is_string($record['thumbnail']['player_type']));
            $this->assertArrayHasKey('permalink', $record['thumbnail']);
            $this->assertArrayHasKey('mime_type', $record['thumbnail']);
            $this->assertTrue(is_string($record['thumbnail']['mime_type']));
            $this->assertArrayHasKey('height', $record['thumbnail']);
            $this->assertTrue(is_int($record['thumbnail']['height']));
            $this->assertArrayHasKey('width', $record['thumbnail']);
            $this->assertTrue(is_int($record['thumbnail']['width']));
            $this->assertArrayHasKey('filesize', $record['thumbnail']);
            $this->assertTrue(is_int($record['thumbnail']['filesize']));
        }

        $this->assertTrue(is_array($record['technical_informations']));

        foreach ($record['technical_informations'] as $technical) {
            $this->assertArrayHasKey('value', $technical);
            $this->assertArrayHasKey('name', $technical);

            $value = $technical['value'];
            if (is_string($value)) {
                $this->assertFalse(ctype_digit($value));
                $this->assertEquals(0, preg_match('/[0-9]?\.[0-9]+/', $value));
            } elseif (is_float($value)) {
                $this->assertTrue(is_float($value));
            } elseif (is_int($value)) {
                $this->assertTrue(is_int($value));
            } else {
                $this->fail('unrecognized technical information');
            }
        }
    }

    protected function evaluateGoodStory($story)
    {
        $this->assertArrayHasKey('databox_id', $story);
        $this->assertTrue(is_int($story['databox_id']));
        $this->assertArrayHasKey('story_id', $story);
        $this->assertTrue(is_int($story['story_id']));
        $this->assertArrayHasKey('updated_on', $story);
        $this->assertDateAtom($story['updated_on']);
        $this->assertArrayHasKey('created_on', $story);
        $this->assertDateAtom($story['created_on']);
        $this->assertArrayHasKey('collection_id', $story);
        $this->assertTrue(is_int($story['collection_id']));
        $this->assertArrayHasKey('thumbnail', $story);
        $this->assertArrayHasKey('uuid', $story);
        $this->assertArrayHasKey('@entity@', $story);
        $this->assertEquals(V1Controller::OBJECT_TYPE_STORY, $story['@entity@']);
        $this->assertTrue(Uuid::isValid($story['uuid']));

        if ( ! is_null($story['thumbnail'])) {
            $this->assertTrue(is_array($story['thumbnail']));
            $this->assertArrayHasKey('player_type', $story['thumbnail']);
            $this->assertTrue(is_string($story['thumbnail']['player_type']));
            $this->assertArrayHasKey('permalink', $story['thumbnail']);
            $this->assertArrayHasKey('mime_type', $story['thumbnail']);
            $this->assertTrue(is_string($story['thumbnail']['mime_type']));
            $this->assertArrayHasKey('height', $story['thumbnail']);
            $this->assertTrue(is_int($story['thumbnail']['height']));
            $this->assertArrayHasKey('width', $story['thumbnail']);
            $this->assertTrue(is_int($story['thumbnail']['width']));
            $this->assertArrayHasKey('filesize', $story['thumbnail']);
            $this->assertTrue(is_int($story['thumbnail']['filesize']));
        }

        $this->assertArrayHasKey('records', $story);
        $this->assertInternalType('array', $story['records']);

        foreach ($story['metadatas'] as $key => $metadata) {
            if (null !== $metadata) {
                $this->assertInternalType('string', $metadata);
            }
            if ($key === '@entity@') {
                continue;
            }

            $this->assertEquals(0, strpos($key, 'dc:'));
        }

        $this->assertArrayHasKey('@entity@', $story['metadatas']);
        $this->assertEquals(V1Controller::OBJECT_TYPE_STORY_METADATA_BAG, $story['metadatas']['@entity@']);

        foreach ($story['records'] as $record) {
            $this->evaluateGoodRecord($record);
        }
    }

    protected function evaluateRecordsCaptionResponse($content)
    {
        $this->assertArrayHasKey('caption_metadatas', $content['response']);

        $this->assertGreaterThan(0, count($content['response']['caption_metadatas']));

        foreach ($content['response']['caption_metadatas'] as $field) {
            $this->assertTrue(is_array($field), 'Un bloc field est un objet');
            $this->assertArrayHasKey('meta_structure_id', $field);
            $this->assertTrue(is_int($field['meta_structure_id']));
            $this->assertArrayHasKey('name', $field);
            $this->assertTrue(is_string($field['name']));
            $this->assertArrayHasKey('value', $field);
            $this->assertTrue(is_string($field['value']));
        }
    }

    protected function evaluateRecordsMetadataResponse($content)
    {
        if (!array_key_exists("record_metadatas", $content['response'])) {
            var_dump($content['response']);
        }

        $this->assertArrayHasKey("record_metadatas", $content['response']);
        foreach ($content['response']['record_metadatas'] as $meta) {
            $this->assertTrue(is_array($meta), 'Un bloc meta est un objet');
            $this->assertArrayHasKey('meta_id', $meta);
            $this->assertTrue(is_int($meta['meta_id']));
            $this->assertArrayHasKey('meta_structure_id', $meta);
            $this->assertTrue(is_int($meta['meta_structure_id']));
            $this->assertArrayHasKey('name', $meta);
            $this->assertTrue(is_string($meta['name']));
            $this->assertArrayHasKey('value', $meta);
            $this->assertArrayHasKey('labels', $meta);
            $this->assertTrue(is_array($meta['labels']));

            $this->assertEquals(['fr', 'en', 'de', 'nl'], array_keys($meta['labels']));

            if (is_array($meta['value'])) {
                foreach ($meta['value'] as $val) {
                    $this->assertTrue(is_string($val));
                }
            } else {
                $this->assertTrue(is_string($meta['value']));
            }
        }
    }

    protected function evaluateRecordsStatusResponse(\record_adapter $record, $content)
    {
        $statusStructure = $record->getDatabox()->getStatusStructure();

        $r_status = strrev($record->getStatus());
        $this->assertArrayHasKey('status', $content['response']);
        $this->assertEquals(count((array) $content['response']['status']), count($statusStructure->toArray()));
        foreach ($content['response']['status'] as $status) {
            $this->assertTrue(is_array($status));
            $this->assertArrayHasKey('bit', $status);
            $this->assertArrayHasKey('state', $status);
            $this->assertTrue(is_int($status['bit']));
            $this->assertTrue(is_bool($status['state']));

            $retrieved = !!substr($r_status, ($status['bit']), 1);

            $this->assertEquals($retrieved, $status['state']);
        }
    }

    protected function injectMetadatas(\record_adapter $record)
    {
        foreach ($record->getDatabox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = [
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'podom pom pom ' . $field->get_id()
            ];
        }

        $record->set_metadatas($toupdate);
    }

    protected function setToken(ApiOauthToken $token)
    {
        self::resetUsersRights(self::$DI['app'], $token->getAccount()->getUser());
        $_GET['oauth_token'] = $token->getOauthToken();
    }

    protected function unsetToken()
    {
        unset($_GET['oauth_token']);
    }
}
