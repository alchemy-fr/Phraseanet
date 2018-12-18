<?php

namespace App\Repository;

use App\Entity\StoryWZ;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Alchemy\Phrasea\Application;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method StoryWZ|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoryWZ|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoryWZ[]    findAll()
 * @method StoryWZ[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoryWZRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StoryWZ::class);
    }

    public function findByUser(Application $app, User $user, $sort)
    {
        $dql = 'SELECT s FROM Phraseanet:StoryWZ s WHERE s.user = :user ';

        if ($sort == 'date') {
            $dql .= ' ORDER BY s.created DESC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['user' => $user]);

        $stories = $query->getResult();

        foreach ($stories as $key => $story) {
            try {
                $story->getRecord($app)->get_title();
            } catch (NotFoundHttpException $e) {
                $this->getEntityManager()->remove($story);
                unset($stories[$key]);
            }
        }

        $this->getEntityManager()->flush();

        if ($sort == 'name') {
            $sortedStories = [];
            foreach ($stories as $story) {
                $sortedStories[] = $story->getRecord($app)->get_title();
            }

            uasort($sortedStories, function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            });

            foreach ($sortedStories as $idStory => $titleStory) {
                $sortedStories[$idStory] = $stories[$idStory];
            }
        }

        return $stories;
    }

    public function findByUserAndId(Application $app, User $user, $id)
    {
        $story = $this->find($id);

        if ($story) {
            try {
                $story->getRecord($app)->get_title();
            } catch (NotFoundHttpException $e) {
                $this->getEntityManager()->remove($story);
                throw new NotFoundHttpException('Story not found');
            }

            if ($story->getUser()->getId() !== $user->getId()) {
                throw new AccessDeniedHttpException('You have not access to ths story');
            }
        } else {
            throw new NotFoundHttpException('Story not found');
        }

        return $story;
    }

    public function findUserStory(Application $app, User $user, \record_adapter $Story)
    {
        $story = $this->findOneBy([
            'user'    => $user->getId(),
            'sbas_id'   => $Story->getDataboxId(),
            'record_id' => $Story->getRecordId(),
        ]);

        if ($story) {
            try {
                $story->getRecord($app);
            } catch (NotFoundHttpException $e) {
                $this->getEntityManager()->remove($story);
                $this->getEntityManager()->flush();
                $story = null;
            }
        }

        return $story;
    }

    /**
     * @param Application     $app
     * @param \record_adapter $Story
     * @return StoryWZ[]
     */
    public function findByRecord(Application $app, \record_adapter $Story)
    {
        $dql = 'SELECT s FROM Phraseanet:StoryWZ s
                WHERE s.sbas_id = :sbas_id
                AND s.record_id = :record_id';

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'sbas_id' => $Story->getDataboxId(),
            'record_id' => $Story->getRecordId(),
        ]);

        /** @var StoryWZ[] $stories */
        $stories = $query->getResult();

        foreach ($stories as $key => $story) {
            try {
                $story->getRecord($app);
            } catch (NotFoundHttpException $e) {
                $this->getEntityManager()->remove($story);
                $this->getEntityManager()->flush();
                unset($stories[$key]);
            }
        }

        return $stories;
    }

    public function findByDatabox(Application $app, \databox $databox)
    {
        $dql = 'SELECT s FROM Phraseanet:StoryWZ s WHERE s.sbas_id = :sbas_id';

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['sbas_id' => $databox->get_sbas_id(),]);

        $stories = $query->getResult();

        foreach ($stories as $key => $story) {
            try {
                $story->getRecord($app);
            } catch (NotFoundHttpException $e) {
                $this->getEntityManager()->remove($story);
                $this->getEntityManager()->flush();
                unset($stories[$key]);
            }
        }

        return $stories;
    }
}
