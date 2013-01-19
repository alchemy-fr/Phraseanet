<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class ControllerTooltipTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRouteBasket()
    {

        $basket = $this->insertOneBasket();

        $crawler = self::$DI['client']->request('POST', '/prod/tooltip/basket/' . $basket->getId() . '/');
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRouteBasketFail()
    {
        $crawler = self::$DI['client']->request('POST', '/prod/tooltip/basket/notanid/');
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertFalse(self::$DI['client']->getResponse()->isOk());

    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRouteBasketFail2()
    {
        $crawler = self::$DI['client']->request('POST', '/prod/tooltip/basket/-5/');
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertFalse(self::$DI['client']->getResponse()->isOk());
    }

    public function testRoutePreview()
    {
        $route = '/prod/tooltip/preview/' . self::$DI['record_1']->get_sbas_id()
            . '/' . self::$DI['record_1']->get_record_id() . '/';

        $crawler = self::$DI['client']->request('POST', $route);
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testRouteCaption()
    {

        $route_base = '/prod/tooltip/caption/' . self::$DI['record_1']->get_sbas_id()
            . '/' . self::$DI['record_1']->get_record_id() . '/%s/';

        $routes = array(
            sprintf($route_base, 'answer')
            , sprintf($route_base, 'lazaret')
            , sprintf($route_base, 'preview')
            , sprintf($route_base, 'basket')
            , sprintf($route_base, 'overview')
        );

        foreach ($routes as $route) {
            $crawler = self::$DI['client']->request('POST', $route);
            $pageContent = self::$DI['client']->getResponse()->getContent();
            $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        }
    }

    public function testRouteCaptionSearchEngine()
    {
        $route_base = '/prod/tooltip/caption/' . self::$DI['record_1']->get_sbas_id()
            . '/' . self::$DI['record_1']->get_record_id() . '/%s/';

        $routes = array(
            sprintf($route_base, 'answer')
            , sprintf($route_base, 'lazaret')
            , sprintf($route_base, 'preview')
            , sprintf($route_base, 'basket')
            , sprintf($route_base, 'overview')
        );

        foreach ($routes as $route) {
            $option = new SearchEngineOptions();
            $crawler = self::$DI['client']->request('POST', $route, array('options_serial' => $option->serialize()));

            $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        }
    }

    public function testRouteTCDatas()
    {
        $route = '/prod/tooltip/tc_datas/' . self::$DI['record_1']->get_sbas_id()
            . '/' . self::$DI['record_1']->get_record_id() . '/';

        $crawler = self::$DI['client']->request('POST', $route);
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testRouteMetasFieldInfos()
    {
        $databox = self::$DI['record_1']->get_databox();

        foreach ($databox->get_meta_structure() as $field) {
            $route = '/prod/tooltip/metas/FieldInfos/' . $databox->get_sbas_id()
                . '/' . $field->get_id() . '/';

            $crawler = self::$DI['client']->request('POST', $route);
            $pageContent = self::$DI['client']->getResponse()->getContent();
            $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        }
    }

    public function testRouteMetasDCESInfos()
    {
        $databox = self::$DI['record_1']->get_databox();
        $dces = array(
            \databox_field::DCES_CONTRIBUTOR => new \databox_Field_DCES_Contributor()
            , \databox_field::DCES_COVERAGE    => new \databox_Field_DCES_Coverage()
            , \databox_field::DCES_CREATOR     => new \databox_Field_DCES_Creator()
            , \databox_field::DCES_DESCRIPTION => new \databox_Field_DCES_Description()
        );

        foreach ($databox->get_meta_structure() as $field) {
            $dces_element = array_shift($dces);
            $field->set_dces_element($dces_element);

            $route = '/prod/tooltip/DCESInfos/' . $databox->get_sbas_id()
                . '/' . $field->get_id() . '/';

            $crawler = self::$DI['client']->request('POST', $route);
            $node = $crawler->filter('div.popover-content');
            $found = trim($node->count());

            $this->assertEquals(1, $found);

            $node = $crawler->filter('div.popover-content *');

            if ($field->get_dces_element() !== null) {
                $this->assertGreaterThan(0, $node->count());
                $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
            } else {
                $this->assertEquals(0, $node->count());
                $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
            }
        }
    }

    public function testRouteMetaRestrictions()
    {
        $databox = self::$DI['record_1']->get_databox();

        foreach ($databox->get_meta_structure() as $field) {

            $route = '/prod/tooltip/metas/restrictionsInfos/' . $databox->get_sbas_id()
                . '/' . $field->get_id() . '/';

            $crawler = self::$DI['client']->request('POST', $route);
            $this->assertGreaterThan(0, strlen(self::$DI['client']->getResponse()->getContent()));
            $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        }
    }

    public function testRouteStory()
    {
        $databox = self::$DI['record_story_1']->get_databox();


        $route = '/prod/tooltip/Story/' . $databox->get_sbas_id()
            . '/' . self::$DI['record_story_1']->get_record_id() . '/';

        self::$DI['client']->request('POST', $route);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testUser()
    {

        $route = '/prod/tooltip/user/' . self::$DI['user']->get_id() . '/';
        self::$DI['client']->request('POST', $route);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
