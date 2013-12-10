<?php

namespace Alchemy\Tests\Phrasea\Application;

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

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testRouteSlash()
    {
        $this->authenticate(self::$DI['app']);

        $baskets = $this->insertFiveBasket();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
        $this->assertEquals(5, $crawler->filter('div.basket_wrapper')->count());

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
        $this->assertEquals($crawler->filter('div.basket_wrapper')->count(), count($baskets));

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
    }

    public function testAuthenticationWithToken()
    {
        $this->logout(self::$DI['app']);

        $Basket = $this->insertOneBasket();
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_VIEW, self::$DI['user_alt2']->get_id(), null, $Basket->getId());

        self::$DI['client']->request('GET', '/lightbox/?LOG='.$token);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/\/lightbox\/validate\/\d+\//', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testAjaxNoteForm()
    {
        $basket = $this->insertOneValidationBasket();
        $basket_element = $basket->getELements()->first();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('', trim(self::$DI['client']->getResponse()->getContent()));

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('', trim(self::$DI['client']->getResponse()->getContent()));

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertNotEquals('', trim(self::$DI['client']->getResponse()->getContent()));
    }

    public function testAjaxElement()
    {
        $basket_element = $this->insertOneBasketElement();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertNotEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
    }

    public function testAjaxFeedItem()
    {
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $item = $this->insertOneFeedItem(self::$DI['user']);
        $entry = $item->getEntry();

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $entry->getId() . '/' . $item->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $entry->getId() . '/' . $item->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertObjectHasAttribute('number', $datas);
        $this->assertObjectHasAttribute('title', $datas);
        $this->assertObjectHasAttribute('preview', $datas);
        $this->assertObjectHasAttribute('options_html', $datas);
        $this->assertObjectHasAttribute('agreement_html', $datas);
        $this->assertObjectHasAttribute('selector_html', $datas);
        $this->assertObjectHasAttribute('note_html', $datas);
        $this->assertObjectHasAttribute('caption', $datas);

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $entry->getId() . '/' . $item->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertNotEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
    }

    public function testValidate()
    {
        $this->authenticate(self::$DI['app']);

        $basket = $this->insertOneValidationBasket();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/validate/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/validate/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/validate/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
    }

    public function testCompare()
    {
        $this->authenticate(self::$DI['app']);

        $basket = $this->insertOneBasket();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/compare/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/compare/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/compare/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
    }

    public function testFeedEntry()
    {
        $this->authenticate(self::$DI['app']);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $item = $this->insertOneFeedItem(self::$DI['user']);
        $entry = $item->getEntry();

        $crawler = self::$DI['client']->request('GET', '/lightbox/feeds/entry/' . $entry->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/feeds/entry/' . $entry->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/feeds/entry/' . $entry->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
    }

    public function testAjaxReport()
    {
        $validationBasket = $this->insertOneValidationBasket();

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_REPORT/' . $validationBasket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());
    }

    public function testAjaxSetNote()
    {
        $validationBasket = $this->insertOneValidationBasket();
        $validationBasketElement = $validationBasket->getElements()->first();

        $crawler = self::$DI['client']->request('POST', '/lightbox/ajax/SET_NOTE/' . $validationBasketElement->getId() . '/');
        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());

        $crawler = self::$DI['client']->request(
            'POST'
            , '/lightbox/ajax/SET_NOTE/' . $validationBasketElement->getId() . '/'
            , ['note' => 'une jolie note']
        );

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));

        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }

    public function testAjaxSetAgreement()
    {
        $validationBasket = $this->insertOneValidationBasket();
        $validationBasketElement = $validationBasket->getElements()->first();

        $crawler = self::$DI['client']->request(
            'POST'
            , '/lightbox/ajax/SET_ELEMENT_AGREEMENT/' . $validationBasketElement->getId() . '/'
        );
        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());

        $crawler = self::$DI['client']->request(
            'POST'
            , '/lightbox/ajax/SET_ELEMENT_AGREEMENT/' . $validationBasketElement->getId() . '/'
            , ['agreement' => 1]
        );

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));

        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }

    public function testAjaxSetReleaseWithRegularBasket()
    {
        $basket = $this->insertOneBasket();

        $crawler = self::$DI['client']->request('POST', '/lightbox/ajax/SET_RELEASE/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertTrue($datas->error);
    }

    public function testAjaxSetReleaseWithRegularBasketWithValidation()
    {
        $validationBasket = $this->insertOneValidationBasket();

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone');

        foreach ($validationBasket->getElements() as $element) {
            $element->getUserValidationDatas(self::$DI['app']['authentication']->getUser(), self::$DI['app'])->setAgreement(true);
            break;
        }

        $crawler = self::$DI['client']->request('POST', '/lightbox/ajax/SET_RELEASE/' . $validationBasket->getId() . '/');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasket->getId()));
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));

        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }
}
