<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Registration;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Repositories\RegistrationRepository;
use Doctrine\ORM\EntityManager;
use igorw;

class RegistrationManager
{
    private $em;
    private $appbox;
    private $repository;
    private $aclProvider;

    public function __construct(EntityManager $em, \appbox $appbox, ACLProvider $aclProvider)
    {
        $this->em = $em;
        $this->appbox = $appbox;
        $this->repository = $this->em->getRepository('Alchemy\Phrasea\Model\Entities\Registration');
        $this->aclProvider = $aclProvider;
    }

    /**
     * Creates a new registration.
     *
     * @param $userId
     * @param $baseId
     *
     * @return Registration
     */
    public function createRegistration($userId, $baseId)
    {
        $registration = new Registration();
        $registration->setUser($userId);
        $registration->setBaseId($baseId);
        $this->em->persist($registration);
        $this->em->flush();

        return $registration;
    }

    /**
     * Rejects a registration.
     *
     * @param $usrId
     * @param $baseId
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
     * @param $userId
     * @param $basId
     */
    public function acceptRegistration(Registration $registration, \User_Adapter $user, \Collection $collection, $grantHd = false, $grantWatermark = false)
    {
        $this->aclProvider->get($user)->give_access_to_sbas([$collection->get_sbas_id()]);
        $this->aclProvider->get($user)->give_access_to_base([$collection->get_base_id()]);
        $this->aclProvider->get($user)->update_rights_to_base($collection->get_base_id(), [
            'canputinalbum'   => '1',
            'candwnldhd'      => (string) (int) $grantHd,
            'nowatermark'     => (string) (int) $grantWatermark,
            'candwnldpreview' => '1',
            'actif'           => '1',
        ]);
        $this->em->remove($registration);
        $this->em->flush();
    }

    /**
     * Gets information about registration configuration and registration status if a user id is provided.
     *
     * @param null|integer $userId
     *
     * @return array
     */
    public function getRegistrationSummary($userId = null)
    {
        $data = $userData = [];

        if (null !== $userId) {
            $userData = $this->getRepository()->getRegistrationsSummaryForUser($userId);
        }

        foreach ($this->appbox->get_databoxes() as $databox) {
            $ddata = [
                'registrations' => [
                    'by-type' => [
                        'inactive'  => [],
                        'accepted'  => [],
                        'in-time'   => [],
                        'out-dated' => [],
                        'pending'   => [],
                        'rejected'  => [],
                    ],
                    'by-collection' => []
                ],
                'config' => [
                    'db-name'       => $databox->get_dbname(),
                    'cgu'           => $databox->get_cgus(),
                    'can-register'  => $databox->isRegistrationEnabled(),
                    'collections'   => [],
                ]
            ];

            foreach ($databox->get_collections() as $collection) {
                // sets collection info
                $ddata['config']['collections'][$collection->get_base_id()] = [
                    'coll-name'     => $collection->get_name(),
                    // gets collection registration or fallback to databox configuration
                    'can-register'  => $collection->isRegistrationEnabled(),
                    'cgu'           => $collection->getTermsOfUse(),
                    'registration'        => null
                ];

                if (null === $userRegistration = igorw\get_in($userData, [$databox->get_sbas_id(), $collection->get_base_id()])) {
                    continue;
                }

                // sets collection name
                $userRegistration['coll-name'] = $collection->get_name();
                // gets registration entity
                $registration = $userRegistration['registration'];

                $noRegistrationMade = is_null($userRegistration['active']);
                $registrationMade = !$noRegistrationMade;
                $registrationStillExists = !is_null($registration);
                $registrationNoMoreExists = !$registrationStillExists;
                $isPending = $registrationStillExists && $registration->isPending() && !$registration->isRejected();
                $isRejected = $registrationStillExists && $registration->isRejected();
                $isDone = ($registrationNoMoreExists && $registrationMade) || (!$isPending && !$isRejected);
                $isActive = (Boolean) $userRegistration['active'];
                $isTimeLimited = (Boolean) $userRegistration['time-limited'];
                $isNotTimeLimited = !$isTimeLimited;
                $isOnTime = (Boolean) $userRegistration['in-time'];
                $isOutDated = !$isOnTime;

                if ($noRegistrationMade) {
                    continue;
                }
                // sets registrations
                $ddata['config']['collections'][$collection->get_base_id()]['registration'] = $userRegistration;
                $ddata['registrations']['by-collection'][$collection->get_base_id()] = $userRegistration;

                if (!$isActive) {
                    $ddata['registrations']['by-type']['inactive'][] = $userRegistration;
                    continue;
                }

                if ($isDone) {
                    $ddata['registrations']['by-type']['accepted'][] = $userRegistration;
                    continue;
                }

                if ($isRejected) {
                    $ddata['registrations']['by-type']['rejected'][] = $userRegistration;
                    continue;
                }

                if ($isTimeLimited && $isOnTime && $isPending) {
                    $ddata['registrations']['by-type']['in-time'][] = $userRegistration;
                    continue;
                }

                if ($isTimeLimited && $isOutDated && $isPending) {
                    $ddata['registrations']['by-type']['out-time'][] = $userRegistration;
                    continue;
                }

                if ($isNotTimeLimited && $isPending) {
                    $ddata['registrations']['by-type']['pending'][] = $userRegistration;
                }
            }
        }

        $data[$databox->get_sbas_id()] = $ddata;

       return $data;
    }

    /**
     * Gets Registration Repository.
     *
     * @return RegistrationRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param       $userId
     * @param array $baseList
     *
     * @return mixed
     */
    public function deleteRegistrationsForUser($userId, array $baseList)
    {
        $qb = $this->getRepository()->createQueryBuilder('d');
        $qb->delete('Alchemy\Phrasea\Model\Entities\Registration', 'd');
        $qb->where($qb->expr()->eq('d.user', ':user'));
        $qb->setParameter(':user', $userId);

        if (count($baseList) > 0) {
            $qb->andWhere('d.baseId IN (:bases)');
            $qb->setParameter(':bases', $baseList);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Deletes old registrations.
     */
    public function deleteOldRegistrations()
    {
        $qb = $this->getRepository()->createQueryBuilder('d');
        $qb->delete('Alchemy\Phrasea\Model\Entities\Registration', 'd');
        $qb->where($qb->expr()->lt('d.created', ':date'));
        $qb->setParameter(':date', new \DateTime('-1 month'));
        $qb->getQuery()->execute();
    }

    /**
     * Deletes registrations on given collection.
     *
     * @param $baseId
     */
    public function deleteRegistrationsOnCollection($baseId)
    {
        $qb = $this->getRepository()->createQueryBuilder('d');
        $qb->delete('Alchemy\Phrasea\Model\Entities\Registration', 'd');
        $qb->where($qb->expr()->eq('d.baseId', ':base'));
        $qb->setParameter(':base', $baseId);
        $qb->getQuery()->execute();
    }
}
