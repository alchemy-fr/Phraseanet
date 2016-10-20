<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Controller\Api\V1Controller;
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

/**
 * @group functional
 * @group legacy
 * @group web
 */
class ApiJsonTest extends ApiTestCase
{
    protected function getParameters(array $parameters = [])
    {
        return $parameters;
    }

    protected function unserialize($data)
    {
        return json_decode($data, true);
    }

    protected function getAcceptMimeType()
    {
        return 'application/json';
    }

    public function testAddStory()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/stories';

        $collection = $this->getCollection();
        $story['base_id'] = $collection->get_base_id();
        $story['title'] = uniqid('story');

        $app = $this->getApplication();
        $file = new File(
            $app,
            $app['mediavorus']->guess(__DIR__ . '/../../../../../files/p4logo.jpg'),
            $collection
        );
        $record = \record_adapter::createFromFile($file, $app);

        $story['story_records'] = array(array(
                                            'databox_id' => $record->getDataboxId(),
                                            'record_id' => $record->getRecordId()
                                        ));

        $client = $this->getClient();
        $client->request(
            'POST',
            $route,
            $this->getParameters(),
            $this->getAddRecordFile(),
            [
                'HTTP_ACCEPT' => $this->getAcceptMimeType(),
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(array('stories' => array($story)))
        );
        $content = $this->unserialize($client->getResponse()->getContent());

        $this->evaluateResponse200($client->getResponse());
        $this->evaluateMeta200($content);
        $data = $content['response'];

        $this->assertArrayHasKey('stories', $data);
        $this->assertCount(1, $data['stories']);
        list($empty, $path, $databox_id, $story_id) = explode('/', current($data['stories']));
        $databox = $app->findDataboxById($databox_id);
        $story = $databox->get_record($story_id);
        $story->delete();
        $record->delete();
    }

