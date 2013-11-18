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
use Alchemy\Phrasea\Model\Entities\AggregateToken;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Alchemy\Phrasea\Model\Entities\FeedToken;
use Doctrine\ORM\Query\ResultSetMapping;

class patch_390alpha7a implements patchInterface
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
        return ['feed', 'aggregate-token'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('Name', 'Name');

        $backup = false;

        foreach ($app['EM']->createNativeQuery('SHOW TABLE STATUS', $rsm)->getResult() as $row) {
            if (0 === strcmp('feeds', $row['Name'])) {
                $backup = true;
                break;
            }
        }

        if (false === $backup) {
            return false;
        }

        $app['EM']->executeQuery("RENAME TABLE `feeds` TO `feeds_backup`");

        $sql = 'DELETE FROM Feeds';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedEntries';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedPublishers';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedItems';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FeedTokens';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM AggregateTokens';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT id, title, subtitle, public, created_on, updated_on, base_id FROM feeds_backup;';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];

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
            $feed->setCollection($row['base_id'] ? collection::get_from_base_id($app, $row['base_id']) : null);

            $fpStmt->execute([':feed_id' => $row['id']]);
            $fpRes = $fpStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($fpRes as $fpRow) {
                $feedPublisher = new FeedPublisher();
                $feedPublisher->setFeed($feed);
                $feed->addPublisher($feedPublisher);
                $feedPublisher->setCreatedOn(new \DateTime($fpRow['created_on']));
                $feedPublisher->setIsOwner((Boolean) $fpRow['owner']);
                $feedPublisher->setUsrId($fpRow['usr_id']);

                $feStmt->execute([':feed_id' => $row['id'], ':publisher_id' => $fpRow['id']]);
                $feRes = $feStmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($feRes as $feRow) {
                    $feedEntry = new FeedEntry();
                    $feedEntry->setFeed($feed);
                    $feed->addEntry($feedEntry);
                    $feedEntry->setPublisher($feedPublisher);
                    $feedEntry->setTitle($feRow['title']);
                    $feedEntry->setSubtitle($feRow['description']);
                    $feedEntry->setAuthorName($feRow['author_name']);
                    $feedEntry->setAuthorEmail($feRow['author_email']);
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
                $token = new FeedToken();
                $token->setFeed($feed);
                $feed->addToken($token);
                $token->setUsrId($ftRow['usr_id']);
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
            $token = new AggregateToken();
            $token->setUsrId($faRow['usr_id']);
            $token->setValue($faRow['token']);

            $em->persist($token);
        }
        $faStmt->closeCursor();

        $em->flush();
        $em->clear();

        return true;
    }
}
