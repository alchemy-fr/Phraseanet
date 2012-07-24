<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed_Adapter extends Feed_Abstract implements Feed_Interface, cache_cacheableInterface
{
    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var collection
     */
    protected $collection;

    /**
     *
     * @var array
     */
    protected $publishers;

    /**
     *
     * @var boolean
     */
    protected $public;

    /**
     *
     * @var Feed_Publisher_Adapter
     */
    protected $owner;

    /**
     *
     * @var string
     */
    protected $icon_url;

    const CACHE_ENTRY_NUMBER = 'entrynumber';
    const CACHE_USER_TOKEN = 'usr_token';
    const MAX_ENTRIES = 20;

    /**
     *
     * @param  appbox       $appbox
     * @param  int          $id
     * @return Feed_Adapter
     */
    public function __construct(appbox &$appbox, $id)
    {
        $this->appbox = $appbox;
        $this->id = (int) $id;

        $this->load();

        return $this;
    }

    protected function load()
    {
        try {
            $datas = $this->get_data_from_cache();

            $this->title = $datas['title'];
            $this->subtitle = $datas['subtitle'];
            $this->collection = $datas['base_id'] ? collection::get_from_base_id($datas['base_id']) : null;
            $this->created_on = $datas['created_on'];
            $this->updated_on = $datas['updated_on'];
            $this->public = $datas['public'];

            return $this;
        } catch (Exception $e) {

        }

        $sql = 'SELECT id, title, subtitle, created_on, updated_on, base_id, public
            FROM feeds WHERE id = :feed_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_id' => $this->id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_FeedNotFound ();

        $this->title = $row['title'];
        $this->subtitle = $row['subtitle'];
        if ( ! is_null($row['base_id']))
            $this->collection = collection::get_from_base_id($row['base_id']);
        $this->created_on = new DateTime($row['created_on']);
        $this->updated_on = new DateTime($row['updated_on']);
        $this->public = ! ! $row['public'];

        $base_id = $this->collection instanceof collection ? $this->collection->get_base_id() : null;

        $datas = array(
            'title'      => $this->title
            , 'subtitle'   => $this->subtitle
            , 'base_id'    => $base_id
            , 'created_on' => $this->created_on
            , 'updated_on' => $this->updated_on
            , 'public'     => $this->public
        );

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_icon_url()
    {
        if ($this->icon_url) {
            return $this->icon_url;
        }

        $url = '/skins/icons/rss32.gif';

        $file = $this->appbox->get_registry()->get('GV_RootPath')
            . 'www/custom/feed_' . $this->get_id() . '.jpg';

        if (file_exists($file)) {
            $url = '/custom/feed_' . $this->get_id() . '.jpg';
        }

        $this->icon_url = $url;

        return $this->icon_url;
    }

    /**
     *
     * @param  string       $file The path to the file
     * @return Feed_Adapter
     */
    public function set_icon($file)
    {
        if ( ! file_exists($file)) {
            throw new \Alchemy\Phrasea\Exception\InvalidArgumentException('File does not exists');
        }

        $registry = registry::get_instance();

        $config_file = $registry->get('GV_RootPath') . 'config/feed_' . $this->get_id() . '.jpg';
        $www_file = $registry->get('GV_RootPath') . 'www/custom/feed_' . $this->get_id() . '.jpg';

        copy($file, $config_file);
        copy($file, $www_file);
        $this->icon_url = null;

        return $this;
    }

    public function set_created_on(DateTime $created_on)
    {
        $sql = 'UPDATE feeds SET created_on = :created_on
            WHERE id = :feed_id';
        $params = array(
            ':created_on' => $created_on->format(DATE_ISO8601)
            , ':feed_id'    => $this->get_id()
        );
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
        $this->created_on = $created_on;
        $this->delete_data_from_cache();

        return $this;
    }

    public function reset_icon()
    {
        $registry = registry::get_instance();
        $config_file = $registry->get('GV_RootPath')
            . 'config/feed_' . $this->get_id() . '.jpg';
        $www_file = $registry->get('GV_RootPath')
            . 'www/custom/feed_' . $this->get_id() . '.jpg';

        if (is_file($config_file))
            unlink($config_file);
        if (is_file($www_file))
            unlink($www_file);

        $this->icon_url = null;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function is_aggregated()
    {
        return false;
    }

    /**
     *
     * @param  User_Adapter $user
     * @return boolean
     */
    public function is_owner(User_Adapter $user)
    {
        $this->load_publishers();

        return $this->owner->get_user()->get_id() === $user->get_id();
    }

    /**
     *
     * @param  User_Adapter $user
     * @return boolean
     */
    public function is_publisher(User_Adapter $user)
    {
        return in_array($user->get_id(), array_keys($this->get_publishers()));
    }

    /**
     * Tells if a user has access to the feed
     *
     * @param  User_Adapter $user
     * @return type
     */
    public function has_access(User_Adapter $user)
    {
        if ($this->get_collection() instanceof collection) {
            return $user->ACL()->has_access_to_base($this->collection->get_base_id());
        }

        return true;
    }

    /**
     *
     * @return boolean
     */
    public function is_public()
    {
        if ($this->get_collection() instanceof collection) {
            return false;
        }

        return $this->public;
    }

    /**
     *
     * @return array
     */
    public function get_publishers()
    {
        return $this->load_publishers();
    }

    /**
     *
     * @return collection
     */
    public function get_collection()
    {
        return $this->collection;
    }

    /**
     *
     * @param  User_Adapter $user
     * @return Feed_Adapter
     */
    public function add_publisher(User_Adapter $user)
    {
        if (in_array($user->get_id(), array_keys($this->get_publishers()))) {
            return $this;
        }

        Feed_Publisher_Adapter::create($this->appbox, $user, $this, false);
        $this->publishers = null;

        return $this;
    }

    /**
     *
     * @return array
     */
    protected function load_publishers()
    {
        if (is_array($this->publishers)) {
            return $this->publishers;
        }

        $sql = 'SELECT id, usr_id, owner FROM feed_publishers
            WHERE feed_id = :feed_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_id' => $this->id));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $publisher = new Feed_Publisher_Adapter($this->appbox, $row['id']);
            $this->publishers[$row['usr_id']] = $publisher;
            if ($publisher->is_owner())
                $this->owner = $publisher;
        }

        return $this->publishers;
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
     * @param  collection   $collection
     * @return Feed_Adapter
     */
    public function set_collection(collection $collection = null)
    {
        $base_id = null;
        if ($collection instanceof collection) {
            $base_id = $collection->get_base_id();
        }

        $sql = 'UPDATE feeds SET base_id = :base_id, updated_on = NOW()
            WHERE id = :feed_id';
        $params = array(':base_id' => $base_id, ':feed_id' => $this->get_id());
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
        $this->collection = $collection;
        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  boolean      $boolean
     * @return Feed_Adapter
     */
    public function set_public($boolean)
    {
        $boolean = ! ! $boolean;
        $sql = 'UPDATE feeds SET public = :public, updated_on = NOW()
            WHERE id = :feed_id';

        $params = array(
            ':public'  => $boolean ? '1' : '0',
            ':feed_id' => $this->get_id()
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
        $this->public = $boolean;
        $this->delete_data_from_cache();

        $feed_collection = new Feed_Collection($this->appbox, array());
        $feed_collection->delete_data_from_cache(Feed_Collection::CACHE_PUBLIC);

        return $this;
    }

    /**
     *
     * @param  string       $title
     * @return Feed_Adapter
     */
    public function set_title($title)
    {
        $title = trim(strip_tags($title));

        if ($title === '')
            throw new Exception_InvalidArgument();

        $sql = 'UPDATE feeds SET title = :title, updated_on = NOW()
            WHERE id = :feed_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':title'   => $title, ':feed_id' => $this->get_id()));
        $stmt->closeCursor();
        $this->title = $title;
        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  string       $subtitle
     * @return Feed_Adapter
     */
    public function set_subtitle($subtitle)
    {
        $subtitle = strip_tags($subtitle);

        $sql = 'UPDATE feeds SET subtitle = :subtitle, updated_on = NOW()
            WHERE id = :feed_id';
        $params = array(':subtitle' => $subtitle, ':feed_id'  => $this->get_id());
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
        $this->subtitle = $subtitle;
        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  appbox       $appbox
     * @param  User_Adapter $user
     * @param  string       $title
     * @param  string       $subtitle
     * @return Feed_Adapter
     */
    public static function create(appbox &$appbox, User_Adapter $user, $title, $subtitle)
    {
        $sql = 'INSERT INTO feeds (id, title, subtitle, created_on, updated_on)
            VALUES (null, :title, :subtitle, NOW(), NOW())';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':title'    => $title, ':subtitle' => $subtitle));
        $stmt->closeCursor();

        $feed_id = $appbox->get_connection()->lastInsertId();

        $feed = new self($appbox, $feed_id);

        Feed_Publisher_Adapter::create($appbox, $user, $feed, true);

        return $feed;
    }

    /**
     *
     * @param  appbox       $appbox
     * @param  User_Adapter $user
     * @param  int          $id
     * @return Feed_Adapter
     */
    public static function load_with_user(appbox &$appbox, User_Adapter &$user, $id)
    {
        $feed = new self($appbox, $id);
        $coll = $feed->get_collection();
        if (
            $feed->is_public()
            || $coll === null
            || in_array($coll->get_base_id(), array_keys($user->ACL()->get_granted_base()))
        ) {
            return $feed;
        }

        throw new Exception_FeedNotFound();
    }

    /**
     *
     * @return int
     */
    public function get_count_total_entries()
    {
        try {
            return $this->get_data_from_cache(self::CACHE_ENTRY_NUMBER);
        } catch (Exception $e) {

        }

        $sql = 'SELECT count(id) as number
            FROM feed_entries WHERE feed_id = :feed_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_id' => $this->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $number = $row ? (int) $row['number'] : 0;
        $stmt->closeCursor();

        $this->set_data_to_cache($number, self::CACHE_ENTRY_NUMBER);

        return $number;
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        $this->reset_icon();
        while ($this->get_count_total_entries() > 0) {
            $entries_coll = $this->get_entries(0, 10);
            foreach ($entries_coll->get_entries() as $entry) {
                $entry->delete();
            }
            unset($entries_coll);
            $this->delete_data_from_cache(self::CACHE_ENTRY_NUMBER);
        }

        foreach ($this->get_publishers() as $publishers)
            $publishers->delete();

        $sql = 'DELETE FROM feed_tokens WHERE feed_id = :feed_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM feeds WHERE id = :feed_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':feed_id' => $this->get_id()));
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        $feed_coll = new Feed_Collection($this->appbox, array());
        $feed_coll->delete_data_from_cache(Feed_Collection::CACHE_PUBLIC);

        return;
    }

    /**
     *
     * @param  int                   $offset_start
     * @param  int                   $how_many
     * @return Feed_Entry_Collection
     */
    public function get_entries($offset_start, $how_many)
    {
        $offset_start = (int) $offset_start;
        $how_many = $how_many > self::MAX_ENTRIES ? self::MAX_ENTRIES : (int) $how_many;

        $sql = 'SELECT id
            FROM feed_entries
            WHERE feed_id = :feed_id
            ORDER BY id DESC
            LIMIT ' . $offset_start . ', ' . $how_many;

        $params = array(
            ':feed_id' => $this->get_id()
        );
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $result = new Feed_Entry_Collection();

        foreach ($rs as $row) {
            $entry = new Feed_Entry_Adapter($this->appbox, $this, $row['id']);
            $result->add_entry($entry);
        }

        return $result;
    }

    /**
     *
     * @param  registryInterface $registry
     * @param  string            $format
     * @param  int               $page
     * @return Feed_Link
     */
    public function get_homepage_link(registryInterface $registry, $format, $page = null)
    {
        if ( ! $this->is_public()) {
            return null;
        }

        switch ($format) {
            case self::FORMAT_ATOM:
                return new Feed_Link(
                        sprintf('%sfeeds/feed/%s/atom/%s'
                            , $registry->get('GV_ServerName')
                            , $this->get_id()
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'Atom')
                        , 'application/atom+xml'
                );
                break;
            case self::FORMAT_RSS:
            default:
                return new Feed_Link(
                        sprintf('%sfeeds/feed/%s/rss/%s'
                            , $registry->get('GV_ServerName')
                            , $this->get_id()
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'RSS')
                        , 'application/rss+xml'
                );
                break;
        }
    }

    /**
     *
     * @param  User_Adapter $user
     * @param  boolean      $renew
     * @return string
     */
    protected function get_token(User_Adapter $user, $renew = false)
    {
        $cache_key = self::CACHE_USER_TOKEN . '_' . $user->get_id();
        try {
            if ( ! $renew) {
                return $this->get_data_from_cache($cache_key);
            }
        } catch (Exception $e) {

        }

        $sql = 'SELECT token FROM feed_tokens
            WHERE usr_id = :usr_id AND feed_id = :feed_id
            AND aggregated IS NULL';

        $params = array(
            ':usr_id'  => $user->get_id(),
            ':feed_id' => $this->get_id()
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row || $renew === true) {
            $token = random::generatePassword(12, random::LETTERS_AND_NUMBERS);
            $sql = 'REPLACE INTO feed_tokens (id, token, feed_id, usr_id, aggregated)
              VALUES (null, :token, :feed_id, :usr_id, :aggregated)';

            $params = array(
                ':token'      => $token
                , ':feed_id'    => $this->get_id()
                , ':usr_id'     => $user->get_id()
                , ':aggregated' => null
            );

            $stmt = $this->appbox->get_connection()->prepare($sql);
            $stmt->execute($params);
            $this->delete_data_from_cache($cache_key);
        } else {
            $token = $row['token'];
        }

        $this->set_data_to_cache($token, $cache_key);

        return $token;
    }

    /**
     *
     * @param  registryInterface $registry
     * @param  User_Adapter      $user
     * @param  string            $format
     * @param  int               $page
     * @param  boolean           $renew_token
     * @return Feed_Link
     */
    public function get_user_link(registryInterface $registry, User_Adapter $user, $format, $page = null, $renew_token = false)
    {
        switch ($format) {
            case self::FORMAT_ATOM:
                return new Feed_Link(
                        sprintf('%sfeeds/userfeed/%s/%s/atom/'
                            , $registry->get('GV_ServerName')
                            , $this->get_token($user, $renew_token)
                            , $this->get_id()
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'Atom')
                        , 'application/atom+xml'
                );
                break;
            case self::FORMAT_RSS:
                return new Feed_Link(
                        sprintf('%sfeeds/userfeed/%s/%s/rss/%s'
                            , $registry->get('GV_ServerName')
                            , $this->get_token($user, $renew_token)
                            , $this->get_id()
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'RSS')
                        , 'application/rss+xml'
                );
                break;
        }
    }

    public function get_cache_key($option = null)
    {
        return 'feed_adapter_' . $this->get_id() . '_' . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        return $this->appbox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->appbox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        return $this->appbox->delete_data_from_cache($this->get_cache_key($option));
    }
}
