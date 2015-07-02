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
use Alchemy\Phrasea\Model\Entities\AggregateToken;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Alchemy\Phrasea\Model\Entities\FeedToken;
use Doctrine\ORM\Query\ResultSetMapping;

class patch_390alpha7a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.7';

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
    public function getDoctrineMigrations()
    {
        return ['20140314000001', '20131118000001'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        if (false === $this->hasFeedBackup($app)) {
            return false;
        }

        $sql = 'DELETE FROM Feeds';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedEntries';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedPublishers';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedItems';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedTokens';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM AggregateTokens';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app->getApplicationBox()->get_connection();

        $sql = 'SELECT id, title, subtitle, public, created_on, updated_on, base_id FROM feeds_backup;';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['orm.em'];

        $fpSql = 'SELECT id, usr_id, owner, created_on FROM feed_publishers WHERE feed_id = :feed_id;';
        $fpStmt = $conn->prepare($fpSql);
        $feSql = 'SELECT id, title, description, created_on, updated_on, author_name, author_email FROM feed_entries WHERE feed_id = :feed_id AND publisher = :publisher_id;';
        $feStmt = $conn->prepare($feSql);
        $fiSql = 'SELECT sbas_id, record_id, ord FROM feed_entry_elements WHERE entry_id = :entry_id;';
        $fiStmt = $conn->prepare($fiSql);
        $ftSql = 'SELECT token, usr_id, aggregated FROM feed_tokens WHERE feed_id = :feed_id;';
        $ftStmt = $conn->prepare($ftSql);
        $faSql = 'SELECT token, usr_id FROM feed_tokens WHERE aggregated = 1;';
        $faStmt = $conn->prepare($faSql);

        foreach ($rs as $row) {
            $feed = new Feed();
            $feed->setTitle($row['title']);
            $feed->setSubtitle($row['subtitle']);
            $feed->setIconUrl(false);
            $feed->setIsPublic($row['public']);
            $feed->setCreatedOn(new \DateTime($row['created_on']));
            $feed->setUpdatedOn(new \DateTime($row['updated_on']));
            $feed->setBaseId($row['base_id']);

            $fpStmt->execute([':feed_id' => $row['id']]);
            $fpRes = $fpStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($fpRes as $fpRow) {
                if (null === $user = $this->loadUser($app['orm.em'], $fpRow['usr_id'])) {
                    continue;
                }

                $feedPublisher = new FeedPublisher();
                $feedPublisher->setFeed($feed);
                $feed->addPublisher($feedPublisher);
                $feedPublisher->setCreatedOn(new \DateTime($fpRow['created_on']));
                $feedPublisher->setIsOwner((Boolean) $fpRow['owner']);
                $feedPublisher->setUser($user);

                $feStmt->execute([':feed_id' => $row['id'], ':publisher_id' => $fpRow['id']]);
                $feRes = $feStmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($feRes as $feRow) {
                    $feedEntry = new FeedEntry();
                    $feedEntry->setFeed($feed);
                    $feed->addEntry($feedEntry);
                    $feedEntry->setPublisher($feedPublisher);
                    $feedEntry->setTitle($feRow['title']);
                    $feedEntry->setSubtitle($feRow['description']);
                    $feedEntry->setAuthorName((string) $feRow['author_name']);
                    $feedEntry->setAuthorEmail((string) $feRow['author_email']);
                    $feedEntry->setCreatedOn(new \DateTime($feRow['created_on']));
                    $feedEntry->setUpdatedOn(new \DateTime($feRow['updated_on']));

                    $fiStmt->execute([':entry_id' => $feRow['id']]);
                    $fiRes = $fiStmt->fetchAll(\PDO::FETCH_ASSOC);

                    foreach ($fiRes as $fiRow) {
                        $feedItem = new FeedItem();
                        $feedItem->setEntry($feedEntry);
                        $feedEntry->addItem($feedItem);
                        $feedItem->setOrd($fiRow['ord']);
                        $feedItem->setSbasId($fiRow['sbas_id']);
                        $feedItem->setRecordId($fiRow['record_id']);

                        $em->persist($feedItem);
                    }
                    $em->persist($feedEntry);
                }
                $em->persist($feedPublisher);
            }

            $ftStmt->execute([':feed_id' => $row['id']]);
            $ftRes = $ftStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($ftRes as $ftRow) {
                if (null === $user = $this->loadUser($app['orm.em'], $ftRow['usr_id'])) {
                    continue;
                }

                $token = new FeedToken();
                $token->setFeed($feed);
                $feed->addToken($token);
                $token->setUser($user);
                $token->setValue($ftRow['token']);

                $em->persist($token);
            }
            $em->persist($feed);

            $n++;

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $fiStmt->closeCursor();
        $feStmt->closeCursor();
        $fpStmt->closeCursor();
        $ftStmt->closeCursor();

        $faStmt->execute();
        $faRes = $faStmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($faRes as $faRow) {
            if (null === $user = $this->loadUser($app['orm.em'], $faRow['usr_id'])) {
                continue;
            }

            $token = new AggregateToken();
            $token->setUser($user);
            $token->setValue($faRow['token']);

            $em->persist($token);
        }
        $faStmt->closeCursor();

        $em->flush();
        $em->clear();

        return true;
    }

    /**
     * Checks whether `feeds_backup` tables exists.
     *
     * @param Application $app
     *
     * @return boolean True if `feeds_backup` table exists.
     */
    private function hasFeedBackup(Application $app)
    {
        $rsm = (new ResultSetMapping())->addScalarResult('Name', 'Name');
        $backup = false;

        foreach ($app['orm.em']->createNativeQuery('SHOW TABLE STATUS', $rsm)->getResult() as $row) {
            if ('feeds_backup' === $row['Name']) {
                $backup = true;
                break;
            }
        }

        return $backup;
    }
}
