<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\Story;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadOneStory extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{

  /**
   *
   * @var \Entities\StoryWZ
   */
  public $story;

  public function load($manager)
  {
    $story = new \Entities\StoryWZ();

    if (null === $this->record)
    {
      throw new \LogicException('Fill a record to store a new story');
    }

    if (null === $this->user)
    {
      throw new \LogicException('Fill a user to store a new story');
    }

    $story->setRecord($this->record);
    $story->setUser($this->user);

    $manager->persist($story);
    $manager->flush();

    $this->story = $story;

    $this->addReference('one-story', $story);
  }

}
