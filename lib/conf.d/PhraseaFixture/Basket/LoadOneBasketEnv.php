<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\Basket;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadOneBasketEnv extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var \Entities\Basket
     */
    public $basket;

    /**
     *
     * @var Array
     */
    protected $participants = array();

    /**
     *
     * @var Array
     */
    protected $basketElements = array();

    public function addParticipant(\User_Adapter $user)
    {
        $this->participants[] = $user;
    }

    public function addBasketElement(\record_adapter $record)
    {
        $this->basketElements[] = $record;
    }

    public function load(ObjectManager $manager)
    {
        $basket = new \Entities\Basket();

        $basket->setName('test');

        $basket->setDescription('description');

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new basket');
        }

        $basket->setOwner($this->user);

        $this->addElementToBasket($manager, $basket);

        $validationSession = new \Entities\ValidationSession();

        $validationSession->setBasket($basket);

        $validationSession->setDescription('Une description au hasard');

        $validationSession->setName('Un nom de validation');

        $expires = new \DateTime();
        $expires->modify('+1 week');

        $validationSession->setExpires($expires);

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new validation Session');
        }

        $validationSession->setInitiator($this->user);

        $this->addParticipantsToSession($manager, $validationSession);

        $this->basket = $basket;
    }

    private function addParticipantsToSession(\Doctrine\ORM\EntityManager $manager, \Entities\ValidationSession $validationSession)
    {
        if (0 === count($this->participants)) {
            throw new \LogicException('Add new participants to validation session');
        }

        foreach ($this->participants as $participant) {
            $validationParticipant = new \Entities\ValidationParticipant();

            $validationParticipant->setUser($participant);

            $validationParticipant->setSession($validationSession);

            $manager->persist($validationParticipant);
        }

        $manager->flush();
    }

    private function addElementToBasket(\Doctrine\ORM\EntityManager $manager, \Entities\Basket $basket)
    {
        if (0 === count($this->basketElements)) {
            throw new \LogicException('Add new elements to basket');
        }

        foreach ($this->basketElements as $record) {
            $basketElement = new \Entities\BasketElement();

            $basketElement->setRecord($record);

            $basketElement->setBasket($basket);

            $manager->persist($basketElement);
        }

        $manager->flush();
    }
}
