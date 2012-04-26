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
class Feed_TokenAggregate extends Feed_Token
{

    /**
     *
     * @param appbox $appbox
     * @param string $token
     * @return Feed_TokenAggregate
     */
    public function __construct(appbox &$appbox, $token)
    {

        $sql = 'SELECT usr_id FROM feed_tokens
            WHERE aggregated = "1" AND token = :token';

        $params = array(
            ':token' => $token
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_FeedNotFound($token);

        $this->usr_id = $row['usr_id'];
        $this->appbox = $appbox;

        return $this;
    }

    /**
     *
     * @return Feed_Aggregate
     */
    public function get_feed()
    {
        if ( ! $this->feed)
            $this->feed = Feed_Aggregate::load_with_user($this->appbox, $this->get_user());

        return $this->feed;
    }
}
