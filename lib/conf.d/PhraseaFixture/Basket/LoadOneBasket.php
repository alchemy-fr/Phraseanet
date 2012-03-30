<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadOneBasket extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{

  /**
   *
   * @var \Entities\Basket
   */
  public $basket;

  public function load(ObjectManager $manager)
  {
    $basket = new \Entities\Basket();

    $basket->setName('test');
    $basket->setDescription('description');

    if (null === $this->user)
    {
      throw new \LogicException('Fill a user to store a new basket');
    }

    $basket->setOwner($this->user);

    $manager->persist($basket);
    $manager->flush();

    $this->basket = $basket;

    $this->addReference('one-basket', $basket);
  }

}
