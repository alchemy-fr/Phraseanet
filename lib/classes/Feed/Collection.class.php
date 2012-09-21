<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed_Collection implements Feed_CollectionInterface, cache_cacheableInterface
{
    /**
     *
     * @var Array
     */
    protected $feeds;

    /**
     *
     * @var Application
     */
    protected $app;

    const CACHE_PUBLIC = 'public';

    /**
     *
     * @param  Application     $app
     * @param  array           $feeds
     * @return Feed_Collection
     */
    public function __construct(Application $app, Array $feeds)
    {
        $this->feeds = $feeds;
        $this->app = $app;

        return $this;
    }

    /**
     *
     * @param  Application $app
     * @param  User_Adapter    $user
     * @return Feed_Collection
     */
    public static function load_all(Application $app, User_Adapter $user)
    {
        $base_ids = array_keys($user->ACL()->get_granted_base());

        $sql = 'SELECT id FROM feeds
            WHERE base_id IS NULL ';

        if (count($base_ids) > 0) {
            $sql .= ' OR base_id
                IN (' . implode(', ', $base_ids) . ') ';
        }

        $sql .= ' OR public = "1"
            ORDER BY created_on DESC';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $feeds = array();

        foreach ($rs as $row) {
            $feeds[] = new Feed_Adapter($app, $row['id']);
        }

        return new self($app, $feeds);
    }

    /**
     *
     * @return Array
     */
    public function get_feeds()
    {
        return $this->feeds;
    }

    /**
     *
     * @return Feed_Aggregate
     */
    public function get_aggregate()
    {
        return new Feed_Aggregate($this->app, $this->feeds);
    }

    /**
     *
     * @param  Application $app
     * @return Feed_Collection
     */
    public static function load_public_feeds(Application $app)
    {
        $rs = self::retrieve_public_feed_ids($app['phraseanet.appbox']);
        $feeds = array();
        foreach ($rs as $feed_id) {
            $feeds[] = new Feed_Adapter($app, $feed_id);
        }

        return new self($app, $feeds);
    }

    protected static function retrieve_public_feed_ids(appbox &$appbox)
    {
        $key = self::get_cache_key(self::CACHE_PUBLIC);

        try {
            return $appbox->get_data_from_cache($key);
        } catch (Exception $e) {

        }

        $sql = 'SELECT id FROM feeds WHERE public = "1" AND base_id IS null ORDER BY created_on DESC';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $feeds = array();

        foreach ($rs as $row) {
            $feeds[] = $row['id'];
        }

        $appbox->set_data_to_cache($feeds, $key);

        return $feeds;
    }

    public function get_cache_key($option = null)
    {
        return 'feedcollection_' . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        return $this->app['phraseanet.appbox']->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->app['phraseanet.appbox']->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        return $this->app['phraseanet.appbox']->delete_data_from_cache($this->get_cache_key($option));
    }
}
