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
class Feed_Token
{
    /**
     *
     * @var int
     */
    protected $usr_id;

    /**
     *
     * @var int
     */
    protected $feed_id;

    /**
     *
     * @var User_Adapter
     */
    protected $user;

    /**
     *
     * @var Feed_Adapter
     */
    protected $feed;

    /**
     *
     * @var appbox
     */
    protected $app;

    /**
     *
     * @param  appbox     $appbox
     * @param  string     $token
     * @param  int        $feed_id
     * @return Feed_Token
     */
    public function __construct(Application $app, $token, $feed_id)
    {
        $sql = 'SELECT feed_id, usr_id FROM feed_tokens
            WHERE feed_id = :feed_id
              AND aggregated IS NULL AND token = :token';

        $params = array(
            ':feed_id' => $feed_id
            , ':token'   => $token
        );

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_FeedNotFound($token);

        $this->feed_id = (int) $row['feed_id'];
        $this->usr_id = (int) $row['usr_id'];
        $this->app = $app;

        return $this;
    }

    /**
     *
     * @return User_Adapter
     */
    public function get_user()
    {
        if ( ! $this->user)
            $this->user = User_Adapter::getInstance($this->usr_id, $this->app);

        return $this->user;
    }

    /**
     *
     * @return Feed_Adapter
     */
    public function get_feed()
    {
        if ( ! $this->feed)
            $this->feed = Feed_Adapter::load_with_user($this->app, $this->get_user(), $this->feed_id);

        return $this->feed;
    }
}
