<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Model\Entities\AggregateToken;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\FeedEntryRepository;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class Aggregate implements FeedInterface
{
    /** @var string */
    private $title;

    /** @var string */
    private $subtitle;

    /** @var \DateTime */
    private $createdOn;

    /** @var \DateTime */
    private $updatedOn;

    /** @var Feed[]|Collection */
    private $feeds;

    /** @var AggregateToken */
    private $token;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * @param EntityManagerInterface $em
     * @param Feed[]                 $feeds
     * @param AggregateToken         $token
     */
    public function __construct(EntityManagerInterface $em, array $feeds, AggregateToken $token = null)
    {
        $this->title = 'AGGREGATE';
        $this->subtitle = 'AGGREGATE SUBTITLE';
        $this->createdOn = new \DateTime();
        $this->updatedOn = new \DateTime();
        $this->em = $em;

        $tmp_feeds = [];

        foreach ($feeds as $feed) {
            $tmp_feeds[$feed->getId()] = $feed;
        }

        $this->feeds = new ArrayCollection($tmp_feeds);
        $this->token = $token;
    }

    /**
     * Creates an aggregate from all the feeds available to a given user.
     *
     * @param Application $app
     * @param User        $user
     *
     * @param array       $restrictions
     * @return Aggregate
     */
    public static function createFromUser(Application $app, User $user, array $restrictions = [])
    {
        /** @var FeedRepository $feedRepository */
        $feedRepository = $app['repo.feeds'];
        $feeds = $feedRepository->filterUserAccessibleByIds($app->getAclForUser($user), $restrictions);
        $token = $app['repo.aggregate-tokens']->findOneBy(['user' => $user]);

        return new static($app['orm.em'], $feeds, $token);
    }

    /**
     * Creates an aggregate from given Feed id array.
     *
     * @param Application $app
     * @param array       $feed_ids
     * @return Aggregate
     */
    public static function create(Application $app, array $feed_ids)
    {
        $feeds = $app['repo.feeds']->findByIds($feed_ids);

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
    public function getEntries()
    {
        /** @var FeedEntryRepository $feedEntryRepository */
        $feedEntryRepository = $this->em->getRepository('Phraseanet:FeedEntry');
        return new AggregateEntryCollection($feedEntryRepository, $this->feeds);
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
        if ($this->feeds->isEmpty()) {
            return 0;
        }

        /** @var FeedEntryRepository $feedEntryRepository */
        $feedEntryRepository = $this->em->getRepository('Phraseanet:FeedEntry');

        return $feedEntryRepository->countByFeeds($this->feeds->getKeys());
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
        return new static($app['orm.em'], $app['repo.feeds']->findBy(['public' => true], ['updatedOn' => 'DESC']));
    }
}
