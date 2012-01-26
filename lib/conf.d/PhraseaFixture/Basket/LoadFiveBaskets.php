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

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadFiveBaskets extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{

  /**
   *
   * @var array
   */
  public $baskets;

  public function load($manager)
  {
    for ($i = 0; $i < 5; $i++)
    {
      $basket = new \Entities\Basket();

      $basket->setName('test ' . $i);
      $basket->setDescription('description');

      if (null === $this->user)
      {
        throw new \LogicException('Fill a user to store a new basket');
      }

      $basket->setOwner($this->user);

      $manager->persist($basket);

      $this->baskets[] = $basket;
    }
    $this->addReference('five-basket', $basket);
    $manager->flush();
  }

}
