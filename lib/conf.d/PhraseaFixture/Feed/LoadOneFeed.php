<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\Feed;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Entities\Feed;

class LoadOneFeed extends AbstractFixture implements FixtureInterface
{
    /**
     *
     * @var \Entities\Feed
     */
    public $feed;
    public $user;
    public $title;
    public $public;

    public function load(ObjectManager $manager)
    {
        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new feed');
        }

        $feed = new Feed();

        $publisher = new \Entities\FeedPublisher();
        $publisher->setUsrId($this->user->get_id());
        $publisher->setIsOwner(true);
        $publisher->setFeed($feed);

        $feed->addPublisher($publisher);
        if (isset($this->title) && $this->title !== null) {
            $feed->setTitle($this->title);
        } else {
            $feed->setTitle("test");
        }

        $feed->setIsPublic((Boolean) $this->public);

        $feed->setSubtitle("description");

        $manager->persist($feed);
        $manager->persist($publisher);
        $manager->flush();

        $this->feed = $feed;

        $this->addReference('one-feed', $feed);
    }

    public function setUser(\User_Adapter $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setPublic($public)
    {
        $this->public = $public;
    }

    public function getPublic()
    {
        return $this->public;
    }
}
