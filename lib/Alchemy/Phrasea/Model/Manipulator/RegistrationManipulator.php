<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class RegistrationManipulator implements ManipulatorInterface
{
    private $em;
    private $app;
    private $appbox;
    private $repository;
    private $aclProvider;

    public function __construct(Application $app, EntityManager $em, ACLProvider $aclProvider, \appbox $appbox, EntityRepository $repo)
    {
        $this->app = $app;
        $this->em = $em;
        $this->appbox = $appbox;
        $this->aclProvider = $aclProvider;
        $this->repository = $repo;
    }

    /**
     * Creates a new registration.
     *
     * @param User        $user
     * @param \collection $collection
     *
     * @return Registration
     */
    public function createRegistration(User $user, \collection $collection)
    {
        $registration = new Registration();
        $registration->setUser($user);
        $registration->setCollection($collection);
        $this->em->persist($registration);
        $this->em->flush();

        return $registration;
    }

    /**
     * Rejects a registration.
     *
     * @param Registration $registration
     */
    public function rejectRegistration(Registration $registration)
    {
        $registration->setPending(false);
        $registration->setRejected(true);
        $this->em->persist($registration);
        $this->em->flush();
    }

    /**
     * Accepts a registration.
     *
     * @param Registration $registration
     * @param bool         $grantHd
     * @param bool         $grantWatermark
     */
    public function acceptRegistration(Registration $registration, $grantHd = false, $grantWatermark = false)
    {
        $user = $registration->getUser();
        $collection = $registration->getCollection($this->app);

        $this->aclProvider->get($user)->give_access_to_sbas([$collection->get_sbas_id()]);
        $this->aclProvider->get($user)->give_access_to_base([$collection->get_base_id()]);
        $this->aclProvider->get($user)->update_rights_to_base(
            $collection->get_base_id(),
            [
                \ACL::CANPUTINALBUM   => true,
                \ACL::CANDWNLDHD      => (bool)$grantHd,
                \ACL::NOWATERMARK     => (bool)$grantWatermark,
                \ACL::CANDWNLDPREVIEW => true,
                \ACL::ACTIF           => true
            ]
        );
        $this->em->remove($registration);
        $this->em->flush();
    }

    /**
     * Deletes registration for given user.
     *
     * @param User          $user
     * @param \collection[] $collections
     *
     * @return mixed
     */
    public function deleteUserRegistrations(User $user, array $collections)
    {
        $qb = $this->repository->createQueryBuilder('d');
        $qb->delete('Phraseanet:Registration', 'd');
        $qb->where($qb->expr()->eq('d.user', ':user'));
        $qb->setParameter(':user', $user->getId());

        if (count($collections) > 0) {
            $qb->andWhere('d.baseId IN (:bases)');
            $qb->setParameter(':bases', array_map(function ($collection) {
                return $collection->get_base_id();
            }, $collections));
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Deletes old registrations.
     */
    public function deleteOldRegistrations()
    {
        $qb = $this->repository->createQueryBuilder('d');
        $qb->delete('Phraseanet:Registration', 'd');
        $qb->where($qb->expr()->lt('d.created', ':date'));
        $qb->setParameter(':date', new \DateTime('-1 month'));
        $qb->getQuery()->execute();
    }

    /**
     * Deletes registrations on given collection.
     *
     * @param $baseId
     */
    public function deleteRegistrationsOnCollection(\collection $collection)
    {
        $qb = $this->repository->createQueryBuilder('d');
        $qb->delete('Phraseanet:Registration', 'd');
        $qb->where($qb->expr()->eq('d.baseId', ':base'));
        $qb->setParameter(':base', $collection->get_base_id());
        $qb->getQuery()->execute();
    }
}
