<?php

namespace Alchemy\Tests\Phrasea\Application;

class ApplicationLightboxTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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

        self::$DI['app']['notification.deliverer']->expects($this->atLeastOnce())
            ->method('deliver')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication'), $this->equalTo(null));

        $this->feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');
        $publishers = $this->feed->get_publishers();
        $publisher = array_shift($publishers);
        $this->entry = \Feed_Entry_Adapter::create(self::$DI['app'], $this->feed, $publisher, 'title', "sub Titkle", " jean pierre", "jp@test.com");
        $this->item = \Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], $this->entry, self::$DI['record_1']);
    }

    public function tearDown()
    {
        if ($this->feed instanceof \Feed_Adapter)
            $this->feed->delete();
        parent::tearDown();
    }

    public function testRouteSlash()
    {
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

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
        self::$DI['app']->closeAccount();

        $Basket = $this->insertOneBasket();
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_VIEW, self::$DI['user_alt2']->get_id(), null, $Basket->getId());

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

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $this->entry->get_id() . '/' . $this->item->get_id() . '/');
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

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $this->entry->get_id() . '/' . $this->item->get_id() . '/');
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

        $crawler = self::$DI['client']->request('GET', '/lightbox/ajax/LOAD_FEED_ITEM/' . $this->entry->get_id() . '/' . $this->item->get_id() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertNotEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
    }

    public function testValidate()
    {
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

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
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

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
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/feeds/entry/' . $this->entry->get_id() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IE6, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/feeds/entry/' . $this->entry->get_id() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('UTF-8', self::$DI['client']->getResponse()->getCharset());

        $this->set_user_agent(self::USER_AGENT_IPHONE, self::$DI['app']);

        $crawler = self::$DI['client']->request('GET', '/lightbox/feeds/entry/' . $this->entry->get_id() . '/');
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
            , array('note' => 'une jolie note')
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
            , array('agreement' => 1)
        );

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));

        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }

    public function testAjaxSetRelease()
    {
        $basket = $this->insertOneBasket();

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone');

        $crawler = self::$DI['client']->request('POST', '/lightbox/ajax/SET_RELEASE/' . $basket->getId() . '/');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertTrue($datas->error);

        $validationBasket = $this->insertOneValidationBasket();

        $crawler = self::$DI['client']->request('POST', '/lightbox/ajax/SET_RELEASE/' . $validationBasket->getId() . '/');

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasket->getId()));
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('Content-type'));

        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($datas), 'asserting good json datas');
        $this->assertObjectHasAttribute('datas', $datas);
        $this->assertObjectHasAttribute('error', $datas);
    }
}
