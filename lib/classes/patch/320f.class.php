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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_320f implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.2.0.0.a4';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return true;
    }

    /**
     *
     * @return Array
     */
    function concern()
    {
        return $this->concern;
    }

    function apply(base &$appbox)
    {
        $feeds = array();

        try {
            $sql = 'ALTER TABLE `ssel` ADD `migrated` INT NOT NULL DEFAULT "0"';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (Exception $e) {

        }

        $sql = 'SELECT ssel_id, usr_id, name, descript, pub_date
                            , updater, pub_restrict, homelink
                        FROM ssel WHERE (public = "1" or homelink="1") and migrated = 0';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $date_ref = new DateTime();

        foreach ($rs as $row) {
            $user = User_Adapter::getInstance($row['usr_id'], $appbox);

            $feed = $this->get_feed($appbox, $user, $row['pub_restrict'], $row['homelink']);

            if ( ! $feed instanceof Feed_Adapter) {
                continue;
            }

            $entry = Feed_Entry_Adapter::create($appbox, $feed, array_shift($feed->get_publishers()), $row['name'], $row['descript'], $user->get_display_name(), $user->get_email());
            $date_create = new DateTime($row['pub_date']);
            if ($date_create < $date_ref) {
                $date_ref = $date_create;
            }
            $entry->set_created_on($date_create);
            if ($row['updater'] != '0000-00-00 00:00:00') {
                $date_update = new DateTime($row['updater']);
                $entry->set_updated_on($date_update);
            }

            $sql = 'SELECT sselcont_id, ssel_id, base_id, record_id
                                FROM sselcont WHERE ssel_id = :ssel_id ORDER BY ord ASC';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ssel_id' => $row['ssel_id']));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                try {
                    $record = new record_adapter(phrasea::sbasFromBas($row['base_id']), $row['record_id']);
                    $item = Feed_Entry_Item::create($appbox, $entry, $record);
                } catch (Exception_NotFound $e) {

                }
            }

            $sql = 'UPDATE ssel SET deleted = "1", migrated="1"
                            WHERE ssel_id = :ssel_id';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ssel_id' => $row['ssel_id']));
            $stmt->closeCursor();
        }
        $this->set_feed_dates($date_ref);

        return true;
    }

    protected function set_feed_dates(DateTime $date_ref)
    {
        foreach (self::$feeds as $array_feeds) {
            foreach ($array_feeds as $feed) {
                $feed->set_created_on($date_ref);
            }
        }

        return;
    }
    protected static $feeds = array();

    protected function get_feed(appbox &$appbox, User_Adapter &$user, $pub_restrict, $homelink)
    {
        $user_key = 'user_' . $user->get_id();
        if ($homelink == '1')
            $feed_key = 'feed_homelink';
        elseif ($pub_restrict == '1')
            $feed_key = 'feed_restricted';
        else
            $feed_key = 'feed_public';

        if ( ! array_key_exists($user_key, self::$feeds) || ! isset(self::$feeds[$user_key][$feed_key])) {
            if ($homelink == '1')
                $title = $user->get_display_name() . ' - ' . 'homelink Feed';
            elseif ($pub_restrict == '1')
                $title = $user->get_display_name() . ' - ' . 'private Feed';
            else
                $title = $user->get_display_name() . ' - ' . 'public Feed';

            $feed = Feed_Adapter::create($appbox, $user, $title, '');

            if ($homelink) {
                $feed->set_public(true);
            } elseif ($pub_restrict == 1) {
                $collection = array_shift($user->ACL()->get_granted_base());
                if ( ! ($collection instanceof collection)) {
                    foreach ($appbox->get_databoxes() as $databox) {
                        foreach ($databox->get_collections() as $coll) {
                            $collection = $coll;
                            break;
                        }
                        if ($collection instanceof collection)
                            break;
                    }
                }

                if ( ! ($collection instanceof collection)) {
                    echo "unable to find a collection to protect feeds";

                    return false;
                }
                $feed->set_collection($collection);
            }
            self::$feeds[$user_key][$feed_key] = $feed;
        } else {
            $feed = self::$feeds[$user_key][$feed_key];
        }

        return $feed;
    }
}
