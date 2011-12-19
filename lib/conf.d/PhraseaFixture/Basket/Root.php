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
class Root extends AbstractUser implements FixtureInterface
{
  
  public $basketId;
  
  public function load($manager)
  {
    $basket = new \Entities\Basket();
    $basket->setName('test');
    $basket->setDescription('description');
    $basket->setOwner($this->user);
    $manager->persist($basket);
    $manager->flush();
    $this->basketId = $basket->getId();
  }

}