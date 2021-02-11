<?php

namespace Alchemy\Phrasea\Account;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
use Alchemy\Phrasea\Model\Entities\User;

class CollectionRequestMapper
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var RegistrationManager
     */
    private $registrationManager;

    public function __construct(Application $app, RegistrationManager $registrationManager)
    {
        $this->app = $app;
        $this->registrationManager = $registrationManager;
    }

    public function getUserRequests(User $user)
    {
        $databoxStatuses = $this->registrationManager->getRegistrationSummary($user);

        $demands = array();

        foreach ($databoxStatuses as $databoxId => $data) {
            foreach (['registrations-by-type']['pending'] as $collectionId => $waiting) {
                $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, "pending");
            }

            foreach ($data['registrations-by-type']['rejected'] as $collectionId => $waiting) {
                $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, "rejected");
            }

            foreach ($data['registrations-by-type']['accepted'] as $collectionId => $waiting) {
                $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, "accepted");
            }
        }

        return $demands;
    }

    private function mapCollectionStatus($databoxId, $collectionId, $status)
    {
        $baseId = \phrasea::baseFromColl($databoxId, $collectionId, $this->app);

        return array(
            "databox_id" => $databoxId,
            "base_id" => $baseId,
            "collection_id" => $collectionId,
            "status" => $status
        );
    }
}