    public function testAddRecordToStory()
    {
        $this->setToken($this->userAccessToken);
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        $route = sprintf('/api/v1/stories/%s/%s/addrecords', $story->getDataboxId(), $story->getRecordId());

        $file = new File(
            self::$DI['app'],
            self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/extractfile.jpg'),
            self::$DI['collection']
        );
        $record = \record_adapter::createFromFile($file, self::$DI['app']);

        $records = array(
            'databox_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId()
        );

        self::$DI['client']->request(
            'POST',
            $route,
            $this->getParameters(),
            $this->getAddRecordFile(),
            [
                'HTTP_ACCEPT' => $this->getAcceptMimeType(),
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(array('story_records' => array($records)))
        );
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $data = $content['response'];

        $this->assertArrayHasKey('records', $data);
        $this->assertCount(1, $data['records']);
        $story->delete();
        $record->delete();
    }

    public function testDelRecordFromStory()
    {
        $this->setToken($this->userAccessToken);
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        $file = new File(
            self::$DI['app'],
            self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/extractfile.jpg'),
            self::$DI['collection']
        );
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $story->appendChild($record);

        $route = sprintf('/api/v1/stories/%s/%s/delrecords', $story->getDataboxId(), $story->getRecordId());
        $records = array(
            'databox_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId()
        );

        self::$DI['client']->request(
            'DELETE',
            $route,
            $this->getParameters(),
            $this->getAddRecordFile(),
            [
                'HTTP_ACCEPT' => $this->getAcceptMimeType(),
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(array('story_records' => array($records)))
        );
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $data = $content['response'];

        $this->assertArrayHasKey('records', $data);
        $this->assertCount(1, $data['records']);
        $story->delete();
        $record->delete();
    }

    /**
     * @dataProvider provideEventNames
     */
    public function testThatEventsAreDispatched($eventName, $className, $route, $context)
    {
        $preEvent = 0;
        self::$DI['app']['dispatcher']->addListener($eventName, function ($event) use (&$preEvent, $className, $context) {
            $preEvent++;
            $this->assertInstanceOf($className, $event);
            if (null !== $context) {
                $this->assertEquals($context, $event->getContext()->getContext());
            }
        });

        $this->setToken($this->userAccessToken);
        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);

        $this->assertEquals(1, $preEvent);
    }

    public function testThatSessionIsClosedAfterRequest()
    {
        $this->assertCount(0, self::$DI['app']['orm.em']->getRepository('Phraseanet:Session')->findAll());
        $this->setToken($this->userAccessToken);
        self::$DI['client']->request('GET', '/api/v1/databoxes/list/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $this->assertCount(0, self::$DI['app']['orm.em']->getRepository('Phraseanet:Session')->findAll());
    }

    public function provideEventNames()
    {
        return [
            [PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate', '/api/v1/databoxes/list/', Context::CONTEXT_OAUTH2_TOKEN],
            [PhraseaEvents::API_OAUTH2_START, 'Alchemy\Phrasea\Core\Event\ApiOAuth2StartEvent', '/api/v1/databoxes/list/', null],
            [PhraseaEvents::API_OAUTH2_END, 'Alchemy\Phrasea\Core\Event\ApiOAuth2EndEvent', '/api/v1/databoxes/list/', null],
            [PhraseaEvents::API_RESULT, 'Alchemy\Phrasea\Core\Event\ApiResultEvent', '/api/v1/databoxes/list/', null],
            [PhraseaEvents::API_RESULT, 'Alchemy\Phrasea\Core\Event\ApiResultEvent', '/api/v1/no-route', null],
        ];
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

    public function testDataboxListRoute()
    {
        $this->setToken($this->userAccessToken);
        self::$DI['client']->request('GET', '/api/v1/databoxes/list/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('databoxes', $content['response']);
        foreach ($content['response']['databoxes'] as $databox) {
            $this->assertTrue(is_array($databox), 'Une databox est un objet');
            $this->assertArrayHasKey('databox_id', $databox);
            $this->assertArrayHasKey('name', $databox);
            $this->assertArrayHasKey('viewname', $databox);
            $this->assertArrayHasKey('labels', $databox);
            $this->assertArrayHasKey('fr', $databox['labels']);
            $this->assertArrayHasKey('en', $databox['labels']);
            $this->assertArrayHasKey('de', $databox['labels']);
            $this->assertArrayHasKey('nl', $databox['labels']);
            $this->assertArrayHasKey('version', $databox);
            break;
        }
    }

    public function testCheckNativeApp()
    {
        $app = $this->getApplication();
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];
        $value = $conf->get(['registry', 'api-clients', 'navigator-enabled']);
        $conf->set(['registry', 'api-clients', 'navigator-enabled'], false);

        $fail = null;

        try {
            $nativeApp = $app['repo.api-applications']->findByClientId(\API_OAuth2_Application_Navigator::CLIENT_ID);
            if (null === $nativeApp) {
                throw new  \Exception(sprintf('%s not found', \API_OAuth2_Application_Navigator::CLIENT_ID));
            }
            $account = $app['manipulator.api-account']->create($nativeApp, self::$DI['user'], V2::VERSION);
            $token = $app['manipulator.api-oauth-token']->create($account);

            $this->setToken($token);
            $client = $this->getClient();
            $client->request('GET', '/api/v1/databoxes/list/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
            $content = $this->unserialize(
                $client->getResponse()->getContent());

            if (403 != $content['meta']['http_code']) {
                $fail = new \Exception('Result does not match expected 403, returns ' . $content['meta']['http_code']);
            }
        } catch (\Exception $e) {
            $fail = $e;
        }

        $conf->set(['registry', 'api-clients', 'navigator-enabled'], $value);

        if ($fail) {
            throw $fail;
        }
    }

    /**
     * Covers mustBeAdmin route middleware
     */
    public function testAdminOnlyShedulerState()
    {
        $this->setToken($this->userAccessToken);

        $client = $this->getClient();

        $client->request('GET', '/api/v1/monitor/tasks/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);

        $client->request('GET', '/api/v1/monitor/scheduler/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);

        $client->request('GET', '/api/v1/monitor/task/1/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);

        $client->request('POST', '/api/v1/monitor/task/1/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);

        $client->request('POST', '/api/v1/monitor/task/1/start/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);

        $client->request('POST', '/api/v1/monitor/task/1/stop/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);

        $client->request('GET', '/api/v1/monitor/phraseanet/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());
        $this->assertEquals(401, $content['meta']['http_code']);
    }

    /**
     * Route GET /API/V1/monitor/task
     */
    public function testGetMonitorTasks()
    {
        $this->setToken($this->adminAccessToken);

        $route = '/api/v1/monitor/tasks/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $response = $content['response'];

        $tasks = self::$DI['app']['repo.tasks']->findAll();
        $this->assertEquals(count($tasks), count($response['tasks']));

        foreach ($response['tasks'] as $task) {
            $this->evaluateGoodTask($task);
        }
    }

    /**
     * Route GET /API/V1/monitor/scheduler
     */
    public function testGetScheduler()
    {
        $this->setToken($this->adminAccessToken);

        $route = '/api/v1/monitor/scheduler/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $response = $content['response'];

        $this->assertInternalType('array', $response['scheduler']);

        $this->assertArrayHasKey('state', $response['scheduler']);
        $this->assertArrayHasKey('pid', $response['scheduler']);
        $this->assertArrayHasKey('updated_on', $response['scheduler']);
        $this->assertArrayHasKey('status', $response['scheduler']);
        $this->assertArrayHasKey('configuration', $response['scheduler']);
        $this->assertArrayHasKey('process-id', $response['scheduler']);

        $this->assertEquals(6, count($response['scheduler']));

        if (null !== $response['scheduler']['updated_on']) {
            $this->assertDateAtom($response['scheduler']['updated_on']);
        }
        if (null !== $response['scheduler']['pid']) {
            $this->assertTrue(is_int($response['scheduler']['pid']));
        }

        $this->assertTrue('' !== $response['scheduler']['state']);
    }

    protected function evaluateGoodTask($task)
    {
        $this->assertArrayHasKey('id', $task);
        $this->assertArrayHasKey('name', $task);
        $this->assertArrayHasKey('state', $task);
        $this->assertArrayHasKey('status', $task);
        $this->assertArrayHasKey('actual-status', $task);
        $this->assertArrayHasKey('pid', $task);
        $this->assertArrayHasKey('process-id', $task);
        $this->assertArrayHasKey('title', $task);
        $this->assertArrayHasKey('crashed', $task);
        $this->assertArrayHasKey('auto_start', $task);
        $this->assertArrayHasKey('last_exec_time', $task);
        $this->assertArrayHasKey('last_execution', $task);
        $this->assertArrayHasKey('updated', $task);
        $this->assertArrayHasKey('created', $task);
        $this->assertArrayHasKey('period', $task);
        $this->assertArrayHasKey('jobId', $task);

        $this->assertInternalType('integer', $task['id']);

        if (!is_null($task['pid'])) {
            $this->assertInternalType('integer', $task['pid']);
        }

        $av_states = [
            Task::STATUS_STARTED,
            Task::STATUS_STOPPED,
        ];

        $this->assertContains($task['state'], $av_states);
        $this->assertInternalType('string', $task['name']);
        $this->assertInternalType('string', $task['title']);

        if (!is_null($task['last_exec_time'])) {
            $this->assertDateAtom($task['last_exec_time']);
        }
    }

    public function testGetMonitorTaskById()
    {
        $idTask = $this->getTestTaskId();

        $this->setToken($this->adminAccessToken);

        $route = '/api/v1/monitor/task/' . $idTask . '/';
        $this->evaluateMethodNotAllowedRoute($route, ['PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('task', $content['response']);
        $this->evaluateGoodTask($content['response']['task']);
    }

    public function testPostMonitorTaskById()
    {
        $idTask = $this->getTestTaskId();

        $this->setToken($this->adminAccessToken);

        $route = '/api/v1/monitor/task/' . $idTask . '/';
        $this->evaluateMethodNotAllowedRoute($route, ['PUT', 'DELETE']);

        $title = 'newTitle' . mt_rand();

        self::$DI['client']->request('POST', $route, $this->getParameters(['title' => $title]), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('task', $content['response']);
        $this->evaluateGoodTask($content['response']['task']);
        $this->assertEquals($title, $content['response']['task']['title']);
    }

    public function testUnknowGetMonitorTaskById()
    {
        if (null === $this->adminAccessToken) {
            $this->markTestSkipped('no tasks created for the current instance');
        }
        $this->setToken($this->adminAccessToken);
        self::$DI['client']->followRedirects();
        self::$DI['client']->request('GET', '/api/v1/monitor/task/0/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateMetaNotFound($content);
    }

    public function testPostMonitorStartTask()
    {
        $idTask = $this->getTestTaskId();

        $this->setToken($this->adminAccessToken);

        $route = '/api/v1/monitor/task/' . $idTask . '/start/';
        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('task', $content['response']);
        $this->evaluateGoodTask($content['response']['task']);

        $task = self::$DI['app']['repo.tasks']->find($idTask);
        $this->assertEquals(Task::STATUS_STARTED, $task->getStatus());
    }

    public function testPostMonitorStopTask()
    {
        $idTask = $this->getTestTaskId();

        $this->setToken($this->adminAccessToken);

        $route = '/api/v1/monitor/task/' . $idTask . '/stop/';
        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('task', $content['response']);
        $this->evaluateGoodTask($content['response']['task']);

        $task = self::$DI['app']['repo.tasks']->find($idTask);
        $this->assertEquals(Task::STATUS_STOPPED, $task->getStatus());
    }

    public function testgetMonitorPhraseanet()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();

        $this->setToken($this->adminAccessToken);

        self::$DI['client']->request('GET', '/api/v1/monitor/phraseanet/', $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $this->assertArrayHasKey('global_values', $content['response']);
        $this->assertArrayHasKey('cache', $content['response']);
        $this->assertArrayHasKey('phraseanet', $content['response']);

        $this->assertInternalType('array', $content['response']['global_values']);
        $this->assertInternalType('array', $content['response']['cache']);
        $this->assertInternalType('array', $content['response']['phraseanet']);
    }

    public function testRecordRoute()
    {
        $this->setToken($this->userAccessToken);

        $record_1 = $this->getRecord1();
        $client = $this->getClient();

        $route = '/api/v1/records/' . $record_1->getDataboxId() . '/' . $record_1->getRecordId() . '/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT']);
        $client->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());

        $this->evaluateResponse200($client->getResponse());
        $this->evaluateMeta200($content);

        $this->evaluateGoodRecord($content['response']['record']);

        $route = '/api/v1/records/1234567890/1/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT']);
        $route = '/api/v1/records/kjslkz84spm/sfsd5qfsd5/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testStoryRoute()
    {
        $this->setToken($this->userAccessToken);
        self::$DI['app']['session']->set('usr_id', self::$DI['user']->getId());
        if (false ===  self::$DI['record_story_1']->hasChild(self::$DI['record_1'])) {
            self::$DI['record_story_1']->appendChild(self::$DI['record_1']);
        }

        self::$DI['app']['session']->remove('usr_id');

        $route = '/api/v1/stories/' . self::$DI['record_story_1']->get_sbas_id() . '/' . self::$DI['record_story_1']->get_record_id() . '/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->evaluateGoodStory($content['response']['story']);
        $this->assertGreaterThan(0, $content['response']['story']['records']);

        $route = '/api/v1/stories/1234567890/1/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/stories/kjslkz84spm/sfsd5qfsd5/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['record_story_1']->removeChild(self::$DI['record_1']);
    }

    public function testDataboxCollectionRoute()
    {
        $this->setToken($this->userAccessToken);
        $databox_id = self::$DI['record_1']->get_sbas_id();
        $route = '/api/v1/databoxes/' . $databox_id . '/collections/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('collections', $content['response']);
        foreach ($content['response']['collections'] as $collection) {
            $this->assertTrue(is_array($collection), 'Une collection est un objet');
            $this->assertArrayHasKey('base_id', $collection);
            $this->assertArrayHasKey('collection_id', $collection);
            $this->assertArrayHasKey('name', $collection);
            $this->assertArrayHasKey('labels', $collection);
            $this->assertArrayHasKey('fr', $collection['labels']);
            $this->assertArrayHasKey('en', $collection['labels']);
            $this->assertArrayHasKey('de', $collection['labels']);
            $this->assertArrayHasKey('nl', $collection['labels']);
            $this->assertArrayHasKey('record_amount', $collection);
            $this->assertTrue(is_int($collection['base_id']));
            $this->assertGreaterThan(0, $collection['base_id']);
            $this->assertTrue(is_int($collection['collection_id']));
            $this->assertGreaterThan(0, $collection['collection_id']);
            $this->assertTrue(is_string($collection['name']));
            $this->assertTrue(is_int($collection['record_amount']));
            break;
        }
        $route = '/api/v1/databoxes/24892534/collections/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/databoxes/any_bad_id/collections/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testDataboxStatusRoute()
    {
        $this->setToken($this->userAccessToken);
        $databox_id = self::$DI['record_1']->get_sbas_id();
        $databox = self::$DI['app']->findDataboxById($databox_id);
        $statusStructure = $databox->getStatusStructure();
        $route = '/api/v1/databoxes/' . $databox_id . '/status/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('status', $content['response']);
        foreach ($content['response']['status'] as $status) {
            $this->assertTrue(is_array($status), 'Un bloc status est un objet');
            $this->assertArrayHasKey('bit', $status);
            $this->assertTrue(is_int($status['bit']));
            $this->assertGreaterThan(3, $status['bit']);
            $this->assertLessThan(65, $status['bit']);
            $this->assertArrayHasKey('label_on', $status);
            $this->assertArrayHasKey('label_off', $status);
            $this->assertArrayHasKey('labels', $status);
            $this->assertArrayHasKey('fr', $status['labels']);
            $this->assertArrayHasKey('en', $status['labels']);
            $this->assertArrayHasKey('de', $status['labels']);
            $this->assertArrayHasKey('nl', $status['labels']);
            $this->assertArrayHasKey('img_on', $status);
            $this->assertArrayHasKey('img_off', $status);
            $this->assertArrayHasKey('searchable', $status);
            $this->assertArrayHasKey('printable', $status);
            $this->assertTrue(is_bool($status['searchable']));
            $this->assertTrue($status['searchable'] === (bool) $statusStructure->getStatus($status['bit'])['searchable']);
            $this->assertTrue(is_bool($status['printable']));
            $this->assertTrue($status['printable'] === (bool) $statusStructure->getStatus($status['bit'])['printable']);
            $this->assertTrue($status['label_on'] === $statusStructure->getStatus($status['bit'])['labelon']);
            $this->assertTrue($status['img_off'] === $statusStructure->getStatus($status['bit'])['img_off']);
            $this->assertTrue($status['img_on'] === $statusStructure->getStatus($status['bit'])['img_on']);
            break;
        }
        $route = '/api/v1/databoxes/24892534/status/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/databoxes/any_bad_id/status/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testDataboxMetadatasRoute()
    {
        $this->setToken($this->userAccessToken);
        $databox_id = self::$DI['record_1']->get_sbas_id();
        $databox = self::$DI['app']->findDataboxById($databox_id);
        $ref_structure = $databox->get_meta_structure();

        try {
            $ref_structure->get_element('idbarbouze');
            $this->fail('An expected exception has not been raised.');
        } catch (\Exception_Databox_FieldNotFound $e) {

        }

        $route = '/api/v1/databoxes/' . $databox_id . '/metadatas/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('document_metadatas', $content['response']);
        foreach ($content['response']['document_metadatas'] as $metadatas) {
            $this->assertTrue(is_array($metadatas), 'Un bloc metadata est un objet');
            $this->assertArrayHasKey('id', $metadatas);
            $this->assertArrayHasKey('namespace', $metadatas);
            $this->assertArrayHasKey('source', $metadatas);
            $this->assertArrayHasKey('tagname', $metadatas);
            $this->assertArrayHasKey('name', $metadatas);
            $this->assertArrayHasKey('separator', $metadatas);
            $this->assertArrayHasKey('thesaurus_branch', $metadatas);
            $this->assertArrayHasKey('type', $metadatas);
            $this->assertArrayHasKey('labels', $metadatas);
            $this->assertArrayHasKey('indexable', $metadatas);
            $this->assertArrayHasKey('multivalue', $metadatas);
            $this->assertArrayHasKey('readonly', $metadatas);
            $this->assertArrayHasKey('required', $metadatas);

            $this->assertTrue(is_int($metadatas['id']));
            $this->assertTrue(is_string($metadatas['namespace']));
            $this->assertTrue(is_string($metadatas['name']));
            $this->assertTrue(is_array($metadatas['labels']));
            $this->assertTrue(is_null($metadatas['source']) || is_string($metadatas['source']));
            $this->assertTrue(is_string($metadatas['tagname']));
            $this->assertTrue((strlen($metadatas['name']) > 0));
            $this->assertTrue(is_string($metadatas['separator']));

            $this->assertEquals(['fr', 'en', 'de', 'nl'], array_keys($metadatas['labels']));

            if ($metadatas['multivalue']) {
                $this->assertTrue((strlen($metadatas['separator']) > 0));
            }

            $this->assertTrue(is_string($metadatas['thesaurus_branch']));
            $this->assertTrue(in_array($metadatas['type'], [\databox_field::TYPE_DATE, \databox_field::TYPE_STRING, \databox_field::TYPE_NUMBER, \databox_field::TYPE_TEXT]));
            $this->assertTrue(is_bool($metadatas['indexable']));
            $this->assertTrue(is_bool($metadatas['multivalue']));
            $this->assertTrue(is_bool($metadatas['readonly']));
            $this->assertTrue(is_bool($metadatas['required']));

            $element = $ref_structure->get_element($metadatas['id']);
            $this->assertTrue($element->is_indexable() === $metadatas['indexable']);
            $this->assertTrue($element->is_required() === $metadatas['required']);
            $this->assertTrue($element->is_readonly() === $metadatas['readonly']);
            $this->assertTrue($element->is_multi() === $metadatas['multivalue']);
            $this->assertTrue($element->get_type() === $metadatas['type']);
            $this->assertTrue($element->get_tbranch() === $metadatas['thesaurus_branch']);
            $this->assertTrue($element->get_separator() === $metadatas['separator']);
            $this->assertTrue($element->get_name() === $metadatas['name']);
            $this->assertTrue($element->get_tag()->getName() === $metadatas['tagname']);
            $this->assertTrue($element->get_tag()->getTagname() === $metadatas['source']);
            $this->assertTrue($element->get_tag()->getGroupName() === $metadatas['namespace']);
            break;
        }
        $route = '/api/v1/databoxes/24892534/metadatas/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/databoxes/any_bad_id/metadatas/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testDataboxTermsOfUseRoute()
    {
        $this->setToken($this->userAccessToken);
        $databox_id = self::$DI['record_1']->get_sbas_id();
        $route = '/api/v1/databoxes/' . $databox_id . '/termsOfUse/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('termsOfUse', $content['response']);
        foreach ($content['response']['termsOfUse'] as $terms) {
            $this->assertTrue(is_array($terms), 'Une bloc cgu est un objet');
            $this->assertArrayHasKey('locale', $terms);
            $this->assertTrue(in_array($terms['locale'], array_keys(Application::getAvailableLanguages())));
            $this->assertArrayHasKey('terms', $terms);
            break;
        }
        $route = '/api/v1/databoxes/24892534/termsOfUse/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/databoxes/any_bad_id/termsOfUse/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testSearchRoute()
    {
        $app = $this->getApplication();

        $app['manipulator.user'] = $this->getMockBuilder('Alchemy\Phrasea\Model\Manipulator\UserManipulator')
            ->setConstructorArgs([
                $app['model.user-manager'],
                $app['auth.password-encoder'],
                $app['geonames.connector'],
                $app['repo.users'],
                $app['random.low'],
                $app['dispatcher'],
            ])
            ->setMethods(['logQuery'])
            ->getMock();

        $app['manipulator.user']->expects($this->once())->method('logQuery');

        $this->setToken($this->userAccessToken);
        $response = $this->request('POST', '/api/v1/search/', $this->getParameters(), [
            'HTTP_Accept' => $this->getAcceptMimeType(),
        ]);
        $content = $this->unserialize($response->getContent());

        $this->evaluateResponse200($response);
        $this->evaluateMeta200($content);

        $response = $content['response'];

        $this->evaluateSearchResponse($response);

        $this->assertArrayHasKey('stories', $response['results']);
        $this->assertArrayHasKey('records', $response['results']);

        $this->assertTrue(count($response['results']['records']) > 0);
    }

    public function testSearchRouteWithStories()
    {
        $this->setToken($this->userAccessToken);

        self::$DI['record_story_1'];

        $response = $this->request(
            'POST',
            '/api/v1/search/',
            $this->getParameters(['search_type' => SearchEngineOptions::RECORD_GROUPING]),
            ['HTTP_Accept' => $this->getAcceptMimeType()]
        );
        $content = $this->unserialize($response->getContent());

        $this->evaluateResponse200($response);
        $this->evaluateMeta200($content);

        $response = $content['response'];

        $this->evaluateSearchResponse($response);

        $this->assertArrayHasKey('stories', $response['results']);
        $this->assertArrayHasKey('records', $response['results']);

        $found = false;

        foreach ($response['results']['stories'] as $story) {
            $this->evaluateGoodStory($story);
            $found = true;
            break;
        }

        if (!$found) {
            $this->fail('Unable to find story back');
        }
    }

    public function testRecordsSearchRoute()
    {
        $this->setToken($this->userAccessToken);
        $response = $this->request('POST', '/api/v1/records/search/', $this->getParameters(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($response->getContent());

        $this->evaluateResponse200($response);
        $this->evaluateMeta200($content);

        $response = $content['response'];

        $this->evaluateSearchResponse($response);

        foreach ($response['results'] as $record) {
            $this->evaluateGoodRecord($record);
            break;
        }
    }

    /**
     * @dataProvider provideAvailableSearchMethods
     */
    public function testRecordsSearchRouteWithQuery($method)
    {
        $this->setToken($this->userAccessToken);
        $searchEngine = $this->getMockBuilder('Alchemy\Phrasea\SearchEngine\SearchEngineResult')
            ->disableOriginalConstructor()
            ->getMock();

        $searchEngine->expects($this->any())
            ->method('getSuggestions')
            ->will($this->returnValue(new ArrayCollection()));

        $app = $this->getApplication();
        $mock = $this->getMock('Alchemy\Phrasea\SearchEngine\SearchEngineInterface');
        $app['phraseanet.SE'] = $mock;

            $mock
            ->expects($this->once())
            ->method('query')
            ->withAnyParameters()
            ->will($this->returnValue(
                $this->getMockBuilder('Alchemy\Phrasea\SearchEngine\SearchEngineResult')
                    ->disableOriginalConstructor()
                    ->getMock()
            ));
        self::$DI['client']->request($method, '/api/v1/records/search/', $this->getParameters(['query' => 'koala']), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
    }

    public function provideAvailableSearchMethods()
    {
        return [['POST'], ['GET']];
    }

    public function testRecordsCaptionRoute()
    {
        $this->setToken($this->userAccessToken);

        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->injectMetadatas(self::$DI['record_1']);

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/caption/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->evaluateRecordsCaptionResponse($content);

        $route = '/api/v1/records/24892534/51654651553/caption/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/records/any_bad_id/sfsd5qfsd5/caption/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testRecordsMetadatasRoute()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/metadatas/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->evaluateRecordsMetadataResponse($content);

        $route = '/api/v1/records/24892534/51654651553/metadatas/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/records/any_bad_id/sfsd5qfsd5/metadatas/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testRecordsStatusRoute()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/status/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->evaluateRecordsStatusResponse(self::$DI['record_1'], $content);

        $route = '/api/v1/records/24892534/51654651553/status/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/records/any_bad_id/sfsd5qfsd5/status/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testRecordsEmbedRoute()
    {
        $this->setToken($this->userAccessToken);

        $app = $this->getApplication();
        /** @var \collection $collection */
        $collection = self::$DI['collection'];
        // Ensure permalinks will return exact subdef permalinks
        if ('none' !== $collection->get_pub_wm()) {
            $collection->set_public_presentation('none');
        }
        $app->getAclForUser(self::$DI['user_notAdmin'])->update_rights_to_base(
            $collection->get_base_id(),
            [
                \ACL::CANDWNLDPREVIEW => 1,
                \ACL::CANDWNLDHD      => 1
            ]
        );

        /** @var \record_adapter $record_1 */
        $record_1 = self::$DI['record_1'];
        $route = '/api/v1/records/' . $record_1->getDataboxId() . '/' . $record_1->getRecordId() . '/embed/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        /** @var Client $client */
        $client = self::$DI['client'];
        $client->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());

        $this->evaluateResponse200($client->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('embed', $content['response']);

        $embedTypes = array_flip(array_map(function($subdef) {return $subdef['name'];}, $content['response']['embed']));

        //access to all subdefs
        $this->assertArrayHasKey('preview', $embedTypes);
        $this->assertArrayHasKey('thumbnail', $embedTypes);

        foreach ($content['response']['embed'] as $embed) {
            $this->checkEmbed($embed, $record_1);
        }
        $route = '/api/v1/records/24892534/51654651553/embed/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/records/any_bad_id/sfsd5qfsd5/embed/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testRecordsEmbedRouteNoHdRights()
    {
        $this->setToken($this->userAccessToken);

        self::$DI['app']->getAclForUser(self::$DI['user_notAdmin'])->update_rights_to_base(self::$DI['collection']->get_base_id(), array(
            \ACL::CANDWNLDPREVIEW => 1,
            \ACL::CANDWNLDHD      => 0
        ));

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/embed/';

        self::$DI['client']->request('GET', $route, $this->getParameters(), array(), array('HTTP_Accept' => $this->getAcceptMimeType()));
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $this->assertArrayHasKey('embed', $content['response']);
        // no hd subdef
        $embedTypes = array_flip(array_map(function($subdef) {return $subdef['name'];},$content['response']['embed']));
        $this->assertArrayHasKey('preview', $embedTypes);
        $this->assertArrayNotHasKey('document', $embedTypes);
    }


    public function testRecordsEmbedRouteNoPreviewAndHdRights()
    {
        $this->setToken($this->userAccessToken);

        self::$DI['app']->getAclForUser(self::$DI['user_notAdmin'])->update_rights_to_base(
            self::$DI['collection']->get_base_id(),
            [
                \ACL::CANDWNLDPREVIEW => 0,
                \ACL::CANDWNLDHD => 0
            ]
        );

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/embed/';

        self::$DI['client']->request('GET', $route, $this->getParameters(), array(), array('HTTP_Accept' => $this->getAcceptMimeType()));
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $this->assertArrayHasKey('embed', $content['response']);
        // no preview
        $this->assertArrayNotHasKey('document', array_flip(array_map(function($subdef) {return $subdef['name'];},$content['response']['embed'])));
        $this->assertArrayNotHasKey('preview', array_flip(array_map(function($subdef) {return $subdef['name'];},$content['response']['embed'])));
    }

    /**
     * @covers \API_V1_adapter::get_record_embed
     * @covers \API_V1_adapter::list_embedable_media
     * @covers \API_V1_adapter::list_permalink
     */
    public function testStoriesEmbedRoute()
    {
        $this->setToken($this->userAccessToken);
        /** @var \record_adapter $story */
        $story = self::$DI['record_story_1'];

        $route = '/api/v1/stories/' . $story->getDataboxId() . '/' . $story->getRecordId() . '/embed/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        /** @var Client $client */
        $client = self::$DI['client'];
        $client->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());

        $this->evaluateResponse200($client->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('embed', $content['response']);

        foreach ($content['response']['embed'] as $embed) {
            $this->checkEmbed($embed, $story);
        }
        $route = '/api/v1/stories/24892534/51654651553/embed/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/stories/any_bad_id/sfsd5qfsd5/embed/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testRecordsEmbedRouteMimeType()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/embed/';

        self::$DI['client']->request('GET', $route, $this->getParameters(['mimes' => ['image/png']]), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertArrayHasKey('embed', $content['response']);

        $this->assertEquals(0, count($content['response']['embed']));
    }

    public function testRecordsEmbedRouteDevices()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/embed/';

        self::$DI['client']->request('GET', $route, $this->getParameters(['devices' => ['nodevice']]), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertEquals(0, count($content['response']['embed']));
    }

    public function testRecordsRelatedRoute()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/records/' . self::$DI['record_1']->get_sbas_id() . '/' . self::$DI['record_1']->get_record_id() . '/related/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $this->assertArrayHasKey("baskets", $content['response']);

        foreach ($content['response']['baskets'] as $basket) {
            $this->evaluateGoodBasket($basket, self::$DI['user_notAdmin']);
        }

        $route = '/api/v1/records/24892534/51654651553/related/';
        $this->evaluateNotFoundRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
        $route = '/api/v1/records/any_bad_id/sfsd5qfsd5/related/';
        $this->evaluateBadRequestRoute($route, ['GET']);
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);
    }

    public function testRecordsSetMetadatas()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->setToken($this->userAccessToken);

        $record = self::$DI['record_1'];

        $route = '/api/v1/records/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/setmetadatas/';
        $caption = $record->get_caption();

        $toupdate = [];

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
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

        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(['metadatas' => $toupdate]), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey("record_metadatas", $content['response']);

        foreach ($caption->get_fields() as $field) {
            foreach ($field->get_values() as $value) {
                if ($field->is_readonly() === false && $field->is_multi() === false) {
                    $saved_value = $toupdate[$field->get_meta_struct_id()]['value'];
                    $this->assertEquals($value->getValue(), $saved_value);
                }
            }
        }

        $this->evaluateRecordsMetadataResponse($content);

        foreach ($content['response']['record_metadatas'] as $metadata) {
            if (!in_array($metadata['meta_id'], array_keys($toupdate)))
                continue;
            $saved_value = $toupdate[$metadata['meta_structure_id']]['value'];
            $this->assertEquals($saved_value, $metadata['value']);
        }
    }

    public function testRecordsSetStatus()
    {
        $app = $this->getApplication();
        $app['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->setToken($this->userAccessToken);

        $record1 = $this->getRecord1();
        $route = '/api/v1/records/' . $record1->getDataboxId() . '/' . $record1->getRecordId() . '/setstatus/';

        $record_status = strrev($record1->getStatus());
        $statusStructure = $record1->getStatusStructure();

        $tochange = [];
        foreach ($statusStructure as $n => $datas) {
            $tochange[$n] = substr($record_status, ($n - 1), 1) == '0' ? '1' : '0';
        }
        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        $response = $this->request('POST', $route, $this->getParameters(['status' => $tochange]), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($response->getContent());

        // Get fresh record_1
        $testRecord = new \record_adapter($app, $record1->getDataboxId(), $record1->getRecordId());

        $this->evaluateResponse200($response);
        $this->evaluateMeta200($content);

        $this->evaluateRecordsStatusResponse($testRecord, $content);

        $record_status = strrev($testRecord->getStatus());
        foreach ($statusStructure as $n => $datas) {
            $this->assertEquals(substr($record_status, ($n), 1), $tochange[$n]);
        }

        foreach ($tochange as $n => $value) {
            $tochange[$n] = $value == '0' ? '1' : '0';
        }

        $response = $this->request('POST', $route, $this->getParameters(['status' => $tochange]), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($response->getContent());

        // Get fresh record_1
        $testRecord = new \record_adapter($app, $testRecord->getDataboxId(), $testRecord->getRecordId());

        $this->evaluateResponse200($response);
        $this->evaluateMeta200($content);

        $this->evaluateRecordsStatusResponse($testRecord, $content);

        $record_status = strrev($testRecord->getStatus());
        foreach ($statusStructure as $n => $datas) {
            $this->assertEquals(substr($record_status, ($n), 1), $tochange[$n]);
        }

        $record1->setStatus(str_repeat('0', 32));
    }

    public function testMoveRecordToCollection()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);

        $this->setToken($this->userAccessToken);

        $route = '/api/v1/records/' . $record->getDataboxId() . '/' . $record->getRecordId() . '/setcollection/';

        $base_id = false;
        foreach ($record->getDatabox()->get_collections() as $collection) {
            if ($collection->get_base_id() != $record->getBaseId()) {
                $base_id = $collection->get_base_id();
                break;
            }
        }
        if (!$base_id) {
            $this->markTestSkipped('No collection');
        }

        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(['base_id' => $base_id]), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $record->delete();
    }

    public function testSearchBaskets()
    {
        $this->setToken($this->adminAccessToken);
        $route = '/api/v1/baskets/list/';
        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $this->assertArrayHasKey("baskets", $content['response']);

        foreach ($content['response']['baskets'] as $basket) {
            $this->evaluateGoodBasket($basket, self::$DI['user']);
        }
    }

    public function testAddBasket()
    {
        $this->setToken($this->userAccessToken);

        $route = '/api/v1/baskets/add/';

        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        $client = $this->getClient();
        $client->request('POST', $route, $this->getParameters(['name' => 'un Joli Nom']), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize($client->getResponse()->getContent());

        $this->evaluateResponse200($client->getResponse());
        $this->evaluateMeta200($content);

        $this->assertEquals(1, count($content['response']));
        $this->assertArrayHasKey("basket", $content['response']);
        $this->evaluateGoodBasket($content['response']['basket'], self::$DI['user_notAdmin']);
        $this->assertEquals('un Joli Nom', $content['response']['basket']['name']);
    }

    public function testBasketContent()
    {
        $this->setToken($this->adminAccessToken);

        $basketElement = self::$DI['app']['orm.em']->find('Phraseanet:BasketElement', 1);
        $basket = $basketElement->getBasket();

        $route = '/api/v1/baskets/' . $basket->getId() . '/content/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertEquals(2, count((array) $content['response']));

        $this->assertArrayHasKey("basket_elements", $content['response']);
        $this->assertArrayHasKey("basket", $content['response']);
        $this->evaluateGoodBasket($content['response']['basket'], self::$DI['user']);

        foreach ($content['response']['basket_elements'] as $basket_element) {
            $this->assertArrayHasKey('basket_element_id', $basket_element);
            $this->assertArrayHasKey('order', $basket_element);
            $this->assertArrayHasKey('record', $basket_element);
            $this->assertArrayHasKey('validation_item', $basket_element);
            $this->assertTrue(is_bool($basket_element['validation_item']));
            $this->assertTrue(is_int($basket_element['order']));
            $this->assertTrue(is_int($basket_element['basket_element_id']));
            $this->evaluateGoodRecord($basket_element['record']);
        }
    }

    public function testSetBasketTitle()
    {
        $this->setToken($this->adminAccessToken);

        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);

        $route = '/api/v1/baskets/' . $basket->getId() . '/setname/';

        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(['name' => 'un Joli Nom']), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertEquals(1, count((array) $content['response']));
        $this->assertArrayHasKey("basket", $content['response']);
        $this->evaluateGoodBasket($content['response']['basket'], self::$DI['user']);

        $this->assertEquals($content['response']['basket']['name'], 'un Joli Nom');

        self::$DI['client']->request('POST', $route, $this->getParameters(['name' => 'un Joli Nom']), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertEquals(1, count((array) $content['response']));

        $this->assertArrayHasKey("basket", $content['response']);

        $this->evaluateGoodBasket($content['response']['basket'], self::$DI['user']);

        $this->assertEquals($content['response']['basket']['name'], 'un Joli Nom');

        self::$DI['client']->request('POST', $route, $this->getParameters(['name' => '<strong>aéaa']), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertEquals(1, count((array) $content['response']));
        $this->assertArrayHasKey("basket", $content['response']);
        $this->evaluateGoodBasket($content['response']['basket'], self::$DI['user']);
        $this->assertEquals($content['response']['basket']['name'], '<strong>aéaa');
    }

    public function testSetBasketDescription()
    {
        $this->setToken($this->adminAccessToken);

        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);

        $route = '/api/v1/baskets/' . $basket->getId() . '/setdescription/';

        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(['description' => 'une belle desc']), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertEquals(1, count((array) $content['response']));

        $this->assertArrayHasKey("basket", $content['response']);
        $this->evaluateGoodBasket($content['response']['basket'], self::$DI['user']);
        $this->assertEquals($content['response']['basket']['description'], 'une belle desc');
    }

    public function testDeleteBasket()
    {
        $this->setToken($this->adminAccessToken);
        $route = '/api/v1/baskets/1/delete/';
        $this->evaluateMethodNotAllowedRoute($route, ['GET', 'PUT', 'DELETE']);

        self::$DI['client']->request('POST', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey("baskets", $content['response']);

        $found = false;
        foreach ($content['response']['baskets'] as $basket) {
            $this->evaluateGoodBasket($basket, self::$DI['user']);
            $found = true;
            break;
        }
        if (!$found) {
            $this->fail('There should be four baskets left');
        }
    }

    public function testAddRecord()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        $params = $this->getAddRecordParameters();
        $params['status'] = '0b10000';

        self::$DI['client']->request('POST', $route, $this->getParameters($params), $this->getAddRecordFile(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);
        $datas = $content['response'];

        $this->assertArrayHasKey('entity', $datas);
        $this->assertArrayHasKey('url', $datas);
    }

    public function testAddRecordForceRecord()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        $params = $this->getAddRecordParameters();
        $params['forceBehavior'] = '0';

        self::$DI['client']->request('POST', $route, $this->getParameters($params), $this->getAddRecordFile(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $datas = $content['response'];

        $this->assertArrayHasKey('entity', $datas);
        $this->assertArrayHasKey('url', $datas);
        $this->assertRegExp('/\/records\/\d+\/\d+\//', $datas['url']);

        // if forced, there is no reason
        $this->assertEquals('0', $datas['entity']);
    }

    public function testAddRecordForceLazaret()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        $params = $this->getAddRecordParameters();
        $params['forceBehavior'] = '1';

        self::$DI['client']->request('POST', $route, $this->getParameters($params), $this->getAddRecordFile(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());
        $datas = $content['response'];

        $this->assertArrayHasKey('entity', $datas);
        $this->assertArrayHasKey('url', $datas);
        $this->assertRegExp('/\/quarantine\/item\/\d+\//', $datas['url']);

        $this->assertEquals('1', $datas['entity']);
    }

    public function testAddRecordWrongBehavior()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        $params = $this->getAddRecordParameters();
        $params['forceBehavior'] = '2';

        self::$DI['client']->request('POST', $route, $this->getParameters($params), $this->getAddRecordFile(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponseBadRequest(self::$DI['client']->getResponse());
        $this->evaluateMetaBadRequest($content);
    }

    public function testAddRecordWrongBaseId()
    {
        $this->setToken($this->adminAccessToken);
        $route = '/api/v1/records/add/';

        $params = $this->getAddRecordParameters();
        $params['base_id'] = self::$DI['collection_no_access']->get_base_id();

        self::$DI['client']->request('POST', $route, $this->getParameters($params), $this->getAddRecordFile(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponseForbidden(self::$DI['client']->getResponse());
        $this->evaluateMetaForbidden($content);
    }

    public function testAddRecordNoBaseId()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        $params = $this->getAddRecordParameters();
        unset($params['base_id']);

        self::$DI['client']->request('POST', $route, $this->getParameters($params), $this->getAddRecordFile(), ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponseBadRequest(self::$DI['client']->getResponse());
        $this->evaluateMetaBadRequest($content);
    }

    public function testAddRecordMultipleFiles()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        $file = [
            new \Symfony\Component\HttpFoundation\File\UploadedFile(self::$DI['app']['root.path'].'/tests/files/recta_logo.gif' , 'recta_logo.gif'),
            new \Symfony\Component\HttpFoundation\File\UploadedFile(self::$DI['app']['root.path'].'/tests/files/rectb_logo.gif', 'rectb_logo.gif'),
        ];

        self::$DI['client']->request('POST', $route, $this->getParameters($this->getAddRecordParameters()), ['file' => $file], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponseBadRequest(self::$DI['client']->getResponse());
        $this->evaluateMetaBadRequest($content);
    }

    public function testAddRecordNofile()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/records/add/';

        self::$DI['client']->request('POST', $route, $this->getParameters($this->getAddRecordParameters()), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponseBadRequest(self::$DI['client']->getResponse());
        $this->evaluateMetaBadRequest($content);
    }

    public function testFeedList()
    {
        $created_feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 1);

        $this->setToken($this->userAccessToken);
        $route = '/api/v1/feeds/list/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('feeds', $content['response']);

        $found = false;
        foreach ($content['response']['feeds'] as $feed) {

            $this->evaluateGoodFeed($feed);

            if ($feed['id'] == $created_feed->getId()) {
                $found = true;
                $this->assertEquals('Feed test, YOLO!', $feed['title']);
                break;
            }
        }

        if (!$found) {
            $this->fail('feed not found !');
        }
    }

    public function testFeedsContent()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $entry_title = 'Superman';
        $entry_subtitle = 'Wonder Woman';
        $author = "W. Shakespeare";
        $author_email = "gontran.bonheur@gmail.com";

        $feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 1);
        $created_entry = $feed->getEntries()->first();

        $created_entry->setAuthorEmail($author_email);
        $created_entry->setAuthorName($author);
        $created_entry->setTitle($entry_title);
        $created_entry->setSubtitle($entry_subtitle);
        self::$DI['app']['orm.em']->persist($created_entry);
        self::$DI['app']['orm.em']->flush();

        $this->setToken($this->userAccessToken);
        $route = '/api/v1/feeds/content/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('total_entries', $content['response']);
        $this->assertArrayHasKey('offset_start', $content['response']);
        $this->assertArrayHasKey('per_page', $content['response']);
        $this->assertArrayHasKey('entries', $content['response']);

        $found = false;

        foreach ($content['response']['entries'] as $entry) {
            $this->assertGoodEntry($entry);

            if ($entry['id'] == $created_entry->getId()) {
                $found = true;
                $this->assertEquals($author_email, $entry['author_email']);
                $this->assertEquals($author, $entry['author_name']);
                $this->assertEquals($entry_title, $entry['title']);
                $this->assertEquals($entry_subtitle, $entry['subtitle']);
                break;
            }
        }

        if (!$found) {
            $this->fail('entry not found !');
        }
    }

    public function testFeedEntry()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 1);
        $created_entry = $feed->getEntries()->first();

        $this->setToken($this->userAccessToken);
        $route = '/api/v1/feeds/entry/' . $created_entry->getId() . '/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('entry', $content['response']);
        $this->assertGoodEntry($content['response']['entry']);

        $this->assertEquals($created_entry->getId(), $content['response']['entry']['id']);

    }

    public function testFeedEntryNoAccess()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $created_feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 1);
        $created_entry = $created_feed->getEntries()->first();

        $created_feed->setCollection(self::$DI['collection_no_access']);

        $this->setToken($this->adminAccessToken);
        $route = '/api/v1/feeds/entry/' . $created_entry->getId() . '/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponseForbidden(self::$DI['client']->getResponse());
        $this->evaluateMetaForbidden($content);
    }

    public function testFeedContent()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $entry_title = 'Superman';
        $entry_subtitle = 'Wonder Woman';

        $created_feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 1);
        $created_entry = $created_feed->getEntries()->first();
        $created_entry->setTitle($entry_title);
        $created_entry->setSubtitle($entry_subtitle);
        self::$DI['app']['orm.em']->persist($created_entry);
        self::$DI['app']['orm.em']->flush();

        $this->setToken($this->userAccessToken);
        $route = '/api/v1/feeds/' . $created_feed->getId() . '/content/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->evaluateResponse200(self::$DI['client']->getResponse());
        $this->evaluateMeta200($content);

        $this->assertArrayHasKey('feed', $content['response']);
        $this->assertArrayHasKey('entries', $content['response']);
        $this->evaluateGoodFeed($content['response']['feed']);

        $found = false;
        foreach ($content['response']['entries'] as $entry) {
            $this->assertGoodEntry($entry);

            if ($entry['id'] == $created_entry->getId()) {
                $this->assertEquals($entry_title, $entry['title']);
                $this->assertEquals($entry_subtitle, $entry['subtitle']);
                $found = true;
                break;
            }
        }

        $this->assertEquals($created_feed->getId(), $content['response']['feed']['id']);

        if (!$found) {
            $this->fail('Entry not found');
        }
    }

    public function testQuarantineList()
    {
        $this->setToken($this->userAccessToken);
        $route = '/api/v1/quarantine/list/';

        $quarantineItemId = self::$DI['lazaret_1']->getId();

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertArrayHasKey('offset_start', $content['response']);
        $this->assertArrayHasKey('per_page', $content['response']);
        $this->assertArrayHasKey('quarantine_items', $content['response']);

        $found = false;

        foreach ($content['response']['quarantine_items'] as $item) {
            $this->evaluateGoodQuarantineItem($item);
            if ($item['id'] == $quarantineItemId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('should find the quarantine item');
        }
    }

    public function testQuarantineContent()
    {
        $this->setToken($this->userAccessToken);

        $quarantineItemId = self::$DI['lazaret_1']->getId();
        $route = '/api/v1/quarantine/item/' . $quarantineItemId . '/';

        $this->evaluateMethodNotAllowedRoute($route, ['POST', 'PUT', 'DELETE']);

        self::$DI['client']->request('GET', $route, $this->getParameters(), [], ['HTTP_Accept' => $this->getAcceptMimeType()]);
        $content = $this->unserialize(self::$DI['client']->getResponse()->getContent());

        $this->assertArrayHasKey('quarantine_item', $content['response']);

        $this->evaluateGoodQuarantineItem($content['response']['quarantine_item']);
        $this->assertEquals($quarantineItemId, $content['response']['quarantine_item']['id']);
    }

    protected function getQuarantineItem()
    {
        $lazaretSession = new LazaretSession();
        self::$DI['app']['orm.em']->persist($lazaretSession);

        $quarantineItem = null;
        $callback = function ($element, $visa, $code) use (&$quarantineItem) {
            $quarantineItem = $element;
        };

        $tmpname = tempnam(sys_get_temp_dir(), 'test_quarantine');
        copy(__DIR__ . '/../../../../files/iphone_pic.jpg', $tmpname);

        $file = File::buildFromPathfile($tmpname, self::$DI['collection'], self::$DI['app']);
        self::$DI['app']['border-manager']->process($lazaretSession, $file, $callback, Manager::FORCE_LAZARET);

        return $quarantineItem;
    }

    protected function evaluateGoodQuarantineItem($item)
    {
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('quarantine_session', $item);

        $session = $item['quarantine_session'];
        $this->assertArrayHasKey('id', $session);
        $this->assertArrayHasKey('usr_id', $session);
        $this->assertArrayHasKey('user', $session);
        if ($session['user'] !== null) {
            $this->evaluateGoodUserItem($session['user'], self::$DI['user']);
        }

        $this->assertArrayHasKey('base_id', $item);
        $this->assertArrayHasKey('original_name', $item);
        $this->assertArrayHasKey('sha256', $item);
        $this->assertArrayHasKey('uuid', $item);
        $this->assertArrayHasKey('forced', $item);
        $this->assertArrayHasKey('checks', $item);
        $this->assertArrayHasKey('created_on', $item);
        $this->assertArrayHasKey('updated_on', $item);

        $this->assertInternalType('boolean', $item['forced']);
        $this->assertDateAtom($item['updated_on']);
        $this->assertDateAtom($item['created_on']);
    }

    private function evaluateSearchResponse($response)
    {
        $this->assertArrayHasKey('available_results', $response);
        $this->assertArrayHasKey('total_results', $response);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('warning', $response);
        $this->assertArrayHasKey('query_time', $response);
        $this->assertArrayHasKey('search_indexes', $response);
        $this->assertArrayHasKey('suggestions', $response);
        $this->assertArrayHasKey('results', $response);
        $this->assertArrayHasKey('query', $response);

        $this->assertTrue(is_int($response['available_results']), 'Le nombre de results dispo est un int');
        $this->assertTrue(is_int($response['total_results']), 'Le nombre de results est un int');
        $this->assertTrue(is_string($response['error']), 'Error est une string');
        $this->assertTrue(is_string($response['warning']), 'Warning est une string');

        $this->assertTrue(is_string($response['search_indexes']));
        $this->assertTrue(is_array($response['suggestions']));
        $this->assertTrue(is_array($response['results']));
        $this->assertTrue(is_string($response['query']));
    }

    /**
     * @return int
     */
    private function getTestTaskId()
    {
        $app = $this->getApplication();
        $tasks = $app['repo.tasks']->findAll();

        if (empty($tasks)) {
            $this->markTestSkipped('no tasks created for the current instance');
        }

        /** @var Task $task */
        $task = array_shift($tasks);

        return $task->getId();
    }
}
