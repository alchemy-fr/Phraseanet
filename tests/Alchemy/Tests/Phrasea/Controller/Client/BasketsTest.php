<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

class BasketsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::connect
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::call
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::getBaskets
     */
    public function testGetClientBaskets()
    {
       self::$DI['client']->request("GET", "/client/baskets/");
       $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::getBaskets
     */
    public function testPostClientBaskets()
    {
       self::$DI['client']->request("POST", "/client/baskets/");
       $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::createBasket
     */
    public function testCreateBasket()
    {
       $nbBasketsBefore = self::$DI['app']['orm.em']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b')->getSingleScalarResult();
       self::$DI['client']->request("POST", "/client/baskets/new/", ['p0' => 'hello']);
       $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
       $nbBasketsAfter = self::$DI['app']['orm.em']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b')->getSingleScalarResult();
       $this->assertGreaterThan($nbBasketsBefore,$nbBasketsAfter);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::addElementToBasket
     */
    public function testAddElementToBasket()
    {
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);
        self::$DI['client']->request("POST", "/client/baskets/add-element/", [
            'courChuId'  => $basket->getId(),
            'sbas'       => self::$DI['record_1']->get_sbas_id(),
            'p0'         => self::$DI['record_1']->get_record_id()
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $basket = self::$DI['app']['orm.em']->getRepository('Phraseanet:Basket')->find($basket->getId());
        $this->assertGreaterThan(0, $basket->getElements()->count());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::deleteBasket
     */
    public function testDeleteBasket()
    {
        self::$DI['client']->request("POST", "/client/baskets/delete/", [
            'courChuId'  => 1
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        try {
            $basket = self::$DI['app']['orm.em']->getRepository('Phraseanet:Basket')->find(1);
            $this->fail('Basket is not deleted');
        } catch (\Exception $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::deleteBasketElement
     */
    public function testDeleteBasketElement()
    {
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);
        $basketElement = self::$DI['app']['orm.em']->find('Phraseanet:BasketElement', 1);

        self::$DI['client']->request("POST", "/client/baskets/delete-element/", [
            'p0'  => $basketElement->getId()
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertEquals(0, $basket->getElements()->count());
    }
}
