<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/Prod/UsrLists.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ControllerTooltipTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = 1;
  protected static $need_subdefs = true;
  protected static $need_story = 1;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRouteBasket()
  {
    $appbox = appbox::get_instance();

    $basket = $this->insertOneBasket();

    $crawler = $this->client->request('POST', '/tooltip/basket/' . $basket->getId() . '/');
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());

    $crawler = $this->client->request('POST', '/tootltip/basket/notanid/');
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertFalse($this->client->getResponse()->isOk());

    $crawler = $this->client->request('POST', '/tooltip/basket/-5/');
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertFalse($this->client->getResponse()->isOk());
  }

  public function testRoutePreview()
  {
    $route = '/tooltip/preview/' . self::$record_1->get_sbas_id()
            . '/' . self::$record_1->get_record_id() . '/';

    $crawler = $this->client->request('POST', $route);
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());
  }

  public function testRouteCaption()
  {

    $route_base = '/tooltip/caption/' . self::$record_1->get_sbas_id()
            . '/' . self::$record_1->get_record_id() . '/%s/';

    $routes = array(
        sprintf($route_base, 'answer')
        , sprintf($route_base, 'lazaret')
        , sprintf($route_base, 'preview')
        , sprintf($route_base, 'basket')
        , sprintf($route_base, 'overview')
    );

    foreach ($routes as $route)
    {
      $crawler = $this->client->request('POST', $route);
      $pageContent = $this->client->getResponse()->getContent();
      $this->assertTrue($this->client->getResponse()->isOk());
    }
  }

  public function testRouteCaptionSearchEngine()
  {
    $route_base = '/tooltip/caption/' . self::$record_1->get_sbas_id()
            . '/' . self::$record_1->get_record_id() . '/%s/';

    $routes = array(
        sprintf($route_base, 'answer')
        , sprintf($route_base, 'lazaret')
        , sprintf($route_base, 'preview')
        , sprintf($route_base, 'basket')
        , sprintf($route_base, 'overview')
    );

    foreach ($routes as $route)
    {
      $option = new \searchEngine_options();
      $crawler = $this->client->request('POST', $route, array('options_serial' => serialize($option)));

      $this->assertTrue($this->client->getResponse()->isOk());
    }
  }

  public function testRouteTCDatas()
  {
    $route = '/tooltip/tc_datas/' . self::$record_1->get_sbas_id()
            . '/' . self::$record_1->get_record_id() . '/';

    $crawler = $this->client->request('POST', $route);
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());
  }

  public function testRouteMetasFieldInfos()
  {
    $databox = self::$record_1->get_databox();

    foreach ($databox->get_meta_structure() as $field)
    {
      $route = '/tooltip/metas/FieldInfos/' . $databox->get_sbas_id()
              . '/' . $field->get_id() . '/';

      $crawler = $this->client->request('POST', $route);
      $pageContent = $this->client->getResponse()->getContent();
      $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
  }

  public function testRouteMetasDCESInfos()
  {
    $databox = self::$record_1->get_databox();
    $dces = array(
        databox_field::DCES_CONTRIBUTOR => new databox_Field_DCES_Contributor()
        , databox_field::DCES_COVERAGE => new databox_Field_DCES_Coverage()
        , databox_field::DCES_CREATOR => new databox_Field_DCES_Creator()
        , databox_field::DCES_DESCRIPTION => new databox_Field_DCES_Description()
    );

    foreach ($databox->get_meta_structure() as $field)
    {
      $dces_element = array_shift($dces);
      $field->set_dces_element($dces_element);

      $route = '/tooltip/DCESInfos/' . $databox->get_sbas_id()
              . '/' . $field->get_id() . '/';

      if ($field->get_dces_element() !== null)
      {
        $crawler = $this->client->request('POST', $route);
        $this->assertGreaterThan(0, strlen($this->client->getResponse()->getContent()));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
      }
      else
      {
        $crawler = $this->client->request('POST', $route);
        $this->assertEquals(0, strlen($this->client->getResponse()->getContent()));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
      }
    }
  }

  public function testRouteMetaRestrictions()
  {
    $databox = self::$record_1->get_databox();

    foreach ($databox->get_meta_structure() as $field)
    {

      $route = '/tooltip/metas/restrictionsInfos/' . $databox->get_sbas_id()
              . '/' . $field->get_id() . '/';

      $crawler = $this->client->request('POST', $route);
      $this->assertGreaterThan(0, strlen($this->client->getResponse()->getContent()));
      $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
  }

  public function testRouteStory()
  {
    $databox = self::$story_1->get_databox();


    $route = '/tooltip/Story/' . $databox->get_sbas_id()
            . '/' . self::$story_1->get_record_id() . '/';

    $this->client->request('POST', $route);
    $this->assertTrue($this->client->getResponse()->isOk());
  }

  public function testUser()
  {

    $route = '/tooltip/user/' . self::$user->get_id() . '/';
    $this->client->request('POST', $route);
    $this->assertTrue($this->client->getResponse()->isOk());
  }

}
