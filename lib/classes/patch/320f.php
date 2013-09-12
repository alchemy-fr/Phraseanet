<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Entities\Feed;
use Entities\FeedEntry;
use Entities\FeedItem;
use Entities\FeedPublisher;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
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
        $n = 0;

        $app['EM']->getEventManager()->removeEventSubscriber(new TimestampableListener());
        foreach ($rs as $row) {
            $user = User_Adapter::getInstance($row['usr_id'], $app);

            $feed = $this->get_feed($appbox, $user, $row['pub_restrict'], $row['homelink'], $app);

            if (! $feed instanceof Feed) {
                continue;
            }

            $publishers = $feed->getPublishers();

            $entry = new FeedEntry();
            $entry->setAuthorEmail($user->get_email());
            $entry->setAuthorName($user->get_display_name());
            $entry->setFeed($feed);
            $entry->setPublisher($publishers->first());
            $entry->setTitle($row['name']);
            $entry->setSubtitle($row['descript']);
            $feed->addEntry($entry);

            $date_create = new DateTime($row['pub_date']);
            if ($date_create < $date_ref) {
                $date_ref = $date_create;
            }
            $entry->setCreatedOn($date_create);
            if ($row['updater'] != '0000-00-00 00:00:00') {
                $date_update = new DateTime($row['updater']);
                $entry->setUpdatedOn($date_update);
            }

            $sql = 'SELECT sselcont_id, ssel_id, base_id, record_id
                                FROM sselcont WHERE ssel_id = :ssel_id ORDER BY ord ASC';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ssel_id' => $row['ssel_id']));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                try {
                    $record = new record_adapter($app, phrasea::sbasFromBas($app, $row['base_id']), $row['record_id']);
                    $item = new FeedItem();
                    $item->setEntry($entry);
                    $entry->addItem($item);
                    $item->setRecordId($record->get_record_id());
                    $item->setSbasId($record->get_sbas_id());
                    $app['EM']->persist($item);
                } catch (NotFoundHttpException $e) {

                }
            }

            $app['EM']->persist($entry);

            $sql = 'UPDATE ssel SET deleted = "1", migrated="1"
                            WHERE ssel_id = :ssel_id';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ssel_id' => $row['ssel_id']));
            $stmt->closeCursor();
            $app['EM']->persist($feed);
            $n++;
            if ($n % 1000 == 0) {
                $app['EM']->flush();
                $app['EM']->clear();
            }
        }
        $this->set_feed_dates($date_ref);
        $app['EM']->flush();
        $app['EM']->clear();

        $app['EM']->getEventManager()->removeEventSubscriber(new TimestampableListener());

        return true;
    }

    protected function set_feed_dates(DateTime $date_ref)
    {
        foreach (self::$feeds as $array_feeds) {
            foreach ($array_feeds as $feed) {
                $feed->setCreatedOn($date_ref);
            }
        }

        return;
    }
    protected static $feeds = array();

    protected function get_feed(appbox $appbox, User_Adapter $user, $pub_restrict, $homelink, Application $app)
    {
        $user_key = 'user_' . $user->get_id();
        if ($homelink == '1') {
            $feed_key = 'feed_homelink';
        } elseif ($pub_restrict == '1') {
            $feed_key = 'feed_restricted';
        } else {
            $feed_key = 'feed_public';
        }

        if ( ! array_key_exists($user_key, self::$feeds) || ! isset(self::$feeds[$user_key][$feed_key])) {
            if ($homelink == '1')
                $title = $user->get_display_name() . ' - ' . 'homelink Feed';
            elseif ($pub_restrict == '1')
                $title = $user->get_display_name() . ' - ' . 'private Feed';
            else
                $title = $user->get_display_name() . ' - ' . 'public Feed';

            $feed = new Feed();
            $publisher = new FeedPublisher();
            $feed->setTitle('title');
            $feed->setSubtitle('');
            $feed->addPublisher($publisher);
            $publisher->setFeed($feed);
            $publisher->setOwner(true);
            $publisher->setUsrId($user->get_id());

            if ($homelink) {
                $feed->setPublic(true);

            $app['EM']->persist($feed);
            $app['EM']->persist($user);
            $app['EM']->flush();

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
                $feed->setCollection($collection);
            }
            self::$feeds[$user_key][$feed_key] = $feed;
        } else {
            $feed = self::$feeds[$user_key][$feed_key];
        }

        return $feed;
    }
}
