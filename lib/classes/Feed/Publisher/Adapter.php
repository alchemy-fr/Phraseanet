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
class Feed_Publisher_Adapter implements Feed_Publisher_Interface, cache_cacheableInterface
{
    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var User_Adapter
     */
    protected $user;

    /**
     *
     * @var boolean
     */
    protected $owner;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var User_Adapter
     */
    protected $added_by;

    /**
     *
     * @param  Application            $app
     * @param  int                    $id
     * @return Feed_Publisher_Adapter
     */
    public function __construct(Application $app, $id)
    {
        $this->app = $app;
        $this->id = (int) $id;
        $this->load();

        return $this;
    }

    /**
     *
     * @return Feed_Publisher_Adapter
     */
    protected function load()
    {
        try {
            $datas = $this->get_data_from_cache();

            $this->user = User_Adapter::getInstance($datas['usr_id'], $this->app);
            $this->added_by = User_Adapter::getInstance($datas['added_by_usr_id'], $this->app);
            $this->created_on = $datas['created_on'];
            $this->owner = $datas['owner'];

            return $this;
        } catch (Exception $e) {

        }

        $sql = 'SELECT id, usr_id, owner, created_on, added_by
            FROM feed_publishers WHERE id = :feed_publisher_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_publisher_id' => $this->id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_Feed_PublisherNotFound();

        $this->user = User_Adapter::getInstance($row['usr_id'], $this->app);
        $this->owner = ! ! $row['owner'];
        $this->created_on = new DateTime($row['created_on']);
        $this->added_by = User_Adapter::getInstance($row['added_by'], $this->app);

        $datas = array(
            'usr_id'          => $this->user->get_id()
            , 'owner'           => $this->owner
            , 'created_on'      => $this->created_on
            , 'added_by_usr_id' => $this->added_by->get_id()
        );

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     *
     * @return User_Adapter
     */
    public function get_user()
    {
        return $this->user;
    }

    /**
     *
     * @return boolean
     */
    public function is_owner()
    {
        return $this->owner;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     *
     * @return User_Adapter
     */
    public function get_added_by()
    {
        return $this->added_by;
    }

    /**
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        $sql = 'DELETE FROM feed_publishers WHERE id = :feed_publisher_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_publisher_id' => $this->get_id()));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param  Application                 $app
     * @param  User_Adapter           $user
     * @param  Feed_Adapter           $feed
     * @param  boolean                $owner
     * @return Feed_Publisher_Adapter
     */
    public static function create(Application $app, User_Adapter $user, Feed_Adapter $feed, $owner)
    {
        $sql = 'INSERT INTO feed_publishers (id, usr_id, feed_id, owner, created_on, added_by)
            VALUES (null, :usr_id, :feed_id, :owner, NOW(), :added_by)';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $params = array(
            ':usr_id'   => $user->get_id()
            , ':feed_id'  => $feed->get_id()
            , ':owner'    => $owner ? '1' : null
            , ':added_by' => $owner ? $user->get_id() : $app['phraseanet.user']->get_id()
        );
        $stmt->execute($params);
        $id = $app['phraseanet.appbox']->get_connection()->lastInsertId();
        $stmt->closeCursor();

        return new self($app, $id);
    }

    /**
     *
     * @param  appbox                 $appbox
     * @param  Feed_Adapter           $feed
     * @param  User_Adapter           $user
     * @return Feed_Publisher_Adapter
     */
    public static function getPublisher(appbox $appbox, Feed_Adapter $feed, User_Adapter $user)
    {
        foreach ($feed->get_publishers() as $publisher) {
            if ($publisher->get_user()->get_id() === $user->get_id()) {
                return $publisher;
            }
        }
        throw new Exception_Feed_PublisherNotFound('Publisher not found');
    }

    public function get_cache_key($option = null)
    {
        return 'feedpublisher_' . $this->get_id() . '_' . ($option ? '_' . $option : '');
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
