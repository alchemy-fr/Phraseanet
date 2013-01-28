<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

class BasketsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
       $nbBasketsBefore = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b')->getSingleScalarResult();
       self::$DI['client']->request("POST", "/client/baskets/new/", array('p0' => 'hello'));
       $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
       $nbBasketsAfter = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b')->getSingleScalarResult();
       $this->assertGreaterThan($nbBasketsBefore,$nbBasketsAfter);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::addElementToBasket
     */
    public function testAddElementToBasket()
    {
        $basket = $this->insertOneBasket();
        self::$DI['client']->request("POST", "/client/baskets/add-element/", array(
            'courChuId'  => $basket->getId(),
            'sbas'       => self::$DI['record_1']->get_sbas_id(),
            'p0'         => self::$DI['record_1']->get_record_id()
        ));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());
        $this->assertGreaterThan(0, $basket->getElements()->count());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::deleteBasket
     */
    public function testDeleteBasket()
    {
        $basket = $this->insertOneBasket();
        self::$DI['client']->request("POST", "/client/baskets/delete/", array(
            'courChuId'  => $basket->getId()
        ));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        try {
            $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());
            $this->fail('Basket is not deleted');
        } catch (\exception $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Baskets::deleteBasketElement
     */
    public function testDeleteBasketElement()
    {
        $basket = $this->insertOneBasket();

        $record = self::$DI['record_1'];

        $basketElement = new \Entities\BasketElement();
        $basketElement->setBasket($basket);
        $basketElement->setRecord($record);
        $basketElement->setLastInBasket();

        $basket->addBasketElement($basketElement);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request("POST", "/client/baskets/delete-element/", array(
            'p0'  => $basketElement->getId()
        ));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertEquals(0, $basket->getElements()->count());
    }
}
