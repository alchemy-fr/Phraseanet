<?php

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\EntityManager;

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

    protected $app;

    public function __construct(Application $app, array $feeds)
    {
        $this->title = 'AGGREGATE';
        $this->subtitle = 'AGGREGATE SUBTITLE';
        $this->created_on = new \DateTime();
        $this->updated_on = new \DateTime();
        $this->app = $app;

        $tmp_feeds = array();

        foreach ($feeds as $feed) {
            $tmp_feeds[$feed->getId()] = $feed;
        }

        $this->feeds = $tmp_feeds;

        return $this;
    }

    public static function createFromUser(Application $app, \User_Adapter $user)
    {
        $feeds = $app["EM"]->getRepository('Entities\Feed')->findByUser($user);

        return new static($app, $feeds);
    }

    public static function create(Application $app, array $feed_ids)
    {
        $feeds = $app["EM"]->getRepository('Entities\Feed')->findByIdArray($feed_ids);

        return new static($app, $feeds);
    }

    public function isAggregated()
    {
        return true;
    }

    public function getEntries($offset_start, $how_many)
    {
        $result = new \Feed_Entry_Collection();

        if (count($this->feeds) === 0) {
            return $result;
        }

        $offset_start = (int) $offset_start;
        $how_many = $how_many > 20 ? 20 : (int) $how_many;

        $rs = $this->app["EM"]->getRepository("Entities\FeedEntry")->findByFeeds($this->feeds, $offset_start, $how_many);

        foreach ($rs as $entry) {
            if ($entry) {
                $result->add_entry($entry);
            }
        }

        return $result;
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
        $url = '/skins/icons/rss32.gif';

        return $url;
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
            return count($this->app["EM"]->getRepository("Entities\FeedEntry")->findByFeeds($this->feeds));
        }
        return 0;
    }
}
