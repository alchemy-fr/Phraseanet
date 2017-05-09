<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Model\Repositories\RegistrationRepository;
use Alchemy\Phrasea\Model\Entities\User;
use igorw;

class RegistrationManager
{
    /** @var \appbox */
    private $appbox;
    private $repository;

    public function __construct(\appbox $appbox, RegistrationRepository $repository, $locale)
    {
        $this->appbox = $appbox;
        $this->repository = $repository;
        $this->locale = $locale;
    }

    /**
     * Tells whether registration is enabled or not.
     *
     * @return boolean
     */
    public function isRegistrationEnabled()
    {
        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                if ($collection->isRegistrationEnabled()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets information about registration configuration and registration status if a user id is provided.
     *
     * @param null|user $user
     *
     * @return array
     */
    public function getRegistrationSummary(User $user = null)
    {
        $data = $userData = [];

        // Get user data
        if (null !== $user) {
            $userData = $this->repository->getRegistrationsSummaryForUser($user);
        }

        foreach ($this->appbox->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $data[$sbas_id] = [
                // Registration configuration on databox and collections that belong to the databox
                'db-name'       => $databox->get_dbname(),
                'cgu'           => $databox->get_cgus(),
                'display'       => false,
                'can-register'  => $databox->isRegistrationEnabled(),
                // Configuration on collection
                'collections'   => [],
                // Registrations on databox by type
                'registrations-by-type' => [
                    'active'      => [],
                    'inactive'    => [],
                    'in-time'     => [],
                    'out-dated'   => [],
                    'pending'     => [],
                    'rejected'    => [],
                    'accepted'    => [],
                    'registrable' => [],
                ],
            ];

            foreach ($databox->get_collections() as $collection) {
                // Set collection info
                $base_id = $collection->get_base_id();
                $data[$sbas_id]['collections'][$base_id] = [
                    'coll-name'     => $collection->get_label($this->locale),
                    // gets collection registration or fallback to databox configuration
                    'can-register'  => $collection->isRegistrationEnabled()
                ];
                // add registration infos
                $reg = igorw\get_in($userData, [$sbas_id, $base_id]);
                foreach(['active', 'time-limited', 'in-time'] as $p) {
                    $data[$sbas_id]['collections'][$base_id][$p] = $reg ? $reg[$p] : null;
                }
                $type = $data[$sbas_id]['collections'][$base_id]['type'] = $this->getRegistrationType($reg);

                // Sets registration by type
                if($type !== null) {
                    // a 'inactive' collection is a collection where user has no access and CANT require one
                    // a 'registrable' collection is a collection where user has no access but CAN require one
                    if($type == 'inactive' && $collection->isRegistrationEnabled()) {
                        $type = 'registrable';
                    }
                    $data[$sbas_id]['registrations-by-type'][$type][] = $base_id;
                    // if at least on collection is displayed, the databox should be displayed
                    if($type != 'inactive') {
                        $data[$sbas_id]['display'] = true;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @return null|string
     */
    private function getRegistrationType($data)
    {
        if(is_null($data)) {
            return null;
        }
        $type = null;
        if($data['time-limited'] === true) {
            // date range set
            $type = $data['in-time'] ? "in-time" : "out-time";
        }
        else {
            // no date range
            if($data['active'] === true) {
                $type = "active";
            }
            elseif($data['active'] === false) {
                $type = "inactive";
            }
            elseif($data['active'] === null) {
                if($data['registration'] === null) {
                    $type = "inactive";
                }
                elseif($data['registration']->isPending()) {
                    $type = "pending";
                }
                elseif(!$data['registration']->isRejected()) {
                    $type = "accepted";
                }
                else {
                    $type = "rejected";
                }
            }
        }

        return $type;
    }
}
