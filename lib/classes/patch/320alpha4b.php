<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Model\Entities\User;

class patch_320alpha4b extends patchAbstract
{
    /** @var string */
    private $release = '3.2.0-alpha.4';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['20131118000009', '20131118000001'];
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        try {
            $sql = 'ALTER TABLE `ssel` ADD `migrated` INT NOT NULL DEFAULT "0"';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {

        }

        $sql = 'SELECT ssel_id, usr_id, name, descript, pub_date, updater, pub_restrict, homelink
                FROM ssel
                WHERE (public = "1" OR homelink="1")
                  AND migrated = 0';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $date_ref = new DateTime();
        $n = 0;

        $app['orm.em']->getEventManager()->removeEventSubscriber(new TimestampableListener());
        foreach ($rs as $row) {
            if (null === $user = $this->loadUser($app['orm.em'], $row['usr_id'])) {
                continue;
            }

            $feed = $this->get_feed($app, $appbox, $user, $row['pub_restrict'], $row['homelink']);

            if (! $feed instanceof Feed) {
                continue;
            }

            $publishers = $feed->getPublishers();

            $entry = new FeedEntry();
            $entry->setAuthorEmail((string) $user->getEmail());
            $entry->setAuthorName((string) $user->getDisplayName());
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
                    FROM sselcont
                    WHERE ssel_id = :ssel_id
                    ORDER BY ord ASC';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute([':ssel_id' => $row['ssel_id']]);
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                try {
                    $record = new record_adapter($app, phrasea::sbasFromBas($app, $row['base_id']), $row['record_id']);
                    $item = new FeedItem();
                    $item->setEntry($entry);
                    $entry->addItem($item);
                    $item->setRecordId($record->getRecordId());
                    $item->setSbasId($record->getDataboxId());
                    $app['orm.em']->persist($item);
                } catch (NotFoundHttpException $e) {

                }
            }

            $app['orm.em']->persist($entry);

            $sql = 'UPDATE ssel SET deleted = "1", migrated="1"
                    WHERE ssel_id = :ssel_id';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute([':ssel_id' => $row['ssel_id']]);
            $stmt->closeCursor();
            $app['orm.em']->persist($feed);
            $n++;
            if ($n % 1000 == 0) {
                $app['orm.em']->flush();
                $app['orm.em']->clear();
            }
        }
        $this->set_feed_dates($date_ref);
        $app['orm.em']->flush();
        $app['orm.em']->clear();

        $app['orm.em']->getEventManager()->removeEventSubscriber(new TimestampableListener());

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
    protected static $feeds = [];

    protected function get_feed(Application $app, appbox $appbox, User $user, $pub_restrict, $homelink)
    {
        $user_key = 'user_' . $user->getId();
        if ($homelink == '1') {
            $feed_key = 'feed_homelink';
        } elseif ($pub_restrict == '1') {
            $feed_key = 'feed_restricted';
        } else {
            $feed_key = 'feed_public';
        }

        if ( ! array_key_exists($user_key, self::$feeds) || ! isset(self::$feeds[$user_key][$feed_key])) {
            if ($homelink == '1')
                $title = $user->getDisplayName() . ' - ' . 'homelink Feed';
            elseif ($pub_restrict == '1')
                $title = $user->getDisplayName() . ' - ' . 'private Feed';
            else
                $title = $user->getDisplayName() . ' - ' . 'public Feed';

            $feed = new Feed();
            $publisher = new FeedPublisher();
            $feed->setTitle('title');
            $feed->setSubtitle('');
            $feed->addPublisher($publisher);
            $publisher->setFeed($feed);
            $publisher->setIsOwner(true);
            $publisher->setUser($user);

            if ($homelink) {
                $feed->setIsPublic(true);

                $app['orm.em']->persist($feed);
                $app['orm.em']->persist($user);
                $app['orm.em']->flush();

            } elseif ($pub_restrict == 1) {
                $collections = $app->getAclForUser($user)->get_granted_base();
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

    public static function purge()
    {
        self::$feeds = [];
    }
}
