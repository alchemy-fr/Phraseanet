<?php

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\EntityManager;
use Entities\AggregateToken;

class Aggregate implements FeedInterface
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $subtitle;

    /**
     * @var DateTime
     */
    protected $created_on;

    /**
     * @var DateTime
     */
    protected $updated_on;

    /**
     * @var array
     */

    protected $feeds;

    protected $token;

    protected $em;

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

    public static function createFromUser(EntityManager $em, \User_Adapter $user)
    {
        $feeds = $em->getRepository('Entities\Feed')->getAllForUser($user);
        $token = $em->getRepository('Entities\AggregateToken')->findByUser($user);

        return new static($em, $feeds, $token);
    }

    public static function create(Application $app, array $feed_ids)
    {
        $feeds = $this->em->getRepository('Entities\Feed')->findByIdArray($feed_ids);

        return new static($app, $feeds);
    }

    public function isAggregated()
    {
        return true;
    }

    public function getEntries($offset_start = 0, $how_many = null)
    {
        return $this->em->getRepository('Entities\FeedEntry')->findByFeeds($this->feeds, $offset_start, $how_many);
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getIconUrl()
    {
        return false;
    }

    public function getCreatedOn()
    {
        return $this->created_on;
    }

    public function getUpdatedOn()
    {
        return $this->updated_on;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getFeeds()
    {
        return $this->feeds;
    }

    public function getCountTotalEntries()
    {
        if (count($this->feeds) > 0) {
            return count($this->em->getRepository('Entities\FeedEntry')->findByFeeds($this->feeds));
        }
        return 0;
    }

    public function hasPage($page, $pageSize)
    {
        $count = $this->getCountTotalEntries();
        if ($page >= $count / $pageSize)
            return true;
        return false;
    }
}
