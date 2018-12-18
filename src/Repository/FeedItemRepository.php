<?php

namespace App\Repository;

use App\Entity\FeedItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method FeedItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedItem[]    findAll()
 * @method FeedItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedItemRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FeedItem::class);
    }

    /**
     * Checks if a record is published in a public feed.
     *
     * @param int $sbas_id
     * @param int $record_id
     *
     * @return bool
     */
    public function isRecordInPublicFeed($sbas_id, $record_id)
    {
        $dql = 'SELECT COUNT(i)
            FROM Phraseanet:FeedItem i
            JOIN i.entry e
            JOIN e.feed f
            WHERE i.sbasId = :sbas_id
                AND i.recordId = :record_id
                AND f.public = true';

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['sbas_id' => $sbas_id, 'record_id' => $record_id]);

        return $query->getSingleScalarResult() > 0;
    }

    /**
     * Gets latest items from public feeds.
     *
     * @param Application $app
     * @param integer     $nbItems
     *
     * @return FeedItem[] An array of FeedItem
     */
    public function loadLatest(Application $app, $nbItems = 20)
    {
        $execution = 0;
        $items = [];

        do {
            $dql = 'SELECT i
                FROM Phraseanet:FeedItem i
                JOIN i.entry e
                JOIN e.feed f
                WHERE f.public = true ORDER BY i.createdOn DESC';

            $query = $this->_em->createQuery($dql);
            $query
                ->setFirstResult((integer) $nbItems * $execution)
                ->setMaxResults((integer) $nbItems);

            $result = $query->getResult();

            foreach ($result as $item) {
                try {
                    $record = $item->getRecord($app);
                } catch (NotFoundHttpException $e) {
                    $app['orm.em']->remove($item);
                    continue;
                } catch (\Exception_Record_AdapterNotFound $e) {
                    $app['orm.em']->remove($item);
                    continue;
                }

                if (null !== $preview = $record->get_subdef('preview')) {
                    if (null !== $permalink = $preview->get_permalink()) {
                        $items[] = $item;

                        if (count($items) >= $nbItems) {
                            break;
                        }
                    }
                }
            }

            $app['orm.em']->flush();
            $execution++;
        } while (count($items) < $nbItems && count($result) !== 0);

        return $items;
    }
}
