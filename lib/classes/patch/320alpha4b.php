<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_320alpha4b implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.2.0-alpha.4';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
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
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base $appbox, Application $app)
    {
        $feeds = array();

        try {
            $sql = 'ALTER TABLE `ssel` ADD `migrated` INT NOT NULL DEFAULT "0"';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {

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
            $user = User_Adapter::getInstance($row['usr_id'], $app);

            $feed = $this->get_feed($app, $appbox, $user, $row['pub_restrict'], $row['homelink']);

            if (! $feed instanceof Feed_Adapter) {
                continue;
            }

            $sql = 'INSERT INTO feed_entries (id, feed_id, publisher, title, description, created_on, updated_on, author_name, author_email)
                    VALUES (null, :feed_id, :publisher_id, :title, :description, :created, :updated, :author_name, :author_email)';

            $params = array(
                ':feed_id'      => $feed->get_id(),
                ':publisher_id' => $feed->get_owner()->get_id(),
                ':title'        => trim($row['name']),
                ':description'  => trim($row['descript']),
                ':author_name'  => trim($user->get_display_name()),
                ':author_email' => trim($user->get_email()),
                ':updated' => $row['updater'],
                ':created' => $row['pub_date'],
            );

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();

            $entry_id = $app['phraseanet.appbox']->get_connection()->lastInsertId();

            $feed->delete_data_from_cache();

            unset($stmt);

            $date_create = new DateTime($row['pub_date']);
            if ($date_create < $date_ref) {
                $date_ref = $date_create;
            }

            $sql = 'SELECT sselcont_id, ssel_id, base_id, record_id
                    FROM sselcont
                    WHERE ssel_id = :ssel_id
                    ORDER BY ord ASC';

            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ssel_id' => $row['ssel_id']));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                try {
                    $record = new record_adapter($app, phrasea::sbasFromBas($app, $row['base_id']), $row['record_id']);

                    $sql = 'SELECT (MAX(ord)+1) as sorter FROM feed_entry_elements
                            WHERE entry_id = :entry_id';

                    $stmt = $appbox->get_connection()->prepare($sql);
                    $stmt->execute(array(':entry_id' => $entry_id));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                    unset($stmt);

                    $sorter = ($row && $row['sorter'] > 0) ? (int) $row['sorter'] : 1;

                    $sql = 'INSERT INTO feed_entry_elements (id, entry_id, sbas_id, record_id, ord)
                            VALUES (null, :entry_id, :sbas_id, :record_id, :ord)';

                    $params = array(
                        ':entry_id'  => $entry_id,
                        ':sbas_id'   => $record->get_sbas_id(),
                        ':record_id' => $record->get_record_id(),
                        ':ord'       => $sorter
                    );

                    $stmt = $appbox->get_connection()->prepare($sql);
                    $stmt->execute($params);
                    $stmt->closeCursor();
                    unset($stmt);

                } catch (NotFoundHttpException $e) {

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

    protected function get_feed(Application $app, appbox $appbox, User_Adapter $user, $pub_restrict, $homelink)
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

            $feed = Feed_Adapter::create($app, $user, $title, '');

            if ($homelink) {
                $feed->set_public(true);
            } elseif ($pub_restrict == 1) {
                $collections = $user->ACL()->get_granted_base();
                $collection = array_shift($collections);
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
