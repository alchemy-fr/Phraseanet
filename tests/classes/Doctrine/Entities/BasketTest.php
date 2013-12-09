<?php

class EntityBasketTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     *
     * @var \Alchemy\Phrasea\Model\Entities\Basket
     */
    protected $basket;

    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    public function setUp()
    {
        parent::setUp();
        $this->em = self::$DI['app']['EM'];
        $this->basket = $this->insertOneBasket();
    }

    public function testGetId()
    {
        $this->assertTrue(is_int($this->basket->getId()));
        $otherBasket = $this->insertOneBasket();
        $this->assertGreaterThan($this->basket->getId(), $otherBasket->getId());
    }

    public function testGetName()
    {
        $this->basket->setName('one name');
        $this->em->persist($this->basket);
        $this->em->flush();
        $this->assertEquals('one name', $this->basket->getName());
    }

    public function testGetDescription()
    {
        $this->basket->setDescription('une jolie description pour mon super panier');
        $this->em->persist($this->basket);
        $this->em->flush();
        $this->assertEquals('une jolie description pour mon super panier', $this->basket->getDescription());
    }

    public function testGetUsrId()
    {
        $this->basket->setUsrId(1);
        $this->em->persist($this->basket);
        $this->em->flush();
        $this->assertEquals(1, $this->basket->getUsrId());
    }

    public function testGetPusherId()
    {
        $this->basket->setPusherId(1);
        $this->em->persist($this->basket);
        $this->em->flush();
        $this->assertEquals(1, $this->basket->getPusherId());
    }

    public function testGetArchived()
    {
        $this->basket->setArchived(true);
        $this->em->persist($this->basket);
        $this->em->flush();
        $this->assertTrue($this->basket->GetArchived());
        $this->basket->setArchived(false);
        $this->em->persist($this->basket);
        $this->em->flush();
        $this->assertFalse($this->basket->GetArchived());
    }

    public function testGetCreated()
    {
        $date = $this->basket->getCreated();
        $this->assertInstanceOf('\DateTime', $date);
    }

    public function testGetUpdated()
    {
        $date = $this->basket->getUpdated();
        $this->assertInstanceOf('\DateTime', $date);
    }

    public function testGetElements()
    {
        $elements = $this->basket->getElements();

        $this->assertInstanceOf('\Doctrine\ORM\PersistentCollection', $elements);

        $this->assertEquals(0, $elements->count());

        $basketElement = new \Alchemy\Phrasea\Model\Entities\BasketElement();

        $basketElement->setRecord(self::$DI['record_1']);

        $basketElement->setBasket($this->basket);

        $this->em->persist($basketElement);

        $this->em->flush();

        $this->em->refresh($this->basket);

        $this->assertEquals(1, $this->basket->getElements()->count());
    }

    public function testGetPusher()
    {
        $this->assertNull($this->basket->getPusher(self::$DI['app'])); //no pusher
        $this->basket->setPusherId(self::$DI['user']->get_id());
        $this->assertInstanceOf('\User_Adapter', $this->basket->getPusher(self::$DI['app']));
        $this->assertEquals($this->basket->getPusher(self::$DI['app'])->get_id(), self::$DI['user']->get_id());
    }

    public function testGetOwner()
    {
        $this->assertNotNull($this->basket->getOwner(self::$DI['app'])); //no owner
        $this->basket->setUsrId(self::$DI['user']->get_id());
        $this->assertInstanceOf('\User_Adapter', $this->basket->getOwner(self::$DI['app']));
        $this->assertEquals($this->basket->getOwner(self::$DI['app'])->get_id(), self::$DI['user']->get_id());
    }

    public function testGetValidation()
    {
        $this->assertNull($this->basket->getValidation());

        $validationSession = new \Alchemy\Phrasea\Model\Entities\ValidationSession();
        $validationSession->setBasket($this->basket);

        $expires = new \DateTime();
        $expires->modify('+1 week');

        $validationSession->setExpires($expires);

        $validationSession->setInitiator(self::$DI['user']);

        $this->em->persist($validationSession);

        $this->em->flush();

        $this->em->refresh($this->basket);

        $this->assertInstanceOf('\Alchemy\Phrasea\Model\Entities\ValidationSession', $this->basket->getValidation());
    }

    public function testGetIsRead()
    {
        $this->markTestIncomplete();
    }

    public function testGetSize()
    {
        $this->markTestIncomplete();
    }

    public function hasRecord()
    {
        $this->markTestIncomplete();
    }
}
