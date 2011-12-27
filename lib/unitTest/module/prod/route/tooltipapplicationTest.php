<?php

require_once dirname(__FILE__) . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once dirname(__FILE__) . '/../../../../bootstrap.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class Module_Prod_Route_TooltipTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = 1;
  protected static $need_subdefs = true;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function createApplication()
  {
    return require dirname(__FILE__) . '/../../../../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRouteBasket()
  {
    $appbox = appbox::get_instance();
    $basketcoll = new basketCollection($appbox, self::$user->get_id());
    $basket_coll = $basketcoll->get_baskets();

    $basket = array_shift($basket_coll['baskets']);

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

      $route = '/tooltip/metas/DCESInfos/' . $databox->get_sbas_id()
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

}
