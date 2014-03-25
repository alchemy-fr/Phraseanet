<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Cache\Exception as CacheException;

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
     * @param  Application     $app
     * @param  User_Adapter    $user
     * @param  array           $reduce
     *
     * @return Feed_Collection
     */
    public static function load(Application $app, User_Adapter $user, $reduce = array())
    {
        $base_ids = array_keys($user->ACL()->get_granted_base());

        $chunkSql = array('SELECT id FROM feeds WHERE');
        // restrict to given feed ids
        if (count($reduce) > 0) {
            $chunkSql[] = sprintf('(id IN (%s)) AND', implode(', ', $reduce));
        } else {
            $chunkSql[] =  '1 AND';
        }

        // restrict to granted collection
        if (count($base_ids) > 0) {
            $chunkSql[] = sprintf('((base_id IN (%s)) OR ', implode(', ', $base_ids));
        } else {
            $chunkSql[] = '(';
        }
        $chunkSql[] = '(public = "1") OR (base_id IS NULL)';
        $chunkSql[] = ')';
        $chunkSql[] = 'ORDER BY created_on DESC';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare(implode(' ', $chunkSql));
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $feeds = array();

        foreach ($rs as $row) {
            $feeds[$row['id']] = new Feed_Adapter($app, $row['id']);
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
     * @param  Application     $app
     * @return Feed_Collection
     */
    public static function load_public_feeds(Application $app)
    {
        $collection = new self($app, array());

        try {
            $feedIds = $collection->get_data_from_cache(self::CACHE_PUBLIC);

            return new self($app, array_map(function($id) use ($app) {
                return new \Feed_Adapter($app, $id);
            }, $feedIds));
        } catch (CacheException $e) {

        }

        $sql = 'SELECT id FROM feeds WHERE public = "1" AND base_id IS null ORDER BY created_on DESC';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $feeds = array();
        foreach ($rs as $row) {
            $feeds[] = new \Feed_Adapter($app, $row['id']);
        }

        $collection->set_data_to_cache(array_map(function($feed) {
            return $feed->get_id();
        }, $feeds), self::CACHE_PUBLIC);

        return new self($app, $feeds);
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
