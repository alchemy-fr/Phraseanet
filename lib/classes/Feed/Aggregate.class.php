<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
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
class Feed_Aggregate extends Feed_Abstract implements Feed_Interface
{
    /**
     *
     * @var array
     */
    protected $feeds;

    /**
     *
     * @param appbox $appbox
     * @param array $feeds
     * @return Feed_Aggregate
     */
    public function __construct(appbox &$appbox, Array $feeds)
    {
        $this->title = 'AGGREGGATE';
        $this->subtitle = 'AGREGGATE SUBTITLE';
        $this->created_on = new DateTime();
        $this->updated_on = new DateTime();
        $this->appbox = $appbox;

        $tmp_feeds = array();

        foreach ($feeds as $feed) {
            $tmp_feeds[$feed->get_id()] = $feed;
        }

        $this->feeds = $tmp_feeds;

        return $this;
    }

    public function get_id()
    {
        throw new LogicException('Aggregate feed does not have an id');
    }

    /**
     *
     * @return string
     */
    public function get_icon_url()
    {
        $url = '/skins/icons/rss32.gif';

        return $url;
    }

    /**
     *
     * @return boolean
     */
    public function is_aggregated()
    {
        return true;
    }

    /**
     *
     * @param int $offset_start
     * @param int $how_many
     * @return Feed_Entry_Collection
     */
    public function get_entries($offset_start, $how_many)
    {
        $result = new Feed_Entry_Collection();

        if (count($this->feeds) === 0) {
            return $result;
        }

        $offset_start = (int) $offset_start;
        $how_many = $how_many > 20 ? 20 : (int) $how_many;

        $sql = 'SELECT id, feed_id
            FROM feed_entries
            WHERE feed_id IN (' . implode(', ', array_keys($this->feeds)) . ')
            ORDER BY id DESC
            LIMIT ' . $offset_start . ', ' . $how_many;

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $entry = new Feed_Entry_Adapter(
                    $this->appbox
                    , $this->feeds[$row['feed_id']], $row['id']
            );
            $result->add_entry($entry);
        }

        return $result;
    }

    /**
     *
     * @return int
     */
    public function get_count_total_entries()
    {
        if (count($this->feeds) === 0) {
            return 0;
        }

        $sql = 'SELECT count(id) as number
            FROM feed_entries
            WHERE feed_id
              IN (' . implode(', ', array_keys($this->feeds)) . ') ';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $number = $row ? (int) $row['number'] : 0;
        $stmt->closeCursor();

        return $number;
    }

    /**
     *
     * @param registryInterface $registry
     * @param string $format
     * @param int $page
     * @return Feed_Link
     */
    public function get_homepage_link(registryInterface $registry, $format, $page = null)
    {
        switch ($format) {
            case self::FORMAT_ATOM:
                return new Feed_Link(
                        sprintf('%sfeeds/aggregated/atom/%s'
                            , $registry->get('GV_ServerName')
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'Atom')
                        , 'application/atom+xml'
                );
                break;
            case self::FORMAT_COOLIRIS:
                return new Feed_Link(
                        sprintf('%sfeeds/cooliris/%s'
                            , $registry->get('GV_ServerName')
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'RSS')
                        , 'application/rss+xml'
                );
                break;
            default:
            case self::FORMAT_RSS:
                return new Feed_Link(
                        sprintf('%sfeeds/aggregated/rss/%s'
                            , $registry->get('GV_ServerName')
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
     * @param User_Adapter $user
     * @param boolean $renew
     * @return string
     */
    protected function get_token(User_Adapter $user, $renew = false)
    {
        $sql = 'SELECT token FROM feed_tokens
            WHERE usr_id = :usr_id AND aggregated = "1"';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $user->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row || $renew === true) {
            $token = random::generatePassword(12, random::LETTERS_AND_NUMBERS);
            $sql = 'REPLACE INTO feed_tokens (id, token, feed_id, usr_id, aggregated)
              VALUES (null, :token, :feed_id, :usr_id, :aggregated)';

            $params = array(
                ':token'      => $token
                , ':feed_id'    => null
                , ':usr_id'     => $user->get_id()
                , ':aggregated' => '1'
            );

            $stmt = $this->appbox->get_connection()->prepare($sql);
            $stmt->execute($params);
        } else {
            $token = $row['token'];
        }

        return $token;
    }

    /**
     *
     * @param appbox $appbox
     * @param User_Adapter $user
     * @return Feed_Aggregate
     */
    public static function load_with_user(appbox &$appbox, User_Adapter &$user)
    {
        $feeds = Feed_Collection::load_all($appbox, $user);

        return new self($appbox, $feeds->get_feeds());
    }

    /**
     *
     * @param registryInterface $registry
     * @param User_Adapter $user
     * @param string $format
     * @param int $page
     * @param boolean $renew_token
     * @return Feed_Link
     */
    public function get_user_link(registryInterface $registry, User_Adapter $user, $format, $page = null, $renew_token = false)
    {
        switch ($format) {
            case self::FORMAT_ATOM:
                return new Feed_Link(
                        sprintf('%sfeeds/userfeed/aggregated/%s/atom/%s'
                            , $registry->get('GV_ServerName')
                            , $this->get_token($user, $renew_token)
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'Atom')
                        , 'application/atom+xml'
                );
                break;
            case self::FORMAT_RSS:
                return new Feed_Link(
                        sprintf('%sfeeds/userfeed/aggregated/%s/rss/%s'
                            , $registry->get('GV_ServerName')
                            , $this->get_token($user, $renew_token)
                            , ($page ? '?page=' . $page : '')
                        )
                        , sprintf('%s - %s', $this->get_title(), 'RSS')
                        , 'application/rss+xml'
                );
                break;
        }
    }
}
