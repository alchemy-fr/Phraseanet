<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiJsonApplication extends PhraseanetWebTestCaseAbstract
{
    /**
     *
     * @var Symfony\Component\HttpKernel\Client
     */
    protected $client;

    /**
     * @var API_OAuth2_Token
     */
    protected static $token;

    /**
     * @var API_OAuth2_Account
     */
    protected static $account;

    /**
     * @var API_OAuth2_Application
     */
    protected static $application;

    /**
     * @var API_OAuth2_Token
     */
    protected static $adminToken;

    /**
     * @var API_OAuth2_Account
     */
    protected static $adminAccount;

    /**
     * @var API_OAuth2_Application
     */
    protected static $adminApplication;
    protected static $databoxe_ids = array();

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function tearDown()
    {
        $this->unsetToken();
        parent::tearDown();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //create basic user token
        $appbox = appbox::get_instance(\bootstrap::getCore());

        self::$application = API_OAuth2_Application::create($appbox, self::$user, 'test API v1');
        self::$account = API_OAuth2_Account::load_with_user($appbox, self::$application, self::$user);
        self::$token = self::$account->get_token()->get_value();

        //create admin user token
        $admins = User_Adapter::get_sys_admins();

        self::$adminToken = null;

        if (0 !== count($admins)) {
            $admin = User_Adapter::getInstance(key($admins), $appbox);
            self::$adminApplication = API_OAuth2_Application::create($appbox, $admin, 'test2 API v1');
            self::$adminAccount = API_OAuth2_Account::load_with_user($appbox, self::$adminApplication, $admin);
            self::$adminToken = self::$adminAccount->get_token()->get_value();
        }
    }

    public static function tearDownAfterClass()
    {
        //delete database entry
        self::$account->delete();
        self::$application->delete();

        if (self::$adminToken) {
            self::$adminAccount->delete();
            self::$adminApplication->delete();
        }
        parent::tearDownAfterClass();
    }

    public function createApplication()
    {
        return require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/Api.php';
    }

    public function testRouteNotFound()
    {
        $route = '/nothinghere';
        $this->setToken(self::$token);
        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponseNotFound($this->client->getResponse());
        $this->evaluateMetaJsonNotFound($content);
    }

    /**
     * @covers \API_V1_adapter::get_databoxes
     * @covers \API_V1_adapter::list_databoxes
     * @covers \API_V1_adapter::list_databox
     */
    public function testDataboxListRoute()
    {
        $this->setToken(self::$token);
        $this->client->request('GET', '/databoxes/list/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);


        $this->assertObjectHasAttribute('databoxes', $content->response);
        foreach ($content->response->databoxes as $databox) {
            $this->assertTrue(is_object($databox), 'Une databox est un objet');
            $this->assertObjectHasAttribute('databox_id', $databox);
            $this->assertObjectHasAttribute('name', $databox);
            $this->assertObjectHasAttribute('version', $databox);
            static::$databoxe_ids[] = $databox->databox_id;
        }
    }

    public function testCheckNativeApp()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $registry = $this->getMock('\\registry', array(), array(), '', false);
        $registry
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('GV_client_navigator'))
            ->will($this->returnValue(false));
        $registryBkp = $this->app["Core"]->getRegistry();

        $fail = null;

        try {

            $this->app["Core"]['Registry'] = $registry;

            $nativeApp = \API_OAuth2_Application::load_from_client_id($appbox, \API_OAuth2_Application_Navigator::CLIENT_ID);

            $account = API_OAuth2_Account::create($appbox, self::$user, $nativeApp);
            $token = $account->get_token()->get_value();
            $this->setToken($token);
            $this->client->request('GET', '/databoxes/list/', array(), array(), array('HTTP_Accept' => 'application/json'));
            $content = json_decode($this->client->getResponse()->getContent());

            if (403 != $content->meta->http_code) {
                $fail = new \Exception('Result does not match expected 403, returns ' . $content->meta->http_code);
            }
        } catch (\Exception $e) {
            $fail = $e;
        }

        $this->app["Core"]['Registry'] = $registryBkp;

        if ($fail) {
            throw $fail;
        }
    }

    /**
     * Covers mustBeAdmin route middleware
     */
    public function testAdminOnlyShedulerState()
    {
        $this->setToken(self::$token);

        $this->client->request('GET', '/monitor/tasks/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(401, $content->meta->http_code);

        $this->client->request('GET', '/monitor/task/1/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(401, $content->meta->http_code);

        $this->client->request('POST', '/monitor/task/1/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(401, $content->meta->http_code);

        $this->client->request('POST', '/monitor/task/1/start/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(401, $content->meta->http_code);

        $this->client->request('POST', '/monitor/task/1/stop/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(401, $content->meta->http_code);

        $this->client->request('GET', '/monitor/phraseanet/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(401, $content->meta->http_code);
    }

    /**
     * Route GET /API/V1/monitor/task
     * @covers API_V1_adapter::get_task_list
     */
    public function testGetMonitorTasks()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        if (null === self::$adminToken) {
            $this->markTestSkipped('there is no user with admin rights');
        }
        $this->setToken(self::$adminToken);
        $this->client->request('GET', '/monitor/tasks/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
        $response = $content->response;
        $task_manager = new \task_manager($appbox);
        $tasks = $task_manager->getTasks();
        $this->assertEquals(count($tasks), count(get_object_vars($response)));
    }

    /**
     * Route GET /API/V1/monitor/task{idTask}
     * @covers API_V1_adapter::get_task
     */
    public function testGetMonitorTaskById()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $task_manager = new \task_manager($appbox);
        $tasks = $task_manager->getTasks();

        if (null === self::$adminToken) {
            $this->markTestSkipped('there is no user with admin rights');
        }

        if ( ! count($tasks)) {
            $this->markTestSkipped('no tasks created for the current instance');
        }

        $this->setToken(self::$adminToken);
        reset($tasks);
        $idTask = key($tasks);
        $this->client->request('GET', '/monitor/task/' . $idTask . '/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
    }

    /**
     * Route GET /API/V1/monitor/task/{idTask}
     * @covers API_V1_adapter::get_task
     */
    public function testUnknowGetMonitorTaskById()
    {
        if (null === self::$adminToken) {
            $this->markTestSkipped('no tasks created for the current instance');
        }
        $this->setToken(self::$adminToken);
        $this->client->followRedirects();
        $this->client->request('GET', '/monitor/task/0/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->evaluateMetaJsonNotFound($content);
    }

    /**
     * Route GET /API/V1/monitor/task/{idTask}/start
     * @covers API_V1_adapter::start_task
     */
    public function testGetMonitorStartTask()
    {
        if (null === self::$adminToken) {
            $this->markTestSkipped('there is no user with admin rights');
        }

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $task_manager = new \task_manager($appbox);
        $tasks = $task_manager->getTasks();

        if ( ! count($tasks)) {
            $this->markTestSkipped('no tasks created for the current instance');
        }

        $this->setToken(self::$adminToken);
        reset($tasks);
        $idTask = key($tasks);
        $this->client->request('POST', '/monitor/task/' . $idTask . '/start/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
        $task_manager->getTasks(true);
        $task = $task_manager->getTask($idTask);
        $this->assertEquals(\task_abstract::STATE_TOSTART, $task->getState());
    }

    /**
     * Route GET /API/V1/monitor/task/{idTask}/stop
     * @covers API_V1_adapter::stop_task
     */
    public function testGetMonitorStopTask()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $task_manager = new \task_manager($appbox);

        $tasks = $task_manager->getTasks();

        if (null === self::$adminToken) {
            $this->markTestSkipped('there is no user with admin rights');
        }

        if ( ! count($tasks)) {
            $this->markTestSkipped('no tasks created for the current instance');
        }

        $this->setToken(self::$adminToken);
        reset($tasks);
        $idTask = key($tasks);
        $this->client->request('POST', '/monitor/task/' . $idTask . '/stop/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());
        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
        $task_manager->getTasks(true);
        $task = $task_manager->getTask($idTask);
        $this->assertEquals(\task_abstract::STATE_TOSTOP, $task->getState());
    }

    /**
     * Route GET /API/V1/monitor/phraseanet
     * @covers API_V1_adapter::get_phraseanet_monitor
     * @covers API_V1_adapter::get_config_info
     * @covers API_V1_adapter::get_cache_info
     * @covers API_V1_adapter::get_gv_info
     */
    public function testgetMonitorPhraseanet()
    {
        if (null === self::$adminToken) {
            $this->markTestSkipped('there is no user with admin rights');
        }

        $this->setToken(self::$adminToken);

        $this->client->request('GET', '/monitor/phraseanet/', array(), array(), array('HTTP_Accept' => 'application/json'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
        $this->assertObjectHasAttribute('global_values', $content->response);
        $this->assertObjectHasAttribute('cache', $content->response);
        $this->assertObjectHasAttribute('phraseanet', $content->response);
    }

    /**
     * @covers \API_V1_adapter::get_record
     * @covers \API_V1_adapter::list_record
     */
    public function testDataboxRecordRoute()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->evaluateGoodRecord($content->response->record);

        $route = '/records/1234567890/1/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/records/kjslkz84spm/sfsd5qfsd5/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_databox_collections
     * @covers \API_V1_adapter::list_databox_collections
     * @covers \API_V1_adapter::list_collection
     */
    public function testDataboxCollectionRoute()
    {
        $this->setToken(self::$token);
        foreach (static::$databoxe_ids as $databox_id) {
            $route = '/databoxes/' . $databox_id . '/collections/';
            $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

            $crawler = $this->client->request('GET', $route);
            $content = json_decode($this->client->getResponse()->getContent());
            $this->evaluateResponse200($this->client->getResponse());
            $this->evaluateMetaJson200($content);

            $this->assertObjectHasAttribute('collections', $content->response);
            foreach ($content->response->collections as $collection) {
                $this->assertTrue(is_object($collection), 'Une collection est un objet');
                $this->assertObjectHasAttribute('base_id', $collection);
                $this->assertObjectHasAttribute('coll_id', $collection);
                $this->assertObjectHasAttribute('name', $collection);
                $this->assertObjectHasAttribute('record_amount', $collection);
                $this->assertTrue(is_int($collection->base_id));
                $this->assertGreaterThan(0, $collection->base_id);
                $this->assertTrue(is_int($collection->coll_id));
                $this->assertGreaterThan(0, $collection->coll_id);
                $this->assertTrue(is_string($collection->name));
                $this->assertTrue(is_int($collection->record_amount));
            }
        }
        $route = '/databoxes/24892534/collections/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/databoxes/any_bad_id/collections/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_databox_status
     * @covers \API_V1_adapter::list_databox_status
     */
    public function testDataboxStatusRoute()
    {
        $this->setToken(self::$token);
        foreach (static::$databoxe_ids as $databox_id) {
            $databox = databox::get_instance($databox_id);
            $ref_status = $databox->get_statusbits();
            $route = '/databoxes/' . $databox_id . '/status/';
            $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

            $crawler = $this->client->request('GET', $route);
            $content = json_decode($this->client->getResponse()->getContent());
            $this->evaluateResponse200($this->client->getResponse());
            $this->evaluateMetaJson200($content);

            $this->assertObjectHasAttribute('status', $content->response);
            foreach ($content->response->status as $status) {
                $this->assertTrue(is_object($status), 'Un bloc status est un objet');
                $this->assertObjectHasAttribute('bit', $status);
                $this->assertTrue(is_int($status->bit));
                $this->assertGreaterThan(3, $status->bit);
                $this->assertLessThan(65, $status->bit);
                $this->assertObjectHasAttribute('label_on', $status);
                $this->assertObjectHasAttribute('label_off', $status);
                $this->assertObjectHasAttribute('img_on', $status);
                $this->assertObjectHasAttribute('img_off', $status);
                $this->assertObjectHasAttribute('searchable', $status);
                $this->assertObjectHasAttribute('printable', $status);
                $this->assertTrue(is_int($status->searchable));
                $this->assertTrue(in_array($status->searchable, array(0, 1)));
                $this->assertTrue($status->searchable === $ref_status[$status->bit]['searchable']);
                $this->assertTrue(is_int($status->printable));
                $this->assertTrue(in_array($status->printable, array(0, 1)));
                $this->assertTrue($status->printable === $ref_status[$status->bit]['printable']);
                $this->assertTrue($status->label_off === $ref_status[$status->bit]['labeloff']);
                $this->assertTrue($status->label_on === $ref_status[$status->bit]['labelon']);
                $this->assertTrue($status->img_off === $ref_status[$status->bit]['img_off']);
                $this->assertTrue($status->img_on === $ref_status[$status->bit]['img_on']);
            }
        }
        $route = '/databoxes/24892534/status/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/databoxes/any_bad_id/status/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_databox_metadatas
     * @covers \API_V1_adapter::list_databox_metadatas_fields
     * @covers \API_V1_adapter::list_databox_metadata_field_properties
     */
    public function testDataboxMetadatasRoute()
    {
        $this->setToken(self::$token);
        foreach (static::$databoxe_ids as $databox_id) {
            $databox = databox::get_instance($databox_id);
            $ref_structure = $databox->get_meta_structure();

            try {
                $ref_structure->get_element('idbarbouze');
                $this->fail('An expected exception has not been raised.');
            } catch (Exception_Databox_FieldNotFound $e) {

            }

            $route = '/databoxes/' . $databox_id . '/metadatas/';
            $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

            $crawler = $this->client->request('GET', $route);
            $content = json_decode($this->client->getResponse()->getContent());
            $this->evaluateResponse200($this->client->getResponse());
            $this->evaluateMetaJson200($content);
            $this->assertObjectHasAttribute('metadatas', $content->response);
            foreach ($content->response->metadatas as $metadatas) {
                $this->assertTrue(is_object($metadatas), 'Un bloc metadata est un objet');
                $this->assertObjectHasAttribute('id', $metadatas);
                $this->assertObjectHasAttribute('namespace', $metadatas);
                $this->assertObjectHasAttribute('source', $metadatas);
                $this->assertObjectHasAttribute('tagname', $metadatas);
                $this->assertObjectHasAttribute('name', $metadatas);
                $this->assertObjectHasAttribute('separator', $metadatas);
                $this->assertObjectHasAttribute('thesaurus_branch', $metadatas);
                $this->assertObjectHasAttribute('type', $metadatas);
                $this->assertObjectHasAttribute('indexable', $metadatas);
                $this->assertObjectHasAttribute('multivalue', $metadatas);
                $this->assertObjectHasAttribute('readonly', $metadatas);
                $this->assertObjectHasAttribute('required', $metadatas);

                $this->assertTrue(is_int($metadatas->id));
                $this->assertTrue(is_string($metadatas->namespace));
                $this->assertTrue(is_string($metadatas->name));
                $this->assertTrue(is_null($metadatas->source) || is_string($metadatas->source));
                $this->assertTrue(is_string($metadatas->tagname));
                $this->assertTrue((strlen($metadatas->name) > 0));
                $this->assertTrue(is_string($metadatas->separator));

                if ($metadatas->multivalue) {
                    $this->assertTrue((strlen($metadatas->separator) > 0));
                }

                $this->assertTrue(is_string($metadatas->thesaurus_branch));
                $this->assertTrue(in_array($metadatas->type, array(databox_field::TYPE_DATE, databox_field::TYPE_STRING, databox_field::TYPE_NUMBER, databox_field::TYPE_TEXT)));
                $this->assertTrue(is_bool($metadatas->indexable));
                $this->assertTrue(is_bool($metadatas->multivalue));
                $this->assertTrue(is_bool($metadatas->readonly));
                $this->assertTrue(is_bool($metadatas->required));

                $element = $ref_structure->get_element($metadatas->id);
                $this->assertTrue($element->is_indexable() === $metadatas->indexable);
                $this->assertTrue($element->is_required() === $metadatas->required);
                $this->assertTrue($element->is_readonly() === $metadatas->readonly);
                $this->assertTrue($element->is_multi() === $metadatas->multivalue);
                $this->assertTrue($element->get_type() === $metadatas->type);
                $this->assertTrue($element->get_tbranch() === $metadatas->thesaurus_branch);
                $this->assertTrue($element->get_separator() === $metadatas->separator);
                $this->assertTrue($element->get_name() === $metadatas->name);
                $this->assertTrue($element->get_tag()->getName() === $metadatas->tagname);
                $this->assertTrue($element->get_tag()->getTagname() === $metadatas->source);
                $this->assertTrue($element->get_tag()->getGroupName() === $metadatas->namespace);
            }
        }
        $route = '/databoxes/24892534/metadatas/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/databoxes/any_bad_id/metadatas/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_databox_terms
     * @covers \API_V1_adapter::list_databox_terms
     * 
     */
    public function testDataboxTermsOfUseRoute()
    {
        $this->setToken(self::$token);
        foreach (static::$databoxe_ids as $databox_id) {
            $route = '/databoxes/' . $databox_id . '/termsOfUse/';
            $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

            $this->client->request('GET', $route);
            $content = json_decode($this->client->getResponse()->getContent());
            $this->evaluateResponse200($this->client->getResponse());
            $this->evaluateMetaJson200($content);

            $this->assertObjectHasAttribute('termsOfUse', $content->response);
            foreach ($content->response->termsOfUse as $terms) {
                $this->assertTrue(is_object($terms), 'Une bloc cgu est un objet');
                $this->assertObjectHasAttribute('locale', $terms);
                $this->assertTrue(in_array($terms->locale, array('fr_FR', 'en_GB', 'ar_SA', 'de_DE', 'es_ES')));
                $this->assertObjectHasAttribute('terms', $terms);
            }
        }
        $route = '/databoxes/24892534/termsOfUse/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/databoxes/any_bad_id/termsOfUse/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::search_records
     * @covers \API_V1_adapter::list_record
     */
    public function testRecordsSearchRoute()
    {
        $this->setToken(self::$token);
        $crawler = $this->client->request('POST', '/records/search/');
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $response = $content->response;

        $this->assertObjectHasAttribute('total_pages', $response);
        $this->assertObjectHasAttribute('current_page', $response);
        $this->assertObjectHasAttribute('available_results', $response);
        $this->assertObjectHasAttribute('total_results', $response);
        $this->assertObjectHasAttribute('error', $response);
        $this->assertObjectHasAttribute('warning', $response);
        $this->assertObjectHasAttribute('query_time', $response);
        $this->assertObjectHasAttribute('search_indexes', $response);
        $this->assertObjectHasAttribute('suggestions', $response);
        $this->assertObjectHasAttribute('results', $response);
        $this->assertObjectHasAttribute('query', $response);


        $this->assertTrue(is_int($response->total_pages), 'Le nombre de page est un int');
        $this->assertTrue(is_int($response->current_page), 'Le nombre de la page courrante  est un int');
        $this->assertTrue(is_int($response->available_results), 'Le nombre de results dispo est un int');
        $this->assertTrue(is_int($response->total_results), 'Le nombre de results est un int');
        $this->assertTrue(is_string($response->error), 'Error est une string');
        $this->assertTrue(is_string($response->warning), 'Warning est une string');

//        $this->assertTrue(is_string($response->query_time));
        $this->assertTrue(is_string($response->search_indexes));
        $this->assertTrue(is_array($response->suggestions));
        $this->assertTrue(is_array($response->results));
        $this->assertTrue(is_string($response->query));

        foreach ($response->results as $record) {
            $this->evaluateGoodRecord($record);
        }
    }

    /**
     * @covers \API_V1_adapter::caption_records
     */
    public function testRecordsCaptionRoute()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/caption/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->evaluateRecordsCaptionResponse($content);

        $route = '/records/24892534/51654651553/metadatas/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/records/any_bad_id/sfsd5qfsd5/metadatas/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::set_record_metadatas
     */
    public function testRecordsMetadatasRoute()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/metadatas/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->evaluateRecordsMetadataResponse($content);

        $route = '/records/24892534/51654651553/metadatas/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/records/any_bad_id/sfsd5qfsd5/metadatas/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_record_status
     */
    public function testRecordsStatusRoute()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/status/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->evaluateRecordsStatusResponse(static::$records['record_1'], $content);

        $route = '/records/24892534/51654651553/status/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/records/any_bad_id/sfsd5qfsd5/status/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_record_embed
     */
    public function testRecordsEmbedRoute()
    {
        $this->setToken(self::$token);

        $keys = array_keys(static::$records['record_1']->get_subdefs());

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/embed/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        foreach ($content->response as $embed) {
            foreach ($keys as $key) {
                $this->assertObjectHasAttribute($key, $embed);
                $this->checkEmbed($key, $embed->$key, static::$records['record_1']);
            }
        }
        $route = '/records/24892534/51654651553/embed/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/records/any_bad_id/sfsd5qfsd5/embed/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::get_record_embed
     */
    public function testRecordsEmbedRouteMimeType()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/embed/';

        $this->client->request('GET', $route, array('mimes' => array('image/jpg', 'image/jpeg')));
        $content = json_decode($this->client->getResponse()->getContent());

        foreach ($content->response as $embed) {
            foreach (array('thumbnail', 'preview') as $key) {
                $this->assertObjectHasAttribute($key, $embed);
                $this->checkEmbed($key, $embed->$key, static::$records['record_1']);
            }
        }
    }

    /**
     * @covers \API_V1_adapter::get_record_related
     */
    public function testRecordsEmbedRouteDevices()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/embed/';

        $this->client->request('GET', $route, array('devices' => array('nodevice')));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(0, count($content->response->embed));
    }


    /**
     * @covers \API_V1_adapter::get_record_related
     */
    public function testRecordsRelatedRoute()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/related/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
        $this->assertObjectHasAttribute("baskets", $content->response);
        
        foreach ($content->response->baskets as $basket) {
            $this->evaluateGoodBasket($basket);
        }

        $route = '/records/24892534/51654651553/related/';
        $this->evaluateNotFoundRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
        $route = '/records/any_bad_id/sfsd5qfsd5/related/';
        $this->evaluateBadRequestRoute($route, array('GET'));
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    }

    /**
     * @covers \API_V1_adapter::set_record_metadatas
     * @covers \API_V1_adapter::list_record_caption
     * @covers \API_V1_adapter::list_record_caption_field
     */
    public function testRecordsSetMetadatas()
    {
        $this->setToken(self::$token);

        $file = new Alchemy\Phrasea\Border\File(\MediaVorus\MediaVorus::guess(new \SplFileInfo(__DIR__ . '/../../../testfiles/test001.CR2')), self::$collection);
        $record = record_adapter::createFromFile($file);

        $route = '/records/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/setmetadatas/';
        $caption = $record->get_caption();

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'podom pom pom ' . $field->get_id()
            );
        }

        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

        $this->client->request('POST', $route, array('metadatas' => $toupdate));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $caption = $record->get_caption();

        $this->assertEquals(count($caption->get_fields()), count(get_object_vars($content->response->metadatas)), 'Retrived metadatas are the same');

        foreach ($caption->get_fields() as $field) {
            foreach ($field->get_values() as $value) {
                if ($field->is_readonly() === false && $field->is_multi() === false) {
                    $saved_value = $toupdate[$field->get_meta_struct_id()]['value'];
                    $this->assertEquals($value->getValue(), $saved_value);
                }
            }
        }
        $this->evaluateRecordsMetadataResponse($content);

        foreach ($content->response->metadatas as $metadata) {
            if ( ! in_array($metadata->meta_id, array_keys($toupdate)))
                continue;
            $saved_value = $toupdate[$metadata->meta_structure_id]['value'];
            $this->assertEquals($saved_value, $metadata->value);
        }
    }

    /**
     * @covers \API_V1_adapter::set_record_status
     * @covers \API_V1_adapter::list_record_status
     */
    public function testRecordsSetStatus()
    {
        $this->setToken(self::$token);

        $route = '/records/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/setstatus/';

        $record_status = strrev(static::$records['record_1']->get_status());
        $status_bits = static::$records['record_1']->get_databox()->get_statusbits();

        $tochange = array();
        foreach ($status_bits as $n => $datas) {
            $tochange[$n] = substr($record_status, ($n - 1), 1) == '0' ? '1' : '0';
        }
        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));


        $crawler = $this->client->request('POST', $route, array('status' => $tochange));
        $content = json_decode($this->client->getResponse()->getContent());

        /**
         * Get fresh record_1
         */
        static::$records['record_1'] = static::$records['record_1']->get_databox()->get_record(static::$records['record_1']->get_record_id());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->evaluateRecordsStatusResponse(static::$records['record_1'], $content);

        $record_status = strrev(static::$records['record_1']->get_status());
        foreach ($status_bits as $n => $datas) {
            $this->assertEquals(substr($record_status, ($n - 1), 1), $tochange[$n]);
        }

        foreach ($tochange as $n => $value) {
            $tochange[$n] = $value == '0' ? '1' : '0';
        }


        $crawler = $this->client->request('POST', $route, array('status' => $tochange));
        $content = json_decode($this->client->getResponse()->getContent());

        /**
         * Get fresh record_1
         */
        static::$records['record_1'] = static::$records['record_1']->get_databox()->get_record(static::$records['record_1']->get_record_id());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->evaluateRecordsStatusResponse(static::$records['record_1'], $content);

        $record_status = strrev(static::$records['record_1']->get_status());
        foreach ($status_bits as $n => $datas) {
            $this->assertEquals(substr($record_status, ($n - 1), 1), $tochange[$n]);
        }
    }

    /**
     * @covers \API_V1_adapter::set_record_collection
     */
    public function testMoveRecordToCollection()
    {
        $file = new Alchemy\Phrasea\Border\File(\MediaVorus\MediaVorus::guess(new \SplFileInfo(__DIR__ . '/../../../testfiles/test001.CR2')), self::$collection);
        $record = record_adapter::createFromFile($file);

        $this->setToken(self::$token);

        $route = '/records/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/setcollection/';

        $base_id = false;
        foreach ($record->get_databox()->get_collections() as $collection) {
            if ($collection->get_base_id() != $record->get_base_id()) {
                $base_id = $collection->get_base_id();
                break;
            }
        }
        if ( ! $base_id) {
            continue;
        }

        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

        $this->client->request('POST', $route, array('base_id' => $base_id));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $record->delete();
    }

    /**
     * @covers \API_V1_adapter::search_baskets
     * @covers \API_V1_adapter::list_baskets
     * @covers \API_V1_adapter::list_basket
     */
    public function testSearchBaskets()
    {
        $this->insertFiveBasket();
        $this->insertOneValidationBasket();
        
        $this->setToken(self::$token);
        $route = '/baskets/list/';
        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);
        $this->assertObjectHasAttribute("baskets", $content->response);

        foreach ($content->response->baskets as $basket) {
            $this->evaluateGoodBasket($basket);
        }
    }
    
    /**
     * @covers \API_V1_adapter::create_basket
     * @covers \API_V1_adapter::list_basket
     */
    public function testAddBasket()
    {
        $this->setToken(self::$token);

        $route = '/baskets/add/';

        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

        $this->client->request('POST', $route, array('name'   => 'un Joli Nom'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertEquals(1, count((array) $content->response));
        $this->assertObjectHasAttribute("basket", $content->response);

        foreach ($content->response->basket as $basket) {
            $this->evaluateGoodBasket($basket);
            $this->assertEquals('un Joli Nom', $basket->name);
        }
    }

    /**
     * @covers \API_V1_adapter::get_basket
     * @covers \API_V1_adapter::list_basket_content
     */
    public function testBasketContent()
    {
        $this->setToken(self::$token);

        $basketElement = $this->insertOneBasketElement();
        $basket = $basketElement->getBasket();
        
        $route = '/baskets/' . $basket->getId() . '/content/';

        $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

        $this->client->request('GET', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertEquals(1, count((array) $content->response));

        $this->assertObjectHasAttribute("basket_elements", $content->response);

        foreach ($content->response->basket_elements as $basket_str) {
            $this->evaluateGoodBasket($basket_str);

            $this->assertEquals(count($basket->getElements()), count((array) $basket_str->basket_elements));
            foreach ($basket_str->basket_elements as $basket_element) {
                $this->assertObjectHasAttribute('basket_element_id', $basket_element);
                $this->assertObjectHasAttribute('order', $basket_element);
                $this->assertObjectHasAttribute('record', $basket_element);
                $this->assertObjectHasAttribute('validation_item', $basket_element);
                $this->assertTrue(is_bool($basket_element->validation_item));
                $this->assertTrue(is_int($basket_element->order));
                $this->assertTrue(is_int($basket_element->basket_element_id));
                $this->evaluateGoodRecord($basket_element->record);
            }
        }
    }

    /**
     * @covers \API_V1_adapter::set_basket_title
     * @covers \API_V1_adapter::list_basket_content
     */
    public function testSetBasketTitle()
    {
        $this->setToken(self::$token);

        $basket = $this->insertOneBasket();

        $route = '/baskets/' . $basket->getId() . '/setname/';

        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

        $crawler = $this->client->request('POST', $route, array('name'   => 'un Joli Nom'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertEquals(1, count((array) $content->response));
        $this->assertObjectHasAttribute("basket", $content->response);
        foreach ($content->response->basket as $basket_str) {
            $this->evaluateGoodBasket($basket_str);

            $this->assertEquals($basket_str->name, 'un Joli Nom');
        }

        $crawler = $this->client->request('POST', $route, array('name'   => '<i>un Joli Nom<i>'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertEquals(1, count((array) $content->response));

        $this->assertObjectHasAttribute("basket", $content->response);

        foreach ($content->response->basket as $basket) {
            $this->evaluateGoodBasket($basket_str);

            $this->assertEquals($basket_str->name, 'un Joli Nom');
        }

        $crawler = $this->client->request('POST', $route, array('name'   => '<strong>aéaa'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertEquals(1, count((array) $content->response));
        $this->assertObjectHasAttribute("basket", $content->response);
        foreach ($content->response->basket as $basket_str) {
            $this->evaluateGoodBasket($basket_str);
            $this->assertEquals($basket_str->name, '<strong>aéaa');
        }
    }

    /**
     * @covers \API_V1_adapter::set_basket_description
     * @covers \API_V1_adapter::list_basket_content
     */
    public function testSetBasketDescription()
    {
        $this->setToken(self::$token);

        $basket = $this->insertOneBasket();

        $route = '/baskets/' . $basket->getId() . '/setdescription/';

        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

        $this->client->request('POST', $route, array('description' => 'une belle desc'));
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertEquals(1, count((array) $content->response));

        $this->assertObjectHasAttribute("basket", $content->response);
        foreach ($content->response->basket as $basket_str) {
            $this->evaluateGoodBasket($basket_str);

            $this->assertEquals($basket_str->description, 'une belle desc');
        }
    }

    /**
     * @covers \API_V1_adapter::delete_basket
     */
    public function testDeleteBasket()
    {
        $this->setToken(self::$token);

        $baskets = $this->insertFiveBasket();

        $route = '/baskets/' . $baskets[0]->getId() . '/delete/';

        $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

        $this->client->request('POST', $route);
        $content = json_decode($this->client->getResponse()->getContent());

        $this->evaluateResponse200($this->client->getResponse());
        $this->evaluateMetaJson200($content);

        $this->assertObjectHasAttribute("baskets", $content->response);

        $found = false;
        foreach ($content->response->baskets as $basket) {
            $this->evaluateGoodBasket($basket);
            $found = true;
        }
        if ( ! $found) {
            $this->fail('There should be four baskets left');
        }
    }

    protected function evaluateNotFoundRoute($route, $methods)
    {
        foreach ($methods as $method) {
            $crawler = $this->client->request($method, $route);
            $content = json_decode($this->client->getResponse()->getContent());

            $this->evaluateResponseNotFound($this->client->getResponse());
            $this->evaluateMetaJsonNotFound($content);
        }
    }
    
    protected function checkEmbed($subdef_name, $embed, record_adapter $record)
    {
        $this->assertObjectHasAttribute("permalink", $embed);
        $this->checkPermalink($embed->permalink, $record->get_subdef($subdef_name));
        $this->assertObjectHasAttribute("height", $embed);
        $this->assertEquals($embed->height, $record->get_subdef($subdef_name)->get_height());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $embed->height);
        $this->assertObjectHasAttribute("width", $embed);
        $this->assertEquals($embed->width, $record->get_subdef($subdef_name)->get_width());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $embed->width);
        $this->assertObjectHasAttribute("filesize", $embed);
        $this->assertEquals($embed->filesize, $record->get_subdef($subdef_name)->get_size());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $embed->filesize);
        $this->assertObjectHasAttribute("player_type", $embed);
        $this->assertEquals($embed->player_type, $record->get_subdef($subdef_name)->get_type());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $embed->player_type);
        $this->assertObjectHasAttribute("mime_type", $embed);
        $this->assertEquals($embed->mime_type, $record->get_subdef($subdef_name)->get_mime());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $embed->mime_type);
        $this->assertObjectHasAttribute("devices", $embed);
        $this->assertEquals($embed->devices, $record->get_subdef($subdef_name)->getDevices());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $embed->devices);
    }

    protected function checkPermalink($permalink, media_subdef $subdef)
    {
        if ($subdef->is_physically_present()) {
            $this->assertNotNull($subdef->get_permalink());
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $permalink);
            $this->assertObjectHasAttribute("created_on", $permalink);
            $now = new Datetime($permalink->created_on);
            $interval = $now->diff($subdef->get_permalink()->get_created_on());
            $this->assertTrue(abs($interval->format('U')) < 2);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink->created_on);
            $this->assertDateAtom($permalink->created_on);
            $this->assertObjectHasAttribute("id", $permalink);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $permalink->id);
            $this->assertEquals($subdef->get_permalink()->get_id(), $permalink->id);
            $this->assertObjectHasAttribute("is_activated", $permalink);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $permalink->is_activated);
            $this->assertEquals($subdef->get_permalink()->get_is_activated(), $permalink->is_activated);
            $this->assertObjectHasAttribute("label", $permalink);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink->label);
            $this->assertObjectHasAttribute("last_modified", $permalink);
            $this->assertEquals($subdef->get_permalink()->get_last_modified()->format(DATE_ATOM), $permalink->last_modified);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink->last_modified);
            $this->assertDateAtom($permalink->last_modified);
            $this->assertObjectHasAttribute("page_url", $permalink);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink->page_url);
            $this->assertEquals($subdef->get_permalink()->get_page(registry::get_instance()), $permalink->page_url);
            $this->checkUrlCode200($permalink->page_url);
            $this->assertPermalinkHeaders($permalink->page_url, $subdef);
            $this->assertObjectHasAttribute("url", $permalink);
            $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $permalink->url);
            $this->assertEquals($subdef->get_permalink()->get_url(), $permalink->url);
            $this->checkUrlCode200($permalink->url);
            $this->assertPermalinkHeaders($permalink->url, $subdef, "url");
        }
    }

    protected function assertPermalinkHeaders($url, media_subdef $subdef, $type_url = "page_url")
    {
        $headers = http_query::getHttpHeaders($url);
        $this->assertEquals(200, $headers["http_code"]);

        switch ($type_url) {
            case "page_url" :
                $this->assertTrue(strpos($headers['content_type'], "text/html") === 0);
                $this->assertNotEquals($subdef->get_size(), $headers["download_content_length"]);
                break;
            case "url" :
                $this->assertTrue(strpos($headers['content_type'], $subdef->get_mime()) === 0, 'Verify that header ' . $headers['content_type'] . ' contains subdef mime type ' . $subdef->get_mime());
                $this->assertEquals($subdef->get_size(), $headers["download_content_length"]);
                break;
        }
    }

    protected function checkUrlCode200($url)
    {
        $code = http_query::getHttpCodeFromUrl(self::$core->getRegistry()->get('GV_ServerName'));

        if ($code == 0) {
            $this->markTestSkipped('Install does not seem to rely on a webserver');
        }

        $code = http_query::getHttpCodeFromUrl($url);
        $this->assertEquals(200, $code, sprintf('verification de url %s', $url));
    }

    protected function evaluateMethodNotAllowedRoute($route, $methods)
    {
        foreach ($methods as $method) {
            $crawler = $this->client->request($method, $route);
            $content = json_decode($this->client->getResponse()->getContent());
            $this->evaluateResponseMethodNotAllowed($this->client->getResponse());
            $this->evaluateMetaJsonMethodNotAllowed($content);
        }
    }

    protected function evaluateBadRequestRoute($route, $methods)
    {
        foreach ($methods as $method) {
            $this->client->request($method, $route);
            $content = json_decode($this->client->getResponse()->getContent());
            $this->evaluateResponseBadRequest($this->client->getResponse());
            $this->evaluateMetaJsonBadRequest($content);
        }
    }

    protected function evaluateMetaJson($content)
    {
        $this->assertTrue(is_object($content), 'La reponse est un objet');
        $this->assertObjectHasAttribute('meta', $content);
        $this->assertObjectHasAttribute('response', $content);
        $this->assertTrue(is_object($content->meta), 'Le bloc meta est un objet json');
        $this->assertTrue(is_object($content->response), 'Le bloc reponse est un objet json');
        $this->assertEquals('1.2', $content->meta->api_version);
        $this->assertNotNull($content->meta->response_time);
        $this->assertEquals('UTF-8', $content->meta->charset);
    }

    protected function evaluateMetaJson200($content)
    {
        $this->evaluateMetaJson($content);
        $this->assertEquals(200, $content->meta->http_code);
        $this->assertNull($content->meta->error_type);
        $this->assertNull($content->meta->error_message);
        $this->assertNull($content->meta->error_details);
    }

    protected function evaluateMetaJsonBadRequest($content)
    {
        $this->evaluateMetaJson($content);
        $this->assertNotNull($content->meta->error_type);
        $this->assertNotNull($content->meta->error_message);
        $this->assertEquals(400, $content->meta->http_code);
    }

    protected function evaluateMetaJsonNotFound($content)
    {
        $this->evaluateMetaJson($content);
        $this->assertNotNull($content->meta->error_type);
        $this->assertNotNull($content->meta->error_message);
        $this->assertEquals(404, $content->meta->http_code);
    }

    protected function evaluateMetaJsonMethodNotAllowed($content)
    {
        $this->evaluateMetaJson($content);
        $this->assertNotNull($content->meta->error_type);
        $this->assertNotNull($content->meta->error_message);
        $this->assertEquals(405, $content->meta->http_code);
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

    protected function evaluateGoodBasket($basket)
    {
        $this->assertTrue(is_object($basket));
        $this->assertObjectHasAttribute('created_on', $basket);
        $this->assertObjectHasAttribute('description', $basket);
        $this->assertObjectHasAttribute('name', $basket);
        $this->assertObjectHasAttribute('pusher_usr_id', $basket);
        $this->assertObjectHasAttribute('ssel_id', $basket);
        $this->assertObjectHasAttribute('updated_on', $basket);
        $this->assertObjectHasAttribute('unread', $basket);

        if ( ! is_null($basket->pusher_usr_id)) {
            $this->assertTrue(is_int($basket->pusher_usr_id));
        }

        $this->assertTrue(is_string($basket->name));
        $this->assertTrue(is_string($basket->description));
        $this->assertTrue(is_int($basket->ssel_id));
        $this->assertTrue(is_bool($basket->unread));
        $this->assertDateAtom($basket->created_on);
        $this->assertDateAtom($basket->updated_on);
    }

    protected function evaluateGoodRecord($record)
    {
        $this->assertObjectHasAttribute('databox_id', $record);
        $this->assertTrue(is_int($record->databox_id));
        $this->assertObjectHasAttribute('record_id', $record);
        $this->assertTrue(is_int($record->record_id));
        $this->assertObjectHasAttribute('mime_type', $record);
        $this->assertTrue(is_string($record->mime_type));
        $this->assertObjectHasAttribute('title', $record);
        $this->assertTrue(is_string($record->title));
        $this->assertObjectHasAttribute('original_name', $record);
        $this->assertTrue(is_string($record->original_name));
        $this->assertObjectHasAttribute('last_modification', $record);
        $this->assertDateAtom($record->last_modification);
        $this->assertObjectHasAttribute('created_on', $record);
        $this->assertDateAtom($record->created_on);
        $this->assertObjectHasAttribute('collection_id', $record);
        $this->assertTrue(is_int($record->collection_id));
        $this->assertObjectHasAttribute('thumbnail', $record);
        $this->assertObjectHasAttribute('sha256', $record);
        $this->assertTrue(is_string($record->sha256));
        $this->assertObjectHasAttribute('technical_informations', $record);
        $this->assertObjectHasAttribute('phrasea_type', $record);
        $this->assertTrue(is_string($record->phrasea_type));
        $this->assertTrue(in_array($record->phrasea_type, array('audio', 'document', 'image', 'video', 'flash', 'unknown')));
        $this->assertObjectHasAttribute('uuid', $record);
        $this->assertTrue(uuid::is_valid($record->uuid));

        $this->assertTrue(is_object($record->thumbnail));
        $this->assertObjectHasAttribute('player_type', $record->thumbnail);
        $this->assertTrue(is_string($record->thumbnail->player_type));
        $this->assertObjectHasAttribute('permalink', $record->thumbnail);
        $this->assertObjectHasAttribute('mime_type', $record->thumbnail);
        $this->assertTrue(is_string($record->thumbnail->mime_type));
        $this->assertObjectHasAttribute('height', $record->thumbnail);
        $this->assertTrue(is_int($record->thumbnail->height));
        $this->assertObjectHasAttribute('width', $record->thumbnail);
        $this->assertTrue(is_int($record->thumbnail->width));
        $this->assertObjectHasAttribute('filesize', $record->thumbnail);
        $this->assertTrue(is_int($record->thumbnail->filesize));

        if (is_array($record->technical_informations))
            $this->assertEquals(0, count($record->technical_informations));
        else
            $this->assertTrue(is_object($record->technical_informations));

        foreach ($record->technical_informations as $key => $value) {
            if (is_string($value))
                $this->assertFalse(ctype_digit($value));
            else
                $this->assertTrue(is_int($value));
        }
    }

    protected function evaluateRecordsCaptionResponse($content)
    {
        foreach ($content->response as $field) {
            $this->assertTrue(is_object($field), 'Un bloc field est un objet');
            $this->assertObjectHasAttribute('meta_structure_id', $field);
            $this->assertTrue(is_int($field->meta_structure_id));
            $this->assertObjectHasAttribute('name', $field);
            $this->assertTrue(is_string($field->name));
            $this->assertObjectHasAttribute('value', $field);
            $this->assertTrue(is_string($field->value));
        }
    }

    protected function evaluateRecordsMetadataResponse($content)
    {
        $this->assertObjectHasAttribute("metadatas", $content->response);
        foreach ($content->response->metadatas as $meta) {
            $this->assertTrue(is_object($meta), 'Un bloc meta est un objet');
            $this->assertObjectHasAttribute('meta_id', $meta);
            $this->assertTrue(is_int($meta->meta_id));
            $this->assertObjectHasAttribute('meta_structure_id', $meta);
            $this->assertTrue(is_int($meta->meta_structure_id));
            $this->assertObjectHasAttribute('name', $meta);
            $this->assertTrue(is_string($meta->name));
            $this->assertObjectHasAttribute('value', $meta);

            if (is_array($meta->value)) {
                foreach ($meta->value as $val) {
                    $this->assertTrue(is_string($val));
                }
            } else {
                $this->assertTrue(is_string($meta->value));
            }
        }
    }

    protected function evaluateRecordsStatusResponse(record_adapter $record, $content)
    {
        $status = $record->get_databox()->get_statusbits();

        $r_status = strrev($record->get_status());
        $this->assertObjectHasAttribute('status', $content->response);
        $this->assertEquals(count((array) $content->response->status), count($status));
        foreach ($content->response->status as $status) {
            $this->assertTrue(is_object($status));
            $this->assertObjectHasAttribute('bit', $status);
            $this->assertObjectHasAttribute('state', $status);
            $this->assertTrue(is_int($status->bit));
            $this->assertTrue(is_bool($status->state));

            $retrieved = ! ! substr($r_status, ($status->bit - 1), 1);

            $this->assertEquals($retrieved, $status->state);
        }
    }

    protected function setToken($token)
    {
        $_GET['oauth_token'] = $token;
    }

    protected function unsetToken()
    {
        unset($_GET['oauth_token']);
    }
}
