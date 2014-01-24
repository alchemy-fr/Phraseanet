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
use Alchemy\Phrasea\Model\Entities\RegistrationDemand;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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
        $this->repository = $this->em->getRepository('Alchemy\Phrasea\Model\Entities\RegistrationDemand');
        $this->aclProvider = $aclProvider;
    }

    /**
     * Creates a new demand.
     *
     * @param $userId
     * @param $baseId
     *
     * @return RegistrationDemand
     */
    public function newDemand($userId, $baseId)
    {
        $demand = new RegistrationDemand();
        $demand->setUser($userId);
        $demand->setBaseId($baseId);
        $this->em->persist($demand);
        $this->em->flush();

        return $demand;
    }

    /**
     * Rejects a demand.
     *
     * @param $usrId
     * @param $baseId
     */
    public function rejectDemand($usrId, $baseId)
    {
        if ($demand = $this->getRepository()->findOneBy([
            'user' => $usrId,
            'baseId' => $baseId
        ])) {
            $demand->setPending(false);
            $demand->setRejected(true);
            $this->em->persist($demand);
        }
        $this->em->flush();
    }

    /**
     * Accepts a demand.
     *
     * @param $userId
     * @param $basId
     */
    public function acceptDemand(\User_Adapter $user, \Collection $collection, $grantHd = false, $grantWatermark = false)
    {
        if ($demand = $this->getRepository()->findOneBy([
            'user' => $user->get_id(),
            'baseId' => $collection->get_base_id()
        ])) {
            $this->aclProvider->get($user)->give_access_to_sbas([$collection->get_sbas_id()]);
            $this->aclProvider->get($user)->give_access_to_base([$collection->get_base_id()]);
            $this->aclProvider->get($user)->update_rights_to_base($collection->get_base_id(), [
                'canputinalbum'   => '1',
                'candwnldhd'      => (string) (int) $grantHd,
                'nowatermark'     => (string) (int) $grantWatermark,
                'candwnldpreview' => '1',
                'actif'           => '1',
            ]);
            $this->em->remove($demand);
            $this->em->flush();
        }
    }

    /**
     * Tells whether registration is enabled or not.
     *
     * @return boolean
     */
    public function isRegistrationEnabled()
    {
        $enabled = false;
        foreach ($this->getRegistrationInformations() as $baseInfo) {
            foreach ($baseInfo['config']['collections'] as $collInfo) {
                if ($collInfo['can-register']) {
                    $enabled = true;
                    break 2;
                }
            }
        }

        return $enabled;
    }

    /**
     * Gets information about registration configuration and demand status if a user id is provided.
     *
     * @param null|integer $userId
     *
     * @return array
     */
    public function getRegistrationInformations($userId = null)
    {
        $data = $userData = [];

        if (null !== $userId) {
            $userData = $this->getRegistrationDemandsForUser($userId);
        }

        foreach ($this->appbox->get_databoxes() as $databox) {
            $ddata = [
                'demands' => [
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
                    'cgu'           => $this->getCguPreferencesForDatabox($databox),
                    'cgu-release'   => $this->getCguReleasedPreferencesForDatabox($databox),
                    'can-register'  => $this->isRegistrationEnabledForDatabox($databox),
                    'collections'   => [],
                ]
            ];

            foreach ($databox->get_collections() as $collection) {
                // sets collection info
                $ddata['config']['collections'][$collection->get_base_id()] = [
                    'coll-name'     => $collection->get_name(),
                    // gets collection registration or fallback to databox configuration
                    'can-register'  => $this->isRegistrationDefinedForCollection($collection) ?
                        $this->isRegistrationEnabledForCollection($collection) : $ddata['config']['can-register'],
                    'cgu'           => $this->getCguPreferencesForCollection($collection),
                    'cgu-release'   => $this->getCguReleasedPreferencesForCollection($collection),
                    'demand'        => null
                ];

                if (null === $userDemand = igorw\get_in($userData, [$databox->get_sbas_id(), $collection->get_base_id()])) {
                    continue;
                }

                // sets collection name
                $userDemand['coll-name'] = $collection->get_name();
                // gets demand entity
                $demand = $userDemand['demand'];

                $noDemandMade = is_null($userDemand['active']);
                $demandMade = !$noDemandMade;
                $demandStillExists = !is_null($demand);
                $demandNoMoreExists = !$demandStillExists;
                $isPending = $demandStillExists && $demand->isPending() && !$demand->isRejected();
                $isRejected = $demandStillExists && $demand->isRejected();
                $isDone = ($demandNoMoreExists && $demandMade) || (!$isPending && !$isRejected);
                $isActive = (Boolean) $userDemand['active'];
                $isTimeLimited = (Boolean) $userDemand['time-limited'];
                $isNotTimeLimited = !$isTimeLimited;
                $isOnTime = (Boolean) $userDemand['in-time'];
                $isOutDated = !$isOnTime;

                if ($noDemandMade) {
                    continue;
                }
                // sets demands
                $ddata['config']['collections'][$collection->get_base_id()]['demand'] = $userDemand;
                $ddata['demands']['by-collection'][$collection->get_base_id()] = $userDemand;

                if (!$isActive) {
                    $ddata['demands']['by-type']['inactive'][] = $userDemand;
                    continue;
                }

                if ($isDone) {
                    $ddata['demands']['by-type']['accepted'][] = $userDemand;
                    continue;
                }

                if ($isRejected) {
                    $ddata['demands']['by-type']['rejected'][] = $userDemand;
                    continue;
                }

                if ($isTimeLimited && $isOnTime && $isPending) {
                    $ddata['demands']['by-type']['in-time'][] = $userDemand;
                    continue;
                }

                if ($isTimeLimited && $isOutDated && $isPending) {
                    $ddata['demands']['by-type']['out-time'][] = $userDemand;
                    continue;
                }

                if ($isNotTimeLimited && $isPending) {
                    $ddata['demands']['by-type']['pending'][] = $userDemand;
                }
            }
        }

        $data[$databox->get_sbas_id()] = $ddata;

       return $data;
    }

    /**
     * Gets registration demands for a user.
     *
     * @param $usrId
     *
     * @return array
     */
    public function getRegistrationDemandsForUser($usrId)
    {
        $data = [];
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\RegistrationDemand', 'd');
        $rsm->addScalarResult('sbas_id','sbas_id');
        $rsm->addScalarResult('bas_id','bas_id');
        $rsm->addScalarResult('dbname','dbname');
        $rsm->addScalarResult('time_limited', 'time_limited');
        $rsm->addScalarResult('limited_from', 'limited_from');
        $rsm->addScalarResult('limited_to', 'limited_to');
        $rsm->addScalarResult('actif', 'actif');

        $sql = "
        SELECT dbname, sbas.sbas_id, time_limited,
               UNIX_TIMESTAMP( limited_from ) AS limited_from,
               UNIX_TIMESTAMP( limited_to ) AS limited_to,
               bas.server_coll_id, usr.usr_id, basusr.actif,
               bas.base_id AS bas_id , " . $rsm->generateSelectClause(['d' => 'd',]) . "
        FROM (usr, bas, sbas)
          LEFT JOIN basusr ON ( usr.usr_id = basusr.usr_id AND bas.base_id = basusr.base_id )
          LEFT JOIN RegistrationDemand d ON ( d.user_id = usr.usr_id AND bas.base_id = d.base_id )
        WHERE bas.active = 1 AND bas.sbas_id = sbas.sbas_id
        AND usr.usr_id = ?
        AND model_of = 0";

        $query = $this->em->createNativeQuery($sql, $rsm);
        $query->setParameter(1, $usrId);

        foreach ($query->getResult() as $row) {
            $demandEntity = $row[0];

            $data[$row['sbas_id']][$row['bas_id']] = [
                'base-id' => $row['bas_id'],
                'db-name' => $row['dbname'],
                'active' => (Boolean) $row['actif'],
                'time-limited' => (Boolean) $row['time_limited'],
                'in-time' => $row['time_limited'] && ! ($row['limited_from'] >= time() && $row['limited_to'] <= time()),
                'demand' => $demandEntity
            ];
        }

        return $data;
    }

    /**
     * Gets RegistrationDemands Repository.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Deletes old demands.
     */
    public function deleteOldDemand()
    {
        $this->repository->deleteDemandsOldestThan('-1 month');
    }

    /**
     * Tells whether the registration is enable for provided databox or not.
     *
     * @param \databox $databox
     *
     * @return boolean
     */
    public function isRegistrationEnabledForDatabox(\databox $databox)
    {
        $enabled = false;

        if ($xml = $databox->get_sxml_structure()) {
            foreach ($xml->xpath('/record/caninscript') as $caninscript) {
                $enabled = (Boolean) (string) $caninscript;
                break;
            }
        }

        return $enabled;
    }

    /**
     * Gets CGU released preference for provided databox.
     *
     * @param \databox $databox
     *
     * @return null|string
     */
    public function getCguReleasedPreferencesForDatabox(\databox $databox)
    {
        $cguRelease = null;

        if ($xml = $databox->get_sxml_structure()) {
            foreach ($xml->xpath('/record/cgu') as $sbpcgu) {
                foreach ($sbpcgu->attributes() as $a => $b) {
                    if ($a == "release") {
                        $cguRelease = (string) $b;
                        break 2;
                    }
                }
            }
        }

        return $cguRelease;
    }

    /**
     * Gets Cgu preference for provided databox.
     *
     * @param \databox $databox
     *
     * @return null|string
     */
    public function getCguPreferencesForDatabox(\databox $databox)
    {
        $cgu = null;

        if ($xml = $databox->get_sxml_structure()) {
            foreach ($xml->xpath('/record/cgu') as $sbpcgu) {
                $cgu = (string) $sbpcgu->saveXML();
                break;
            }
        }

        return $cgu;
    }

    /**
     * Tells whether registration is activated for provided collection or not.
     *
     * @param \collection $collection
     *
     * @return boolean
     */
    public function isRegistrationEnabledForCollection(\collection $collection)
    {
        $enabled = false;
        if ($xml = simplexml_load_string($collection->get_prefs())) {
            foreach ($xml->xpath('/baseprefs/caninscript') as $caninscript) {
                $enabled = (Boolean) (string) $caninscript;
                break;
            }
        }

        return $enabled;
    }

    /**
     * Gets CGU released preferences for provided collection.
     *
     * @param \collection $collection
     *
     * @return null|string
     */
    public function getCguReleasedPreferencesForCollection(\collection $collection)
    {
        $cguRelease = null;

        if ($xml = simplexml_load_string($collection->get_prefs())) {
            foreach ($xml->xpath('/baseprefs/cgu') as $sbpcgu) {
                foreach ($sbpcgu->attributes() as $a => $b) {
                    if ($a == "release") {
                        $cguRelease = (string) $b;
                        break 2;
                    }
                }
            }
        }

        return $cguRelease;
    }

    /**
     * Gets CGU preferences for provided collection.
     *
     * @param \collection $collection
     *
     * @return null|string
     */
    public function getCguPreferencesForCollection(\collection $collection)
    {
        $cgu = null;

        if ($xml = simplexml_load_string($collection->get_prefs())) {
            foreach ($xml->xpath('/baseprefs/cgu') as $sbpcgu) {
                $cgu = (string) $sbpcgu->saveXML();
                break;
            }
        }

        return $cgu;
    }

    /**
     * Tells whether registration preference is defined for provided collection.
     *
     * @param \collection $collection
     *
     * @return bool
     */
    private function isRegistrationDefinedForCollection(\collection $collection)
    {
        $defined = false;
        if ($xml = simplexml_load_string($collection->get_prefs())) {
            if (count($xml->xpath('/baseprefs/caninscript')) > 0) {
                $defined = true;
            }
        }

        return $defined;
    }
}
