<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\LogicException;
use Doctrine\ORM\EntityManager;
use Entities\AggregateToken;

class Aggregate implements FeedInterface
{
    /** @var string */
    private $title;

    /** @var string */
    private $subtitle;

    /** @var DateTime */
    private $createdOn;

    /** @var DateTime */
    private $updatedOn;

    /** @var array */
    private $feeds;

    /** @var AggregateToken */
    private $token;

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager  $em
     * @param array          $feeds
     * @param AggregateToken $token
     *
     * @return Aggregate
     */
    public function __construct(EntityManager $em, array $feeds, AggregateToken $token = null)
    {
        $this->title = 'AGGREGATE';
        $this->subtitle = 'AGGREGATE SUBTITLE';
        $this->created_on = new \DateTime();
        $this->updated_on = new \DateTime();
        $this->em = $em;

        $tmp_feeds = array();

        foreach ($feeds as $feed) {
            $tmp_feeds[$feed->getId()] = $feed;
        }

        $this->feeds = $tmp_feeds;
        $this->token = $token;

        return $this;
    }

    /**
     * Creates an aggregate from all the feeds available to a given user.
     *
     * @param EntityManager $em
     * @param \User_Adapter $user
     *
     * @return Aggregate
     */
    public static function createFromUser(EntityManager $em, \User_Adapter $user)
    {
        $feeds = $em->getRepository('Entities\Feed')->getAllForUser($user);
        $token = $em->getRepository('Entities\AggregateToken')->findOneBy(array('usrId' => $user->get_id()));

        return new static($em, $feeds, $token);
    }

    /**
     * Creates an aggregate from given Feed id array.
     *
     * @param EntityManager $em
     * @param array         $feed_ids
     *
     * @return Aggregate
     */
    public static function create(Application $app, array $feed_ids)
    {
        $feeds = $this->em->getRepository('Entities\Feed')->findByIds($feed_ids);

        return new static($app, $feeds);
    }

    /**
     * {@inheritdoc}
     */
    public function isAggregated()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntries($offset_start = null, $how_many = null)
    {
        if (0 === count($this->feeds)) {
            return null;
        }

        $feedIds = array();
        foreach ($this->feeds as $feed) {
            $feedIds[] = $feed->getId();
        }
        return $this->em->getRepository('Entities\FeedEntry')->findByFeeds($feedIds, $offset_start, $how_many);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconUrl()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Get AggregateToken
     *
     * @return AggregateToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set AggregateToken
     *
     * @param AggregateToken $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Get Feeds
     *
     * @return array
     */
    public function getFeeds()
    {
        return $this->feeds;
    }

    /**
     * Returns the total number of entries from all the feeds.
     *
     * @return int
     */
    public function getCountTotalEntries()
    {
        if (count($this->feeds) > 0) {
            $feedIds = array();
            foreach ($this->feeds as $feed) {
                $feedIds[] = $feed->getId();
            }
            return count($this->em->getRepository('Entities\FeedEntry')->findByFeeds($feedIds));
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPage($pageNumber, $nbEntriesByPage)
    {
        if (0 >= $nbEntriesByPage) {
            throw new LogicException;
        }

        $count = $this->getCountTotalEntries();
        if (0 > $pageNumber && $pageNumber <= $count / $nbEntriesByPage) {
            return true;
        }

        return false;
    }

    /**
     * Creates an Aggregate from all the public feeds.
     *
     * @param Application $app
     *
     * @return Aggregate
     */
    public static function getPublic(Application $app)
    {
        return new static($app['EM'], $app['EM']->getRepository('Entities\Feed')->findBy(array('public' => true), array('updatedOn' => 'DESC')));
    }
}
