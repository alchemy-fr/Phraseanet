<?php

namespace Alchemy\Tests\Phrasea\Application;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class LightboxTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    protected $feed;
    protected $entry;
    protected $item;
    protected $validation_basket;

    public function setUp()
    {
        parent::setUp();

        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testRouteSlash()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->authenticate($app);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);

        $crawler = $client->request('GET', '/lightbox/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
        $this->assertCount(3, $crawler->filter('div.basket_wrapper'));

        $this->set_user_agent(self::USER_AGENT_IE6, $app);

        $crawler = $client->request('GET', '/lightbox/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
        $this->assertCount(3, $crawler->filter('div.basket_wrapper'));

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);

        $client->request('GET', '/lightbox/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
    }

    public function testAuthenticationWithToken()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->logout($app);

        $Basket = $app['orm.em']->find('Phraseanet:Basket', 1);
        $token = $app['manipulator.token']->createBasketAccessToken($Basket, self::$DI['user_alt2']);

        $client->request('GET', '/lightbox/?LOG='.$token->getValue());

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertRegExp('/\/lightbox\/validate\/\d+\//', $client->getResponse()->headers->get('location'));
    }

    public function testAjaxNoteForm()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $basket = $app['orm.em']->find('Phraseanet:Basket', 4);
        $basket_element = $basket->getELements()->first();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);

        $client->request('GET', '/lightbox/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('', trim($client->getResponse()->getContent()));

        $this->set_user_agent(self::USER_AGENT_IE6, $app);

        $client->request('GET', '/lightbox/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('', trim($client->getResponse()->getContent()));

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);

        $client->request('GET', '/lightbox/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotEquals('', trim($client->getResponse()->getContent()));
    }

    public function testAjaxElement()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $basket_element = $app['orm.em']->find('Phraseanet:BasketElement', 1);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);

        $client->request('GET', '/lightbox/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));
        $datas = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IE6, $app);

        $client->request('GET', '/lightbox/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));
        $datas = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);

        $client->request('GET', '/lightbox/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotEquals('application/json', $client->getResponse()->headers->get('Content-type'));
    }

    public function testAjaxFeedItem()
    {
        $this->markTestSkipped("Review this test that always fail");

        $app = $this->getApplication();
        $client = $this->getClient();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);

        $feed = $app['orm.em']->find('Phraseanet:Feed', 1);
        $entry = $feed->getEntries()->first();
        $item = $entry->getItems()->first();

        $client->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $entry->getId() . '/' . $item->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));
        $datas = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IE6, $app);

        $client->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $entry->getId() . '/' . $item->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));
        $datas = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);

        $client->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $entry->getId() . '/' . $item->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotEquals('application/json', $client->getResponse()->headers->get('Content-type'));
    }

    public function testValidate()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->authenticate($app);
        $basket = $app['orm.em']->find('Phraseanet:Basket', 4);
        $path = $app['url_generator']->generate('lightbox_validation', [
            'basket' => $basket->getId()
        ]);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client->request('GET', $path);

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, $app);
        $client->request('GET', $path);

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);
        $client->request('GET', $path);

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
    }

    public function testCompare()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->authenticate($app);

        $basket = $app['orm.em']->find('Phraseanet:Basket', 1);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);

        $client->request('GET', '/lightbox/compare/' . $basket->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, $app);

        $client->request('GET', '/lightbox/compare/' . $basket->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);

        $client->request('GET', '/lightbox/compare/' . $basket->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
    }

    public function testFeedEntry()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $this->authenticate($app);
        $entry = $app['orm.em']->find('Phraseanet:Feed', 1)->getEntries()->first();
        $path = $app['url_generator']->generate('lightbox_feed_entry', [
            'entry_id' => $entry->getId()
        ]);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client->request('GET', $path);

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, $app);
        $client->request('GET', $path);

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, $app);
        $client->request('GET', $path);

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
    }

    public function testAjaxReport()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $validationBasket = $app['orm.em']->find('Phraseanet:Basket', 4);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, $app);
        $client->request('GET', '/lightbox/ajax/LOAD_REPORT/' . $validationBasket->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
    }

    public function testAjaxSetNote()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $validationBasket = $app['orm.em']->find('Phraseanet:Basket', 4);
        $validationBasketElement = $validationBasket->getElements()->first();

        $randomValue = $this->setSessionFormToken('lightbox');

        $client->request('POST', '/lightbox/ajax/SET_NOTE/' . $validationBasketElement->getId() . '/', ['lightbox_token'  => $randomValue]);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $client->request(
            'POST'
            , '/lightbox/ajax/SET_NOTE/' . $validationBasketElement->getId() . '/'
            , ['note' => 'une jolie note', 'lightbox_token'  => $randomValue]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));

        $datas = json_decode($client->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }

    public function testAjaxSetAgreement()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $validationBasket = $app['orm.em']->find('Phraseanet:Basket', 4);
        $validationBasketElement = $validationBasket->getElements()->first();

        $client->request(
            'POST'
            , '/lightbox/ajax/SET_ELEMENT_AGREEMENT/' . $validationBasketElement->getId() . '/'
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $client->request(
            'POST'
            , '/lightbox/ajax/SET_ELEMENT_AGREEMENT/' . $validationBasketElement->getId() . '/'
            , ['agreement' => 1]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));

        $datas = json_decode($client->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }

    public function testAjaxSetReleaseWithRegularBasket()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $basket = $app['orm.em']->find('Phraseanet:Basket', 1);

        $client->request('POST', '/lightbox/ajax/SET_RELEASE/' . $basket->getId() . '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));
        $datas = json_decode($client->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertTrue($datas->error);
    }

    public function testAjaxSetReleaseWithRegularBasketWithValidation()
    {
        $app = $this->getApplication();
        $client = $this->getClient();
        $validationBasket = $app['orm.em']->find('Phraseanet:Basket', 4);

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone');
        $this->mockUserNotificationSettings('eventsmanager_notify_validationdone');

        foreach ($validationBasket->getElements() as $element) {
            $element->getUserVote(self::$DI['user'], true)->setAgreement(true);
            break;
        }

        $client->request('POST', '/lightbox/ajax/SET_RELEASE/' . $validationBasket->getId() . '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasket->getId()));
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-type'));

        $datas = json_decode($client->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }
}
