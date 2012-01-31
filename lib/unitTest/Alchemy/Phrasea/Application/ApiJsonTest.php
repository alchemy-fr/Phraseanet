<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';

require_once __DIR__ . '/../../../../Alchemy/Phrasea/Application/Api.php';

API_OAuth2_Autoloader::register();

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiJsonApplication extends PhraseanetWebTestCaseAbstract
{

  protected $client;
  protected static $token;
  protected static $account_id;
  protected static $application;
  protected static $databoxe_ids = array();

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    $appbox = appbox::get_instance();
    self::$application = API_OAuth2_Application::create($appbox, self::$user, 'test API v1');
    $account = API_OAuth2_Account::load_with_user($appbox, self::$application, self::$user);
    self::$token = $account->get_token()->get_value();
    self::$account_id = $account->get_id();
    $_GET['oauth_token'] = self::$token;
  }

  public static function tearDownAfterClass()
  {
    self::$application->delete();
    $_GET = array();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Api.php';
  }

  public function testRouteNotFound()
  {
    $route = '/nothinghere?oauth_token=' . self::$token;
    $crawler = $this->client->request('GET', $route);
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponseNotFound($this->client->getResponse());
    $this->evaluateMetaJsonNotFound($content);
  }

  public function testDatboxListRoute()
  {
    $crawler = $this->client->request('GET', '/databoxes/list/?oauth_token=' . self::$token, array(), array(), array('HTTP_Accept' => 'application/json'));
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);


    $this->assertObjectHasAttribute('databoxes', $content->response);
    foreach ($content->response->databoxes as $databox)
    {
      $this->assertTrue(is_object($databox), 'Une databox est un objet');
      $this->assertObjectHasAttribute('databox_id', $databox);
      $this->assertObjectHasAttribute('name', $databox);
      $this->assertObjectHasAttribute('version', $databox);
      static::$databoxe_ids[] = $databox->databox_id;
    }
  }

  /*
   * Routes /API/V1/databoxes/DATABOX_ID/xxxxxx
   *
   */

  public function testDataboxRecordRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);

      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);
      $record_id = $record->get_record_id();
      $route = '/records/' . $databox_id . '/' . $record_id . '/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $this->evaluateGoodRecord($content->response->record);
      $record->delete();
    }
    $route = '/records/1234567890/1/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/records/kjslkz84spm/sfsd5qfsd5/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testDataboxCollectionRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $route = '/databoxes/' . $databox_id . '/collections/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());
      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $this->assertObjectHasAttribute('collections', $content->response);
      foreach ($content->response->collections as $collection)
      {
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
    $route = '/databoxes/24892534/collections/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/databoxes/any_bad_id/collections/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testDataboxStatusRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $ref_status = $databox->get_statusbits();
      $route = '/databoxes/' . $databox_id . '/status/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());
      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $this->assertObjectHasAttribute('status', $content->response);
      foreach ($content->response->status as $status)
      {
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
    $route = '/databoxes/24892534/status/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/databoxes/any_bad_id/status/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testDataboxMetadatasRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $ref_structure = $databox->get_meta_structure();

      try
      {
        $ref_structure->get_element('idbarbouze');
        $this->fail('An expected exception has not been raised.');
      }
      catch (Exception_Databox_FieldNotFound $e)
      {

      }

      $route = '/databoxes/' . $databox_id . '/metadatas/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());
      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);
      $this->assertObjectHasAttribute('metadatas', $content->response);
      foreach ($content->response->metadatas as $metadatas)
      {
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

        if ($metadatas->multivalue)
          $this->assertTrue((strlen($metadatas->separator) > 0));

        $this->assertTrue(is_string($metadatas->thesaurus_branch));
        $this->assertTrue(in_array($metadatas->type, array(databox_field::TYPE_DATE,databox_field::TYPE_STRING, databox_field::TYPE_NUMBER, databox_field::TYPE_TEXT)));
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
        $this->assertTrue($element->get_metadata_tagname() === $metadatas->tagname);
        $this->assertTrue($element->get_metadata_source() === $metadatas->source);
        $this->assertTrue($element->get_metadata_namespace() === $metadatas->namespace);
      }
    }
    $route = '/databoxes/24892534/metadatas/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/databoxes/any_bad_id/metadatas/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testDataboxTermsOfUseRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);

      $route = '/databoxes/' . $databox_id . '/termsOfUse/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());
      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $this->assertObjectHasAttribute('termsOfUse', $content->response);
      foreach ($content->response->termsOfUse as $terms)
      {
        $this->assertTrue(is_object($terms), 'Une bloc cgu est un objet');
        $this->assertObjectHasAttribute('locale', $terms);
        $this->assertTrue(in_array($terms->locale, array('fr_FR', 'en_GB', 'ar_SA', 'de_DE', 'es_ES')));
        $this->assertObjectHasAttribute('terms', $terms);
      }
    }
    $route = '/databoxes/24892534/termsOfUse/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/databoxes/any_bad_id/termsOfUse/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  /*
   *
   * End /API/V1/databoxes/DATABOX_ID/xxxxxx Routes
   *
   * **************************************************************************
   *
   * Routes /API/V1/records/DATABOX_ID/RECORD_ID/xxxxx
   *
   */

  public function testRecordsSearchRoute()
  {


    $crawler = $this->client->request('POST', '/records/search/?oauth_token=' . self::$token);
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
    /**
     * @todo null quand erreur
     */
//    $this->assertTrue(is_string($response->query_time));
    $this->assertTrue(is_string($response->search_indexes));
    $this->assertTrue(is_array($response->suggestions));
    $this->assertTrue(is_array($response->results));
    $this->assertTrue(is_string($response->query));

    foreach ($response->results as $record)
    {
      $this->evaluateGoodRecord($record);
    }
  }

  public function testRecordsMetadatasRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);

      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/metadatas/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $this->evaluateRecordsMetadataResponse($content);
      $record->delete();
    }
    $route = '/records/24892534/51654651553/metadatas/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/records/any_bad_id/sfsd5qfsd5/metadatas/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testRecordsStatusRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/status/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $this->evaluateRecordsStatusResponse($record, $content);
      $record->delete();
    }
    $route = '/records/24892534/51654651553/status/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/records/any_bad_id/sfsd5qfsd5/status/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testRecordsEmbedRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $keys = array_keys($record->get_subdefs());

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/embed/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      foreach ($content->response as $embed)
      {
        foreach ($keys as $key)
        {
          $this->assertObjectHasAttribute($key, $embed);
          $this->checkEmbed($key, $embed->$key, $record);
        }
      }
      $record->delete();
    }
    $route = '/records/24892534/51654651553/embed/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/records/any_bad_id/sfsd5qfsd5/embed/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
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
  }

  protected function checkPermalink($permalink, media_subdef $subdef)
  {
    if ($subdef->is_physically_present())
    {
      $this->assertNotNull($subdef->get_permalink());
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $permalink);
      $this->assertObjectHasAttribute("created_on", $permalink);
      $this->assertEquals($subdef->get_permalink()->get_created_on()->format(DATE_ATOM), $permalink->created_on);
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

    switch ($type_url)
    {
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
    $code = http_query::getHttpCodeFromUrl($url);
    $this->assertEquals(200, $code, sprintf('verification de url %s', $url));
  }

  public function testRecordsRelatedRoute()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/related/?oauth_token=' . self::$token;
      $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

      $crawler = $this->client->request('GET', $route);
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);
      $this->assertObjectHasAttribute("baskets", $content->response);
      foreach ($content->response->baskets as $basket)
      {
        $this->evaluateGoodBasket($basket);
      }
      $record->delete();
    }
    $route = '/records/24892534/51654651553/related/?oauth_token=' . self::$token;
    $this->evaluateNotFoundRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
    $route = '/records/any_bad_id/sfsd5qfsd5/related/?oauth_token=' . self::$token;
    $this->evaluateBadRequestRoute($route, array('GET'));
    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));
  }

  public function testRecordsSetMetadatas()
  {

    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/setmetadatas/?oauth_token=' . self::$token;
      $caption = $record->get_caption();


      $old_datas = array();
      $toupdate = array();

      /**
       * @todo enhance the test, if there's no field, there's no update
       */
      foreach ($caption->get_fields() as $field)
      {
        foreach($field->get_values() as $value)
        {
          $old_datas[$value->getId()] = $value->getValue();
          if ($field->is_readonly() === false && $field->is_multi() === false)
          {
            $toupdate[$value->getId()] = array(
                'meta_struct_id' => $field->get_meta_struct_id(),
                'meta_id' => $value->getId(),
                'value' => array($value->getValue() . ' test')
            );
          }
        }
      }
      $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

      $crawler = $this->client->request('POST', $route, array('metadatas' => $toupdate));
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $record = $databox->get_record($record_id);
      $caption = $record->get_caption();

      $this->assertEquals(count($caption->get_fields()), count($content->response->metadatas));

      foreach ($caption->get_fields() as $field)
      {
        foreach($field->get_values() as $value)
        {
          if ($field->is_readonly() === false && $field->is_multi() === false)
          {
            $saved_value = $toupdate[$value->getId()]['value'][0];
            $this->assertEquals($value->getValue(), $saved_value);
          }
        }
      }
      $this->evaluateRecordsMetadataResponse($content);

      foreach ($content->response->metadatas as $metadata)
      {
        if (!in_array($metadata->meta_id, array_keys($toupdate)))
          continue;
        $saved_value = $toupdate[$metadata->meta_id]['value'][0];
        $this->assertEquals($saved_value, $metadata->value);
      }
      $record->delete();
    }
  }

  public function testRecordsSetStatus()
  {

    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/setstatus/?oauth_token=' . self::$token;

      $record_status = strrev($record->get_status());
      $status_bits = $databox->get_statusbits();

      $tochange = array();
      foreach ($status_bits as $n => $datas)
      {
        $tochange[$n] = substr($record_status, ($n - 1), 1) == '0' ? '1' : '0';
      }
      $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));


      $crawler = $this->client->request('POST', $route, array('status' => $tochange));
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $record = $databox->get_record($record_id);
      $this->evaluateRecordsStatusResponse($record, $content);

      $record_status = strrev($record->get_status());
      foreach ($status_bits as $n => $datas)
      {
        $this->assertEquals(substr($record_status, ($n - 1), 1), $tochange[$n]);
      }

      foreach ($tochange as $n => $value)
      {
        $tochange[$n] = $value == '0' ? '1' : '0';
      }


      $crawler = $this->client->request('POST', $route, array('status' => $tochange));
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);

      $record = $databox->get_record($record_id);
      $this->evaluateRecordsStatusResponse($record, $content);

      $record_status = strrev($record->get_status());
      foreach ($status_bits as $n => $datas)
      {
        $this->assertEquals(substr($record_status, ($n - 1), 1), $tochange[$n]);
      }
      $record->delete();
    }
  }

  public function testMoveRecordToColleciton()
  {
    foreach (static::$databoxe_ids as $databox_id)
    {
      $databox = databox::get_instance($databox_id);
      $collection = array_shift($databox->get_collections());
      $system_file = new system_file(__DIR__ . '/../../../testfiles/cestlafete.jpg');

      $record = record_adapter::create($collection, $system_file);

      $record_id = $record->get_record_id();

      $route = '/records/' . $databox_id . '/' . $record_id . '/setcollection/?oauth_token=' . self::$token;

      $base_id = false;
      foreach ($databox->get_collections() as $collection)
      {
        if ($collection->get_base_id() != $record->get_base_id())
        {
          $base_id = $collection->get_base_id();
          break;
        }
      }
      if (!$base_id)
      {
        continue;
      }

      $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

      $crawler = $this->client->request('POST', $route, array('base_id' => $base_id));
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponse200($this->client->getResponse());
      $this->evaluateMetaJson200($content);
      $record->delete();
    }
  }

  public function testSearchBaskets()
  {
    $route = '/baskets/list/?oauth_token=' . self::$token;

    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

    $crawler = $this->client->request('GET', $route);
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);
    $this->assertObjectHasAttribute("baskets", $content->response);

    foreach ($content->response->baskets as $basket)
    {
      $this->evaluateGoodBasket($basket);
    }
  }

  public function testAddBasket()
  {
    $route = '/baskets/add/?oauth_token=' . self::$token;

    $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

    $crawler = $this->client->request('POST', $route, array('name' => 'un Joli Nom'));
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertEquals(1, count((array) $content->response));
    $appbox = appbox::get_instance();
    $this->assertObjectHasAttribute("basket", $content->response);

    foreach ($content->response->basket as $basket)
    {
      $this->evaluateGoodBasket($basket);
      $this->assertEquals('un Joli Nom', $basket->name);
    }
  }

  public function testBasketContent()
  {
    $appbox = appbox::get_instance();
    $usr_id = $appbox->get_session()->get_usr_id();

    $basket = $this->insertOneBasket();

    $route = '/baskets/' . $basket->getId() . '/content/?oauth_token=' . self::$token;

    $this->evaluateMethodNotAllowedRoute($route, array('POST', 'PUT', 'DELETE'));

    $crawler = $this->client->request('GET', $route);
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertEquals(1, count((array) $content->response));

    $this->assertObjectHasAttribute("basket_elements", $content->response);

    foreach ($content->response->basket_elements as $basket_str)
    {
      $this->evaluateGoodBasket($basket_str);

      $this->assertEquals(count($basket->getElements()), count((array) $basket_str->basket_elements));
      foreach ($basket_str->basket_elements as $basket_element)
      {
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

  public function testSetBasketTitle()
  {

    $basket = $this->insertOneBasket();

    $route = '/baskets/' . $basket->getId() . '/setname/?oauth_token=' . self::$token;

    $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

    $crawler = $this->client->request('POST', $route, array('name' => 'un Joli Nom'));
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertEquals(1, count((array) $content->response));
    $this->assertObjectHasAttribute("basket", $content->response);
    foreach ($content->response->basket as $basket_str)
    {
      $this->evaluateGoodBasket($basket_str);

      $this->assertEquals($basket_str->name, 'un Joli Nom');
    }

    $crawler = $this->client->request('POST', $route, array('name' => '<i>un Joli Nom<i>'));
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertEquals(1, count((array) $content->response));

    $this->assertObjectHasAttribute("basket", $content->response);

    foreach ($content->response->basket as $basket)
    {
      $this->evaluateGoodBasket($basket_str);

      $this->assertEquals($basket_str->name, 'un Joli Nom');
    }

    $crawler = $this->client->request('POST', $route, array('name' => '<strong>aéaa'));
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertEquals(1, count((array) $content->response));
    $this->assertObjectHasAttribute("basket", $content->response);
    foreach ($content->response->basket as $basket_str)
    {
      $this->evaluateGoodBasket($basket_str);
      $this->assertEquals($basket_str->name, '<strong>aéaa');
    }

  }

  public function testSetBasketDescription()
  {
    $basket = $this->insertOneBasket();

    $route = '/baskets/' . $basket->getId() . '/setdescription/?oauth_token=' . self::$token;

    $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

    $crawler = $this->client->request('POST', $route, array('description' => 'une belle desc'));
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertEquals(1, count((array) $content->response));

    $this->assertObjectHasAttribute("basket", $content->response);
    foreach ($content->response->basket as $basket_str)
    {
      $this->evaluateGoodBasket($basket_str);

      $this->assertEquals($basket_str->description, 'une belle desc');
    }


  }

  public function testDeleteBasket()
  {
    $baskets = $this->insertFiveBasket();

    $route = '/baskets/' . $baskets[0]->getId() . '/delete/?oauth_token=' . self::$token;

    $this->evaluateMethodNotAllowedRoute($route, array('GET', 'PUT', 'DELETE'));

    $crawler = $this->client->request('POST', $route);
    $content = json_decode($this->client->getResponse()->getContent());

    $this->evaluateResponse200($this->client->getResponse());
    $this->evaluateMetaJson200($content);

    $this->assertObjectHasAttribute("baskets", $content->response);

    $found = false;
    foreach ($content->response->baskets as $basket)
    {
      $this->evaluateGoodBasket($basket);
      $found = true;
    }
    if(!$found)
    {
      $this->fail('There should be four baskets left');
    }
  }

  /**
   *
   * End /API/V1/records/DATABOX_ID/RECORD_ID/xxxxx Routes
   *
   */
  protected function evaluateNotFoundRoute($route, $methods)
  {
    foreach ($methods as $method)
    {
      $crawler = $this->client->request($method, $route);
      $content = json_decode($this->client->getResponse()->getContent());

      $this->evaluateResponseNotFound($this->client->getResponse());
      $this->evaluateMetaJsonNotFound($content);
    }
  }

  protected function evaluateMethodNotAllowedRoute($route, $methods)
  {
    foreach ($methods as $method)
    {
      $crawler = $this->client->request($method, $route);
      $content = json_decode($this->client->getResponse()->getContent());
      $this->evaluateResponseMethodNotAllowed($this->client->getResponse());
      $this->evaluateMetaJsonMethodNotAllowed($content);
    }
  }

  protected function evaluateBadRequestRoute($route, $methods)
  {
    foreach ($methods as $method)
    {
      $crawler = $this->client->request($method, $route);
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
    $this->assertEquals('1.0', $content->meta->api_version);
    $this->assertNotNull($content->meta->response_time);
    $this->assertEquals('UTF-8', $content->meta->charset);
  }

  protected function evaluateMetaJson200($content)
  {
    $this->evaluateMetaJson($content);
    $this->assertEquals(200, $content->meta->http_code);
    $this->assertNull($content->meta->error_message);
    $this->assertNull($content->meta->error_details);
  }

  protected function evaluateMetaJsonBadRequest($content)
  {
    $this->evaluateMetaJson($content);
    $this->assertNotNull($content->meta->error_message);
    $this->assertNotNull($content->meta->error_details);
    $this->assertEquals(400, $content->meta->http_code);
  }

  protected function evaluateMetaJsonNotFound($content)
  {
    $this->evaluateMetaJson($content);
    $this->assertNotNull($content->meta->error_message);
    $this->assertNotNull($content->meta->error_details);
    $this->assertEquals(404, $content->meta->http_code);
  }

  protected function evaluateMetaJsonMethodNotAllowed($content)
  {
    $this->evaluateMetaJson($content);
    $this->assertNotNull($content->meta->error_message);
    $this->assertNotNull($content->meta->error_details);
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


    if (!is_null($basket->pusher_usr_id))
    {
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

    foreach ($record->technical_informations as $key => $value)
    {
      switch ($key)
      {
        case system_file::TC_DATAS_DURATION:
        case 'size':
        case system_file::TC_DATAS_WIDTH:
        case system_file::TC_DATAS_HEIGHT:
        case system_file::TC_DATAS_COLORDEPTH:
          $this->assertTrue(is_int($value), 'test technical data ' . $key . ' ' . $value);
          break;
        default;
          $this->assertTrue(is_string($value), 'test technical data ' . $key);
          break;
      }
    }
  }

  protected function evaluateRecordsMetadataResponse($content)
  {
    $this->assertObjectHasAttribute("metadatas", $content->response);
    foreach ($content->response->metadatas as $meta)
    {
      $this->assertTrue(is_object($meta->metadatas), 'Un bloc meta est un objet');
      $this->assertObjectHasAttribute('meta_id', $meta);
      $this->assertTrue(is_int($meta->meta_id));
      $this->assertObjectHasAttribute('meta_structure_id', $meta);
      $this->assertTrue(is_int($meta->meta_structure_id));
      $this->assertObjectHasAttribute('name', $meta);
      $this->assertTrue(is_string($meta->name));
      $this->assertObjectHasAttribute('value', $meta);

      if (is_array($meta->value))
      {
        foreach ($meta->value as $val)
        {
          $this->assertTrue(is_string($val));
        }
      }
      else
      {
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
    foreach ($content->response->status as $status)
    {
      $this->assertTrue(is_object($status));
      $this->assertObjectHasAttribute('bit', $status);
      $this->assertObjectHasAttribute('state', $status);
      $this->assertTrue(is_int($status->bit));
      $this->assertTrue(is_bool($status->state));

      $retrieved = !!substr($r_status, ($status->bit - 1), 1);

      $this->assertEquals($retrieved, $status->state);
    }
  }

}
